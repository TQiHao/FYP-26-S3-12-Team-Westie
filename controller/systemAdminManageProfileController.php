<?php

require_once "../entity/systemAdmins.php";
require_once "../entity/systemLog.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../controller/authGuard.php";
requireRole(['system_admin']);

class SystemAdminManageProfileController
{
    public function getProfile($systemAdminId)
    {
        return SystemAdmins::getProfile($systemAdminId);
    }

    public function updateProfile($systemAdminId, $fullName, $department, $position, $contactNumber)
    {
        $fullName = trim($fullName);

        if (empty($fullName)) {
            return "missing_name";
        }

        return SystemAdmins::updateProfile($systemAdminId, $fullName, $department, $position, $contactNumber)
            ? true
            : "error";
    }
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_profile'])) {

    $systemAdminId = $_SESSION['user_id'];
    $fullName = trim($_POST['fullName'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $contactNumber = trim($_POST['contactNumber'] ?? '');

    $controller = new SystemAdminManageProfileController();
    $result = $controller->updateProfile($systemAdminId, $fullName, $department, $position, $contactNumber);

    if ($result === true) {
        $_SESSION['user_name'] = $fullName;
        $_SESSION['sysadmin_profile_success'] = "Profile Updated Successfully.";
    } elseif ($result === "missing_name") {
        $_SESSION['sysadmin_profile_error'] = "Please enter your full name.";
        header("Location: ../boundary/updateSystemAdminProfilePage.php");
        exit();
    } else {
        $_SESSION['sysadmin_profile_error'] = "Profile update failed. Please try again later.";
    }

    header("Location: ../boundary/systemAdminProfilePage.php");
    exit();
}