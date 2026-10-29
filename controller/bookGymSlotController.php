<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../database/database.php";
require_once "../entity/bookableFacilities.php";
require_once "../entity/facilityBooking.php";

class BookGymSlotController
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
    }

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
            error_log("Bookable gym slots fetch error: " . $e->getMessage());
            return false;
        }
    }

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
            error_log("Gym booking form fetch error: " . $e->getMessage());
            return false;
        }
    }

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
            error_log("Gym availability check error: " . $e->getMessage());
            return false;
        }
    }

    public function confirmBooking($facilityId, $studentId, $bookingDate, $startTime, $endTime, $purpose)
    {
        try {
            $facilityStmt = $this->db->prepare(
                "SELECT bf.slotDuration FROM BookableFacilities bf WHERE bf.facilityId = ? AND bf.isBookable = TRUE LIMIT 1"
            );
            $facilityStmt->execute([$facilityId]);
            $facility = $facilityStmt->fetch(PDO::FETCH_ASSOC);

            if (!$facility) {
                return false;
            }

            $slotDuration = (int) $facility['slotDuration'];
            $startParts = explode(':', $startTime);
            $endParts = explode(':', $endTime);

            if (count($startParts) < 2 || count($endParts) < 2) {
                return 'invalid_time';
            }

            $startMinutes = (int) $startParts[0] * 60 + (int) $startParts[1];
            $endMinutes = (int) $endParts[0] * 60 + (int) $endParts[1];
            $duration = $endMinutes - $startMinutes;

            if ($duration <= 0 || $duration > $slotDuration) {
                return 'invalid_time';
            }

            if (!$this->checkAvailability($facilityId, $bookingDate, $startTime)) {
                return null;
            }

            $sql = "INSERT INTO FacilityBookings
                (facilityId, userId, bookingDate, startTime, endTime, purpose, status, createdAt, updatedAt)
                VALUES (?, ?, ?, ?, ?, ?, 'confirmed', NOW(), NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$facilityId, $studentId, $bookingDate, $startTime, $endTime, $purpose]);

            return true;

        } catch (Exception $e) {
            error_log("Booking confirm error: " . $e->getMessage());
            return false;
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

    $controller = new BookGymSlotController();
    $result = $controller->confirmBooking($facilityId, $studentId, $bookingDate, $startTime, $endTime, $purpose);

    if ($result === true) {
        $_SESSION['booking_success'] = "Gym slot booked successfully.";
    } elseif ($result === 'invalid_time') {
        $_SESSION['booking_error'] = "Invalid time range. Booking duration must be greater than zero and must not exceed the facility slot duration.";
    } elseif ($result === null) {
        $_SESSION['booking_error'] = "This gym room is no longer available for the selected time. Please choose another slot.";
    } else {
        $_SESSION['booking_error'] = "Unable to process booking. Please try again later.";
    }

    header("Location: ../boundary/bookGymSlotPage.php");
    exit();
}
?>