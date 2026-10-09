<?php

require_once "../database/database.php";

class CampusFloors
{
    private $id;
    private $buildingId;
    private $floorName;
    private $floorNumber;
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

    public function getBuildingId()
    {
        return $this->buildingId;
    }

    public function getFloorName()
    {
        return $this->floorName;
    }

    public function getFloorNumber()
    {
        return $this->floorNumber;
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

    public function setBuildingId($buildingId)
    {
        $this->buildingId = $buildingId;
    }

    public function setFloorName($floorName)
    {
        $this->floorName = $floorName;
    }

    public function setFloorNumber($floorNumber)
    {
        $this->floorNumber = $floorNumber;
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

    // Create a new floor
    public function createFloor(
        $buildingId,
        $floorName,
        $floorNumber,
        $description
    ) {
        $sql = "INSERT INTO CampusFloors
            (buildingId, floorName, floorNumber, description, status)
            VALUES (?, ?, ?, ?, 'active')";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            return false;
        }

        return $stmt->execute([
            $buildingId,
            $floorName,
            $floorNumber,
            $description
        ]);
    }

    // Get all floors for a building
    public function getFloorsByBuilding($buildingId)
    {
        $sql = "SELECT *
            FROM CampusFloors
            WHERE buildingId = ?
            AND status = 'active'
            ORDER BY floorNumber ASC";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            return [];
        }

        $stmt->execute([$buildingId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get one floor by ID
    public function getFloorById($id)
    {
        $sql = "SELECT *
            FROM CampusFloors
            WHERE id = ?";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            return null;
        }

        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Update a floor
    public function updateFloor(
        $id,
        $floorName,
        $floorNumber,
        $description
    ) {
        $sql = "UPDATE CampusFloors
            SET floorName = ?,
                floorNumber = ?,
                description = ?
            WHERE id = ?";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            return false;
        }

        return $stmt->execute([
            $floorName,
            $floorNumber,
            $description,
            $id
        ]);
    }

    // Deactivate a floor
    public function deactivateFloor($id)
    {
        $sql = "UPDATE CampusFloors
            SET status = 'inactive'
            WHERE id = ?";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            return false;
        }

        return $stmt->execute([$id]);
    }

    public function floorExists(
        $buildingId,
        $floorName,
        $floorNumber,
        $excludeId = null
    ) {
        $sql = "SELECT id
            FROM CampusFloors
            WHERE buildingId = ?
            AND status = 'active'
            AND (
                floorName = ?
                OR floorNumber = ?
            )";

        $params = [
            $buildingId,
            $floorName,
            $floorNumber
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