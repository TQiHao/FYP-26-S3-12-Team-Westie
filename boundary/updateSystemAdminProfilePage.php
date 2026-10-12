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
                    <a href="ManageUniversityPage.php">Manage Universities</a>
                    <a href="manageLandingPage.php">Manage Landing Page</a>
                    <a href="UpdateAIModelPage.php">Update AI Model</a>
                    <a href="SystemOperationPage.php">System Operations</a>
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

        <form id="updateProfileForm" action="../controller/systemAdminManageProfileController.php" method="POST" class="admin-edit-form">

            <input type="hidden" name="update_profile" value="1">

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

            <button type="button" class="admin-edit-submit" onclick="openUpdateConfirm()">Update</button>

        </form>

    </main>

    <!-- Confirmation popup -->
    <div id="confirmModal" class="modal-overlay">
        <div class="modal-box modal-large">
            <button type="button" class="modal-close" onclick="closeModal('confirmModal')">&times;</button>

            <p class="modal-title">Are you sure you want to update the profile details?</p>

            <div class="modal-actions">
                <button type="button" class="modal-btn modal-cancel" onclick="closeModal('confirmModal')">Cancel</button>
                <button type="button" class="modal-btn modal-confirm-approve"
                        onclick="document.getElementById('updateProfileForm').submit();">Confirm</button>
            </div>
        </div>
    </div>

    <!-- Pop up message when nothing was edited -->
    <div id="noChangesModal" class="modal-overlay">
        <div class="modal-box modal-flash modal-large">
            <button type="button" class="modal-close" onclick="closeModal('noChangesModal')">&times;</button>
            <p class="modal-title">No changes were made to the profile details.</p>
        </div>
    </div>

    <footer>
        <div class="footer-bottom-bar">
            &copy; 2026 UniBee. All rights reserved.
        </div>
    </footer>

    <script>
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        function openUpdateConfirm() {
            var form = document.getElementById('updateProfileForm');

            if (!form.reportValidity()) {
                return;
            }

            var fields = form.querySelectorAll('input[type="text"]');
            var changed = false;

            fields.forEach(function (field) {
                if (field.value.trim() !== field.defaultValue.trim()) {
                    changed = true;
                }
            });

            openModal(changed ? 'confirmModal' : 'noChangesModal');
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