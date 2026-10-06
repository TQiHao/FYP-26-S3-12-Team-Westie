<?php

require_once "../database/database.php";

class Event
{
    private $id;
    private $universityId;
    private $createdBy;
    private $title;
    private $description;
    private $location;
    private $startDatetime;
    private $endDatetime;
    private $capacity;
    private $status;
    private $createdAt;
    private $updatedAt;
    private $db;

    public function __construct(
        $id = null,
        $universityId = null,
        $createdBy = null,
        $title = null,
        $description = null,
        $location = null,
        $startDatetime = null,
        $endDatetime = null,
        $capacity = null,
        $status = null,
        $createdAt = null,
        $updatedAt = null,
        $db = null
    ) {
        $this->id = $id;
        $this->universityId = $universityId;
        $this->createdBy = $createdBy;
        $this->title = $title;
        $this->description = $description;
        $this->location = $location;
        $this->startDatetime = $startDatetime;
        $this->endDatetime = $endDatetime;
        $this->capacity = $capacity;
        $this->status = $status;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;

        if ($db !== null) {
            $this->db = $db;
        } else {
            $database = new Database();
            $this->db = $database->connect();
        }
    }

    // ===== Getters =====
    public function getId() { return $this->id; }
    public function getUniversityId() { return $this->universityId; }
    public function getCreatedBy() { return $this->createdBy; }
    public function getTitle() { return $this->title; }
    public function getDescription() { return $this->description; }
    public function getLocation() { return $this->location; }
    public function getStartDatetime() { return $this->startDatetime; }
    public function getEndDatetime() { return $this->endDatetime; }
    public function getCapacity() { return $this->capacity; }
    public function getStatus() { return $this->status; }
    public function getCreatedAt() { return $this->createdAt; }
    public function getUpdatedAt() { return $this->updatedAt; }

    /**
     * Get all active events for a university
     * @param int $universityId
     * @return array|false
     */
    public function getEventList($universityId)
    {
        try {
            $sql = "SELECT id, universityId, createdBy, title, description, location,
                           startDatetime, endDatetime, capacity, status, createdAt, updatedAt
                    FROM Events
                    WHERE universityId = ?
                      AND status = 'active'
                    ORDER BY startDatetime ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$universityId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("Event list fetch error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Find events by search criteria
     * @param int $universityId
     * @param string $keyword
     * @return array|false
     */
    public function findEventsByCriteria($universityId, $keyword)
    {
        try {
            $sql = "SELECT id, universityId, createdBy, title, description, location,
                           startDatetime, endDatetime, capacity, status, createdAt, updatedAt
                    FROM Events
                    WHERE universityId = ?
                      AND status = 'active'
                      AND (
                          title LIKE ?
                          OR description LIKE ?
                          OR location LIKE ?
                          OR DATE(startDatetime) LIKE ?
                      )
                    ORDER BY startDatetime ASC";
            $stmt = $this->db->prepare($sql);
            $searchTerm = '%' . $keyword . '%';
            $stmt->execute([$universityId, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("findEventsByCriteria error: " . $e->getMessage());
            return false;
        }
    }

    public function eventExists($universityId, $title,  $startDatetime, $endDatetime) 
    {
        try {

            $sql = "SELECT id
                FROM Events
                WHERE universityId = ?
                  AND title = ?
                  AND startDatetime = ?
                  AND endDatetime = ?
                LIMIT 1";

            $stmt = $this->db->prepare($sql);

            $stmt->execute([
                $universityId,
                $title,
                $startDatetime,
                $endDatetime
            ]);

            return $stmt->fetch(PDO::FETCH_ASSOC) !== false;

        } catch (Exception $e) {

            error_log(
                "Event duplicate check error: "
                . $e->getMessage()
            );

            return false;
        }
    }

    public function createUniversityEvent($universityId, $createdBy, $title, $description, $location, $startDatetime, $endDatetime, $capacity) 
    {
        try {

            $sql = "INSERT INTO Events
                (
                    universityId,
                    createdBy,
                    title,
                    description,
                    location,
                    startDatetime,
                    endDatetime,
                    capacity,
                    status
                )
                VALUES
                (
                    ?, ?, ?, ?, ?, ?, ?, ?,
                    'active'
                )";

            $stmt = $this->db->prepare($sql);

            $stmt->execute([
                $universityId,
                $createdBy,
                $title,
                $description,
                $location,
                $startDatetime,
                $endDatetime,
                $capacity
            ]);

            return $this->db->lastInsertId();

        } catch (Exception $e) {

            error_log(
                "Create event error: "
                . $e->getMessage()
            );

            return false;
        }
    }

    public function locationExistsInActiveEvent(
        $universityId,
        $location
    ) {
        try {

            $sql = "SELECT id
                FROM Events
                WHERE universityId = ?
                  AND location = ?
                  AND status = 'active'
                LIMIT 1";

            $stmt = $this->db->prepare($sql);

            $stmt->execute([
                $universityId,
                $location
            ]);

            return $stmt->fetch(PDO::FETCH_ASSOC) !== false;

        } catch (Exception $e) {

            error_log(
                "Active event location check error: "
                . $e->getMessage()
            );

            return false;
        }
    }

    public function getAllEvents($universityId)
    {
        try {

            $sql = "SELECT
                    id,
                    universityId,
                    createdBy,
                    title,
                    description,
                    location,
                    startDatetime,
                    endDatetime,
                    capacity,
                    status,
                    createdAt,
                    updatedAt
                FROM Events
                WHERE universityId = ?
                ORDER BY startDatetime ASC";

            $stmt = $this->db->prepare($sql);

            $stmt->execute([
                $universityId
            ]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {

            error_log(
                "Get all events error: "
                . $e->getMessage()
            );

            return false;
        }
    }

    public function searchAllEvents(
        $universityId,
        $keyword
    ) {
        try {

            $sql = "SELECT
                    id,
                    universityId,
                    createdBy,
                    title,
                    description,
                    location,
                    startDatetime,
                    endDatetime,
                    capacity,
                    status,
                    createdAt,
                    updatedAt
                FROM Events
                WHERE universityId = ?
                  AND (
                      title LIKE ?
                      OR description LIKE ?
                      OR location LIKE ?
                      OR DATE(startDatetime) LIKE ?
                  )
                ORDER BY startDatetime ASC";

            $searchTerm =
                '%' . $keyword . '%';

            $stmt = $this->db->prepare($sql);

            $stmt->execute([
                $universityId,
                $searchTerm,
                $searchTerm,
                $searchTerm,
                $searchTerm
            ]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {

            error_log(
                "Search all events error: "
                . $e->getMessage()
            );

            return false;
        }
    }

    public function getEventByIdAndUniversity(
        $eventId,
        $universityId
    ) {
        try {

            $sql = "SELECT
                    id,
                    universityId,
                    createdBy,
                    title,
                    description,
                    location,
                    startDatetime,
                    endDatetime,
                    capacity,
                    status,
                    createdAt,
                    updatedAt
                FROM Events
                WHERE id = ?
                  AND universityId = ?
                LIMIT 1";

            $stmt = $this->db->prepare($sql);

            $stmt->execute([
                $eventId,
                $universityId
            ]);

            $event =
                $stmt->fetch(PDO::FETCH_ASSOC);

            return $event ?: false;

        } catch (Exception $e) {

            error_log(
                "Get event by ID error: "
                . $e->getMessage()
            );

            return false;
        }
    }

    public function updateUniversityEvent(
        $eventId,
        $universityId,
        $title,
        $description,
        $location,
        $startDatetime,
        $endDatetime,
        $capacity
    ) {
        try {

            $sql = "UPDATE Events
                SET title = ?,
                    description = ?,
                    location = ?,
                    startDatetime = ?,
                    endDatetime = ?,
                    capacity = ?,
                    updatedAt = NOW()
                WHERE id = ?
                  AND universityId = ?";

            $stmt = $this->db->prepare($sql);

            $stmt->execute([
                $title,
                $description,
                $location,
                $startDatetime,
                $endDatetime,
                $capacity,
                $eventId,
                $universityId
            ]);

            return $stmt->rowCount() >= 0;

        } catch (Exception $e) {

            error_log(
                "Update event error: "
                . $e->getMessage()
            );

            return false;
        }
    }

    public function suspendUniversityEvent($eventId, $universityId)
    {
        try {

            $sql = "UPDATE Events
                SET status = 'suspended',
                    updatedAt = NOW()
                WHERE id = ?
                  AND universityId = ?
                  AND status = 'active'";

            $stmt = $this->db->prepare($sql);

            $stmt->execute([
                $eventId,
                $universityId
            ]);

            return $stmt->rowCount() > 0;

        } catch (PDOException $e) {

            error_log(
                "Suspend university event error: "
                . $e->getMessage()
            );

            return false;
        }
    }

    public function completePastEvents($universityId)
    {
        try {

            // Find active events whose end time has passed
            $sql = "SELECT id, location
                FROM Events
                WHERE universityId = ?
                  AND status = 'active'
                  AND endDatetime < NOW()";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$universityId]);

            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!$events) {
                return [];
            }

            // Mark events as completed
            $sql = "UPDATE Events
                SET status = 'completed',
                    updatedAt = NOW()
                WHERE universityId = ?
                  AND status = 'active'
                  AND endDatetime < NOW()";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$universityId]);

            return $events;

        } catch (PDOException $e) {

            error_log(
                "Complete past events error: " .
                $e->getMessage()
            );

            return [];
        }
    }
}
?>