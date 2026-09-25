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

                    <a href="ManageProfilePage.php">Manage Profile</a>
                    <a href="">AI Chatbot</a>
                    <a href="academicsPage.php">Academics</a>
                    <a href="ViewFacilitiesPage.php">Facility Booking</a>
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

            <!-- Next Class -->
            <div class="summary-item">
                <div class="summary-title">
                    <img src="../images/nextClass.png" alt="Next class" class="summary-icon">
                    <span>Next class</span>
                </div>

                <h2>Data Structures, 2:00pm</h2>
                <p>Room B204</p>
            </div>

            <!-- Notifications -->
            <div class="summary-item">
                <div class="summary-title">
                    <img src="../images/noti.png" alt="Notifications" class="summary-icon">
                    <span>Notifications</span>
                </div>

                <h2>3 unread</h2>
                <p>Exam schedule updated</p>
            </div>

            <!-- Upcoming Event -->
            <div class="summary-item">
                <div class="summary-title">
                    <img src="../images/upcomingEvent.png" alt="Upcoming event" class="summary-icon">
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
                <a href="academicsPage.php" class="dashboard-card">
                    <img src="../images/academic.png" alt="Academics" class="card-icon">
                    <h2>Academics</h2>
                    <p>5 Courses Enrolled</p>
                </a>


                <!-- Facilities -->
                <a href="ViewFacilitiesPage.php" class="dashboard-card">
                    <img src="../images/facilityBooking.png" alt="Facilities Booking" class="card-icon">
                    <h2>Facilities Booking</h2>
                    <p>1 Active Booking</p>
                </a>


                <!-- Study Groups -->
                <a href="StudyGroupsPage.php" class="dashboard-card">
                    <img src="../images/studyGrp.png" alt="Study Groups" class="card-icon">
                    <h2>Study Groups</h2>
                    <p>2 Groups joined</p>
                </a>


                <!-- Campus Events -->
                <a href="viewEventsPage.php" class="dashboard-card">
                    <img src="../images/campusEvent.png" alt="Campus Events" class="card-icon">
                    <h2>Campus Events</h2>
                    <p>3 Upcoming</p>
                </a>


                <!-- AI Chatbot -->
                <a href="AIChatbotPage.php" class="dashboard-card">
                    <img src="../images/aiChatbot.png" alt="AI Chatbot" class="card-icon">
                    <h2>AI Chatbot</h2>
                    <p>Ask a Question</p>
                </a>


                <!-- Feedback -->
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
