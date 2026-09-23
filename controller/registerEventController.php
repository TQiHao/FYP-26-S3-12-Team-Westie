<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../database/database.php";
require_once "../entity/Event.php";
require_once "../entity/EventRegistration.php";

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

    /**
     * Get event details by ID
     * @param int $eventId
     * @return array|false
     */
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

    /**
     * Get the current registration count for an event
     * @param int $eventId
     * @return int
     */
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
     * Register a student for an event
     * @param int $eventId
     * @param int $studentId
     * @return bool|null|string
     *   true = success
     *   "full" = event is full
     *   "already" = already registered
     *   null = unable to register
     */
    public function registerForEvent($eventId, $studentId)
    {
        // 1. Get event details
        $event = $this->getEventById($eventId);

        if (!$event) {
            return null;  // <-- Alt flow: unable to register
        }

        // 2. Check if event is full
        $registrationCount = $this->getRegistrationCount($eventId);
        if ($event['capacity'] !== null && $registrationCount >= $event['capacity']) {
            return "full";  // <-- Alt flow: event full
        }

        // 3. Check if already registered
        if ($this->registrationEntity->checkExistingMembership($studentId, $eventId)) {
            return "already";  // <-- Alt flow: already registered
        }

        // 4. Register the student
        if ($this->registrationEntity->registerForEvent($studentId, $eventId)) {
            return true;  // <-- Success
        }

        return null;  // <-- Alt flow: unable to register
    }
}

// ===== HANDLE FORM SUBMISSION =====
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['register_event'])) {

    $eventId = $_POST['eventId'];
    $studentId = $_SESSION['user_id'];

    $controller = new RegisterEventController();
    $result = $controller->registerForEvent($eventId, $studentId);

    if ($result === true) {
        $_SESSION['register_success'] = "Successfully Registered For Event";
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