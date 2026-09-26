<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../database/database.php";
require_once "../entity/StudyGroup.php";

class StudentInteractionController
{
    private $db;
    private $studyGroupEntity;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
        $this->studyGroupEntity = new StudyGroup(null, null, null, null, null, null, null, null, null, null, $this->db);
    }

    /**
     * Get both my groups and available groups
     * @param int $studentId
     * @return array ['myGroups' => [], 'availableGroups' => []]
     */
    public function getMyGroupsAndAvailableGroups($studentId)
    {
        $myGroups = $this->studyGroupEntity->getGroupsByStudentId($studentId);
        $availableGroups = $this->studyGroupEntity->getAvailableGroups($studentId);

        return [
            'myGroups' => $myGroups === false ? [] : $myGroups,
            'availableGroups' => $availableGroups === false ? [] : $availableGroups
        ];
    }

        /**
     * Get all available modules for the create-group form
     * @param int $universityId
     * @return array
     */
    public function getModules($universityId)
    {
        $modules = $this->studyGroupEntity->getModulesByUniversity($universityId);
        return $modules === false ? [] : $modules;
    }

    /**
     * Create a new study group
     * @param int $studentId
     * @param array $data
     * @return bool
     */
    public function createStudyGroup($studentId, $data)
    {
        return $this->studyGroupEntity->createStudyGroup($studentId, $data);
    }

        /**
     * Get group details + member list for the view page
     * @param int $groupId
     * @param int $studentId
     * @return array|false|null
     */
    public function getStudyGroupDetails($groupId, $studentId)
    {
        $group = $this->studyGroupEntity->getGroupById($groupId);
        if ($group === false) return false;
        if (!$group) return null;

        $isMember = $this->studyGroupEntity->isMember($groupId, $studentId);
        $isAdmin  = ((int)$group['adminId'] === (int)$studentId);

        require_once "../entity/StudyGroupMember.php";
        $memberEntity = new StudyGroupMember(null, null, null, null, null, null, $this->db);
        $members = $memberEntity->getMembersByGroupId($groupId);
        if ($members === false) return false;

        return [
            'group' => $group,
            'isMember' => $isMember,
            'isAdmin' => $isAdmin,
            'members' => $members
        ];
    }

        /**
     * Join a study group (send a request).
     * @param int $studentId
     * @param int $groupId
     * @return string|false
     *   "success"      → request sent
     *   "full"         → group has reached max members
     *   "already"      → already a member or pending
     *   false          → DB error
     */

    public function joinStudyGroup($studentId, $groupId)
    {
        // 1. Get group details
        $group = $this->studyGroupEntity->getGroupById($groupId);
        if (!$group) {
            return false;
        }

        // 2. Check existing membership (active only)
        if ($this->studyGroupEntity->checkExistingMembership($groupId, $studentId)) {
            return "already";
        }

        // 3. Check if group is full
        $activeCount = $this->studyGroupEntity->getActiveMemberCount($groupId);
        if ($group['maxMembers'] !== null && $activeCount >= $group['maxMembers']) {
            return "full";
        }

        // 4. Join the group immediately
        require_once "../entity/StudyGroupMember.php";
        $memberEntity = new StudyGroupMember(null, null, null, null, null, null, $this->db);

        if ($memberEntity->joinGroup($groupId, $studentId)) {
            return "success";
        }

        return false;
    }

        /**
     * Search for study groups by criteria.
     * Returns both matching groups and the user's joined group IDs.
     * @param int $studentId
     * @param int $universityId
     * @param string $keyword
     * @return array|false
     */
    public function searchStudyGroups($studentId, $universityId, $keyword)
    {
        $results = $this->studyGroupEntity->findGroupsByCriteria($universityId, $keyword);

        if ($results === false) {
            return false;   // DB error
        }
        if (empty($results)) {
            return null;    // no results
        }

        // Get the student's joined group IDs (so we can mark them as "member")
        $myGroups = $this->studyGroupEntity->getGroupsByStudentId($studentId);
        $myGroupIds = [];
        if (is_array($myGroups)) {
            foreach ($myGroups as $g) {
                $myGroupIds[] = $g->getId();
            }
        }

        // Attach isMember flag to each result row
        foreach ($results as &$row) {
            $row['isMember'] = in_array($row['id'], $myGroupIds);
            $row['userRole'] = ((int)$row['adminId'] === (int)$studentId) ? 'admin'
                : ($row['isMember'] ? 'member' : null);
        }
        unset($row);

        return $results;
    }

        /**
     * Update a study group.
     * @param int $groupId
     * @param int $studentId
     * @param array $data
     * @return bool
     */
    public function updateStudyGroup($groupId, $studentId, $data)
    {
        return $this->studyGroupEntity->updateStudyGroup($groupId, $studentId, $data);
    }

        /**
     * Suspend a study group.
     * @param int $groupId
     * @param int $studentId
     * @return bool
     */
    public function suspendStudyGroup($groupId, $studentId)
    {
        return $this->studyGroupEntity->suspendStudyGroup($groupId, $studentId);
    }
}
?>