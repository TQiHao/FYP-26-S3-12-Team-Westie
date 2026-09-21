<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../database/database.php";
require_once "../entity/Facilities.php";

class ViewGymSlotController
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
    }

    /**
     * Get gym slots for a university
     * @param int $universityId
     * @return array|false
     */
    public function getAvailableGymSlots($universityId)
    {
        try {
            $sql = "SELECT id, universityId, name, type, description, location,
                           blockFloor, capacity, status, createdAt, updatedAt
                    FROM Facilities
                    WHERE universityId = ?
                      AND type = 'gym'
                      AND status = 'active'
                    ORDER BY name ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$universityId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("Gym slot fetch error: " . $e->getMessage());
            return false;  // <-- Alt flow: return Null
        }
    }
}
?>