<?php

require_once "../database/database.php";

class FloorPlans
{
    private $id;
    private $universityId;
    private $buildingName;
    private $fileName;
    private $filePath;
    private $uploadedBy;
    private $uploadedAt;
    private $updatedAt;


    public function __construct()
    {
    }


    // Getters and setters

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }


    public function getUniversityId()
    {
        return $this->universityId;
    }

    public function setUniversityId($universityId)
    {
        $this->universityId = $universityId;
    }


    public function getBuildingName()
    {
        return $this->buildingName;
    }

    public function setBuildingName($buildingName)
    {
        $this->buildingName = $buildingName;
    }


    public function getFileName()
    {
        return $this->fileName;
    }

    public function setFileName($fileName)
    {
        $this->fileName = $fileName;
    }


    public function getFilePath()
    {
        return $this->filePath;
    }

    public function setFilePath($filePath)
    {
        $this->filePath = $filePath;
    }


    public function getUploadedBy()
    {
        return $this->uploadedBy;
    }

    public function setUploadedBy($uploadedBy)
    {
        $this->uploadedBy = $uploadedBy;
    }


    public function getUploadedAt()
    {
        return $this->uploadedAt;
    }

    public function setUploadedAt($uploadedAt)
    {
        $this->uploadedAt = $uploadedAt;
    }


    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }


    // Upload floor plan information into database 
    public function uploadFloorPlan(
        $universityId,
        $buildingName,
        $fileName,
        $filePath,
        $uploadedBy
    ) {
        $database = new Database();
        $db = $database->connect();

        try {

            $sql = "INSERT INTO FloorPlans
                    (
                        universityId,
                        buildingName,
                        fileName,
                        filePath,
                        uploadedBy,
                        uploadedAt,
                        updatedAt
                    )
                    VALUES
                    (
                        ?, ?, ?, ?, ?, NOW(), NOW()
                    )";

            $stmt = $db->prepare($sql);

            $stmt->execute([
                $universityId,
                $buildingName,
                $fileName,
                $filePath,
                $uploadedBy
            ]);

            return true;

        } catch (PDOException $e) {

            return false;
        }
    }


    // Get the latest floor plan for a university
    public function getLatestFloorPlan($universityId)
    {
        $database = new Database();
        $db = $database->connect();

        try {

            $sql = "SELECT
                        id,
                        universityId,
                        buildingName,
                        fileName,
                        filePath,
                        uploadedBy,
                        uploadedAt,
                        updatedAt
                    FROM FloorPlans
                    WHERE universityId = ?
                    ORDER BY id DESC
                    LIMIT 1";

            $stmt = $db->prepare($sql);

            $stmt->execute([
                $universityId
            ]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ?: null;

        } catch (PDOException $e) {

            return null;
        }
    }
}