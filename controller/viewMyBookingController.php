<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../database/database.php";
require_once "../entity/FacilityBooking.php";
require_once "../entity/BookableFacilities.php";

class ViewMyBookingController
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
    }

    /**
     * Get all bookings for a student, joined with facility details
     * @param int $studentId
     * @return array|false
     */
    public function getMyBookings($studentId)
    {
        try {
            $sql = "SELECT fb.id AS bookingId,
                           fb.facilityId,
                           fb.userId,
                           fb.bookingDate,
                           fb.startTime,
                           fb.endTime,
                           fb.purpose,
                           fb.status,
                           fb.cancelledAt,
                           fb.createdAt,
                           fb.updatedAt,
                           f.name AS facilityName,
                           f.type AS facilityType,
                           f.location AS facilityLocation,
                           f.capacity AS facilityCapacity
                    FROM FacilityBookings fb
                    INNER JOIN Facilities f ON fb.facilityId = f.id
                    WHERE fb.userId = ?
                    ORDER BY fb.bookingDate DESC, fb.startTime DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$studentId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("View my bookings error: " . $e->getMessage());
            return false;  // <-- Alt flow: return Error
        }
    }
}
?>