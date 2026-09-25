<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: loginPage.php");
    exit();
}

require_once "../controller/teachingController.php";
require_once "../controller/lecturerDashboardController.php";

$fullName = $_SESSION['user_name'];
$staffId = $_SESSION['user_id'] ?? null;
$universityId = $_SESSION['university_id'] ?? 1;

// Initialize controllers
$teachingController = new TeachingController();
$dashboardController = new LecturerDashboardController();

// Fetch dynamic values
$teachingData = $teachingController->loadTeachingView($staffId);
$assignedCount = $teachingData['assignedCount'] ?? 0;

$upcomingEventsCount = $dashboardController->getUpcomingEventsCount($universityId);
$nextClass = $dashboardController->getNextClass($staffId);
$notiSummary = $dashboardController->getNotificationSummary($staffId);
$nextEvent = $dashboardController->getNextUpcomingEvent($universityId);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lecturer Dashboard - UniBee</title>
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
            <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong>
        </div>

        <!-- Header Icons -->
        <div class="dashboard-header-right">
            <a href="notificationPage.php" class="header-icon">
                <img src="../images/notification.png" alt="Notifications">
            </a>

            <!-- Profile + Dropdown -->
            <div class="profile-dropdown">
                <div class="profile-container" onclick="toggleDropdown()">
                    <img src="../images/profilePic.png" alt="Profile" class="profile-icon">
                    <img src="../images/dropdown.png" alt="Menu" class="dropdown-arrow">
                </div>

                <div id="profileMenu" class="dropdown-menu">
                    <a href="manageProfilePage.php">Manage Profile</a>
                    <a href="teachingPage.php">Teaching</a>
                    <a href="viewEventsPage.php">University Campus Events</a>
                    <a href="submitFeedbackPage.php">Submit Feedback</a>
                    <a href="../controller/logOutController.php">Log Out</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Lecturer Dashboard -->
    <main class="dashboard">

        <!-- Dynamic Dashboard Summary -->
        <section class="dashboard-summary">

            <!-- Next Class -->
            <div class="summary-item">
                <div class="summary-title">
                    <img src="../images/nextclass.png" alt="Next class" class="summary-icon">
                    <span>Next class</span>
                </div>

                <?php if ($nextClass): ?>
                    <h2><?php echo htmlspecialchars($nextClass['title']) . ', ' . date('g:iA', strtotime($nextClass['startTime'])); ?>
                    </h2>
                    <p><?php echo htmlspecialchars($nextClass['location']); ?></p>
                <?php else: ?>
                    <h2>No upcoming classes</h2>
                    <p>Schedule is clear</p>
                <?php endif; ?>
            </div>

            <!-- Notifications -->
            <div class="summary-item">
                <div class="summary-title">
                    <img src="../images/noti.png" alt="Notifications" class="summary-icon">
                    <span>Notifications</span>
                </div>

                <h2><?php echo $notiSummary['unreadCount']; ?> unread</h2>
                <p><?php echo htmlspecialchars($notiSummary['latestMessage']); ?></p>
            </div>

            <!-- Upcoming Event -->
            <div class="summary-item">
                <div class="summary-title">
                    <img src="../images/upcomingevent.png" alt="Upcoming event" class="summary-icon">
                    <span>Upcoming event</span>
                </div>

                <?php if ($nextEvent): ?>
                    <h2><?php echo htmlspecialchars($nextEvent['title']) . ', ' . date('D j M', strtotime($nextEvent['startDatetime'])); ?>
                    </h2>
                    <p><?php echo htmlspecialchars($nextEvent['location']); ?></p>
                <?php else: ?>
                    <h2>No upcoming events</h2>
                    <p>Check back later</p>
                <?php endif; ?>
            </div>

        </section>

        <!-- Quick Actions -->
        <section class="dashboard-section">
            <h3 class="section-label">Quick actions</h3>
            <div class="quick-actions">
                <a href="notificationPage.php" class="quick-button">Event Reminders</a>
                <a href="teachingPage.php?tab=timetable" class="quick-button">View Timetable</a>
            </div>
        </section>

        <!-- Explore -->
        <section class="dashboard-section">
            <h3 class="section-label">Explore</h3>
            <div class="dashboard-grid">

                <!-- Teaching -->
                <a href="teachingPage.php" class="dashboard-card">
                    <img src="../images/academic.png" alt="Teaching" class="card-icon">
                    <h2>Teaching</h2>
                    <p><?php echo $assignedCount . ' Course' . ($assignedCount === 1 ? '' : 's') . ' Assigned'; ?></p>
                </a>

                <!-- Campus Events -->
                <a href="viewEventsPage.php" class="dashboard-card">
                    <img src="../images/campusevent.png" alt="Campus Events" class="card-icon">
                    <h2>Campus Events</h2>
                    <p><?php echo $upcomingEventsCount . ' Upcoming' . ($upcomingEventsCount === 1 ? '' : 's'); ?></p>
                </a>

                <!-- Submit Feedback -->
                <a href="submitFeedbackPage.php" class="dashboard-card">
                    <img src="../images/feedback.png" alt="Submit Feedback" class="card-icon">
                    <h2>Submit Feedback</h2>
                    <p>Share your thoughts</p>
                </a>

            </div>
        </section>

    </main>

    <!-- Footer -->
    <footer>
        <div class="footer-bottom-bar">
            &copy; 2026 UniBee. All rights reserved.
        </div>
    </footer>

</body>

</html>