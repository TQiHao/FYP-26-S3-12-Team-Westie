<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: LoginPage.php");
    exit();
}

require_once "../controller/viewUniversityController.php";

$universityId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$controller = new ViewUniversityController();
$university = $controller->getUniversityDetails($universityId);

if (!$university) {
    header("Location: manageUniversityPage.php");
    exit();
}

$isActive = $university['status'] === 'active';

$flashMessage = null;
if (isset($_SESSION['university_action_success'])) {
    $flashMessage = $_SESSION['university_action_success'];
    unset($_SESSION['university_action_success']);
} elseif (isset($_SESSION['university_action_error'])) {
    $flashMessage = $_SESSION['university_action_error'];
    unset($_SESSION['university_action_error']);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>University Details - UniBee</title>

    <link rel="stylesheet" href="../style.css">
</head>

<body class="system-admin-dashboard">
    <script src="../script.js"></script>

    <!-- Header -->
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
                    <a href="">Profile</a>
                    <a href="">Universities</a>
                    <a href="">Manage Landing Page</a>
                    <a href="">Update AI Model</a>
                    <a href="">System Operations</a>
                    <a href="../controller/logoutController.php">Log Out</a>
                </div>

            </div>
        </div>

    </header>

    <main class="registration-page">

        <h2 class="registration-title">University Details</h2>

        <div class="registration-card">

            <!-- University Information -->
            <div class="detail-section">
                <h3 class="detail-section-title">University Information</h3>

                <div class="detail-row">
                    <span class="detail-label">University ID:</span>
                    <span class="detail-value">UNI-<?php echo str_pad($university['id'], 4, '0', STR_PAD_LEFT); ?></span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">University Name:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($university['name']); ?></span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Registration Date:</span>
                    <span class="detail-value"><?php echo date('d F Y', strtotime($university['registrationDate'])); ?></span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Account Status:</span>
                    <span class="detail-value">
                        <?php if ($isActive): ?>
                            <span class="status-badge status-active">Active</span>
                        <?php else: ?>
                            <span class="status-badge status-suspended">Suspended</span>
                        <?php endif; ?>
                    </span>
                </div>
            </div>

            <!-- University Representative Information -->
            <div class="detail-section">
                <h3 class="detail-section-title">University Representative Information</h3>

                <div class="detail-row">
                    <span class="detail-label">Name:</span>
                    <span class="detail-value"><?php echo $university['repName'] ? htmlspecialchars($university['repName']) : '—'; ?></span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Email:</span>
                    <span class="detail-value"><?php echo $university['repEmail'] ? htmlspecialchars($university['repEmail']) : '—'; ?></span>
                </div>
            </div>

            <!-- License Information -->
            <div class="detail-section">
                <h3 class="detail-section-title">License Information</h3>

                <div class="detail-row">
                    <span class="detail-label">License Plan:</span>
                    <span class="detail-value"><?php echo $university['licensePlan'] ? htmlspecialchars($university['licensePlan']) : 'No license on record'; ?></span>
                </div>

                <?php if ($university['licenseStartDate']): ?>
                    <div class="detail-row">
                        <span class="detail-label">License Start Date:</span>
                        <span class="detail-value"><?php echo date('d F Y', strtotime($university['licenseStartDate'])); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($university['licenseExpiryDate']): ?>
                    <div class="detail-row">
                        <span class="detail-label">License Expiry Date:</span>
                        <span class="detail-value"><?php echo date('d F Y', strtotime($university['licenseExpiryDate'])); ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="registration-actions">

                <a href="manageUniversityPage.php" class="btn-large back-btn">Back</a>

                <?php if ($isActive): ?>
                    <button type="button" class="btn-large reject-btn" onclick="openModal('suspendModal')">Suspend</button>
                <?php else: ?>
                    <button type="button" class="btn-large reactivate-btn" onclick="openModal('reactivateModal')">Reactivate</button>

                    <form id="reactivateForm" action="../controller/viewUniversityController.php" method="POST">
                        <input type="hidden" name="university_id" value="<?php echo $university['id']; ?>">
                        <input type="hidden" name="action" value="reactivate">
                    </form>
                <?php endif; ?>

            </div>

        </div>

    </main>

    <?php if ($isActive): ?>

        <!-- Suspend confirmation modal - this IS the real suspend form -->
        <div id="suspendModal" class="modal-overlay">
            <div class="modal-box">
                <button type="button" class="modal-close" onclick="closeModal('suspendModal')">&times;</button>

                <p class="modal-title">
                    Are you sure you want to suspend <?php echo htmlspecialchars($university['name']); ?>?
                </p>

                <form action="../controller/viewUniversityController.php" method="POST">
                    <input type="hidden" name="university_id" value="<?php echo $university['id']; ?>">
                    <input type="hidden" name="action" value="suspend">

                    <textarea name="reason" class="modal-reason-textarea" placeholder="Enter reason" required></textarea>

                    <div class="modal-actions">
                        <button type="button" class="modal-btn modal-cancel" onclick="closeModal('suspendModal')">Cancel</button>
                        <button type="submit" class="modal-btn modal-confirm-reject">Suspend</button>
                    </div>
                </form>
            </div>
        </div>
    <?php else: ?>

        <!-- Reactivate confirmation modal -->
        <div id="reactivateModal" class="modal-overlay">
            <div class="modal-box">
                <button type="button" class="modal-close" onclick="closeModal('reactivateModal')">&times;</button>

                <p class="modal-title">
                    Are you sure you want to reactivate <?php echo htmlspecialchars($university['name']); ?>?
                </p>

                <div class="modal-actions">
                    <button type="button" class="modal-btn modal-cancel" onclick="closeModal('reactivateModal')">Cancel</button>
                    <button type="button" class="modal-btn modal-confirm-reactivate" onclick="document.getElementById('reactivateForm').submit();">Confirm</button>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($flashMessage): ?>
        <div id="flashModal" class="modal-overlay active">
            <div class="modal-box modal-flash">
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
    </script>

</body>
</html>