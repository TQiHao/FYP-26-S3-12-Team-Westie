<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../database/database.php";
require_once "../entity/event.php";

class ViewEventsController
{
    private $db;
    private $eventEntity;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();

        // Pass $db into the Event entity so it uses the same connection
        $this->eventEntity = new Event(
            null, null, null, null, null, null,
            null, null, null, null, null, null, null, null,
            $this->db
        );
    }

    public function getEventList($universityId)
    {
        return $this->eventEntity->getEventList($universityId);
    }

    public function getUserRegisteredEvents($userId)
    {
        try {
            $sql = "SELECT e.id, e.title, e.startDatetime, e.endDatetime,
                           f.location AS location
                    FROM EventRegistrations er
                    JOIN Events e ON er.eventId = e.id
                    INNER JOIN BookableFacilities bf ON e.facilityId = bf.id
                    INNER JOIN Facilities f ON bf.facilityId = f.id
                    WHERE er.userId = ?
                      AND er.status = 'registered'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        } catch (Exception $e) {
            error_log("getUserRegisteredEvents error: " . $e->getMessage());
            return [];
        }
    }

    public function getUserRegisteredEventIds($userId)
    {
        try {
            $sql = "SELECT eventId FROM EventRegistrations
                    WHERE userId = ?
                      AND status IN ('registered', 'attended')";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

        } catch (Exception $e) {
            error_log("getUserRegisteredEventIds error: " . $e->getMessage());
            return [];
        }
    }
}
?>