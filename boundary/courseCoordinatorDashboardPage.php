<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: loginPage.php");
    exit();
}

require_once "../controller/ManageClassController.php";

$controller = new ManageClassController();
$activeClasses = $controller->getClasses('', '', '', 'active');
$activeClassCount = is_array($activeClasses) ? count($activeClasses) : 0;
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

        <!-- Profile + Dropdown -->
        <div class="dashboard-header-right">

            <div class="profile-dropdown">

                <div class="profile-container" onclick="toggleDropdown()">
                    <img src="../images/profilePic.png" alt="Profile" class="profile-icon">
                    <img src="../images/dropdown.png" alt="Menu" class="dropdown-arrow">
                </div>

                <div id="profileMenu" class="dropdown-menu">
                    <a href="manageClassPage.php">Manage Classes</a>
                    <a href="AIChatbotPage.php">AI Chatbot</a>
                    <a href="submitFeedbackPage.php">Submit Feedback</a>
                    <a href="../controller/logoutController.php">Log Out</a>
                </div>

            </div>

        </div>

    </header>

    <!-- Course Coordinator Dashboard -->
    <main class="dashboard">

        <!-- Explore -->
        <section class="dashboard-section">

            <h3 class="section-label">Explore</h3>

            <div class="dashboard-grid">

                <!-- Manage Classes -->
                <a href="manageClassPage.php" class="dashboard-card">
                    <img src="../images/academic.png" alt="Manage Classes" class="card-icon">
                    <h2>Manage Classes</h2>
                    <p><?php echo $activeClassCount . ' class' . ($activeClassCount === 1 ? ' is' : 'es are') . ' active'; ?>
                    </p>
                </a>

                <!-- AI Chatbot -->
                <a href="AIChatbotPage.php" class="dashboard-card">
                    <img src="../images/aichatbot.png" alt="AI Chatbot" class="card-icon">
                    <h2>AI Chatbot</h2>
                    <p>Ask a Question</p>
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