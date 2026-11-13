<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../controller/authGuard.php";
requireRole(['student', 'lecturer']);

require_once "../database/database.php";
require_once "../entity/event.php";
require_once "../entity/eventRegistration.php";

class RegisterEventController
{
    private $db;
    private $eventEntity;
    private $registrationEntity;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
        $this->eventEntity = new Event();
        $this->registrationEntity = new EventRegistration(null, null, null, null, null, $this->db);
    }

    public function getEventById($eventId)
    {
        try {
            $sql = "SELECT e.id, e.universityId, e.title, e.description,
                        e.startDatetime, e.endDatetime, e.capacity, e.status,
                        f.location AS location
                    FROM Events e
                    INNER JOIN BookableFacilities bf ON bf.id = e.facilityId
                    INNER JOIN Facilities f ON f.id = bf.facilityId
                    WHERE e.id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$eventId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("getEventById error: " . $e->getMessage());
            return false;
        }
    }

    public function getRegistrationCount($eventId)
    {
        try {
            $sql = "SELECT COUNT(*) FROM EventRegistrations
                    WHERE eventId = ? AND status = 'registered'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$eventId]);
            return (int) $stmt->fetchColumn();
        } catch (Exception $e) {
            error_log("getRegistrationCount error: " . $e->getMessage());
            return 0;
        }
    }

    public function hasScheduleConflict($userId, $startDatetime, $endDatetime)
    {
        try {
            $eventStart = new DateTime($startDatetime);
            $eventEnd = new DateTime($endDatetime);

            $dayOfWeek = strtolower($eventStart->format('D'));
            $startTime = $eventStart->format('H:i:s');
            $endTime = $eventEnd->format('H:i:s');

            $sql = "SELECT c.classCode, c.className, c.startTime, c.endTime, m.code AS moduleCode
                    FROM Classes c
                    INNER JOIN Modules m ON m.id = c.moduleId
                    LEFT JOIN StudentEnrolments se
                        ON se.classId = c.id
                        AND se.studentId = ?
                        AND se.status = 'enrolled'
                    WHERE c.status = 'active'
                      AND c.dayOfWeek = ?
                      AND c.startTime < ?
                      AND c.endTime > ?
                      AND (c.staffId = ? OR se.id IS NOT NULL)
                    LIMIT 1";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $dayOfWeek, $endTime, $startTime, $userId]);
            $conflict = $stmt->fetch(PDO::FETCH_ASSOC);

            return $conflict ?: false;
        } catch (Exception $e) {
            error_log("hasScheduleConflict error: " . $e->getMessage());
            return false;
        }
    }

    public function registerForEvent($eventId, $userId)
    {
        $event = $this->getEventById($eventId);
        if (!$event) {
            return null;
        }

        $conflict = $this->hasScheduleConflict($userId, $event['startDatetime'], $event['endDatetime']);
        if ($conflict) {
            return "conflict";
        }

        $registrationCount = $this->getRegistrationCount($eventId);
        if ($event['capacity'] !== null && $registrationCount >= $event['capacity']) {
            return "full";
        }

        if ($this->registrationEntity->checkExistingMembership($userId, $eventId)) {
            return "already";
        }

        if ($this->registrationEntity->registerForEvent($userId, $eventId)) {
            return true;
        }

        return null;
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['register_event'])) {
    $eventId = $_POST['eventId'];
    $userId = $_SESSION['user_id'];

    $controller = new RegisterEventController();
    $result = $controller->registerForEvent($eventId, $userId);

    if ($result === true) {
        $_SESSION['register_success'] = "Successfully Registered For Event";
    } elseif ($result === "conflict") {
        $_SESSION['register_error'] = "Schedule Conflict: Cannot register as this event clashes with your class schedule.";
    } elseif ($result === "full") {
        $_SESSION['register_error'] = "This event is already full.";
    } elseif ($result === "already") {
        $_SESSION['register_error'] = "You are already registered for this event.";
    } else {
        $_SESSION['register_error'] = "Failed to register for the event. Please try again later.";
    }

    header("Location: ../boundary/viewEventsPage.php");
    exit();
}
?>