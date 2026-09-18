<?php

require_once "../database/database.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: LoginPage.php");
    exit();
}

class ViewRegistrationController 
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
    }

    /** Get a single registration by id, for the review page */
    public function getRegistration($registrationId)
    {
        $sql = "SELECT id, universityName, applicantName, applicantEmail, applicantContact,
                       institutionType, country, postalCode, status, rejectionReason, createdAt
                FROM UniversityRegistrations
                WHERE id = ?";
 
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$registrationId]);
        $registration = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $registration ?: null;
    }

    /** Approve a pending registration **/
    public function approveRegistration($registrationId, $reviewerId)
    {
        $registration = $this->getRegistration($registrationId);
 
        if (!$registration) {
            return "not_found";
        }
 
        if ($registration['status'] !== 'pending') {
            return "already_reviewed";
        }

        $fullSql = "SELECT * FROM UniversityRegistrations WHERE id = ?";
        $fullStmt = $this->db->prepare($fullSql);
        $fullStmt->execute([$registrationId]);
        $full = $fullStmt->fetch(PDO::FETCH_ASSOC);
 
        try {
            $this->db->beginTransaction();
 
            // Create the university
            $insertUniSql = "INSERT INTO Universities
                                (name, institutionType, email, country, postalCode, status, registrationDate)
                              VALUES (?, ?, ?, ?, ?, 'active', NOW())";
            $insertUniStmt = $this->db->prepare($insertUniSql);
            $insertUniStmt->execute([
                $full['universityName'],
                $full['institutionType'],
                $full['applicantEmail'],
                $full['country'],
                $full['postalCode']
            ]);
 
            $newUniversityId = $this->db->lastInsertId();

            // Create the university admin login account
            $insertUserSql = "INSERT INTO Users
                                (universityId, email, passwordHash, fullName, role, status)
                              VALUES (?, ?, ?, ?, 'university_admin', 'active')";
            $insertUserStmt = $this->db->prepare($insertUserSql);
            $insertUserStmt->execute([
                $newUniversityId,
                $full['applicantEmail'],
                $full['passwordHash'],
                $full['applicantName']
            ]);

             // Mark the registration approved and link it to the new university
            $updateRegSql = "UPDATE UniversityRegistrations
                              SET status = 'approved',
                                  universityId = ?,
                                  reviewedBy = ?,
                                  reviewedAt = NOW()
                              WHERE id = ?";
            $updateRegStmt = $this->db->prepare($updateRegSql);
            $updateRegStmt->execute([$newUniversityId, $reviewerId, $registrationId]);
 
            $this->db->commit();
            return true;
 
        } catch (PDOException $e) {
            $this->db->rollBack();
            return "error";
        }
    }

    /** Reject a pending registration. A reason is required. */
    public function rejectRegistration($registrationId, $reviewerId, $reason)
    {
        $reason = trim($reason);
 
        if (empty($reason)) {
            return "missing_reason";
        }
 
        $registration = $this->getRegistration($registrationId);
 
        if (!$registration) {
            return "not_found";
        }
 
        if ($registration['status'] !== 'pending') {
            return "already_reviewed";
        }
 
        $sql = "UPDATE UniversityRegistrations
                SET status = 'rejected',
                    rejectionReason = ?,
                    reviewedBy = ?,
                    reviewedAt = NOW()
                WHERE id = ?";
 
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$reason, $reviewerId, $registrationId]);
    }
}

// Handle POST actions (Approve / Reject)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && isset($_POST['registration_id'])) {
 
    $controller = new ViewRegistrationController();
    $registrationId = (int) $_POST['registration_id'];
    $reviewerId = $_SESSION['user_id'];
 
    if ($_POST['action'] === 'approve') {
 
        $result = $controller->approveRegistration($registrationId, $reviewerId);
 
        if ($result === true) {
            $_SESSION['registration_review_success'] = "University registration approved.";
        } else {
            $_SESSION['registration_review_error'] = "Unable to approve this registration. Please try again.";
        }
 
    } elseif ($_POST['action'] === 'reject') {
 
        $reason = $_POST['rejection_reason'] ?? '';
        $result = $controller->rejectRegistration($registrationId, $reviewerId, $reason);
 
        if ($result === true) {
            $_SESSION['registration_review_success'] = "University registration rejected.";
        } elseif ($result === "missing_reason") {
            $_SESSION['registration_review_error'] = "Please provide a reason for rejecting this registration.";
            header("Location: ../boundary/viewRegistrationPage.php?id=" . $registrationId);
            exit();
        } else {
            $_SESSION['registration_review_error'] = "Unable to reject this registration. Please try again.";
        }
    }
 
    header("Location: ../boundary/manageUniversityPage.php");
    exit();
}