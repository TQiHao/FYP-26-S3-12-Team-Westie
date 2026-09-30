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
    private $createdAt;
    private $updatedAt;


    public function __construct(
        $id = null,
        $userId = null,
        $studentId = null,
        $programmeId = null,
        $academicYear = null,
        $semester = null,
        $createdAt = null,
        $updatedAt = null
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->studentId = $studentId;
        $this->programmeId = $programmeId;
        $this->academicYear = $academicYear;
        $this->semester = $semester;
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
        $semester
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
                        createdAt,
                        updatedAt
                    )
                    VALUES
                    (
                        ?, ?, ?, ?, ?, NOW(), NOW()
                    )";

            $stmt = $db->prepare($sql);

            $stmt->execute([
                $userId,
                $studentId,
                $programmeId,
                $academicYear,
                $semester
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
}