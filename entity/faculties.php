<?php

require_once "../database/database.php";

class Faculties
{
    private $id;
    private $universityId;
    private $name;
    private $code;
    private $description;
    private $createdAt;
    private $updatedAt;

    public function __construct(
        $id = null,
        $universityId = null,
        $name = null,
        $code = null,
        $description = null,
        $createdAt = null,
        $updatedAt = null
    ) {
        $this->id = $id;
        $this->universityId = $universityId;
        $this->name = $name;
        $this->code = $code;
        $this->description = $description;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    // Getters
    public function getId()
    {
        return $this->id;
    }

    public function getUniversityId()
    {
        return $this->universityId;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getCode()
    {
        return $this->code;
    }

    public function getDescription()
    {
        return $this->description;
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
    public function setUniversityId($universityId)
    {
        $this->universityId = $universityId;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function setCode($code)
    {
        $this->code = $code;
    }

    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    // Insert verified Faculty data into database
    public function uploadFacultyList($rows, $universityId)
    {
        $database = new Database();
        $db = $database->connect();

        try {

            $db->beginTransaction();

            $sql = "INSERT INTO Faculties
                (
                    universityId,
                    name,
                    code,
                    description,
                    createdAt,
                    updatedAt
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    NOW(),
                    NOW()
                )";

            $stmt = $db->prepare($sql);

            foreach ($rows as $row) {

                $stmt->execute([
                    $universityId,
                    $row['name'],
                    $row['code'],
                    $row['description']
                ]);
            }

            $db->commit();

            return true;

        } catch (PDOException $e) {

            if ($db->inTransaction()) {
                $db->rollBack();
            }

            return false;
        }
    }

    public function getFacultiesByUniversity($universityId)
    {
        $database = new Database();
        $db = $database->connect();

        $sql = "SELECT
                id,
                universityId,
                name,
                code,
                description,
                createdAt,
                updatedAt
            FROM Faculties
            WHERE universityId = ?
            ORDER BY id ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute([$universityId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getFacultyById($facultyId, $universityId)
    {
        $database = new Database();
        $db = $database->connect();

        $sql = "SELECT
                id,
                universityId,
                name,
                code,
                description,
                createdAt,
                updatedAt
            FROM Faculties
            WHERE id = ?
            AND universityId = ?";

        $stmt = $db->prepare($sql);
        $stmt->execute([
            $facultyId,
            $universityId
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Check whether Faculty already exists
    public function facultyExists($universityId, $name, $code)
    {
        $database = new Database();
        $db = $database->connect();

        $sql = "SELECT id
            FROM Faculties
            WHERE universityId = ?
            AND (
                name = ?
                OR code = ?
            )
            LIMIT 1";

        $stmt = $db->prepare($sql);

        $stmt->execute([
            $universityId,
            $name,
            $code
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }
}

