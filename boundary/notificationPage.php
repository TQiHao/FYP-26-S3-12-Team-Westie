<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: loginPage.php");
    exit();
}

require_once "../controller/NotificationController.php";

$controller = new NotificationController();
$userId = $_SESSION['user_id'] ?? null;

// Determine user role (defaults to 'student' if not set)
$userRole = strtolower($_SESSION['user_role'] ?? $_SESSION['role'] ?? 'student');
$isLecturer = in_array($userRole, ['lecturer', 'staff', 'teacher']);

// Dynamic back button destination
$backDashboard = $isLecturer ? 'lecturerDashboardPage.php' : 'studentDashboardPage.php';

// Fetch user-specific notifications
$notifications = $controller->getNotification($userId);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - UniBee</title>
    <link rel="stylesheet" href="../style.css">
</head>

<body>
    <script src="../script.js"></script>

    <!-- Header -->
    <header class="header">
        <div class="logo-container">
            <img src="../images/uniBeeLogo.png" alt="UniBee Logo">
        </div>

        <div class="welcome-message">
            <span>Welcome back,</span>
            <strong><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></strong>
        </div>

        <div class="dashboard-header-right">
            <a href="NotificationPage.php" class="header-icon">
                <img src="../images/notification.png" alt="Notifications">
            </a>

            <div class="profile-dropdown">
                <div class="profile-container" onclick="toggleDropdown()">
                    <img src="../images/profilePic.png" alt="Profile" class="profile-icon">
                    <img src="../images/dropdown.png" alt="Menu" class="dropdown-arrow">
                </div>

                <div id="profileMenu" class="dropdown-menu">
                    <a href="ManageProfilePage.php">Manage Profile</a>
                    <?php if ($isLecturer): ?>
                        <!-- Lecturer Menu Items -->
                        <a href="TeachingPage.php">Teaching</a>
                        <a href="CampusEventsPage.php">University Campus Events</a>
                        <a href="SubmitFeedbackPage.php">Submit Feedback</a>
                    <?php else: ?>
                        <!-- Student Menu Items -->
                        <a href="AIChatbotPage.php">AI Chatbot</a>
                        <a href="AcademicsPage.php">Academics</a>
                        <a href="FacilitiesBookingPage.php">Facility Booking</a>
                        <a href="CampusEventsPage.php">University Campus Event</a>
                    <?php endif; ?>
                    <a href="../controller/logOutController.php">Log Out</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Notifications Section -->
    <main class="dashboard">

        <div class="profile-header">
            <!-- Dynamic Back Link based on role -->
            <a href="<?php echo $backDashboard; ?>" class="btn-back">&#8592; Back</a>
            <h2 class="section-label">Notifications</h2>
            <div></div> <!-- Spacer for centering title -->
        </div>

        <?php if ($notifications === false): ?>
            <p class="error-message">Unable to load notifications. Please try again later.</p>

        <?php elseif (empty($notifications)): ?>
            <p class="no-notifications">You have no new notifications.</p>

        <?php else: ?>
            <div class="notifications-list">

                <?php foreach ($notifications as $n): ?>

                    <?php
                    // Choose icon based on notification type
                    $icon = '../images/notification.png';
                    switch ($n['type'] ?? '') {
                        case 'event':
                            $icon = '../images/upcomingEvent.png';
                            break;
                        case 'booking':
                            $icon = '../images/facilityBooking.png';
                            break;
                        case 'exam':
                        case 'class':
                            $icon = '../images/academic.png';
                            break;
                        case 'study_grp':
                            $icon = '../images/studyGrp.png';
                            break;
                        case 'system':
                        default:
                            $icon = '../images/notification.png';
                            break;
                    }
                    ?>

                    <div class="notification-item">
                        <img src="<?php echo $icon; ?>" alt="Icon" class="notification-icon">

                        <div class="notification-content">
                            <p class="notification-title">
                                <?php echo htmlspecialchars($n['title'] ?? ''); ?>
                            </p>
                            <p class="notification-time">
                                <?php echo htmlspecialchars($n['createdAt'] ?? ''); ?>
                            </p>
                        </div>
                    </div>

                <?php endforeach; ?>

            </div>
        <?php endif; ?>

    </main>

    <!-- Footer -->
    <footer>
        <div class="footer-bottom-bar">
            &copy; 2026 UniBee. All rights reserved.
        </div>
    </footer>
</body>

</html>