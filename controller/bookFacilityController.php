<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../database/database.php";
require_once "../entity/BookableFacilities.php";
require_once "../entity/FacilityBooking.php";

class BookFacilityController
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
    }

    /**
     * Get bookable facilities by type/name keyword
     * @param int $universityId
     * @param string $keyword
     * @return array|false
     */
    public function getBookableFacilitiesByName($universityId, $keyword)
    {
        try {
            $sql = "SELECT f.id AS facilityId, f.name, f.type, f.location, f.capacity,
                           f.description, bf.id AS bookableFacilityId, bf.slotDuration,
                           bf.openTime, bf.closeTime, bf.status AS bookableStatus
                    FROM Facilities f
                    INNER JOIN BookableFacilities bf ON f.id = bf.facilityId
                    WHERE f.universityId = ?
                      AND f.status = 'active'
                      AND bf.isBookable = TRUE
                      AND bf.status = 'available'
                      AND f.name LIKE ?
                    ORDER BY f.name ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$universityId, '%' . $keyword . '%']);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("Bookable facilities fetch error: " . $e->getMessage());
            return false;  // Alt flow
        }
    }

    /**
     * Get booking form details for a facility
     * @param int $facilityId
     * @return array|false
     */
    public function getBookingForm($facilityId)
    {
        try {
            $sql = "SELECT f.id AS facilityId, f.name, f.type, f.location, f.capacity,
                           bf.slotDuration, bf.openTime, bf.closeTime
                    FROM Facilities f
                    INNER JOIN BookableFacilities bf ON f.id = bf.facilityId
                    WHERE f.id = ?
                      AND bf.isBookable = TRUE
                      AND bf.status = 'available'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$facilityId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("Booking form fetch error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check availability of a facility for a date/time
     * @param int $facilityId
     * @param string $bookingDate
     * @param string $startTime
     * @return bool
     */
    public function checkAvailability($facilityId, $bookingDate, $startTime)
    {
        try {
            $sql = "SELECT COUNT(*) FROM FacilityBookings
                    WHERE facilityId = ?
                      AND bookingDate = ?
                      AND startTime = ?
                      AND status IN ('pending', 'confirmed')";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$facilityId, $bookingDate, $startTime]);
            return $stmt->fetchColumn() == 0;

        } catch (Exception $e) {
            error_log("Availability check error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Confirm a booking
     * @param int $facilityId
     * @param int $studentId
     * @param string $bookingDate
     * @param string $startTime
     * @param string $endTime
     * @param string $purpose
     * @return bool
     */
    public function confirmBooking($facilityId, $studentId, $bookingDate, $startTime, $endTime, $purpose)
    {
        try {
            // Check availability first
            if (!$this->checkAvailability($facilityId, $bookingDate, $startTime)) {
                return null;  // Alt flow: not available
            }

            $sql = "INSERT INTO FacilityBookings
                    (facilityId, userId, bookingDate, startTime, endTime, purpose, status, createdAt, updatedAt)
                    VALUES (?, ?, ?, ?, ?, ?, 'confirmed', NOW(), NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$facilityId, $studentId, $bookingDate, $startTime, $endTime, $purpose]);

            return true;

        } catch (Exception $e) {
            error_log("Booking confirm error: " . $e->getMessage());
            return false;  // Alt flow: error
        }
    }
}

// ===== HANDLE FORM SUBMISSION =====
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['confirm_booking'])) {

    $facilityId = $_POST['facilityId'];
    $studentId = $_SESSION['user_id'];
    $bookingDate = $_POST['bookingDate'];
    $startTime = $_POST['startTime'];
    $endTime = $_POST['endTime'];
    $purpose = trim($_POST['purpose']);

    $controller = new BookFacilityController();
    $result = $controller->confirmBooking($facilityId, $studentId, $bookingDate, $startTime, $endTime, $purpose);

    if ($result === true) {
        $_SESSION['booking_success'] = "Facility booked successfully.";
    } elseif ($result === null) {
        $_SESSION['booking_error'] = "This facility is no longer available for the selected time. Please choose another slot.";
    } else {
        $_SESSION['booking_error'] = "Unable to process booking. Please try again later.";
    }

    header("Location: ../boundary/BookFacilityPage.php");
    exit();
}
?>