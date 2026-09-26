<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: loginPage.php");
    exit();
}

require_once "../controller/studentInteractionController.php";

$controller = new StudentInteractionController();
$studentId = $_SESSION['user_id'] ?? null;

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['suspend_group'])) {

    $groupId = (int) ($_POST['groupId'] ?? 0);

    if ($controller->suspendStudyGroup($groupId, $studentId)) {
        $_SESSION['group_success'] = "Study Group Has Been Suspended.";
    } else {
        $_SESSION['group_error'] = "Failed to suspend study group, please try again later";
    }

    header("Location: academicsPage.php?tab=interaction");
    exit();
}

// Fallback
header("Location: academicsPage.php?tab=interaction");
exit();
?>