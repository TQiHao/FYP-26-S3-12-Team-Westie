<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
            $sql = "SELECT id, universityId, title, description, location,
                           startDatetime, endDatetime, capacity, status
                    FROM Events
                    WHERE id = ?";
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

    /**
     * Check if an event conflicts with user's mandatory class schedule
     */
    public function hasScheduleConflict($userId, $startDatetime, $endDatetime)
    {
        try {
            $eventStart = new DateTime($startDatetime);
            $eventEnd = new DateTime($endDatetime);

            // Use 3-letter format ('mon', 'tue', 'thu') to match TimetableEntries table
            $dayOfWeek = strtolower($eventStart->format('D'));
            $startTime = $eventStart->format('H:i:s');
            $endTime = $eventEnd->format('H:i:s');

            // Query TimetableEntries table
            $sql = "SELECT title, startTime, endTime 
                    FROM TimetableEntries 
                    WHERE userId = ? 
                      AND LOWER(dayOfWeek) = ?
                      AND startTime < ? 
                      AND endTime > ?
                    LIMIT 1";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $dayOfWeek, $endTime, $startTime]);
            $conflict = $stmt->fetch(PDO::FETCH_ASSOC);

            return $conflict ?: false;
        } catch (Exception $e) {
            error_log("hasScheduleConflict error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Register any valid user (Student or Lecturer) for an event
     */
    public function registerForEvent($eventId, $userId)
    {
        $event = $this->getEventById($eventId);
        if (!$event) {
            return null;
        }

        // 1. Check schedule conflict with existing timetable classes
        $conflict = $this->hasScheduleConflict($userId, $event['startDatetime'], $event['endDatetime']);
        if ($conflict) {
            return "conflict";
        }

        // 2. Check capacity
        $registrationCount = $this->getRegistrationCount($eventId);
        if ($event['capacity'] !== null && $registrationCount >= $event['capacity']) {
            return "full";
        }

        // 3. Check existing registration
        if ($this->registrationEntity->checkExistingMembership($userId, $eventId)) {
            return "already";
        }

        // 4. Perform registration
        if ($this->registrationEntity->registerForEvent($userId, $eventId)) {
            return true;
        }

        return null;
    }
}

// Handle Form Submission
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