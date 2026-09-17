<?php

require_once "../database/database.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: LoginPage.php");
    exit();
}

class ManageUniversityController 
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
    }

    /** Get all pending university registration */
    public function getPendingRegistrations()
    {
        $sql = "SELECT id, universityName, applicantName, applicantEmail, applicantContact, status, createdAt
                FROM universityregistrations
                WHERE status = 'pending'
                ORDER BY createdAt ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Get all existing universities */
    Public function getExistingUniversities()
    {
        $sql = "SELECT
                    u.id,
                    u.name,
                    u.status,
                    (
                        SELECT ul.expiryDate
                        FROM universitylicenses ul
                        WHERE ul.universityID = u.id
                        ORDER BY ul.expiryDate DESC
                        LIMIT 1
                    ) AS licenseExpiryDate
                FROM Universities u
                WHERE u.status IN ('active', 'suspended')
                ORDER BY u.id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Suspend a university */
    public function suspendUniversity($universityId)
    {
        $sql = "UPDATE Universities SET status = 'suspended', updatedAt = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$universityId]);
    }

    /** Reactivate a suspended university */
    public function reactivateUniversity($universityId)
    {
        $sql = "UPDATE Universities SET status = 'active', updatedAt = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$universityId]);
    }
}

// Handle POST actions (Suspend / Reactivate)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && isset($_POST['university_id'])) {
 
    $controller = new UniversityManagementController();
    $universityId = (int) $_POST['university_id'];
 
    if ($_POST['action'] === 'suspend') {
        $controller->suspendUniversity($universityId);
    } elseif ($_POST['action'] === 'reactivate') {
        $controller->reactivateUniversity($universityId);
    }
 
    header("Location: ../boundary/ManageUniversityPage.php");
    exit();
}