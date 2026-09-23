<?php

class EventRegistration
{
    private $id;
    private $eventId;
    private $userId;
    private $registrationDate;
    private $status;

    private $db;

    public function __construct(
        $id = null,
        $eventId = null,
        $userId = null,
        $registrationDate = null,
        $status = null,
        $db = null
    ) {
        $this->id = $id;
        $this->eventId = $eventId;
        $this->userId = $userId;
        $this->registrationDate = $registrationDate;
        $this->status = $status;
        $this->db = $db;
    }

    // Getters
    public function getId() { return $this->id; }
    public function getEventId() { return $this->eventId; }
    public function getUserId() { return $this->userId; }
    public function getRegistrationDate() { return $this->registrationDate; }
    public function getStatus() { return $this->status; }

    /**
     * Check if a student is already registered for an event
     * @param int $studentId
     * @param int $eventId
     * @return bool
     */
    public function checkExistingMembership($studentId, $eventId)
    {
        try {
            $sql = "SELECT COUNT(*) FROM EventRegistrations
                    WHERE userId = ?
                      AND eventId = ?
                      AND status = 'registered'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$studentId, $eventId]);
            return $stmt->fetchColumn() > 0;

        } catch (Exception $e) {
            error_log("checkExistingMembership error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Register a student for an event
     * @param int $studentId
     * @param int $eventId
     * @return bool
     */
    public function registerForEvent($studentId, $eventId)
    {
        try {
            $sql = "INSERT INTO EventRegistrations (eventId, userId, registrationDate, status)
                    VALUES (?, ?, NOW(), 'registered')";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$eventId, $studentId]);
            return true;

        } catch (Exception $e) {
            error_log("registerForEvent error: " . $e->getMessage());
            return false;
        }
    }
}
?>