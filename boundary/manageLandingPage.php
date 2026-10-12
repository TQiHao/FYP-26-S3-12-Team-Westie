<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (
    !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true ||
    ($_SESSION['user_role'] ?? '') !== 'system_admin'
) {
    header("Location: LoginPage.php");
    exit();
}

$flashMessage = null;
if (isset($_SESSION['landing_success'])) {
    $flashMessage = $_SESSION['landing_success'];
    unset($_SESSION['landing_success']);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Landing Page - UniBee</title>

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

    <main class="landing-manage-page">

        <a href="systemAdminDashboardPage.php" class="admin-edit-back">&#8249; Back</a>

        <div class="landing-manage-bar">
            <h2 class="landing-manage-title">View Landing page</h2>
            <a href="updateLandingPage.php" class="landing-update-btn">Update content</a>
        </div>

        <!-- Live preview: the REAL landing page, so what the admin sees is what visitors see -->
        <div class="landing-preview-frame">
            <iframe id="landingPreview" src="viewLandingPage.php" title="Landing page preview" scrolling="no"></iframe>
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
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
        }

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

        var preview = document.getElementById('landingPreview');

        function fitPreview() {
            try {
                var doc = preview.contentWindow.document;
                preview.style.height = doc.documentElement.scrollHeight + 'px';
            } catch (e) {}
        }

        preview.addEventListener('load', function () {
            fitPreview();
            setTimeout(fitPreview, 500);
            setTimeout(fitPreview, 1500);
        });
        window.addEventListener('resize', fitPreview);
    </script>

</body>

</html>