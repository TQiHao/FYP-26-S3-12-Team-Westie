<?php

require_once "../database/database.php";

class Programmes
{
    private $id;
    private $facultyId;
    private $name;
    private $code;
    private $description;
    private $createdAt;
    private $updatedAt;

    public function __construct(
        $id = null,
        $facultyId = null,
        $name = null,
        $code = null,
        $description = null,
        $createdAt = null,
        $updatedAt = null
    ) {
        $this->id = $id;
        $this->facultyId = $facultyId;
        $this->name = $name;
        $this->code = $code;
        $this->description = $description;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public function getProgrammesByFaculty($facultyId)
    {
        $database = new Database();
        $db = $database->connect();

        $sql = "SELECT
                id,
                facultyId,
                name,
                code,
                durationYears,
                description,
                createdAt,
                updatedAt
            FROM Programmes
            WHERE facultyId = ?
            ORDER BY id ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute([$facultyId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function uploadProgrammeList($rows, $facultyId)
    {
        $database = new Database();
        $db = $database->connect();

        try {

            $db->beginTransaction();

            $sql = "INSERT INTO Programmes
                (
                    facultyId,
                    name,
                    code,
                    durationYears,
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
                    NOW(),
                    NOW()
                )";

            $stmt = $db->prepare($sql);

            foreach ($rows as $row) {

                $stmt->execute([
                    $facultyId,
                    $row['name'],
                    $row['code'],
                    $row['durationYears'],
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

    public function getProgrammeById($programmeId, $facultyId, $universityId)
    {
        $database = new Database();
        $db = $database->connect();

        $sql = "SELECT
                p.id,
                p.facultyId,
                p.name,
                p.code,
                p.durationYears,
                p.description,
                p.createdAt,
                p.updatedAt
            FROM Programmes p
            INNER JOIN Faculties f
                ON p.facultyId = f.id
            WHERE p.id = ?
            AND p.facultyId = ?
            AND f.universityId = ?";

        $stmt = $db->prepare($sql);

        $stmt->execute([
            $programmeId,
            $facultyId,
            $universityId
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function programmeExists($facultyId, $name, $code)
    {
        $database = new Database();
        $db = $database->connect();

        $sql = "SELECT id
            FROM Programmes
            WHERE facultyId = ?
            AND (
                name = ?
                OR code = ?
            )
            LIMIT 1";

        $stmt = $db->prepare($sql);

        $stmt->execute([
            $facultyId,
            $name,
            $code
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }
}