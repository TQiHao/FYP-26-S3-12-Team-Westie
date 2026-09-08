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

            <a href="NotificationPage.php" class="header-icon">
                <img src="../images/notification.png" alt="Notifications">
            </a>

            <!-- Profile + Dropdown -->
            <div class="profile-dropdown">

                <div class="profile-container" onclick="toggleDropdown()">

                    <img src="../images/profilePic.png"
                         alt="Profile"
                         class="profile-icon">

                    <img src="../images/dropdown.png"
                         alt="Menu"
                         class="dropdown-arrow">

                </div>

                <div id="profileMenu" class="dropdown-menu">

                    <a href="">Teaching</a>
                    <a href="">University Campus Events</a>
                    <a href="">Manage Profile</a>
                    <a href="">Submit Feedback</a>
                    <a href="../controller/logoutController.php">Log Out</a>

                </div>

            </div>

        </div>

    </header>

    <!-- Lecturer Dashboard -->
    <main class="dashboard">

        <!-- Dashboard Summary -->
        <section class="dashboard-summary">

            <!-- Next Class -->
            <div class="summary-item">

                <div class="summary-title">
                    <img src="../images/nextclass.png"
                         alt="Next class"
                         class="summary-icon">

                    <span>Next class</span>
                </div>

                <h2>Data Structures, 2:00pm</h2>
                <p>Room B204</p>

            </div>


            <!-- Notifications -->
            <div class="summary-item">

                <div class="summary-title">
                    <img src="../images/noti.png"
                         alt="Notifications"
                         class="summary-icon">

                    <span>Notifications</span>
                </div>

                <h2>3 unread</h2>
                <p>Exam schedule updated</p>

            </div>


            <!-- Upcoming Event -->
            <div class="summary-item">

                <div class="summary-title">
                    <img src="../images/upcomingevent.png"
                         alt="Upcoming event"
                         class="summary-icon">

                    <span>Upcoming event</span>
                </div>

                <h2>Faculty Meeting, Fri 16 Apr</h2>
                <p>Main Hall</p>

            </div>

        </section>


        <!-- Quick Actions -->
        <section class="dashboard-section">

            <h3 class="section-label">Quick actions</h3>

            <div class="quick-actions">

                <a href="" class="quick-button">
                    Event Reminders
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

                <!-- Teaching -->
                <a href="" class="dashboard-card">

                    <img src="../images/academic.png" alt="Teaching" class="card-icon">
                    <h2>Teaching</h2>
                    <p>5 Courses Assigned</p>

                </a>


                <!-- Campus Events -->
                <a href="CampusEventsPage.php" class="dashboard-card">

                    <img src="../images/campusevent.png" alt="Campus Events" class="card-icon">
                    <h2>Campus Events</h2>
                    <p>3 Upcomings</p>

                </a>


                <!-- Submit Feedback -->
                <a href="SubmitFeedbackPage.php" class="dashboard-card">

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