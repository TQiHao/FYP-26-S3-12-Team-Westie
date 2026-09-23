<?php

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
        $this->db = $db;
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
}
?>