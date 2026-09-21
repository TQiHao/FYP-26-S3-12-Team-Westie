<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../database/database.php";
require_once "../entity/FacilityBooking.php";

class CancelMyBookingController
{
    private $db;
    private $bookingEntity;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
        $this->bookingEntity = new FacilityBooking(null, null, null, null, null, null, null, null, null, null, null, $this->db);
    }

    /**
     * Get all bookings for a student
     * @param int $studentId
     * @return array|false
     */
    public function getMyBookings($studentId)
    {
        return $this->bookingEntity->getBookingsByStudentId($studentId);
    }

    /**
     * Cancel a booking
     * @param int $bookingId
     * @param int $studentId
     * @return bool|null
     */
    public function cancelBooking($bookingId, $studentId)
    {
        return $this->bookingEntity->cancelBooking($bookingId, $studentId);
    }
}

// ===== HANDLE FORM SUBMISSION =====
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['cancel_booking'])) {

    $bookingId = $_POST['bookingId'];
    $studentId = $_SESSION['user_id'];

    $controller = new CancelMyBookingController();
    $result = $controller->cancelBooking($bookingId, $studentId);

    if ($result === true) {
        $_SESSION['cancel_success'] = "Booking cancelled successfully.";
    } elseif ($result === null) {
        $_SESSION['cancel_error'] = "Unable to cancel booking. Please try again later.";
    } else {
        $_SESSION['cancel_error'] = "Unable to cancel booking. Please try again later.";
    }

    header("Location: ../boundary/cancelMyBookingPage.php");
    exit();
}
?>