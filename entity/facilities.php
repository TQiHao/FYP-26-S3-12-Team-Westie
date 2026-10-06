<?php

class Facilities
{
    private $id;
    private $universityId;
    private $name;
    private $type;
    private $description;
    private $location;
    private $blockFloor;
    private $capacity;
    private $status;
    private $createdAt;
    private $updatedAt;

    public function __construct(
        $id = null,
        $universityId = null,
        $name = null,
        $type = null,
        $description = null,
        $location = null,
        $blockFloor = null,
        $capacity = null,
        $status = null,
        $createdAt = null,
        $updatedAt = null
    ) {
        $this->id = $id;
        $this->universityId = $universityId;
        $this->name = $name;
        $this->type = $type;
        $this->description = $description;
        $this->location = $location;
        $this->blockFloor = $blockFloor;
        $this->capacity = $capacity;
        $this->status = $status;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    // Getters
    public function getId() { return $this->id; }
    public function getUniversityId() { return $this->universityId; }
    public function getName() { return $this->name; }
    public function getType() { return $this->type; }
    public function getDescription() { return $this->description; }
    public function getLocation() { return $this->location; }
    public function getBlockFloor() { return $this->blockFloor; }
    public function getCapacity() { return $this->capacity; }
    public function getStatus() { return $this->status; }
    public function getCreatedAt() { return $this->createdAt; }
    public function getUpdatedAt() { return $this->updatedAt; }

    // Setters
    public function setUniversityId($universityId) { $this->universityId = $universityId; }
    public function setName($name) { $this->name = $name; }
    public function setType($type) { $this->type = $type; }
    public function setDescription($description) { $this->description = $description; }
    public function setLocation($location) { $this->location = $location; }
    public function setBlockFloor($blockFloor) { $this->blockFloor = $blockFloor; }
    public function setCapacity($capacity) { $this->capacity = $capacity; }
    public function setStatus($status) { $this->status = $status; }
    public function setCreatedAt($createdAt) { $this->createdAt = $createdAt; }
    public function setUpdatedAt($updatedAt) { $this->updatedAt = $updatedAt; }

    // Upload verified Facility data into database
    public function uploadFacilityList($rows, $universityId)
    {
        $database = new Database();
        $db = $database->connect();

        try {

            $db->beginTransaction();

            $sql = "INSERT INTO Facilities
                    (
                        universityId,
                        name,
                        type,
                        description,
                        location,
                        blockFloor,
                        capacity,
                        status,
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
                        ?,
                        'active',
                        NOW(),
                        NOW()
                    )";

            $stmt = $db->prepare($sql);

            foreach ($rows as $row) {

                $stmt->execute([
                    $universityId,
                    $row['name'],
                    $row['type'],
                    $row['description'],
                    $row['location'],
                    $row['blockFloor'],
                    $row['capacity']
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

    // Get all Facilities for a University
    public function getFacilitiesByUniversity($universityId)
    {
        $database = new Database();
        $db = $database->connect();

        $sql = "SELECT
                    id,
                    universityId,
                    name,
                    type,
                    description,
                    location,
                    blockFloor,
                    capacity,
                    status,
                    createdAt,
                    updatedAt
                FROM Facilities
                WHERE universityId = ?
                ORDER BY id ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute([$universityId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Check whether Facility already exists
    public function facilityExists($universityId, $name, $location)
    {
        $database = new Database();
        $db = $database->connect();

        $sql = "SELECT id
                FROM Facilities
                WHERE universityId = ?
                AND name = ?
                AND location = ?
                LIMIT 1";

        $stmt = $db->prepare($sql);

        $stmt->execute([
            $universityId,
            $name,
            $location
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }

    public function updateFacilityStatus($facilityId, $status)
    {
        $database = new Database();
        $db = $database->connect();

        try {

            $sql = "UPDATE Facilities
                SET status = ?,
                    updatedAt = NOW()
                WHERE id = ?";

            $stmt = $db->prepare($sql);

            $stmt->execute([
                $status,
                $facilityId
            ]);

            return $stmt->rowCount() > 0;

        } catch (PDOException $e) {

            error_log(
                "Update facility status error: "
                . $e->getMessage()
            );

            return false;
        }
    }

    public function getFacilityByIdAndUniversity(
        $facilityId,
        $universityId
    ) {
        $database = new Database();
        $db = $database->connect();

        try {

            $sql = "SELECT
                    id,
                    universityId,
                    name,
                    type,
                    description,
                    location,
                    blockFloor,
                    capacity,
                    status
                FROM Facilities
                WHERE id = ?
                  AND universityId = ?
                LIMIT 1";

            $stmt = $db->prepare($sql);

            $stmt->execute([
                $facilityId,
                $universityId
            ]);

            $result =
                $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ?: false;

        } catch (PDOException $e) {

            error_log(
                "Get facility error: "
                . $e->getMessage()
            );

            return false;
        }
    }

    public function releaseFacilitiesForCompletedEvents($universityId)
    {
        $database = new Database();
        $db = $database->connect();

        try {

            $sql = "UPDATE Facilities f
                INNER JOIN Events e
                    ON e.universityId = f.universityId
                    AND (
                        TRIM(e.location) = TRIM(f.name)

                        OR

                        TRIM(e.location) =
                        TRIM(
                            CONCAT(
                                f.name,
                                ' — ',
                                f.location,
                                ', ',
                                f.blockFloor
                            )
                        )
                    )
                SET
                    f.status = 'active',
                    f.updatedAt = NOW()
                WHERE f.universityId = ?
                  AND f.status = 'occupied'
                  AND e.status = 'completed'";

            $stmt = $db->prepare($sql);

            $stmt->execute([
                $universityId
            ]);

            return $stmt->rowCount() > 0;

        } catch (PDOException $e) {

            error_log(
                "Release completed event facilities error: "
                . $e->getMessage()
            );

            return false;
        }
    }

    public function releaseFacilityByEventLocation(
        $universityId,
        $eventLocation
    ) {
        $database = new Database();
        $db = $database->connect();

        try {

            $sql = "UPDATE Facilities
                SET status = 'active',
                    updatedAt = NOW()
                WHERE universityId = ?
                  AND status = 'occupied'
                  AND LOWER(TRIM(
                      CONCAT(
                          name,
                          ' — ',
                          location,
                          ', ',
                          blockFloor
                      )
                  )) = LOWER(TRIM(?))";

            $stmt = $db->prepare($sql);

            $stmt->execute([
                $universityId,
                $eventLocation
            ]);

            return $stmt->rowCount() > 0;

        } catch (PDOException $e) {

            error_log(
                "Release facility error: "
                . $e->getMessage()
            );

            return false;
        }
    }
}
?>