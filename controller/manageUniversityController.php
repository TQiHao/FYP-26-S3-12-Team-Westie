<?php

require_once "../database/database.php";
require_once "../entity/systemLog.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (
    !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true ||
    ($_SESSION['user_role'] ?? '') !== 'system_admin'
) {
    header("Location: ../boundary/loginPage.php");
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

    public function suspendUniversity($universityId, $reason)
    {
        $reason = trim($reason);

        if (empty($reason)) {
            return "missing_reason";
        }

        $sql = "UPDATE Universities SET status = 'suspended', suspensionReason = ?, updatedAt = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$reason, $universityId]);
    }

    public function reactivateUniversity($universityId)
    {
        $sql = "UPDATE Universities SET status = 'active', suspensionReason = NULL, updatedAt = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$universityId]);
    }
}

// Handle POST actions (Suspend / Reactivate)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && isset($_POST['university_id'])) {

    $controller = new ManageUniversityController();
    $universityId = (int) $_POST['university_id'];

    if ($_POST['action'] === 'suspend') {

        $reason = $_POST['reason'] ?? '';
        $result = $controller->suspendUniversity($universityId, $reason);

        if ($result === true) {
            $_SESSION['university_action_success'] = "University suspended successfully.";
            SystemLog::record('university', 'warning', 'University Suspended',
                'University UNI-' . str_pad($universityId, 4, '0', STR_PAD_LEFT) . ' was suspended. Reason: ' . substr(trim($reason), 0, 200));
        } elseif ($result === "missing_reason") {
        } elseif ($result === "missing_reason") {
            $_SESSION['university_action_error'] = "Please provide a reason for suspending this university.";
        } else {
            $_SESSION['university_action_error'] = "Unable to suspend this university. Please try again.";
        }

    } elseif ($_POST['action'] === 'reactivate') {

        $result = $controller->reactivateUniversity($universityId);

        if ($result === true) {
            $_SESSION['university_action_success'] = "University reactivated successfully.";
            SystemLog::record('university', 'info', 'University Reactivated',
                'University UNI-' . str_pad($universityId, 4, '0', STR_PAD_LEFT) . ' was reactivated.');
        } else {
            $_SESSION['university_action_error'] = "Unable to reactivate this university. Please try again.";
        }
    }

    header("Location: ../boundary/manageUniversityPage.php");
    exit();
}