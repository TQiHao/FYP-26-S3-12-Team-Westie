<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../database/database.php";
require_once "../entity/timetableEntry.php";
require_once "../entity/StudentEnrolment.php";
require_once "../entity/Exam.php";

class AcademicsController
{
    private $db;
    private $enrolmentEntity;
    private $examEntity;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
        $this->enrolmentEntity = new StudentEnrolment(null, null, null, null, null, null, $this->db);
        $this->examEntity = new Exam(null, null, null, null, null, null, null, null, null, $this->db);
    }

    /**
     * Get timetable entries for a student.
     * Combines their personal TimetableEntries with classes they are enrolled in.
     * Returns List<TimetableEntry> | null | false
     */
    public function getTimetable($studentId)
    {
        if (!$this->isValidId($studentId)) {
            return null;
        }

        $sql = "SELECT id, userId, title, dayOfWeek, startTime, endTime, location, createdAt, updatedAt
                FROM (
                    SELECT id, userId, title, dayOfWeek, startTime, endTime, location, createdAt, updatedAt
                    FROM TimetableEntries
                    WHERE userId = :studentId

                    UNION ALL

                    SELECT c.id,
                           se.studentId AS userId,
                           m.code AS title,
                           c.dayOfWeek,
                           c.startTime,
                           c.endTime,
                           c.room AS location,
                           c.createdAt,
                           c.updatedAt
                    FROM Classes c
                    INNER JOIN Modules m ON c.moduleId = m.id
                    INNER JOIN StudentEnrolments se ON se.classId = c.id
                    WHERE se.studentId = :studentId
                      AND se.status = 'enrolled'
                      AND c.status = 'active'
                ) AS CombinedTimetable
                ORDER BY FIELD(dayOfWeek,'mon','tue','wed','thu','fri','sat','sun'),
                         startTime ASC";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':studentId', (int) $studentId, PDO::PARAM_INT);
            $stmt->execute();

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($rows)) {
                return null;
            }

            $entries = [];
            foreach ($rows as $row) {
                $entries[] = new TimetableEntry(
                    $row['id'],
                    $row['userId'],
                    $row['title'],
                    $row['dayOfWeek'],
                    $row['startTime'],
                    $row['endTime'],
                    $row['location'],
                    $row['createdAt'],
                    $row['updatedAt']
                );
            }
            return $entries;

        } catch (PDOException $e) {
            error_log("AcademicsController getTimetable error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get enrolled courses (with module, class, and lecturer details)
     * Returns array | null | false
     */
    public function getEnrolledCourses($studentId)
    {
        if (!$this->isValidId($studentId)) {
            return null;
        }

        $sql = "SELECT
                    se.id AS enrolmentId,
                    se.enrolmentDate,
                    se.status AS enrolmentStatus,

                    c.id AS classId,
                    c.className,
                    c.classCode,
                    c.room,
                    c.dayOfWeek,
                    c.startTime,
                    c.endTime,
                    c.semester,
                    c.academicYear,

                    m.id AS moduleId,
                    m.code AS moduleCode,
                    m.name AS moduleName,
                    m.credits,
                    m.semester AS moduleSemester,

                    u.id AS lecturerId,
                    u.fullName AS lecturerName
                FROM StudentEnrolments se
                INNER JOIN Classes c ON se.classId = c.id
                INNER JOIN Modules m ON c.moduleId = m.id
                LEFT JOIN Users u ON c.staffId = u.id
                WHERE se.studentId = :studentId
                  AND se.status = 'enrolled'
                  AND c.status = 'active'
                ORDER BY c.semester ASC, m.code ASC";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':studentId', (int) $studentId, PDO::PARAM_INT);
            $stmt->execute();

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($rows)) {
                return null;
            }
            return $rows;

        } catch (PDOException $e) {
            error_log("getEnrolledCourses error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get exam schedule for a student
     * Follows the sequence diagram:
     *  1. getEnrolmentsByStudentId(studentId): List<StudentEnrolment>
     *  2. For each enrolment → getUpcomingExamByClassId(classId): List<Exam>
     * Returns List<Exam> | null | false
     */
    public function getExamSchedule($studentId)
    {
        if (!$this->isValidId($studentId)) {
            return null;
        }

        // Step 1: get enrolments via StudentEnrolment entity
        $enrolments = $this->enrolmentEntity->getEnrolmentsByStudentId($studentId);

        if ($enrolments === false) {
            return false; // DB error
        }
        if (empty($enrolments)) {
            return null; // no enrolments
        }

        // Step 2: gather exams for each class
        $allExams = [];
        foreach ($enrolments as $enrolment) {
            $classId = $enrolment->getClassId();

            $exams = $this->examEntity->getUpcomingExamByClassId($classId);

            if ($exams === false) {
                return false; // DB error
            }
            if (!empty($exams)) {
                foreach ($exams as $exam) {
                    $allExams[] = $exam;
                }
            }
        }

        if (empty($allExams)) {
            return null;
        }

        // Sort by exam date + time
        usort($allExams, function ($a, $b) {
            $aKey = $a['examDate'] . ' ' . $a['startTime'];
            $bKey = $b['examDate'] . ' ' . $b['startTime'];
            return strcmp($aKey, $bKey);
        });

        return $allExams;
    }

    private function isValidId($id)
    {
        return isset($id) && is_numeric($id) && (int) $id > 0;
    }
}
?>