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

require_once "../controller/updateAIModelController.php";

$controller = new UpdateAIModelController();

$current = $controller->getCurrentModel();
$history = $controller->getHistory();
$maxUpload = $controller->getUploadLimitText();

// Secret token that must come back with every form (stops forged requests)
if (empty($_SESSION['ai_model_csrf'])) {
    $_SESSION['ai_model_csrf'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['ai_model_csrf'];

$errorMessage = null;
if (isset($_SESSION['ai_model_error'])) {
    $errorMessage = $_SESSION['ai_model_error'];
    unset($_SESSION['ai_model_error']);
}

$successMessage = null;
$successNote = null;
if (isset($_SESSION['ai_model_success'])) {
    $successMessage = $_SESSION['ai_model_success'];
    unset($_SESSION['ai_model_success']);
}
if (isset($_SESSION['ai_model_note'])) {
    $successNote = $_SESSION['ai_model_note'];
    unset($_SESSION['ai_model_note']);
}

$old = ['modelName' => '', 'version' => '', 'releaseNotes' => ''];
if (isset($_SESSION['ai_model_old'])) {
    $old = array_merge($old, $_SESSION['ai_model_old']);
    unset($_SESSION['ai_model_old']);
} elseif ($current) {
    $old['modelName'] = $current['modelName'];
}

$staged = null;
if (isset($_GET['confirm'])) {
    $staged = $controller->getStagedUpdate((int) $_GET['confirm'], $_SESSION['user_id']);
}

function formatDate($value)
{
    return $value ? date('d M Y', strtotime($value)) : '—';
}

$statusLabels  = [
    'deployed' => 'Active',
    'approved' => 'Awaiting Confirmation',
    'archived' => 'Replaced',
    'failed'   => 'Failed',
];
$statusClasses = [
    'deployed' => 'status-active',
    'approved' => 'status-pending',
    'archived' => 'status-archived',
    'failed'   => 'status-suspended',
];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Update AI Model - UniBee</title>

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

    <main class="registration-page">

        <h2 class="registration-title">Update AI Model</h2>

        <?php if ($errorMessage): ?>
            <div class="error-message admin-edit-error"><?php echo htmlspecialchars($errorMessage); ?></div>
        <?php endif; ?>

        <div class="registration-card">

            <!-- Current AI Model -->
            <div class="detail-section">
                <h3 class="detail-section-title">Current AI Model</h3>

                <?php if ($current): ?>
                    <div class="detail-row">
                        <span class="detail-label">Model Name:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($current['modelName']); ?></span>
                    </div>

                    <div class="detail-row">
                        <span class="detail-label">Current Version:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($current['version']); ?></span>
                    </div>

                    <div class="detail-row">
                        <span class="detail-label">Status:</span>
                        <span class="detail-value"><span class="status-badge status-active ai-model-badge">Active</span></span>
                    </div>

                    <div class="detail-row">
                        <span class="detail-label">Deployment Date:</span>
                        <span class="detail-value"><?php echo formatDate($current['deployedAt']); ?></span>
                    </div>

                    <div class="detail-row">
                        <span class="detail-label">Last Updated:</span>
                        <span class="detail-value"><?php echo formatDate($current['deployedAt'] ?: $current['approvedAt']); ?></span>
                    </div>
                <?php else: ?>
                    <p class="registration-status-note">No AI model has been deployed yet. Upload one below to get started.</p>
                <?php endif; ?>
            </div>

            <!-- Update new AI Model -->
            <div class="detail-section">
                <h3 class="detail-section-title">Update New AI Model</h3>

                <form id="updateModelForm" action="../controller/updateAIModelController.php" method="POST"
                    enctype="multipart/form-data">
                    <input type="hidden" name="ai_action" value="stage">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

                    <div class="ai-model-form">

                        <label for="modelName">Model Name: *</label>
                        <input type="text" id="modelName" name="modelName" maxlength="255"
                            value="<?php echo htmlspecialchars($old['modelName']); ?>">

                        <label for="version">New Version: *</label>
                        <input type="text" id="version" name="version" maxlength="50" placeholder="e.g. v3.0"
                            value="<?php echo htmlspecialchars($old['version']); ?>">

                        <label for="modelFile">Model File: *</label>
                        <div>
                            <input type="file" id="modelFile" name="modelFile"
                                accept=".<?php echo implode(',.', UpdateAIModelController::ALLOWED_EXTENSIONS); ?>">
                            <p class="ai-model-hint">
                                Accepted: <?php echo '.' . implode(', .', UpdateAIModelController::ALLOWED_EXTENSIONS); ?>.
                                Largest file this server accepts: <?php echo htmlspecialchars($maxUpload); ?>.
                            </p>
                        </div>

                        <label for="releaseNotes">Release Notes: *</label>
                        <textarea id="releaseNotes" name="releaseNotes" rows="4" maxlength="2000"><?php echo htmlspecialchars($old['releaseNotes']); ?></textarea>

                    </div>
                </form>
            </div>

            <div class="registration-actions">
                <a href="systemAdminDashboardPage.php" class="btn-large back-btn">Back</a>
                <button type="button" id="saveBtn" class="btn-large update-btn" disabled
                    onclick="submitOnce('updateModelForm', this, 'Validating...')">Save Changes</button>
            </div>

        </div>

        <!-- Version history -->
        <h3 class="section-label ai-model-history-title">Version History</h3>

        <section class="table-section">
            <div class="table-wrapper">
                <table class="admin-table ai-model-table">
                    <thead>
                        <tr>
                            <th>Model</th>
                            <th>Version</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Updated By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($history)): ?>
                            <tr>
                                <td colspan="5" class="empty-row">No AI model versions have been registered yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($history as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['modelName']); ?></td>
                                    <td><?php echo htmlspecialchars($row['version']); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $statusClasses[$row['status']] ?? 'status-archived'; ?>">
                                            <?php echo $statusLabels[$row['status']] ?? htmlspecialchars($row['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatDate($row['deployedAt'] ?: ($row['approvedAt'] ?: $row['createdAt'])); ?></td>
                                    <td><?php echo $row['updatedByName'] ? htmlspecialchars($row['updatedByName']) : '—'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

    </main>

    <!-- Confirm pop-up -->
    <?php if ($staged): ?>
        <div id="confirmModal" class="modal-overlay active">
            <div class="modal-box modal-large">
                <button type="button" class="modal-close"
                    onclick="document.getElementById('cancelForm').submit();">&times;</button>

                <p class="modal-title">
                    <?php if ($current): ?>
                        Are you sure you want to update the UniBee AI model from
                        <?php echo htmlspecialchars($current['version']); ?> to
                        <?php echo htmlspecialchars($staged['version']); ?>?
                    <?php else: ?>
                        Are you sure you want to install the UniBee AI model
                        <?php echo htmlspecialchars($staged['modelName'] . ' ' . $staged['version']); ?>?
                    <?php endif; ?>
                </p>

                <form id="cancelForm" action="../controller/updateAIModelController.php" method="POST">
                    <input type="hidden" name="ai_action" value="cancel">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="model_id" value="<?php echo (int) $staged['id']; ?>">
                </form>

                <form id="confirmForm" action="../controller/updateAIModelController.php" method="POST">
                    <input type="hidden" name="ai_action" value="confirm">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="model_id" value="<?php echo (int) $staged['id']; ?>">
                </form>

                <div class="modal-actions">
                    <button type="button" class="modal-btn modal-cancel"
                        onclick="document.getElementById('cancelForm').submit();">Cancel</button>
                    <button type="button" class="modal-btn modal-confirm-approve"
                        onclick="submitOnce('confirmForm', this, 'Updating...')">Confirm</button>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Success pop-up -->
    <?php if ($successMessage): ?>
        <div id="successModal" class="modal-overlay active">
            <div class="modal-box modal-flash modal-large">
                <button type="button" class="modal-close" onclick="closeModal('successModal')">&times;</button>
                <p class="modal-title"><?php echo htmlspecialchars($successMessage); ?></p>
                <?php if ($successNote): ?>
                    <p class="modal-message"><?php echo htmlspecialchars($successNote); ?></p>
                <?php endif; ?>
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

        function submitOnce(formId, button, workingText) {
            button.disabled = true;
            button.textContent = workingText;
            document.getElementById(formId).submit();
        }

        var requiredFields = ['modelName', 'version', 'modelFile', 'releaseNotes'];

        function updateSaveButton() {
            var ready = requiredFields.every(function (id) {
                var field = document.getElementById(id);
                return id === 'modelFile' ? field.files.length > 0 : field.value.trim() !== '';
            });
            document.getElementById('saveBtn').disabled = !ready;
        }

        requiredFields.forEach(function (id) {
            var field = document.getElementById(id);
            field.addEventListener('input', updateSaveButton);
            field.addEventListener('change', updateSaveButton);
        });
        updateSaveButton();

        document.querySelectorAll('.modal-overlay').forEach(function (overlay) {
            if (overlay.id === 'confirmModal') {
                return;
            }
            overlay.addEventListener('click', function (event) {
                if (event.target === overlay) {
                    overlay.classList.remove('active');
                }
            });
        });
    </script>

</body>

</html>