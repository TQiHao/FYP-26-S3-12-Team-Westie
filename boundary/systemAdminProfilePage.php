<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: loginPage.php");
    exit();
}

require_once "../controller/systemAdminManageProfileController.php";

$controller = new SystemAdminManageProfileController();
$admin = $controller->getProfile($_SESSION['user_id']);

if (!$admin) {
    header("Location: systemAdminDashboardPage.php");
    exit();
}

$flashMessage = null;
if (isset($_SESSION['sysadmin_profile_success'])) {
    $flashMessage = $_SESSION['sysadmin_profile_success'];
    unset($_SESSION['sysadmin_profile_success']);
} elseif (isset($_SESSION['sysadmin_profile_error'])) {
    $flashMessage = $_SESSION['sysadmin_profile_error'];
    unset($_SESSION['sysadmin_profile_error']);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Profile - UniBee</title>

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

    <main>

        <div class="profile-header-actions">
            <a href="systemAdminDashboardPage.php" class="admin-edit-back">&#8249; Back</a>
            <a href="updateSystemAdminProfilePage.php" class="btn-update">Update</a>
        </div>

        <div class="profile-view">

            <div class="profile-identity">
                <img src="../images/profilePic.png" alt="Profile" class="profile-large-icon">
                <div>
                    <h2><?php echo htmlspecialchars($admin['fullName']); ?></h2>
                    <p>Admin ID: SYSADM-<?php echo str_pad($admin['id'], 4, '0', STR_PAD_LEFT); ?></p>
                </div>
            </div>

            <hr class="profile-divider">

            <div class="profile-details">

                <div class="profile-field">
                    <label>Department</label>
                    <p><?php echo $admin['department'] ? htmlspecialchars($admin['department']) : '-'; ?></p>
                </div>

                <div class="profile-field">
                    <label>Position</label>
                    <p><?php echo $admin['position'] ? htmlspecialchars($admin['position']) : '-'; ?></p>
                </div>

                <div class="profile-field">
                    <label>Email</label>
                    <p><?php echo htmlspecialchars($admin['email']); ?></p>
                </div>

                <div class="profile-field">
                    <label>Contact Number</label>
                    <p><?php echo $admin['contactNumber'] ? htmlspecialchars($admin['contactNumber']) : '-'; ?></p>
                </div>

            </div>

        </div>

    </main>

    <?php if ($flashMessage): ?>
        <div id="flashModal" class="modal-overlay active">
           <div class="modal-box modal-flash modal-large">
                <button type="button" class="modal-close" onclick="closeModal('flashModal')">&times;</button>
                <p class="modal-title"><?php echo htmlspecialchars($flashMessage); ?></p>
            </div>
        </div>
    <?php endif; ?>

    <footer>
        <div class="footer-bottom-bar">
            &copy; 2026 UniBee. All rights reserved.
        </div>
    </footer>

    <script>
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        document.querySelectorAll('.modal-overlay').forEach(function (overlay) {
            overlay.addEventListener('click', function (event) {
                if (event.target === overlay) {
                    overlay.classList.remove('active');
                }
            });
        });
    </script>

</body>
</html>