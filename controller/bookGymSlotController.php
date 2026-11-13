<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../controller/authGuard.php";
requireRole(['student']);

require_once "../database/database.php";

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
            $sql = "SELECT f.id AS facilityId, bf.id AS bookableFacilityId,
                           f.name, f.type, f.location, f.capacity, f.description,
                           bf.slotDuration, bf.openTime, bf.closeTime, bf.status AS bookableStatus
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

    public function getBookingForm($bookableFacilityId)
    {
        try {
            $sql = "SELECT bf.id AS bookableFacilityId, f.id AS facilityId,
                           f.name, f.type, f.location, f.capacity,
                           bf.slotDuration, bf.openTime, bf.closeTime
                    FROM Facilities f
                    INNER JOIN BookableFacilities bf ON f.id = bf.facilityId
                    WHERE bf.id = ?
                      AND bf.isBookable = TRUE
                      AND bf.status = 'available'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$bookableFacilityId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Gym booking form fetch error: " . $e->getMessage());
            return false;
        }
    }

    public function checkAvailability($bookableFacilityId, $bookingDate, $startTime, $endTime)
    {
        try {
            $sql = "SELECT COUNT(*) FROM FacilityBookings
                    WHERE facilityId = ?
                      AND bookingDate = ?
                      AND startTime < ?
                      AND endTime > ?
                      AND status IN ('pending', 'confirmed')";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$bookableFacilityId, $bookingDate, $endTime, $startTime]);
            return $stmt->fetchColumn() == 0;
        } catch (Exception $e) {
            error_log("Gym availability check error: " . $e->getMessage());
            return false;
        }
    }

    public function confirmBooking($bookableFacilityId, $studentId, $bookingDate, $startTime, $endTime, $purpose)
    {
        try {
            $facilityStmt = $this->db->prepare(
                "SELECT bf.slotDuration FROM BookableFacilities bf WHERE bf.id = ? AND bf.isBookable = TRUE LIMIT 1"
            );
            $facilityStmt->execute([$bookableFacilityId]);
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

            if (!$this->checkAvailability($bookableFacilityId, $bookingDate, $startTime, $endTime)) {
                return null;
            }

            $sql = "INSERT INTO FacilityBookings
                (facilityId, userId, bookingDate, startTime, endTime, purpose, status, createdAt, updatedAt)
                VALUES (?, ?, ?, ?, ?, ?, 'confirmed', NOW(), NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$bookableFacilityId, $studentId, $bookingDate, $startTime, $endTime, $purpose]);

            return true;
        } catch (Exception $e) {
            error_log("Booking confirm error: " . $e->getMessage());
            return false;
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['confirm_booking'])) {

    $bookableFacilityId = $_POST['facilityId'];
    $studentId = $_SESSION['user_id'];
    $bookingDate = $_POST['bookingDate'];
    $startTime = $_POST['startTime'];
    $endTime = $_POST['endTime'];
    $purpose = trim($_POST['purpose']);

    $controller = new BookGymSlotController();
    $result = $controller->confirmBooking($bookableFacilityId, $studentId, $bookingDate, $startTime, $endTime, $purpose);

    if ($result === true) {
        $_SESSION['booking_success'] = "Gym slot booked successfully.";
    } elseif ($result === 'invalid_time') {
        $_SESSION['booking_error'] = "Invalid time range. Booking duration must be greater than zero and must not exceed the facility slot duration.";
    } elseif ($result === null) {
        $_SESSION['booking_error'] = "This gym slot is no longer available for the selected time. Please choose another slot.";
    } else {
        $_SESSION['booking_error'] = "Unable to process booking. Please try again later.";
    }

    header("Location: ../boundary/bookGymSlotPage.php");
    exit();
}
?>