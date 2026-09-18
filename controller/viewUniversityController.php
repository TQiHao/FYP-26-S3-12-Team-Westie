<?php

require_once "../database/database.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: LoginPage.php");
    exit();
}

class ViewUniversityController
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
    }

    public function getUniversityDetails($universityId)
    {
        $sql = "SELECT
                    u.id,
                    u.name,
                    u.registrationDate,
                    u.status,
                    (
                        SELECT us.fullName
                        FROM Users us
                        WHERE us.universityId = u.id AND us.role = 'university_admin'
                        LIMIT 1
                    ) AS repName,
                    (
                        SELECT us.email
                        FROM Users us
                        WHERE us.universityId = u.id AND us.role = 'university_admin'
                        LIMIT 1
                    ) AS repEmail,
                    (
                        SELECT l.name
                        FROM UniversityLicenses ul
                        JOIN Licenses l ON l.id = ul.licenseId
                        WHERE ul.universityId = u.id
                        ORDER BY ul.expiryDate DESC
                        LIMIT 1
                    ) AS licensePlan,
                    (
                        SELECT ul.startDate
                        FROM UniversityLicenses ul
                        WHERE ul.universityId = u.id
                        ORDER BY ul.expiryDate DESC
                        LIMIT 1
                    ) AS licenseStartDate,
                    (
                        SELECT ul.expiryDate
                        FROM UniversityLicenses ul
                        WHERE ul.universityId = u.id
                        ORDER BY ul.expiryDate DESC
                        LIMIT 1
                    ) AS licenseExpiryDate
                FROM Universities u
                WHERE u.id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$universityId]);
        $details = $stmt->fetch(PDO::FETCH_ASSOC);

        return $details ?: null;
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

// Handle POST actions (Suspend / Reactivate) from the View University page
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && isset($_POST['university_id'])) {

    $controller = new ViewUniversityController();
    $universityId = (int) $_POST['university_id'];

    if ($_POST['action'] === 'suspend') {

        $reason = $_POST['reason'] ?? '';
        $result = $controller->suspendUniversity($universityId, $reason);

        if ($result === true) {
            $_SESSION['university_action_success'] = "University suspended successfully.";
        } elseif ($result === "missing_reason") {
            $_SESSION['university_action_error'] = "Please provide a reason for suspending this university.";
        } else {
            $_SESSION['university_action_error'] = "Unable to suspend this university. Please try again.";
        }

    } elseif ($_POST['action'] === 'reactivate') {

        $result = $controller->reactivateUniversity($universityId);

        if ($result === true) {
            $_SESSION['university_action_success'] = "University reactivated successfully.";
        } else {
            $_SESSION['university_action_error'] = "Unable to reactivate this university. Please try again.";
        }
    }

    header("Location: ../boundary/viewUniversityPage.php?id=" . $universityId);
    exit();
}