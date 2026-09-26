<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: loginPage.php");
    exit();
}

require_once "../controller/studentInteractionController.php";

$controller = new StudentInteractionController();
$studentId = $_SESSION['user_id'] ?? null;

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['join_group'])) {

    $groupId = (int) ($_POST['groupId'] ?? 0);
    $result = $controller->joinStudyGroup($studentId, $groupId);

    switch ($result) {
        case "success":
            $_SESSION['join_success'] = "Joined Study Group Successfully";
            break;
        case "already":
            $_SESSION['join_error'] = "You are already a member of this study group";
            break;
        case "full":
            $_SESSION['join_error'] = "This study group has reached the maximum number of members";
            break;
        default:
            $_SESSION['join_error'] = "Failed to join study group, please try again later";
            break;
    }

    header("Location: viewStudyGroupPage.php?groupId=" . urlencode($groupId));
    exit();
}

// Fallback: if accessed directly, go back
header("Location: academicsPage.php?tab=interaction");
exit();
?>