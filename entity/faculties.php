<?php

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

    // Upload and import Faculty CSV
    public function uploadFacultyList($csvFile, $universityId)
    {
        if (
            !isset($csvFile) ||
            $csvFile['error'] !== UPLOAD_ERR_OK
        ) {
            return false;
        }

        // Check file type
        $extension = strtolower(
            pathinfo($csvFile['name'], PATHINFO_EXTENSION)
        );

        if ($extension !== 'csv') {
            return false;
        }

        // Open CSV
        $handle = fopen($csvFile['tmp_name'], 'r');

        if ($handle === false) {
            return false;
        }

        // Read header
        $headers = fgetcsv($handle);

        if ($headers === false) {
            fclose($handle);
            return false;
        }

        $headers = array_map('trim', $headers);

        // Expected Faculty CSV columns
        $expectedHeaders = [
            'name',
            'code',
            'description'
        ];

        // Verify header
        if ($headers !== $expectedHeaders) {
            fclose($handle);
            return false;
        }

        // Store verified rows first
        $rows = [];

        while (($row = fgetcsv($handle)) !== false) {

            // Skip empty rows
            if (
                count($row) === 1 &&
                trim($row[0]) === ''
            ) {
                continue;
            }

            // Must contain exactly 3 columns
            if (count($row) !== 3) {
                fclose($handle);
                return false;
            }

            $name = trim($row[0]);
            $code = trim($row[1]);
            $description = trim($row[2]);

            // Required fields
            if ($name === '' || $code === '') {
                fclose($handle);
                return false;
            }

            $rows[] = [
                'name' => $name,
                'code' => $code,
                'description' => $description
            ];
        }

        fclose($handle);

        // Connect to database
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

            // Insert only after the entire CSV is verified
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
}

