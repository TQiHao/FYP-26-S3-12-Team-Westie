<?php

class StudyGroupMember
{
    private $id;
    private $groupId;
    private $studentId;
    private $status;
    private $joinedAt;
    private $leftAt;
    private $db;

    public function __construct(
        $id = null,
        $groupId = null,
        $studentId = null,
        $status = null,
        $joinedAt = null,
        $leftAt = null,
        $db = null
    ) {
        $this->id = $id;
        $this->groupId = $groupId;
        $this->studentId = $studentId;
        $this->status = $status;
        $this->joinedAt = $joinedAt;
        $this->leftAt = $leftAt;
        $this->db = $db;
    }

    public function getId() { return $this->id; }
    public function getGroupId() { return $this->groupId; }
    public function getStudentId() { return $this->studentId; }
    public function getStatus() { return $this->status; }
    public function getJoinedAt() { return $this->joinedAt; }
    public function getLeftAt() { return $this->leftAt; }

        /**
     * Get all active members of a group with user names
     * @param int $groupId
     * @return array|false
     */
    public function getMembersByGroupId($groupId)
    {
        try {
            $sql = "SELECT sgm.id, sgm.groupId, sgm.studentId, sgm.status, sgm.joinedAt,
                           u.fullName, u.email
                    FROM StudyGroupMembers sgm
                    INNER JOIN Users u ON sgm.studentId = u.id
                    WHERE sgm.groupId = ? AND sgm.status = 'active'
                    ORDER BY sgm.joinedAt ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$groupId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("getMembersByGroupId error: " . $e->getMessage());
            return false;
        }
    }

        /**
     * Create a membership request (pending status)
     * @param int $groupId
     * @param int $studentId
     * @return bool
     */
    public function joinGroup($groupId, $studentId)
    {
        try {
            $sql = "INSERT INTO StudyGroupMembers (groupId, studentId, status, joinedAt)
                    VALUES (?, ?, 'active', NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$groupId, $studentId]);
            return true;

        } catch (PDOException $e) {
            error_log("joinGroup error: " . $e->getMessage());
            return false;
        }
    }
}
?>