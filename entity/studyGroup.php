<?php

class StudyGroup
{
    private $id;
    private $universityId;
    private $moduleId;
    private $adminId;
    private $name;
    private $description;
    private $maxMembers;
    private $status;
    private $createdAt;
    private $updatedAt;
    private $db;

    // Derived fields
    private $moduleCode;
    private $moduleName;
    private $currentMembers;
    private $userRole; // 'admin' or 'member'

    public function __construct(
        $id = null,
        $universityId = null,
        $moduleId = null,
        $adminId = null,
        $name = null,
        $description = null,
        $maxMembers = null,
        $status = null,
        $createdAt = null,
        $updatedAt = null,
        $db = null
    ) {
        $this->id = $id;
        $this->universityId = $universityId;
        $this->moduleId = $moduleId;
        $this->adminId = $adminId;
        $this->name = $name;
        $this->description = $description;
        $this->maxMembers = $maxMembers;
        $this->status = $status;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->db = $db;
    }

    // Getters
    public function getId() { return $this->id; }
    public function getUniversityId() { return $this->universityId; }
    public function getModuleId() { return $this->moduleId; }
    public function getAdminId() { return $this->adminId; }
    public function getName() { return $this->name; }
    public function getDescription() { return $this->description; }
    public function getMaxMembers() { return $this->maxMembers; }
    public function getStatus() { return $this->status; }
    public function getCreatedAt() { return $this->createdAt; }
    public function getUpdatedAt() { return $this->updatedAt; }
    public function getModuleCode() { return $this->moduleCode; }
    public function getModuleName() { return $this->moduleName; }
    public function getCurrentMembers() { return $this->currentMembers; }
    public function getUserRole() { return $this->userRole; }

    /**
     * Get groups the student is a member of (admin OR regular member)
     * @return array|false
     */
    public function getGroupsByStudentId($studentId)
    {
        try {
            $sql = "SELECT sg.id, sg.universityId, sg.moduleId, sg.adminId,
                           sg.name, sg.description, sg.maxMembers, sg.status,
                           sg.createdAt, sg.updatedAt,
                           m.code AS moduleCode, m.name AS moduleName,
                           (SELECT COUNT(*) FROM StudyGroupMembers
                            WHERE groupId = sg.id AND status = 'active') AS currentMembers
                    FROM StudyGroups sg
                    INNER JOIN Modules m ON sg.moduleId = m.id
                    INNER JOIN StudyGroupMembers sgm ON sgm.groupId = sg.id
                    WHERE sgm.studentId = ?
                      AND sgm.status = 'active'
                      AND sg.status = 'active'
                    ORDER BY sg.createdAt DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$studentId]);

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $groups = [];
            foreach ($rows as $row) {
                $g = new StudyGroup(
                    $row['id'], $row['universityId'], $row['moduleId'], $row['adminId'],
                    $row['name'], $row['description'], $row['maxMembers'], $row['status'],
                    $row['createdAt'], $row['updatedAt']
                );
                $g->moduleCode = $row['moduleCode'];
                $g->moduleName = $row['moduleName'];
                $g->currentMembers = $row['currentMembers'];

                // Determine user role by adminId
                $g->userRole = ((int)$row['adminId'] === (int)$studentId) ? 'admin' : 'member';

                $groups[] = $g;
            }
            return $groups;

        } catch (PDOException $e) {
            error_log("getGroupsByStudentId error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get groups the student is NOT a member of
     * @return array|false
     */
    public function getAvailableGroups($studentId)
    {
        try {
            $sql = "SELECT sg.id, sg.universityId, sg.moduleId, sg.adminId,
                           sg.name, sg.description, sg.maxMembers, sg.status,
                           sg.createdAt, sg.updatedAt,
                           m.code AS moduleCode, m.name AS moduleName,
                           (SELECT COUNT(*) FROM StudyGroupMembers
                            WHERE groupId = sg.id AND status = 'active') AS currentMembers
                    FROM StudyGroups sg
                    INNER JOIN Modules m ON sg.moduleId = m.id
                    WHERE sg.status = 'active'
                      AND sg.id NOT IN (
                          SELECT groupId FROM StudyGroupMembers
                          WHERE studentId = ? AND status = 'active'
                      )
                      AND (
                          sg.maxMembers IS NULL
                          OR (SELECT COUNT(*) FROM StudyGroupMembers
                              WHERE groupId = sg.id AND status = 'active') < sg.maxMembers
                      )
                    ORDER BY sg.createdAt DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$studentId]);

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $groups = [];
            foreach ($rows as $row) {
                $g = new StudyGroup(
                    $row['id'], $row['universityId'], $row['moduleId'], $row['adminId'],
                    $row['name'], $row['description'], $row['maxMembers'], $row['status'],
                    $row['createdAt'], $row['updatedAt']
                );
                $g->moduleCode = $row['moduleCode'];
                $g->moduleName = $row['moduleName'];
                $g->currentMembers = $row['currentMembers'];
                $g->userRole = null;
                $groups[] = $g;
            }
            return $groups;

        } catch (PDOException $e) {
            error_log("getAvailableGroups error: " . $e->getMessage());
            return false;
        }
    }

        /**
     * Create a new study group and add the creator as admin.
     * @param int $studentId
     * @param array $data ['name', 'moduleId', 'description', 'maxMembers']
     * @return bool
     */
    public function createStudyGroup($studentId, $data)
    {
        // Validate
        if (empty($studentId) || empty($data['name']) || empty($data['moduleId'])) {
            return false;
        }

        try {
            $this->db->beginTransaction();

            // 1. Insert into StudyGroups
            $sql = "INSERT INTO StudyGroups
                    (universityId, moduleId, adminId, name, description, maxMembers, status, createdAt, updatedAt)
                    VALUES (?, ?, ?, ?, ?, ?, 'active', NOW(), NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['universityId'],
                $data['moduleId'],
                $studentId,
                $data['name'],
                $data['description'] ?? null,
                $data['maxMembers'] ?? null
            ]);

            $newGroupId = $this->db->lastInsertId();

            // 2. Add creator to StudyGroupMembers
            $sql = "INSERT INTO StudyGroupMembers (groupId, studentId, status, joinedAt)
                    VALUES (?, ?, 'active', NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$newGroupId, $studentId]);

            $this->db->commit();
            return true;

        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("createStudyGroup error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all modules for a university (for the course dropdown)
     * @param int $universityId
     * @return array|false
     */
    public function getModulesByUniversity($universityId)
    {
        try {
            $sql = "SELECT m.id, m.code, m.name
                    FROM Modules m
                    INNER JOIN Programmes p ON m.programmeId = p.id
                    INNER JOIN Faculties f ON p.facultyId = f.id
                    WHERE f.universityId = ?
                    ORDER BY m.code ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$universityId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("getModulesByUniversity error: " . $e->getMessage());
            return false;
        }
    }

        /**
     * Get a single group by ID with module details
     * @param int $groupId
     * @return array|false|null
     */
    public function getGroupById($groupId)
    {
        try {
            $sql = "SELECT sg.id, sg.universityId, sg.moduleId, sg.adminId,
                           sg.name, sg.description, sg.maxMembers, sg.status,
                           sg.createdAt, sg.updatedAt,
                           m.code AS moduleCode, m.name AS moduleName,
                           u.fullName AS adminName
                    FROM StudyGroups sg
                    INNER JOIN Modules m ON sg.moduleId = m.id
                    LEFT JOIN Users u ON sg.adminId = u.id
                    WHERE sg.id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$groupId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("getGroupById error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if a user is a member of a group
     * @param int $groupId
     * @param int $studentId
     * @return bool
     */
    public function isMember($groupId, $studentId)
    {
        try {
            $sql = "SELECT COUNT(*) FROM StudyGroupMembers
                    WHERE groupId = ? AND studentId = ? AND status = 'active'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$groupId, $studentId]);
            return $stmt->fetchColumn() > 0;

        } catch (PDOException $e) {
            error_log("isMember error: " . $e->getMessage());
            return false;
        }
    }

        /**
     * Check if a student is already a member (or has a pending request)
     * @param int $groupId
     * @param int $studentId
     * @return bool
     */
    public function checkExistingMembership($groupId, $studentId)
    {
        try {
            $sql = "SELECT COUNT(*) FROM StudyGroupMembers
                    WHERE groupId = ? AND studentId = ?
                      AND status IN ('active')";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$groupId, $studentId]);
            return $stmt->fetchColumn() > 0;

        } catch (PDOException $e) {
            error_log("checkExistingMembership error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get the current number of active members
     * @param int $groupId
     * @return int
     */
    public function getActiveMemberCount($groupId)
    {
        try {
            $sql = "SELECT COUNT(*) FROM StudyGroupMembers
                    WHERE groupId = ? AND status = 'active'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$groupId]);
            return (int) $stmt->fetchColumn();

        } catch (PDOException $e) {
            error_log("getActiveMemberCount error: " . $e->getMessage());
            return 0;
        }
    }

        /**
     * Search study groups by keyword (name, module code, or module name).
     * @param int $universityId
     * @param string $keyword
     * @return array|false
     */
    public function findGroupsByCriteria($universityId, $keyword)
    {
        try {
            $sql = "SELECT sg.id, sg.universityId, sg.moduleId, sg.adminId,
                           sg.name, sg.description, sg.maxMembers, sg.status,
                           sg.createdAt, sg.updatedAt,
                           m.code AS moduleCode, m.name AS moduleName,
                           (SELECT COUNT(*) FROM StudyGroupMembers
                            WHERE groupId = sg.id AND status = 'active') AS currentMembers
                    FROM StudyGroups sg
                    INNER JOIN Modules m ON sg.moduleId = m.id
                    WHERE sg.universityId = ?
                      AND sg.status = 'active'
                      AND (
                          sg.name LIKE ?
                          OR m.code LIKE ?
                          OR m.name LIKE ?
                      )
                    ORDER BY sg.name ASC";
            $stmt = $this->db->prepare($sql);
            $searchTerm = '%' . $keyword . '%';
            $stmt->execute([$universityId, $searchTerm, $searchTerm, $searchTerm]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("findGroupsByCriteria error: " . $e->getMessage());
            return false;
        }
    }

        /**
     * Update an existing study group.
     * Only the group admin can update.
     * @param int $groupId
     * @param int $studentId (must be the admin)
     * @param array $data ['name', 'moduleId', 'description', 'maxMembers']
     * @return bool
     */
    public function updateStudyGroup($groupId, $studentId, $data)
    {
        // Validate
        if (empty($groupId) || empty($studentId) || empty($data['name']) || empty($data['moduleId'])) {
            return false;
        }

        try {
            // Only the admin can update
            $sql = "SELECT adminId FROM StudyGroups WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$groupId]);
            $group = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$group || (int)$group['adminId'] !== (int)$studentId) {
                return false;
            }

            // Update
            $sql = "UPDATE StudyGroups
                    SET name = ?, moduleId = ?, description = ?, maxMembers = ?, updatedAt = NOW()
                    WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['name'],
                $data['moduleId'],
                $data['description'] ?? null,
                $data['maxMembers'] ?? null,
                $groupId
            ]);

            return true;

        } catch (PDOException $e) {
            error_log("updateStudyGroup error: " . $e->getMessage());
            return false;
        }
    }

        /**
     * Suspend a study group (set status to inactive).
     * Only the admin can suspend.
     * @param int $groupId
     * @param int $studentId
     * @return bool
     */
    public function suspendStudyGroup($groupId, $studentId)
    {
        try {
            // Verify admin
            $sql = "SELECT adminId FROM StudyGroups WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$groupId]);
            $group = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$group || (int)$group['adminId'] !== (int)$studentId) {
                return false;
            }

            // Set status to inactive
            $sql = "UPDATE StudyGroups
                    SET status = 'inactive', updatedAt = NOW()
                    WHERE id = ? AND adminId = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$groupId, $studentId]);

            return $stmt->rowCount() > 0;

        } catch (PDOException $e) {
            error_log("suspendStudyGroup error: " . $e->getMessage());
            return false;
        }
    }
}
?>