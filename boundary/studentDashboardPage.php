<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    $_SESSION['login_error'] = "Session expired. Please log in again.";
    header("Location: LoginPage.php");
    exit();
}

require_once "../controller/studentDashboardController.php";

$dashboardController = new StudentDashboardController();

$studentId = $_SESSION['user_id'] ?? null;
$universityId = $_SESSION['university_id'] ?? null;

$nextClass = $dashboardController->getNextClass($studentId);
$notificationSum = $dashboardController->getNotificationSummary($studentId);
$nextEvent = $dashboardController->getNextEvent($studentId, $universityId);

$coursesEnrolled = $dashboardController->getEnrolledCoursesCount($studentId);
$activeBookings = $dashboardController->getActiveBookingsCount($studentId);
$groupsJoined = $dashboardController->getGroupsJoinedCount($studentId);
$upcomingEventsCnt = $dashboardController->getUpcomingEventsCount($universityId);

$fullName = $_SESSION['user_name'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Student Dashboard - UniBee</title>

    <link rel="stylesheet" href="../style.css">
</head>

<body>
    <script src="../script.js"></script>

    <!-- Header -->
    <header class="header">

        <div class="logo-container">
            <a href="studentDashboardPage.php">
                <img src="../images/uniBeeLogo.png" alt="UniBee Logo">
            </a>
        </div>

        <div class="welcome-message">
            <span>Welcome back,</span>
            <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong>
        </div>

        <!-- Header Icons -->
        <div class="dashboard-header-right">

            <a href="NotificationPage.php" class="header-icon">
                <img src="../images/notification.png" alt="Notifications">
            </a>

            <!-- Profile + Dropdown -->
            <div class="profile-dropdown">

                <div class="profile-container" onclick="toggleDropdown()">

                    <img src="../images/profilePic.png" alt="Profile" class="profile-icon">

                    <img src="../images/dropdown.png" alt="Menu" class="dropdown-arrow">

                </div>

                <div id="profileMenu" class="dropdown-menu">

                    <a href="ManageProfilePage.php">Manage Profile</a>
                    <a href="AIChatbotPage.php">AI Chatbot</a>
                    <a href="academicsPage.php">Academics</a>
                    <a href="viewFacilitiesPage.php">Facility Booking</a>
                    <a href="viewEventsPage.php">University Campus Event</a>
                    <a href="../controller/logoutController.php">Log Out</a>

                </div>

            </div>

        </div>

    </header>


    <!-- Student Dashboard -->
    <main class="dashboard">

        <!-- Dashboard Summary -->
        <section class="dashboard-summary">

            <!-- Next Class (Dynamic) -->
            <div class="summary-item">
                <div class="summary-title">
                    <img src="../images/nextClass.png" alt="Next class" class="summary-icon">
                    <span>Next class</span>
                </div>

                <?php if ($nextClass === false): ?>
                    <h2>Unable to load</h2>
                    <p>Please try again later</p>
                <?php elseif ($nextClass === null): ?>
                    <h2>No upcoming class</h2>
                    <p>You're free!</p>
                <?php else: ?>
                    <h2>
                        <?php echo htmlspecialchars($nextClass['title']); ?>,
                        <?php echo htmlspecialchars(date('D j M, g:ia', strtotime($nextClass['nextDate'] . ' ' . $nextClass['startTime']))); ?>
                    </h2>
                    <p><?php echo htmlspecialchars($nextClass['location']); ?></p>
                <?php endif; ?>
            </div>

            <!-- Notifications (Dynamic) -->
            <div class="summary-item">
                <div class="summary-title">
                    <img src="../images/noti.png" alt="Notifications" class="summary-icon">
                    <span>Notifications</span>
                </div>

                <?php if ($notificationSum === false): ?>
                    <h2>Unable to load</h2>
                    <p>Please try again later</p>
                <?php else: ?>
                    <h2><?php echo (int) $notificationSum['unreadCount']; ?> unread</h2>
                    <?php if (!empty($notificationSum['latest'])): ?>
                        <p><?php echo htmlspecialchars($notificationSum['latest']['title']); ?></p>
                    <?php else: ?>
                        <p>No notifications yet</p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Upcoming Event (Dynamic) -->
            <div class="summary-item">
                <div class="summary-title">
                    <img src="../images/upcomingEvent.png" alt="Upcoming event" class="summary-icon">
                    <span>Upcoming event</span>
                </div>

                <?php if ($nextEvent === false): ?>
                    <h2>Unable to load</h2>
                    <p>Please try again later</p>
                <?php elseif ($nextEvent === null): ?>
                    <h2>No upcoming event</h2>
                    <p>Check back later</p>
                <?php else: ?>
                    <h2>
                        <?php echo htmlspecialchars($nextEvent['title']); ?>,
                        <?php echo htmlspecialchars(date('D d M', strtotime($nextEvent['startDatetime']))); ?>
                    </h2>
                    <p><?php echo htmlspecialchars($nextEvent['location']); ?></p>
                <?php endif; ?>
            </div>

        </section>


        <!-- Quick Actions -->
        <section class="dashboard-section">

            <h3 class="section-label">Quick actions</h3>

            <div class="quick-actions">

                <a href="academicsPage.php?tab=exams" class="quick-button">
                    View Exam Schedule
                </a>

                <a href="bookFacilityPage.php" class="quick-button">
                    Book Study Room
                </a>

                <a href="academicsPage.php?tab=timetable" class="quick-button">
                    View Timetable
                </a>

            </div>

        </section>


        <!-- Explore -->
        <section class="dashboard-section">

            <h3 class="section-label">Explore</h3>

            <div class="dashboard-grid">

                <!-- Academics -->
                <a href="academicsPage.php" class="dashboard-card">
                    <img src="../images/academic.png" alt="Academics" class="card-icon">
                    <h2>Academics</h2>
                    <p><?php echo $coursesEnrolled . ' Course' . ($coursesEnrolled === 1 ? '' : 's') . ' Enrolled'; ?>
                    </p>
                </a>


                <!-- Facilities -->
                <a href="viewFacilitiesPage.php" class="dashboard-card">
                    <img src="../images/facilityBooking.png" alt="Facilities Booking" class="card-icon">
                    <h2>Facilities Booking</h2>
                    <p><?php echo $activeBookings . ' Active Booking' . ($activeBookings === 1 ? '' : 's'); ?></p>
                </a>


                <!-- Study Groups -->
                <a href="academicsPage.php?tab=interaction" class="dashboard-card">
                    <img src="../images/studyGrp.png" alt="Study Groups" class="card-icon">
                    <h2>Study Groups</h2>
                    <p><?php echo $groupsJoined . ' Group' . ($groupsJoined === 1 ? '' : 's') . ' joined'; ?></p>
                </a>


                <!-- Campus Events -->
                <a href="viewEventsPage.php" class="dashboard-card">
                    <img src="../images/campusEvent.png" alt="Campus Events" class="card-icon">
                    <h2>Campus Events</h2>
                    <p><?php echo $upcomingEventsCnt . ' Upcoming'; ?></p>
                </a>


                <!-- AI Chatbot -->
                <a href="AIChatbotPage.php" class="dashboard-card">
                    <img src="../images/aiChatbot.png" alt="AI Chatbot" class="card-icon">
                    <h2>AI Chatbot</h2>
                    <p>Ask a Question</p>
                </a>


                <!-- Feedback -->
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