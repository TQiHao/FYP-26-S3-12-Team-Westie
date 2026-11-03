<?php

require_once "../database/database.php";

class Students
{
    private $id;
    private $userId;
    private $studentId;
    private $programmeId;
    private $academicYear;
    private $semester;
    private $semesterStart;
    private $semesterEnd;
    private $createdAt;
    private $updatedAt;


    public function __construct(
        $id = null,
        $userId = null,
        $studentId = null,
        $programmeId = null,
        $academicYear = null,
        $semester = null,
        $semesterStart = null,
        $semesterEnd = null,
        $createdAt = null,
        $updatedAt = null
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->studentId = $studentId;
        $this->programmeId = $programmeId;
        $this->academicYear = $academicYear;
        $this->semester = $semester;
        $this->semesterStart = $semesterStart;
        $this->semesterEnd = $semesterEnd;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }


    // Getters
    public function getId()
    {
        return $this->id;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function getStudentId()
    {
        return $this->studentId;
    }

    public function getProgrammeId()
    {
        return $this->programmeId;
    }

    public function getAcademicYear()
    {
        return $this->academicYear;
    }

    public function getSemester()
    {
        return $this->semester;
    }

    public function getSemesterStartDate()
    {
        return $this->semesterStartDate;
    }

    public function getSemesterEndDate()
    {
        return $this->semesterEndDate;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }


    // Setters
    public function setId($id)
    {
        $this->id = $id;
    }

    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    public function setStudentId($studentId)
    {
        $this->studentId = $studentId;
    }

    public function setProgrammeId($programmeId)
    {
        $this->programmeId = $programmeId;
    }

    public function setAcademicYear($academicYear)
    {
        $this->academicYear = $academicYear;
    }

    public function setSemester($semester)
    {
        $this->semester = $semester;
    }

    public function setSemesterStartDate($semesterStartDate)
    {
        $this->semesterStartDate = $semesterStartDate;
    }

    public function setSemesterEndDate($semesterEndDate)
    {
        $this->semesterEndDate = $semesterEndDate;
    }

    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }


    // Check whether a student ID already exists.
    public function studentIdExists($studentId)
    {
        $database = new Database();
        $db = $database->connect();

        try {

            $sql = "SELECT id
                    FROM Students
                    WHERE studentId = ?
                    LIMIT 1";

            $stmt = $db->prepare($sql);

            $stmt->execute([
                $studentId
            ]);

            return $stmt->fetch(PDO::FETCH_ASSOC) !== false;

        } catch (PDOException $e) {

            return false;
        }
    }

    public function createStudentRecord(
        $userId,
        $studentId,
        $programmeId,
        $academicYear,
        $semester,
        $semesterStart,
        $semesterEnd
    ) {
        $database = new Database();
        $db = $database->connect();

        try {

            $sql = "INSERT INTO Students
                    (
                        userId,
                        studentId,
                        programmeId,
                        academicYear,
                        semester,
                        semesterStart,
                        semesterEnd,
                        createdAt,
                        updatedAt
                    )
                    VALUES
                    (
                        ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()
                    )";

            $stmt = $db->prepare($sql);

            $stmt->execute([
                $userId,
                $studentId,
                $programmeId,
                $academicYear,
                $semester,
                $semesterStart,
                $semesterEnd
            ]);

            return $db->lastInsertId();

        } catch (PDOException $e) {

            return false;
        }
    }


    // Get all students belonging to a university.
    public function getStudentsByUniversity($universityId)
    {
        $database = new Database();
        $db = $database->connect();

        try {

            $sql = "SELECT
                        s.id,
                        s.userId,
                        s.studentId,
                        s.programmeId,
                        s.academicYear,
                        s.semester,

                        u.fullName,
                        u.email,
                        u.status,

                        p.name AS programmeName,
                        p.code AS programmeCode

                    FROM Students s

                    JOIN Users u
                        ON s.userId = u.id

                    JOIN Programmes p
                        ON s.programmeId = p.id

                    JOIN Faculties f
                        ON p.facultyId = f.id

                    WHERE u.universityId = ?
                      AND u.role = 'student'

                    ORDER BY s.id ASC";

            $stmt = $db->prepare($sql);

            $stmt->execute([
                $universityId
            ]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {

            return [];
        }
    }


    // Find an existing student using their email and university.
    public function getStudentByEmail($email, $universityId)
    {
        $database = new Database();
        $db = $database->connect();

        try {
            $sql = "SELECT
                        s.id,
                        s.userId,
                        s.studentId,
                        s.programmeId,
                        s.academicYear,
                        s.semester,
                        s.semesterStart,
                        s.semesterEnd,
                        u.fullName,
                        u.email
                    FROM Students s
                    JOIN Users u ON s.userId = u.id
                    WHERE LOWER(TRIM(u.email)) = LOWER(TRIM(?))
                      AND u.universityId = ?
                      AND u.role = 'student'
                    LIMIT 1";

            $stmt = $db->prepare($sql);
            $stmt->execute([$email, $universityId]);

            return $stmt->fetch(PDO::FETCH_ASSOC) ?: false;

        } catch (PDOException $e) {
            error_log("Find student by email error: " . $e->getMessage());
            return false;
        }
    }


    // Update an existing student's details without changing their password.
    public function updateStudentRecord(
        $userId,
        $studentId,
        $fullName,
        $programmeId,
        $academicYear,
        $semester,
        $semesterStart,
        $semesterEnd
    ) {
        $database = new Database();
        $db = $database->connect();

        try {
            $db->beginTransaction();

            // Update the student's name only.
            // The password is deliberately not included.
            $sql = "UPDATE Users
                    SET fullName = ?
                    WHERE id = ?
                      AND role = 'student'";

            $stmt = $db->prepare($sql);
            $stmt->execute([$fullName, $userId]);

            // Preserve the existing student ID if the CSV does not provide one.
            $sql = "UPDATE Students
                    SET studentId = COALESCE(NULLIF(?, ''), studentId),
                        programmeId = ?,
                        academicYear = ?,
                        semester = ?,
                        semesterStart = ?,
                        semesterEnd = ?,
                        updatedAt = NOW()
                    WHERE userId = ?";

            $stmt = $db->prepare($sql);
            $stmt->execute([
                $studentId,
                $programmeId,
                $academicYear,
                $semester,
                $semesterStart,
                $semesterEnd,
                $userId
            ]);

            $db->commit();
            return true;

        } catch (PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            error_log("Update student record error: " . $e->getMessage());
            return false;
        }
    }

}