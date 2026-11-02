<?php

require_once "../entity/universityRegistrations.php";
require_once "../entity/systemLog.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../controller/authGuard.php";
requireRole(['system_admin']);

class ViewRegistrationController
{
    /** Get a single registration by id, for the review page */
    public function getRegistration($registrationId)
    {
        return UniversityRegistrations::getById($registrationId);
    }

    //The verification documents uploaded with a registration
    public function getDocuments($registrationId)
    {
        return UniversityRegistrations::getDocuments($registrationId);
    }

    public function streamDocument($documentId)
    {
        $doc = UniversityRegistrations::getDocumentById($documentId);

        $folder = realpath(dirname(__DIR__) . '/uploads/registration_documents');
        $file   = $doc ? realpath(dirname(__DIR__) . '/' . $doc['filePath']) : false;

        // The resolved file must really be inside the documents folder (blocks ../ tricks)
        if (!$doc || !$folder || !$file || strpos($file, $folder . DIRECTORY_SEPARATOR) !== 0 || !is_file($file)) {
            http_response_code(404);
            echo "Document not found.";
            exit();
        }

        $types = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png'];
        $ext   = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        SystemLog::record('university', 'info', 'Registration Document Viewed',
            'Document "' . $doc['fileName'] . '" of registration REG-' . str_pad($doc['registrationId'], 4, '0', STR_PAD_LEFT) . ' was viewed.');

        header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
        header('Content-Length: ' . filesize($file));
        header('Content-Disposition: inline; filename="' . preg_replace('/[^\w.\-]/', '_', $doc['fileName']) . '"');
        header('X-Content-Type-Options: nosniff');
        readfile($file);
        exit();
    }

    //Approve a pending registration
    public function approveRegistration($registrationId, $reviewerId)
    {
        $registration = UniversityRegistrations::getById($registrationId);

        if (!$registration) {
            return "not_found";
        }

        if ($registration['status'] !== 'pending') {
            return "already_reviewed";
        }

        return UniversityRegistrations::approve($registrationId, $reviewerId) ? true : "error";
    }

    // Reject a pending registration. A reason is required.
    public function rejectRegistration($registrationId, $reviewerId, $reason)
    {
        $reason = trim($reason);

        if (empty($reason)) {
            return "missing_reason";
        }

        $registration = UniversityRegistrations::getById($registrationId);

        if (!$registration) {
            return "not_found";
        }

        if ($registration['status'] !== 'pending') {
            return "already_reviewed";
        }

        return UniversityRegistrations::reject($registrationId, $reviewerId, $reason);
    }
}

// Handle GET: open a verification document
if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET['document'])) {
    $controller = new ViewRegistrationController();
    $controller->streamDocument((int) $_GET['document']);
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
            SystemLog::record('university', 'info', 'Registration Approved',
                'University registration REG-' . str_pad($registrationId, 4, '0', STR_PAD_LEFT) . ' was approved.');
        } else {
            $_SESSION['registration_review_error'] = "Unable to approve this registration. Please try again.";
        }

    } elseif ($_POST['action'] === 'reject') {

        $reason = $_POST['rejection_reason'] ?? '';
        $result = $controller->rejectRegistration($registrationId, $reviewerId, $reason);

        if ($result === true) {
            $_SESSION['registration_review_success'] = "University registration rejected.";
            SystemLog::record('university', 'info', 'Registration Rejected',
                'University registration REG-' . str_pad($registrationId, 4, '0', STR_PAD_LEFT) . ' was rejected.');
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