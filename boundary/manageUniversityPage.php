<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: LoginPage.php");
    exit();
}

require_once "../controller/manageUniversityController.php";

$controller=new ManageUniversityController();
$pendingRegistrations=$controller->getPendingRegistrations();
$existingUniversities=$controller->getExistingUniversities();

$flashMessage = null;
if (isset($_SESSION['registration_review_success'])) {
    $flashMessage = $_SESSION['registration_review_success'];
    unset($_SESSION['registration_review_success']);
} elseif (isset($_SESSION['registration_review_error'])) {
    $flashMessage = $_SESSION['registration_review_error'];
    unset($_SESSION['registration_review_error']);
} elseif (isset($_SESSION['university_action_success'])) {
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

    <title>Manage University - UniBee</title>

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

    <main class="admin-table-page">

    <!-- Pending Registration -->
    <section class="table-section">
        <h3 class="section-label">Pending University Registrations</h3>

        <div class="table-wrapper">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>SID</th>
                        <th>University Name</th>
                        <th>Registration Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (empty($pendingRegistrations)): ?>
                        <tr>
                            <td colspan="5" class="empty-row">No pending registrations.</td>
                        </tr>
                    
                    <?php else: ?>
                        <?php foreach ($pendingRegistrations as $reg): ?>
                            <tr>
                                <td>UNI-<?php echo str_pad($reg['id'], 4, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo htmlspecialchars($reg['universityName']); ?></td>
                                <td><?php echo date('d F Y', strtotime($reg['createdAt'])); ?></td>
                                <td><span class="status-badge status-pending">Pending</span></td>

                                <td>
                                    <a href="viewRegistrationPage.php?id=<?php echo $reg['id']; ?>" class="action-btn view-btn">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Existing Universities -->
    <section class="table-section">
        <h3 class="section-label">Exsting Universities</h3>

        <div class="table-wrapper">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>SID</th>
                        <th>University Name</th>
                        <th>Status</th>
                        <th>License</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (empty($existingUniversities)): ?>
                        <tr>
                            <td colspan="5" class="empty-row">No Universities Found.</td>
                        </tr>
                    
                    <?php else: ?>
                        <?php foreach ($existingUniversities as $uni): ?>
                            <tr>
                                <td>UNI-<?php echo str_pad($uni['id'], 4, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo htmlspecialchars($uni['name']); ?></td>
                                <td>
                                    <?php if ($uni['status'] === 'active'): ?>
                                        <span class="status-badge status-active">Active</span>
                                    <?php else: ?>
                                        <span class="status-badge status-suspended">Suspended</span>

                                    <?php endif; ?>
                                </td>

                                <td class="license-cell">
                                    <?php echo $uni['licenseExpiryDate']
                                        ? date('d F Y', strtotime($uni['licenseExpiryDate'])): 'No license on record'; ?>
                                </td>
                                
                                <td class="action-cell">
                                    <a href="viewUniversityPage.php?id=<?php echo $uni['id']; ?>" class="action-btn view-btn">View</a>
                                    <?php if ($uni['status'] === 'active'): ?>
                                        <button type="button" class="action-btn suspend-btn"
                                            onclick="openSuspendModal(<?php echo $uni['id']; ?>, '<?php echo htmlspecialchars($uni['name'], ENT_QUOTES); ?>')">
                                            Suspend
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="action-btn reactivate-btn"
                                            onclick="openReactivateModal(<?php echo $uni['id']; ?>, '<?php echo htmlspecialchars($uni['name'], ENT_QUOTES); ?>')">
                                            Reactivate
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
    </main>

    <!-- Suspend confirmation pop up -->
    <div id="suspendModal" class="modal-overlay">
        <div class="modal-box">
            <button type="button" class="modal-close" onclick="closeModal('suspendModal')">&times;</button>

            <p class="modal-title" id="suspendModalTitle">Are you sure you want to suspend this university?</p>

            <form action="../controller/manageUniversityController.php" method="POST">
                <input type="hidden" name="university_id" id="suspendUniversityId" value="">
                <input type="hidden" name="action" value="suspend">

                <textarea name="reason" class="modal-reason-textarea" placeholder="Enter reason" required></textarea>

                <div class="modal-actions">
                    <button type="button" class="modal-btn modal-cancel" onclick="closeModal('suspendModal')">Cancel</button>
                    <button type="submit" class="modal-btn modal-confirm-reject">Suspend</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Reactivate confirmation pop up -->
    <div id="reactivateModal" class="modal-overlay">
        <div class="modal-box">
            <button type="button" class="modal-close" onclick="closeModal('reactivateModal')">&times;</button>

            <p class="modal-title" id="reactivateModalTitle">Are you sure you want to reactivate this university?</p>

            <form id="reactivateForm" action="../controller/manageUniversityController.php" method="POST">
                <input type="hidden" name="university_id" id="reactivateUniversityId" value="">
                <input type="hidden" name="action" value="reactivate">
            </form>

            <div class="modal-actions">
                <button type="button" class="modal-btn modal-cancel" onclick="closeModal('reactivateModal')">Cancel</button>
                <button type="button" class="modal-btn modal-confirm-approve" onclick="document.getElementById('reactivateForm').submit();">Confirm</button>
            </div>
        </div>
    </div>

    <?php if ($flashMessage): ?>
        <div id="flashModal" class="modal-overlay active">
            <div class="modal-box modal-flash">
                <button type="button" class="modal-close" onclick="closeModal('flashModal')">&times;</button>
                <p class="modal-title"><?php echo htmlspecialchars($flashMessage); ?></p>
            </div>
        </div>
    <?php endif; ?>

    <script>
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        function openSuspendModal(universityId, universityName) {
            document.getElementById('suspendUniversityId').value = universityId;
            document.getElementById('suspendModalTitle').textContent =
                'Are you sure you want to suspend ' + universityName + '?';
            openModal('suspendModal');
        }

        function openReactivateModal(universityId, universityName) {
            document.getElementById('reactivateUniversityId').value = universityId;
            document.getElementById('reactivateModalTitle').textContent =
                'Are you sure you want to reactivate ' + universityName + '?';
            openModal('reactivateModal');
        }

        document.querySelectorAll('.modal-overlay').forEach(function (overlay) {
            overlay.addEventListener('click', function (event) {
                if (event.target === overlay) {
                    overlay.classList.remove('active');
                }
            });
        });
    </script>

    <footer>
        <div class="footer-bottom-bar">
            &copy; 2026 UniBee. All rights reserved.
        </div>
    </footer>
 
</body>
</html>