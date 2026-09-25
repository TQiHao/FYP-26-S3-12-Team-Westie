<?php

class StudentEnrolment
{
    private $id;
    private $studentId;
    private $classId;
    private $enrolledBy;
    private $enrolmentDate;
    private $status;
    private $db;

    public function __construct(
        $id = null,
        $studentId = null,
        $classId = null,
        $enrolledBy = null,
        $enrolmentDate = null,
        $status = null,
        $db = null
    ) {
        $this->id = $id;
        $this->studentId = $studentId;
        $this->classId = $classId;
        $this->enrolledBy = $enrolledBy;
        $this->enrolmentDate = $enrolmentDate;
        $this->status = $status;
        $this->db = $db;
    }

    // Getters
    public function getId() { return $this->id; }
    public function getStudentId() { return $this->studentId; }
    public function getClassId() { return $this->classId; }
    public function getEnrolledBy() { return $this->enrolledBy; }
    public function getEnrolmentDate() { return $this->enrolmentDate; }
    public function getStatus() { return $this->status; }

    /**
     * Get all enrolments for a student.
     * Returns List<StudentEnrolment> | null (none found) | false (DB error)
     */
    public function getEnrolmentsByStudentId($studentId)
    {
        if (!$this->isValidId($studentId)) {
            return null;
        }

        try {
            $sql = "SELECT id, studentId, classId, enrolledBy, enrolmentDate, status
                    FROM StudentEnrolments
                    WHERE studentId = :studentId
                      AND status = 'enrolled'
                    ORDER BY enrolmentDate ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':studentId', (int) $studentId, PDO::PARAM_INT);
            $stmt->execute();

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($rows)) {
                return null;
            }

            $enrolments = [];
            foreach ($rows as $row) {
                $enrolments[] = new StudentEnrolment(
                    $row['id'],
                    $row['studentId'],
                    $row['classId'],
                    $row['enrolledBy'],
                    $row['enrolmentDate'],
                    $row['status']
                );
            }
            return $enrolments;

        } catch (PDOException $e) {
            error_log("getEnrolmentsByStudentId error: " . $e->getMessage());
            return false;
        }
    }

    private function isValidId($id)
    {
        return isset($id) && is_numeric($id) && (int) $id > 0;
    }
}
?>