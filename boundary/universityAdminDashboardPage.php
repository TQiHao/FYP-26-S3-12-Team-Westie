<?php

session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: LoginPage.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Course Coordinator Dashboard - UniBee</title>

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
            <strong>
                <?php echo htmlspecialchars($_SESSION['user_name']); ?>
            </strong>
        </div>

        <!-- Header Icons -->
        <div class="dashboard-header-right">

            <!-- Profile + Dropdown -->
            <div class="profile-dropdown">

                <div class="profile-container" onclick="toggleDropdown()">

                    <img src="../images/profilePic.png" alt="Profile" class="profile-icon">
                    <img src="../images/dropdown.png" alt="Menu" class="dropdown-arrow">

                </div>

                <div id="profileMenu" class="dropdown-menu">

                    <a href="ManageClassesPage.php">
                        Manage Classes
                    </a>

                    <a href="AIChatbotPage.php">
                        AI Chatbot
                    </a>

                    <a href="SubmitFeedbackPage.php">
                        Submit Feedback
                    </a>

                    <a href="../controller/logoutController.php">
                        Log Out
                    </a>

                </div>

            </div>

        </div>

    </header>


    <!-- University Admin Dashboard -->
    <main class="dashboard">

        <!-- Explore -->
        <section class="dashboard-section">

            <h3 class="section-label">Explore</h3>

            <div class="dashboard-grid">

                <!-- Renew License -->
                <a href="RenewLicensePage.php" class="dashboard-card">

                    <img src="../images/license.png" alt="Renew License" class="card-icon">
                    <h2>Renew License</h2>
                    <p>1 year 5 months left</p>

                </a>


                <!-- Manage University Information -->
                <a href="ManageUniversityInformationPage.php"
                   class="dashboard-card">

                    <img src="../images/info.png" alt="Manage University Information" class="card-icon">
                    <h2>Manage University Information</h2>
                    <p>Upload University Documents</p>

                </a>


                <!-- Manage University Events -->
                <a href="ManageUniversityEventsPage.php"
                   class="dashboard-card">

                    <img src="../images/campusEvent.png" alt="Manage University Events" class="card-icon">
                    <h2>Manage University Events</h2>
                    <p>4 Ongoing Events</p>

                </a>


                <!-- Manage Facilities Booking -->
                <a href="ManageFacilitiesBookingPage.php"
                   class="dashboard-card">

                    <img src="../images/facilityBooking.png" alt="Manage Facilities Booking" class="card-icon">
                    <h2>Manage Facilities Booking</h2>
                    <p>30 Active Bookable Facilities</p>

                </a>


                <!-- Manage FAQ Database -->
                <a href="ManageFAQDatabasePage.php"
                   class="dashboard-card">

                    <img src="../images/aichatbot.png" alt="Manage FAQ Database" class="card-icon">
                    <h2>Manage FAQ Database</h2>
                    <p>20 active FAQs</p>

                </a>


                <!-- AI Chatbot -->
                <a href="AIChatbotPage.php"
                   class="dashboard-card">

                    <img src="../images/aichatbot.png" alt="AI Chatbot" class="card-icon">
                    <h2>AI Chatbot</h2>
                    <p>Ask a Question</p>

                </a>


                <!-- Submit Feedback -->
                <a href="SubmitFeedbackPage.php"
                   class="dashboard-card">

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