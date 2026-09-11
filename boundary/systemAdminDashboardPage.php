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

    <title>System Admin Dashboard - UniBee</title>

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

                    <a href="systemAdminDashboardPage.php">Dashboard</a>
                    <a href="">Universities</a>
                    <a href="">System Operations</a>
                    <a href="">Manage Landing Page</a>
                    <a href="">Update AI Model</a>
                    <a href="">Profile</a>
                    <a href="../controller/logoutController.php">Log Out</a>

                </div>

            </div>

        </div>

    </header>

    <!-- System Admin Dashboard -->
    <main class="dashboard system-admin-dashboard">

        <!-- Management Actions -->
        <section class="dashboard-section">

            <h3 class="section-label system-admin-dashboard">Management Actions</h3>

            <div class="dashboard-grid system-admin-dashboard">

                <!-- Manage University -->
                <a href="ManageUniversityPage.php" class="dashboard-card">
                    <img src="../images/manageUni.png" alt="Manage University" class="card-icon system-admin-dashboard">
                    <h2>Manage University</h2>
                </a>

                <!-- Manage Landing Page -->
                <a href="ManageLandingPagePage.php" class="dashboard-card">
                    <img src="../images/manageLandingPage.png" alt="Manage Landing Page" class="card-icon system-admin-dashboard">
                    <h2>Manage Landing Page</h2>
                </a>

                <!-- Update AI Model -->
                <a href="UpdateAIModelPage.php" class="dashboard-card">
                    <img src="../images/updateAI.png" alt="Update AI Model" class="card-icon system-admin-dashboard">
                    <h2>Update AI Model</h2>
                </a>

                <!-- System Operation -->
                <a href="SystemOperationPage.php" class="dashboard-card">
                    <img src="../images/SystemOps.png" alt="System Operation" class="card-icon system-admin-dashboard">
                    <h2>System Operation</h2>
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