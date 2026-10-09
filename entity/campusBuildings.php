<?php

require_once "../database/database.php";

class CampusBuildings
{
    private $id;
    private $universityId;
    private $buildingName;
    private $buildingCode;
    private $description;
    private $status;
    private $createdAt;
    private $updatedAt;

    private $db;

    public function __construct($db = null)
    {
        if ($db !== null) {
            $this->db = $db;
        } else {
            $database = new Database();
            $this->db = $database->connect();
        }
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

    public function getBuildingName()
    {
        return $this->buildingName;
    }

    public function getBuildingCode()
    {
        return $this->buildingCode;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getStatus()
    {
        return $this->status;
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

    public function setUniversityId($universityId)
    {
        $this->universityId = $universityId;
    }

    public function setBuildingName($buildingName)
    {
        $this->buildingName = $buildingName;
    }

    public function setBuildingCode($buildingCode)
    {
        $this->buildingCode = $buildingCode;
    }

    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    // Create a new building
    public function createBuilding($universityId, $buildingName, $buildingCode, $description)
    {
        $sql = "INSERT INTO CampusBuildings
            (universityId, buildingName, buildingCode, description, status)
            VALUES (?, ?, ?, ?, 'active')";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            return false;
        }

        return $stmt->execute([
            $universityId,
            $buildingName,
            $buildingCode,
            $description
        ]);
    }

    // Get all buildings for a university
    public function getBuildingsByUniversity($universityId)
    {
        $sql = "SELECT *
            FROM CampusBuildings
            WHERE universityId = ?
            AND status = 'active'
            ORDER BY buildingName ASC";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            return [];
        }

        $stmt->execute([$universityId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get one building by ID
    public function getBuildingById($id)
    {
        $sql = "SELECT *
            FROM CampusBuildings
            WHERE id = ?";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            return null;
        }

        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Update a building
    public function updateBuilding($id, $buildingName, $buildingCode, $description)
    {
        $sql = "UPDATE CampusBuildings
            SET buildingName = ?,
                buildingCode = ?,
                description = ?
            WHERE id = ?";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            return false;
        }

        return $stmt->execute([
            $buildingName,
            $buildingCode,
            $description,
            $id
        ]);
    }

    // Deactivate a building
    public function deactivateBuilding($id)
    {
        $sql = "UPDATE CampusBuildings
            SET status = 'inactive'
            WHERE id = ?";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            return false;
        }

        return $stmt->execute([$id]);
    }

    public function buildingCodeExists(
        $universityId,
        $buildingCode,
        $excludeId = null
    ) {
        $sql = "SELECT id
            FROM CampusBuildings
            WHERE universityId = ?
            AND buildingCode = ?
            AND status = 'active'";

        $params = [
            $universityId,
            $buildingCode
        ];

        if ($excludeId !== null) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }

        $sql .= " LIMIT 1";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            return false;
        }

        $stmt->execute($params);

        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }
}
?>