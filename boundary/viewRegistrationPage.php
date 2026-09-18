<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: LoginPage.php");
    exit();
}

require_once "../controller/viewRegistrationController.php";

$registrationId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$controller = new ViewRegistrationController();
$registration = $controller->getRegistration($registrationId);

if (!$registration) {
    header("Location: manageUniversityPage.php");
    exit();
}

$isPending = $registration['status'] === 'pending';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage University - UniBee</title>

    <link rel="stylesheet" href="../style.css">
</head>

<body class="system-admin-dashboard">
    <script src="../script.js"></script>

    <!-- Header -->
    <header class="header">

        <div class="logo-container">
            <img src="../images/uniBeeLogo.png" alt="UniBee Logo">
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
 
        <h2 class="registration-title">University Registration Details</h2>
 
        <div class="registration-card">
 
            <!-- University Information -->
            <div class="detail-section">
                <h3 class="detail-section-title">University Information</h3>
 
                <div class="detail-row">
                    <span class="detail-label">University ID:</span>
                    <span class="detail-value">UNI-<?php echo str_pad($registration['id'], 4, '0', STR_PAD_LEFT); ?></span>
                </div>
 
                <div class="detail-row">
                    <span class="detail-label">University Name:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($registration['universityName']); ?></span>
                </div>
 
                <div class="detail-row">
                    <span class="detail-label">Registration Date:</span>
                    <span class="detail-value"><?php echo date('d F Y', strtotime($registration['createdAt'])); ?></span>
                </div>
            </div>
 
            <!-- University Representative Information -->
            <div class="detail-section">
                <h3 class="detail-section-title">University Representative Information</h3>
 
                <div class="detail-row">
                    <span class="detail-label">Name:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($registration['applicantName']); ?></span>
                </div>
 
                <div class="detail-row">
                    <span class="detail-label">Email:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($registration['applicantEmail']); ?></span>
                </div>
 
                <div class="detail-row">
                    <span class="detail-label">Contact:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($registration['applicantContact']); ?></span>
                </div>
            </div>
 
            <?php if (!$isPending): ?>
                <p class="registration-status-note">
                    This registration has already been
                    <strong><?php echo htmlspecialchars($registration['status']); ?></strong>.
                    <?php if ($registration['status'] === 'rejected' && !empty($registration['rejectionReason'])): ?>
                        Reason: <?php echo htmlspecialchars($registration['rejectionReason']); ?>
                    <?php endif; ?>
                </p>
            <?php endif; ?>
 
            <div class="registration-actions">
 
                <a href="manageUniversityPage.php" class="btn-large back-btn">Back</a>
 
                <?php if ($isPending): ?>
 
                    <button type="button" class="btn-large reject-btn" onclick="openModal('rejectModal')">Reject</button>
 
                    <button type="button" class="btn-large approve-btn" onclick="openModal('approveModal')">Approve</button>
 
                    <form id="approveForm" action="../controller/viewRegistrationController.php" method="POST">
                        <input type="hidden" name="registration_id" value="<?php echo $registration['id']; ?>">
                        <input type="hidden" name="action" value="approve">
                    </form>
 
                <?php endif; ?>
 
            </div>
 
        </div>
 
    </main>
 
    <?php if ($isPending): ?>
 
        <!-- Approve confirmation pop up -->
        <div id="approveModal" class="modal-overlay">
            <div class="modal-box">
                <button type="button" class="modal-close" onclick="closeModal('approveModal')">&times;</button>
 
                <p class="modal-title">
                    Are you sure you want to approve <?php echo htmlspecialchars($registration['universityName']); ?>?
                </p>
                <p class="modal-message">
                    Once approved, the university account will be activated and the University Admin will be notified.
                </p>
 
                <div class="modal-actions">
                    <button type="button" class="modal-btn modal-cancel" onclick="closeModal('approveModal')">Cancel</button>
                    <button type="button" class="modal-btn modal-confirm-approve" onclick="document.getElementById('approveForm').submit();">Confirm</button>
                </div>
            </div>
        </div>
 
        <!-- Reject confirmation pop up -->
        <div id="rejectModal" class="modal-overlay">
            <div class="modal-box">
                <button type="button" class="modal-close" onclick="closeModal('rejectModal')">&times;</button>
 
                <p class="modal-title">
                    Are you sure you want to reject <?php echo htmlspecialchars($registration['universityName']); ?>?
                </p>
 
                <form action="../controller/viewRegistrationController.php" method="POST">
                    <input type="hidden" name="registration_id" value="<?php echo $registration['id']; ?>">
                    <input type="hidden" name="action" value="reject">
 
                    <textarea name="rejection_reason" class="modal-reason-textarea" placeholder="Enter reason" required></textarea>
 
                    <div class="modal-actions">
                        <button type="button" class="modal-btn modal-cancel" onclick="closeModal('rejectModal')">Cancel</button>
                        <button type="submit" class="modal-btn modal-confirm-reject">Reject</button>
                    </div>
                </form>
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