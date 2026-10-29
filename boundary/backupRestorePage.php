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

require_once "../controller/backupRestoreController.php";

$controller = new BackupRestoreController();

$page   = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$result = $controller->getBackups($page);

$backups    = $result['rows'];
$page       = $result['page'];
$totalPages = $result['totalPages'];
$totalRows  = $result['total'];

// Secret token that must come back with every create / restore form (stops forged requests)
if (empty($_SESSION['backup_csrf'])) {
    $_SESSION['backup_csrf'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['backup_csrf'];

$flashMessage = null;
if (isset($_SESSION['backup_success'])) {
    $flashMessage = $_SESSION['backup_success'];
    unset($_SESSION['backup_success']);
} elseif (isset($_SESSION['backup_error'])) {
    $flashMessage = $_SESSION['backup_error'];
    unset($_SESSION['backup_error']);
}

$firstShown = $totalRows === 0 ? 0 : (($page - 1) * BackupRestoreController::PER_PAGE) + 1;
$lastShown  = $firstShown === 0 ? 0 : $firstShown + count($backups) - 1;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Backup / Restore Data - UniBee</title>

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

    <main class="admin-table-page feedback-page">

        <a href="systemOperationPage.php" class="admin-edit-back">&#8249; Back</a>

        <div class="backup-title-row">
            <h3 class="section-label">Backup / Restore Data</h3>

            <button type="button" class="backup-create-btn" onclick="openModal('createModal')">+ Create new Backup</button>
        </div>

        <section class="table-section">
            <div class="table-wrapper">
                <table class="admin-table backup-table">
                    <thead>
                        <tr>
                            <th>Backup ID</th>
                            <th>File Name</th>
                            <th>Created Date</th>
                            <th>Created By</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (empty($backups)): ?>
                            <tr>
                                <td colspan="6" class="empty-row">No backups have been created yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($backups as $backup): ?>
                                <?php $isCompleted = $backup['status'] === 'completed'; ?>
                                <tr>
                                    <td>BKP-<?php echo str_pad($backup['id'], 4, '0', STR_PAD_LEFT); ?></td>

                                    <td class="wrap-cell">
                                        <?php echo htmlspecialchars($backup['fileName']); ?>
                                        <?php if ($backup['backupType'] === 'pre_restore'): ?>
                                            <span class="cell-sub">Automatic safety backup before a restore</span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="wrap-cell">
                                        <?php echo date('j M Y', strtotime($backup['createdAt'])); ?>
                                        <span class="cell-sub"><?php echo date('g:i A', strtotime($backup['createdAt'])); ?></span>
                                    </td>

                                    <td class="wrap-cell"><?php echo $backup['createdByName'] ? htmlspecialchars($backup['createdByName']) : '—'; ?></td>

                                    <td class="wrap-cell">
                                        <?php if ($isCompleted): ?>
                                            <span class="status-badge status-active">Completed</span>
                                        <?php else: ?>
                                            <span class="status-badge status-suspended">Failed</span>
                                        <?php endif; ?>

                                        <?php if (!empty($backup['restoredAt'])): ?>
                                            <span class="cell-sub">Restored <?php echo date('j M Y', strtotime($backup['restoredAt'])); ?></span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="action-cell">
                                        <?php if ($isCompleted && $backup['fileExists']): ?>
                                            <button type="button" class="action-btn reactivate-btn"
                                                onclick="openRestoreModal(<?php echo (int) $backup['id']; ?>, '<?php echo htmlspecialchars($backup['fileName'], ENT_QUOTES); ?>')">
                                                Restore
                                            </button>
                                        <?php elseif ($isCompleted): ?>
                                            <span class="cell-sub">File missing</span>
                                        <?php else: ?>
                                            &ndash;
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="feedback-pagination">
                <span class="pagination-info">
                    Showing <?php echo $firstShown; ?>–<?php echo $lastShown; ?> of <?php echo $totalRows; ?>
                </span>

                <?php if ($totalPages > 1): ?>
                    <div class="pagination-links">
                        <?php if ($page > 1): ?>
                            <a href="backupRestorePage.php?page=<?php echo $page - 1; ?>">&lsaquo; Prev</a>
                        <?php endif; ?>

                        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                            <a href="backupRestorePage.php?page=<?php echo $p; ?>"
                                class="<?php echo $p === $page ? 'current' : ''; ?>"><?php echo $p; ?></a>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <a href="backupRestorePage.php?page=<?php echo $page + 1; ?>">Next &rsaquo;</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>

    </main>

    <!-- Create backup: confirm pop-up -->
    <div id="createModal" class="modal-overlay">
        <div class="modal-box modal-large">
            <button type="button" class="modal-close" onclick="closeModal('createModal')">&times;</button>

            <p class="modal-title">Create a new backup of all system data now?</p>

            <form id="createForm" action="../controller/backupRestoreController.php" method="POST">
                <input type="hidden" name="backup_action" value="create">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            </form>

            <div class="modal-actions">
                <button type="button" class="modal-btn modal-cancel" onclick="closeModal('createModal')">Cancel</button>
                <button type="button" class="modal-btn modal-confirm-approve" id="createConfirmBtn"
                    onclick="submitOnce('createForm', this, 'Creating backup...')">Confirm</button>
            </div>
        </div>
    </div>

    <!-- Restore backup: confirm pop-up with a warning -->
    <div id="restoreModal" class="modal-overlay">
        <div class="modal-box modal-large">
            <button type="button" class="modal-close" onclick="closeModal('restoreModal')">&times;</button>

            <p class="modal-title modal-title-tight" id="restoreModalTitle">Restore this backup?</p>

            <p class="modal-warning">
                This will replace <strong>all current data</strong> with the data from this backup.
                Anything added since then will be lost.<br>
                A safety backup of the current data is saved automatically first.
            </p>

            <form id="restoreForm" action="../controller/backupRestoreController.php" method="POST">
                <input type="hidden" name="backup_action" value="restore">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="backup_id" id="restoreBackupId" value="">
            </form>

            <div class="modal-actions">
                <button type="button" class="modal-btn modal-cancel" onclick="closeModal('restoreModal')">Cancel</button>
                <button type="button" class="modal-btn modal-confirm-reject" id="restoreConfirmBtn"
                    onclick="submitOnce('restoreForm', this, 'Restoring...')">Restore</button>
            </div>
        </div>
    </div>

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

        function openRestoreModal(backupId, fileName) {
            document.getElementById('restoreBackupId').value = backupId;
            document.getElementById('restoreModalTitle').textContent = 'Restore ' + fileName + '?';
            openModal('restoreModal');
        }

        // Backups / restores can take a few seconds. Lock the button after the first click
        // so a second click cannot start a second backup or restore.
        function submitOnce(formId, button, workingText) {
            button.disabled = true;
            button.textContent = workingText;
            document.getElementById(formId).submit();
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