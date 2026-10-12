<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: LoginPage.php");
    exit();
}

require_once "../controller/systemAdminManageProfileController.php";

$controller = new SystemAdminManageProfileController();
$admin = $controller->getProfile($_SESSION['user_id']);

if (!$admin) {
    header("Location: systemAdminProfilePage.php");
    exit();
}

$flashError = null;
if (isset($_SESSION['sysadmin_profile_error'])) {
    $flashError = $_SESSION['sysadmin_profile_error'];
    unset($_SESSION['sysadmin_profile_error']);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Update Profile - UniBee</title>

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
                    <a href="">Universities</a>
                    <a href="">Manage Landing Page</a>
                    <a href="">Update AI Model</a>
                    <a href="">System Operations</a>
                    <a href="../controller/logoutController.php">Log Out</a>
                </div>

            </div>
        </div>

    </header>

    <main class="admin-edit-page">

        <a href="systemAdminProfilePage.php" class="admin-edit-back">&lsaquo; Back</a>

        <?php if ($flashError): ?>
            <div class="error-message admin-edit-error">
                <?php echo htmlspecialchars($flashError); ?>
            </div>
        <?php endif; ?>

        <form action="../controller/systemAdminManageProfileController.php" method="POST" class="admin-edit-form">

            <!-- Admin ID and Email are not editable -->
            <div class="admin-edit-row">
                <label>Admin ID:</label>
                <span>SYSADM-<?php echo str_pad($admin['id'], 4, '0', STR_PAD_LEFT); ?></span>
            </div>

            <div class="admin-edit-row">
                <label for="fullName">Full Name:</label>
                <input type="text" id="fullName" name="fullName"
                       value="<?php echo htmlspecialchars($admin['fullName']); ?>" required>
            </div>

            <div class="admin-edit-row">
                <label>Email Address:</label>
                <span><?php echo htmlspecialchars($admin['email']); ?></span>
            </div>

            <div class="admin-edit-row">
                <label for="contactNumber">Contact:</label>
                <input type="text" id="contactNumber" name="contactNumber"
                       value="<?php echo htmlspecialchars($admin['contactNumber'] ?? ''); ?>">
            </div>

            <div class="admin-edit-row">
                <label for="position">Position:</label>
                <input type="text" id="position" name="position"
                       value="<?php echo htmlspecialchars($admin['position'] ?? ''); ?>">
            </div>

            <div class="admin-edit-row">
                <label for="department">Department:</label>
                <input type="text" id="department" name="department"
                       value="<?php echo htmlspecialchars($admin['department'] ?? ''); ?>">
            </div>

            <button type="submit" name="update_profile" class="admin-edit-submit">Update</button>

        </form>

    </main>

    <footer>
        <div class="footer-bottom-bar">
            &copy; 2026 UniBee. All rights reserved.
        </div>
    </footer>

</body>
</html>