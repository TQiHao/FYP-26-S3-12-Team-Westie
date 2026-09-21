<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../database/database.php";
require_once "../entity/Facilities.php";

class ViewFacilitiesController
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
    }

    public function getAvailableFacilities($universityId)
    {
        try {
            $sql = "SELECT id, universityId, name, type, description, location,
                           blockFloor, capacity, status, createdAt, updatedAt
                    FROM Facilities
                    WHERE universityId = ?
                      AND status = 'active'
                    ORDER BY type, name ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$universityId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("Facilities fetch error: " . $e->getMessage());
            return false;
        }
    }
}
?>