<?php

class FacilityBooking
{
    private $id;
    private $facilityId;
    private $userId;
    private $bookingDate;
    private $startTime;
    private $endTime;
    private $purpose;
    private $status;
    private $cancelledAt;
    private $createdAt;
    private $updatedAt;

    private $db;

    public function __construct(
        $id = null,
        $facilityId = null,
        $userId = null,
        $bookingDate = null,
        $startTime = null,
        $endTime = null,
        $purpose = null,
        $status = null,
        $cancelledAt = null,
        $createdAt = null,
        $updatedAt = null,
        $db = null
    ) {
        $this->id = $id;
        $this->facilityId = $facilityId;
        $this->userId = $userId;
        $this->bookingDate = $bookingDate;
        $this->startTime = $startTime;
        $this->endTime = $endTime;
        $this->purpose = $purpose;
        $this->status = $status;
        $this->cancelledAt = $cancelledAt;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->db = $db;
    }

    // Getters (existing)
    public function getId() { return $this->id; }
    public function getFacilityId() { return $this->facilityId; }
    public function getUserId() { return $this->userId; }
    public function getBookingDate() { return $this->bookingDate; }
    public function getStartTime() { return $this->startTime; }
    public function getEndTime() { return $this->endTime; }
    public function getPurpose() { return $this->purpose; }
    public function getStatus() { return $this->status; }
    public function getCancelledAt() { return $this->cancelledAt; }
    public function getCreatedAt() { return $this->createdAt; }
    public function getUpdatedAt() { return $this->updatedAt; }

    // ===== NEW METHODS =====

    /**
     * Get all bookings for a student (joined with facility details)
     * @param int $studentId
     * @return array|false
     */
    public function getBookingsByStudentId($studentId)
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
                           f.location AS facilityLocation
                    FROM FacilityBookings fb
                    INNER JOIN Facilities f ON fb.facilityId = f.id
                    WHERE fb.userId = ?
                    ORDER BY fb.bookingDate DESC, fb.startTime DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$studentId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("getBookingsByStudentId error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cancel a booking (only if it belongs to the student and is cancellable)
     * @param int $bookingId
     * @param int $studentId
     * @return bool|null (true = success, null = not found / not allowed, false = error)
     */
    public function cancelBooking($bookingId, $studentId)
    {
        try {
            // Verify booking belongs to this student and is cancellable
            $sql = "SELECT status FROM FacilityBookings
                    WHERE id = ? AND userId = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$bookingId, $studentId]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$booking) {
                return null;  // Not found
            }

            if (in_array($booking['status'], ['cancelled', 'completed'])) {
                return null;  // Already cancelled or completed
            }

            // Update status
            $sql = "UPDATE FacilityBookings
                    SET status = 'cancelled', cancelledAt = NOW(), updatedAt = NOW()
                    WHERE id = ? AND userId = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$bookingId, $studentId]);

            return true;

        } catch (Exception $e) {
            error_log("cancelBooking error: " . $e->getMessage());
            return false;
        }
    }
}
?>