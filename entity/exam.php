<?php

class Exam
{
    private $id;
    private $classId;
    private $examDate;
    private $startTime;
    private $endTime;
    private $venue;
    private $createdBy;
    private $createdAt;
    private $updatedAt;
    private $db;

    public function __construct(
        $id = null,
        $classId = null,
        $examDate = null,
        $startTime = null,
        $endTime = null,
        $venue = null,
        $createdBy = null,
        $createdAt = null,
        $updatedAt = null,
        $db = null
    ) {
        $this->id = $id;
        $this->classId = $classId;
        $this->examDate = $examDate;
        $this->startTime = $startTime;
        $this->endTime = $endTime;
        $this->venue = $venue;
        $this->createdBy = $createdBy;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->db = $db;
    }

    // Getters
    public function getId() { return $this->id; }
    public function getClassId() { return $this->classId; }
    public function getExamDate() { return $this->examDate; }
    public function getStartTime() { return $this->startTime; }
    public function getEndTime() { return $this->endTime; }
    public function getVenue() { return $this->venue; }
    public function getCreatedBy() { return $this->createdBy; }
    public function getCreatedAt() { return $this->createdAt; }
    public function getUpdatedAt() { return $this->updatedAt; }

    // Setters
    public function setExamDate($examDate) { $this->examDate = $examDate; }
    public function setStartTime($startTime) { $this->startTime = $startTime; }
    public function setEndTime($endTime) { $this->endTime = $endTime; }
    public function setVenue($venue) { $this->venue = $venue; }

    /**
     * Get upcoming exams for a class.
     * Returns List<Exam> | null (none found) | false (DB error)
     */
    public function getUpcomingExamByClassId($classId)
    {
        if (!$this->isValidId($classId)) {
            return null;
        }

        try {
            $sql = "SELECT e.id, e.classId, e.examDate, e.startTime, e.endTime,
                           e.venue, e.createdBy, e.createdAt, e.updatedAt,
                           c.className, c.classCode,
                           m.code AS moduleCode, m.name AS moduleName
                    FROM Exam e
                    INNER JOIN Classes c ON e.classId = c.id
                    INNER JOIN Modules m ON c.moduleId = m.id
                    WHERE e.classId = :classId
                      AND e.examDate >= CURDATE()
                    ORDER BY e.examDate ASC, e.startTime ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':classId', (int) $classId, PDO::PARAM_INT);
            $stmt->execute();

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($rows)) {
                return null;
            }
            return $rows;

        } catch (PDOException $e) {
            error_log("getUpcomingExamByClassId error: " . $e->getMessage());
            return false;
        }
    }

    private function isValidId($id)
    {
        return isset($id) && is_numeric($id) && (int) $id > 0;
    }
}
?>