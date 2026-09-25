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
        $this->eventEntity = new Event(null, null, null, null, null, null, null, null, null, null, null, null, $this->db);
    }

    /**
     * Get all active events for a university
     * @param int $universityId
     * @return array|false
     */
    public function getEventList($universityId)
    {
        return $this->eventEntity->getEventList($universityId);
    }

    /**
     * Get all registered events for a specific user to display in personal timetable
     * @param int|string $userId
     * @return array
     */
    public function getUserRegisteredEvents($userId)
    {
        try {
            $sql = "SELECT e.id, e.title, e.location, e.startDatetime, e.endDatetime
                    FROM EventRegistrations er
                    JOIN Events e ON er.eventId = e.id
                    WHERE er.userId = ? AND er.status = 'registered'";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            error_log("getUserRegisteredEvents error: " . $e->getMessage());
            return [];
        }
    }
}
?>