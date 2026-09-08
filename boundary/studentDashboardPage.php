<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: LoginPage.php");
    exit();
}

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

    <!-- Student Dashboard Header -->
    <header class="student-header">

        <div class="student-header-left">

            <!-- UniBee Logo -->
            <div class="student-logo">
                <img src="../images/uniBeeLogo.png" alt="UniBee Logo">
            </div>

            <!-- Welcome Message -->
            <div class="welcome-message">
                <span>Welcome back,</span>
                <strong><?= htmlspecialchars($fullName) ?></strong>
            </div>

        </div>


        <!-- Header Icons -->
        <div class="student-header-right">

            <a href="NotificationPage.php" class="header-icon">
                <img src="../images/notification.png" alt="Notifications">
            </a>

            <a href="ManageProfilePage.php" class="header-icon">
                <img src="../images/profile.png" alt="Profile">
            </a>

            <span class="dropdown-arrow">⌄</span>

        </div>

    </header>


    <!-- Student Dashboard -->
    <main class="student-dashboard">

        <!-- Dashboard Summary -->
        <section class="dashboard-summary">

            <!-- Next Class -->
            <div class="summary-item">
                <div class="summary-title">
                    <span class="summary-icon">◷</span>
                    <span>Next class</span>
                </div>

                <h2>Data Structures, 2:00pm</h2>
                <p>Room B204</p>
            </div>


            <!-- Notifications -->
            <div class="summary-item">
                <div class="summary-title">
                    <span class="summary-icon">♧</span>
                    <span>Notifications</span>
                </div>

                <h2>3 unread</h2>
                <p>Exam schedule updated</p>
            </div>


            <!-- Upcoming Event -->
            <div class="summary-item">
                <div class="summary-title">
                    <span class="summary-icon">▣</span>
                    <span>Upcoming event</span>
                </div>

                <h2>Career fair, Fri 22 Aug</h2>
                <p>Main Hall</p>
            </div>

        </section>


        <!-- Quick Actions -->
        <section class="dashboard-section">

            <h3 class="section-label">Quick actions</h3>

            <div class="quick-actions">

                <a href="AIChatbotPage.php" class="quick-button">
                    Ask AI Chatbot
                </a>

                <a href="FacilitiesBookingPage.php" class="quick-button">
                    Book Study Room
                </a>

                <a href="TimetablePage.php" class="quick-button">
                    View Timetable
                </a>

            </div>

        </section>


        <!-- Explore -->
        <section class="dashboard-section">

            <h3 class="section-label">Explore</h3>

            <div class="dashboard-grid">

                <!-- Academics -->
                <a href="AcademicsPage.php" class="dashboard-card">
                    <span class="card-icon">▣</span>
                    <h2>Academics</h2>
                    <p>5 Courses Enrolled</p>
                </a>


                <!-- Facilities -->
                <a href="FacilitiesBookingPage.php" class="dashboard-card">
                    <span class="card-icon">▤</span>
                    <h2>Facilities Booking</h2>
                    <p>1 Active Booking</p>
                </a>


                <!-- Study Groups -->
                <a href="StudyGroupsPage.php" class="dashboard-card">
                    <span class="card-icon">♧</span>
                    <h2>Study Groups</h2>
                    <p>2 Groups joined</p>
                </a>


                <!-- Campus Events -->
                <a href="CampusEventsPage.php" class="dashboard-card">
                    <span class="card-icon">▣</span>
                    <h2>Campus Events</h2>
                    <p>3 Upcoming</p>
                </a>


                <!-- AI Chatbot -->
                <a href="AIChatbotPage.php" class="dashboard-card">
                    <span class="card-icon">⚙</span>
                    <h2>AI Chatbot</h2>
                    <p>Ask a Question</p>
                </a>


                <!-- Feedback -->
                <a href="SubmitFeedbackPage.php" class="dashboard-card">
                    <span class="card-icon">▤</span>
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
