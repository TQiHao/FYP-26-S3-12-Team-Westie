<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (
    !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true ||
    ($_SESSION['user_role'] ?? '') !== 'system_admin'
) {
    header("Location: loginPage.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>System Operation - UniBee</title>

    <link rel="stylesheet" href="../style.css">
</head>

<body class="system-admin-dashboard">
    <script src="../script.js"></script>

    <header class="header">

        <div class="logo-container">
            <img src="../images/uniBeeLogo.png" alt="UniBee Logo">
        </div>

        <div class="dashboard-header-right">
            <div class="profile-dropdown">

                <div class="profile-container" onclick="toggleDropdown()">
                    <img src="../images/profilePic.png" alt="Profile" class="profile-icon">
                    <img src="../images/dropdown.png" alt="Menu" class="dropdown-arrow">
                </div>

                <div id="profileMenu" class="dropdown-menu">
                    <a href="systemAdminDashboardPage.php">Dashboard</a>
                    <a href="systemAdminProfilePage.php">Profile</a>
                    <a href="manageUniversityPage.php">Universities</a>
                    <a href="manageLandingPage.php">Manage Landing Page</a>
                    <a href="updateAIModelPage.php">Update AI Model</a>
                    <a href="systemOperationPage.php">System Operations</a>
                    <a href="reviewFeedbackPage.php">Review Feedback</a>
                    <a href="../controller/logOutController.php">Log Out</a>
                </div>

            </div>
        </div>

    </header>

    <main class="dashboard system-admin-dashboard">

        <div class="sysops-wrap">

            <a href="systemAdminDashboardPage.php" class="admin-edit-back">&#8249; Back</a>

            <section class="dashboard-section">

                <h3 class="section-label system-admin-dashboard">System Operations</h3>

                <div class="sysops-grid">

                    <a href="viewSystemLogsPage.php" class="sysops-card">
                        <div class="sysops-card-head">
                            <svg class="sysops-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="3" y="4" width="18" height="13" rx="2" />
                                <path d="M8 21h8M12 17v4" />
                                <path d="M12 8v3M12 13.5v.01" />
                            </svg>
                            <h2>View System Logs</h2>
                        </div>
                        <p>View system activities, warnings and errors.</p>
                        <span class="sysops-link">View Logs &rsaquo;</span>
                    </a>

                    <a href="backupRestorePage.php" class="sysops-card">
                        <div class="sysops-card-head">
                            <svg class="sysops-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <ellipse cx="11" cy="5" rx="7" ry="3" />
                                <path d="M4 5v6c0 1.7 3.1 3 7 3M4 11v6c0 1.7 3.1 3 7 3" />
                                <path d="M20 14a4 4 0 1 0 1 3.5M21 13v4h-4" />
                            </svg>
                            <h2>Backup / Restore Data</h2>
                        </div>
                        <p>Create system backups or restore previous backups.</p>
                        <span class="sysops-link">Manage Backup &rsaquo;</span>
                    </a>

                </div>

            </section>

        </div>

    </main>

    <footer>
        <div class="footer-bottom-bar">
            &copy; 2026 UniBee. All rights reserved.
        </div>
    </footer>

</body>

</html>