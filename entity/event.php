<?php

require_once "../database/database.php";

class Event
{
    private $id;
    private $universityId;
    private $createdBy;
    private $facilityId;
    private $title;
    private $description;
    private $startDatetime;
    private $endDatetime;
    private $capacity;
    private $eventPoster;
    private $eventInfo;
    private $status;
    private $createdAt;
    private $updatedAt;
    private $db;

    public function __construct(
        $id = null,
        $universityId = null,
        $createdBy = null,
        $facilityId = null,
        $title = null,
        $description = null,
        $startDatetime = null,
        $endDatetime = null,
        $capacity = null,
        $eventPoster = null,
        $eventInfo = null,
        $status = null,
        $createdAt = null,
        $updatedAt = null,
        $db = null
    ) {
        $this->id = $id;
        $this->universityId = $universityId;
        $this->createdBy = $createdBy;
        $this->facilityId = $facilityId;
        $this->title = $title;
        $this->description = $description;
        $this->startDatetime = $startDatetime;
        $this->endDatetime = $endDatetime;
        $this->capacity = $capacity;
        $this->eventPoster = $eventPoster;
        $this->eventInfo = $eventInfo;
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
    public function getFacilityId() { return $this->facilityId; }
    public function getTitle() { return $this->title; }
    public function getDescription() { return $this->description; }
    public function getStartDatetime() { return $this->startDatetime; }
    public function getEndDatetime() { return $this->endDatetime; }
    public function getCapacity() { return $this->capacity; }
    public function getEventPoster() { return $this->eventPoster; }
    public function getEventInfo() { return $this->eventInfo; }
    public function getStatus() { return $this->status; }
    public function getCreatedAt() { return $this->createdAt; }
    public function getUpdatedAt() { return $this->updatedAt; }

    /**
     * Get all active events for a university (with facility location)
     */
    public function getEventList($universityId)
    {
        try {
            $sql = "SELECT e.id, e.universityId, e.createdBy, e.facilityId,
                           e.title, e.description,
                           e.startDatetime, e.endDatetime, e.capacity,
                           e.eventPoster, e.eventInfo, e.status,
                           e.createdAt, e.updatedAt,
                           f.location AS location,
                           f.name AS facilityName
                    FROM Events e
                    INNER JOIN BookableFacilities bf ON e.facilityId = bf.id
                    INNER JOIN Facilities f ON bf.facilityId = f.id
                    WHERE e.universityId = ?
                      AND e.status = 'active'
                    ORDER BY e.startDatetime ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$universityId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("Event list fetch error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Find events by search criteria (with facility location)
     */
    public function findEventsByCriteria($universityId, $keyword)
    {
        try {
            $sql = "SELECT e.id, e.universityId, e.createdBy, e.facilityId,
                           e.title, e.description,
                           e.startDatetime, e.endDatetime, e.capacity,
                           e.eventPoster, e.eventInfo, e.status,
                           e.createdAt, e.updatedAt,
                           f.location AS location,
                           f.name AS facilityName
                    FROM Events e
                    INNER JOIN BookableFacilities bf ON e.facilityId = bf.id
                    INNER JOIN Facilities f ON bf.facilityId = f.id
                    WHERE e.universityId = ?
                      AND e.status = 'active'
                      AND (
                          e.title LIKE ?
                          OR e.description LIKE ?
                          OR f.location LIKE ?
                          OR DATE(e.startDatetime) LIKE ?
                      )
                    ORDER BY e.startDatetime ASC";
            $stmt = $this->db->prepare($sql);
            $searchTerm = '%' . $keyword . '%';
            $stmt->execute([$universityId, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("findEventsByCriteria error: " . $e->getMessage());
            return false;
        }
    }

    public function eventExists($universityId, $title, $startDatetime, $endDatetime)
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
            $stmt->execute([$universityId, $title, $startDatetime, $endDatetime]);
            return $stmt->fetch(PDO::FETCH_ASSOC) !== false;

        } catch (Exception $e) {
            error_log("Event duplicate check error: " . $e->getMessage());
            return false;
        }
    }

    public function createUniversityEvent($universityId, $createdBy, $facilityId, $title, $description, $startDatetime, $endDatetime, $capacity)
    {
        try {
            $sql = "INSERT INTO Events
                    (universityId, createdBy, facilityId, title, description,
                     startDatetime, endDatetime, capacity, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $universityId,
                $createdBy,
                $facilityId,
                $title,
                $description,
                $startDatetime,
                $endDatetime,
                $capacity
            ]);
            return $this->db->lastInsertId();

        } catch (Exception $e) {
            error_log("Create event error: " . $e->getMessage());
            return false;
        }
    }

    public function getAllEvents($universityId)
    {
        try {
            $sql = "SELECT e.id, e.universityId, e.createdBy, e.facilityId,
                           e.title, e.description,
                           e.startDatetime, e.endDatetime, e.capacity,
                           e.eventPoster, e.eventInfo, e.status,
                           e.createdAt, e.updatedAt,
                           f.location AS location,
                           f.name AS facilityName
                    FROM Events e
                    INNER JOIN BookableFacilities bf ON e.facilityId = bf.id
                    INNER JOIN Facilities f ON bf.facilityId = f.id
                    WHERE e.universityId = ?
                    ORDER BY e.startDatetime ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$universityId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("Get all events error: " . $e->getMessage());
            return false;
        }
    }

    public function searchAllEvents($universityId, $keyword)
    {
        try {
            $sql = "SELECT e.id, e.universityId, e.createdBy, e.facilityId,
                           e.title, e.description,
                           e.startDatetime, e.endDatetime, e.capacity,
                           e.eventPoster, e.eventInfo, e.status,
                           e.createdAt, e.updatedAt,
                           f.location AS location,
                           f.name AS facilityName
                    FROM Events e
                    INNER JOIN BookableFacilities bf ON e.facilityId = bf.id
                    INNER JOIN Facilities f ON bf.facilityId = f.id
                    WHERE e.universityId = ?
                      AND (
                          e.title LIKE ?
                          OR e.description LIKE ?
                          OR f.location LIKE ?
                          OR DATE(e.startDatetime) LIKE ?
                      )
                    ORDER BY e.startDatetime ASC";
            $searchTerm = '%' . $keyword . '%';
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$universityId, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("Search all events error: " . $e->getMessage());
            return false;
        }
    }

    public function getEventByIdAndUniversity($eventId, $universityId)
    {
        try {
            $sql = "SELECT e.id, e.universityId, e.createdBy, e.facilityId,
                           e.title, e.description,
                           e.startDatetime, e.endDatetime, e.capacity,
                           e.eventPoster, e.eventInfo, e.status,
                           e.createdAt, e.updatedAt,
                           f.location AS location,
                           f.name AS facilityName
                    FROM Events e
                    INNER JOIN BookableFacilities bf ON e.facilityId = bf.id
                    INNER JOIN Facilities f ON bf.facilityId = f.id
                    WHERE e.id = ?
                      AND e.universityId = ?
                    LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$eventId, $universityId]);
            $event = $stmt->fetch(PDO::FETCH_ASSOC);
            return $event ?: false;

        } catch (Exception $e) {
            error_log("Get event by ID error: " . $e->getMessage());
            return false;
        }
    }

    public function updateUniversityEvent($eventId, $universityId, $facilityId, $title, $description, $startDatetime, $endDatetime, $capacity)
    {
        try {
            $sql = "UPDATE Events
                    SET facilityId = ?,
                        title = ?,
                        description = ?,
                        startDatetime = ?,
                        endDatetime = ?,
                        capacity = ?,
                        updatedAt = NOW()
                    WHERE id = ?
                      AND universityId = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $facilityId,
                $title,
                $description,
                $startDatetime,
                $endDatetime,
                $capacity,
                $eventId,
                $universityId
            ]);
            return $stmt->rowCount() >= 0;

        } catch (Exception $e) {
            error_log("Update event error: " . $e->getMessage());
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
            $stmt->execute([$eventId, $universityId]);
            return $stmt->rowCount() > 0;

        } catch (PDOException $e) {
            error_log("Suspend university event error: " . $e->getMessage());
            return false;
        }
    }

    public function completePastEvents($universityId)
    {
        try {
            $sql = "SELECT id, facilityId
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
            error_log("Complete past events error: " . $e->getMessage());
            return [];
        }
    }

    public function getRecommendedFacilities($universityId, $capacity, $startDatetime, $endDatetime)
    {
        try {
            $bufferStart = date('Y-m-d H:i:s', strtotime($startDatetime . ' -30 minutes'));
            $bufferEnd   = date('Y-m-d H:i:s', strtotime($endDatetime . ' +30 minutes'));

            $sql = "SELECT
                        bf.id AS bookableFacilityId,
                        f.id AS facilityId,
                        f.name,
                        f.type,
                        f.description,
                        f.location,
                        f.blockFloor,
                        f.capacity,
                        bf.isBookable,
                        bf.status AS bookableStatus,
                        bf.slotDuration,
                        bf.bookingCapacity,
                        bf.openTime,
                        bf.closeTime
                    FROM BookableFacilities bf
                    INNER JOIN Facilities f ON f.id = bf.facilityId
                    WHERE f.universityId = ?
                      AND f.status = 'active'
                      AND f.capacity >= ?
                      AND bf.isBookable = TRUE
                      AND bf.status = 'available'
                      AND NOT EXISTS (
                          SELECT 1 FROM Events e
                          WHERE e.facilityId = bf.id
                            AND e.status = 'active'
                            AND e.startDatetime < ?
                            AND e.endDatetime > ?
                      )
                      AND NOT EXISTS (
                          SELECT 1 FROM FacilityBookings fb
                          WHERE fb.facilityId = bf.id
                            AND fb.status IN ('pending', 'confirmed')
                            AND TIMESTAMP(fb.bookingDate, fb.startTime) < ?
                            AND TIMESTAMP(fb.bookingDate, fb.endTime) > ?
                      )
                    ORDER BY f.capacity ASC, f.name ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $universityId,
                $capacity,
                $bufferEnd,
                $bufferStart,
                $bufferEnd,
                $bufferStart
            ]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("Get recommended facilities error: " . $e->getMessage());
            return [];
        }
    }
}
?>