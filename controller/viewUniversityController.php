<?php

require_once "../entity/universities.php";
require_once "../entity/systemLog.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../controller/authGuard.php";
requireRole(['system_admin']);

class ViewUniversityController
{
    public function getUniversityDetails($universityId)
    {
        return Universities::getDetails($universityId);
    }

    public function suspendUniversity($universityId, $reason)
    {
        $reason = trim($reason);

        if (empty($reason)) {
            return "missing_reason";
        }

        return Universities::suspend($universityId, $reason);
    }

        public function reactivateUniversity($universityId)
    {
        if (!Universities::hasValidLicense($universityId)) {
            return "license_inactive";
        }

        return Universities::reactivate($universityId);
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
            SystemLog::record('university', 'warning', 'University Suspended',
                'University UNI-' . str_pad($universityId, 4, '0', STR_PAD_LEFT) . ' was suspended. Reason: ' . substr(trim($reason), 0, 200));
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
        } elseif ($result === "license_inactive") {
            $_SESSION['university_action_error'] = "The university's license is inactive or expired. Please resolve the license status before reactivation.";
        } else {
            $_SESSION['university_action_error'] = "Unable to reactivate this university. Please try again.";
        }
    }

    header("Location: ../boundary/viewUniversityPage.php?id=" . $universityId);
    exit();
}