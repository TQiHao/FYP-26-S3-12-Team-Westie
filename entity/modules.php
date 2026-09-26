<?php

require_once "../database/database.php";

class Modules
{
    private $id;
    private $programmeId;
    private $name;
    private $code;
    private $credits;
    private $semester;
    private $description;
    private $createdAt;
    private $updatedAt;

    public function __construct(
        $id = null,
        $programmeId = null,
        $name = null,
        $code = null,
        $credits = null,
        $semester = null,
        $description = null,
        $createdAt = null,
        $updatedAt = null
    ) {
        $this->id = $id;
        $this->programmeId = $programmeId;
        $this->name = $name;
        $this->code = $code;
        $this->credits = $credits;
        $this->semester = $semester;
        $this->description = $description;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }


    // Getters
    public function getId()
    {
        return $this->id;
    }

    public function getProgrammeId()
    {
        return $this->programmeId;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getCode()
    {
        return $this->code;
    }

    public function getCredits()
    {
        return $this->credits;
    }

    public function getSemester()
    {
        return $this->semester;
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
    public function setProgrammeId($programmeId)
    {
        $this->programmeId = $programmeId;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function setCode($code)
    {
        $this->code = $code;
    }

    public function setCredits($credits)
    {
        $this->credits = $credits;
    }

    public function setSemester($semester)
    {
        $this->semester = $semester;
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


    // Get modules by programme
    public function getModulesByProgramme($programmeId)
    {
        $database = new Database();
        $db = $database->connect();

        $sql = "SELECT
                    id,
                    programmeId,
                    name,
                    code,
                    credits,
                    semester,
                    description,
                    createdAt,
                    updatedAt
                FROM Modules
                WHERE programmeId = ?
                ORDER BY id ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute([$programmeId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    // Verify and insert module into database
    public function insertModuleList($rows, $programmeId)
    {
        $database = new Database();
        $db = $database->connect();

        try {

            $db->beginTransaction();

            $sql = "INSERT INTO Modules
                    (
                        programmeId,
                        name,
                        code,
                        credits,
                        semester,
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
                        ?,
                        ?,
                        NOW(),
                        NOW()
                    )";

            $stmt = $db->prepare($sql);

            foreach ($rows as $row) {

                $stmt->execute([
                    $programmeId,
                    $row['name'],
                    $row['code'],
                    $row['credits'],
                    $row['semester'],
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

    public function moduleExists($programmeId, $name, $code)
    {
        $database = new Database();
        $db = $database->connect();

        $sql = "SELECT id
            FROM Modules
            WHERE programmeId = ?
            AND (
                name = ?
                OR code = ?
            )
            LIMIT 1";

        $stmt = $db->prepare($sql);
        $stmt->execute([
            $programmeId,
            $name,
            $code
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }
}