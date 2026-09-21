<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: loginPage.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Feedback - UniBee</title>
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
                    <a href="AIChatbotPage.php">AI Chatbot</a>
                    <a href="AcademicsPage.php">Academics</a>
                    <a href="FacilitiesBookingPage.php">Facility Booking</a>
                    <a href="CampusEventsPage.php">University Campus Event</a>
                    <a href="../controller/logoutController.php">Log Out</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Submit Feedback Section -->
    <main class="dashboard">

        <div class="profile-header">
            <a href="studentDashboardPage.php" class="btn-back">&#8592; Back</a>
            <h2 class="section-label">Submit Feedback</h2>
            <div></div>
        </div>

        <form action="../controller/SubmitFeedbackController.php" method="POST" class="feedback-form">

            <div class="form-group">
                <label for="message">Feedback</label>
                <textarea id="message" name="message" rows="8" placeholder="Write your feedback here..." required></textarea>
            </div>

            <button type="submit" name="submit_feedback" class="btn-primary">Submit</button>

        </form>

    </main>

    <!-- Success Modal -->
    <?php if (isset($_SESSION['feedback_success'])): ?>
        <div class="modal-overlay" id="successModal">
            <div class="modal-box">
                <span class="modal-close" onclick="closeModal()">&times;</span>
                <p class="modal-message"><?php echo htmlspecialchars($_SESSION['feedback_success']); ?></p>
            </div>
        </div>
        <?php unset($_SESSION['feedback_success']); ?>
    <?php endif; ?>

    <!-- Error Message -->
    <?php if (isset($_SESSION['feedback_error'])): ?>
        <div class="error-message" style="max-width:800px; margin: 20px auto;">
            <?php echo htmlspecialchars($_SESSION['feedback_error']); ?>
        </div>
        <?php unset($_SESSION['feedback_error']); ?>
    <?php endif; ?>

    <!-- Footer -->
    <footer>
        <div class="footer-bottom-bar">
            &copy; 2026 UniBee. All rights reserved.
        </div>
    </footer>

    <script>
        function closeModal() {
            var modal = document.getElementById("successModal");
            if (modal) modal.style.display = "none";
            // Redirect to dashboard after closing
            window.location.href = "studentDashboardPage.php";
        }
    </script>
</body>
</html>