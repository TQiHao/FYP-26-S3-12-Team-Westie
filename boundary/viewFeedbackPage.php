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

require_once "../controller/reviewFeedbackController.php";

$feedbackId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$controller = new ReviewFeedbackController();
$feedback = $controller->getFeedbackDetails($feedbackId);

if (!$feedback) {
    header("Location: reviewFeedbackPage.php");
    exit();
}

$flashMessage = null;
if (isset($_SESSION['feedback_review_success'])) {
    $flashMessage = $_SESSION['feedback_review_success'];
    unset($_SESSION['feedback_review_success']);
} elseif (isset($_SESSION['feedback_review_error'])) {
    $flashMessage = $_SESSION['feedback_review_error'];
    unset($_SESSION['feedback_review_error']);
}

$statusLabels  = ['new' => 'New', 'in_review' => 'In Review', 'resolved' => 'Resolved'];
$statusClasses = ['new' => 'status-new', 'in_review' => 'status-pending', 'resolved' => 'status-active'];
$status = $feedback['status'];

function renderStars($rating)
{
    $rating = (int) $rating;
    return '<span class="stars" aria-label="' . $rating . ' out of 5">'
        . str_repeat('&#9733;', $rating)
        . '<span class="stars-empty">' . str_repeat('&#9733;', 5 - $rating) . '</span></span>'
        . ' <span class="rating-number">(' . $rating . '/5)</span>';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Feedback Details - UniBee</title>

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

        <h2 class="registration-title">Feedback Details</h2>

        <div class="registration-card feedback-detail">

            <!-- Feedback Information -->
            <div class="detail-section">
                <h3 class="detail-section-title">Feedback Information</h3>

                <div class="detail-row">
                    <span class="detail-label">Feedback ID:</span>
                    <span class="detail-value">FB-<?php echo str_pad($feedback['id'], 4, '0', STR_PAD_LEFT); ?></span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">University:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($feedback['universityName']); ?></span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Category:</span>
                    <span class="detail-value"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $feedback['category']))); ?></span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Rating:</span>
                    <span class="detail-value"><?php echo renderStars($feedback['rating']); ?></span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Date Submitted:</span>
                    <span class="detail-value"><?php echo date('d F Y, g:i A', strtotime($feedback['createdAt'])); ?></span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Status:</span>
                    <span class="detail-value">
                        <span class="status-badge <?php echo $statusClasses[$status] ?? ''; ?>">
                            <?php echo $statusLabels[$status] ?? htmlspecialchars($status); ?>
                        </span>
                    </span>
                </div>
            </div>

            <!-- Submitted By -->
            <div class="detail-section">
                <h3 class="detail-section-title">Submitted By</h3>

                <div class="detail-row">
                    <span class="detail-label">Name:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($feedback['fullName']); ?></span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Role:</span>
                    <span class="detail-value"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $feedback['role']))); ?></span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Email:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($feedback['email']); ?></span>
                </div>
            </div>

            <!-- Message -->
            <div class="detail-section">
                <h3 class="detail-section-title">Feedback Message</h3>
                <div class="feedback-message-box">
                    <?php echo nl2br(htmlspecialchars(trim($feedback['message'] ?? '')) ?: 'No message provided.'); ?>
                </div>
            </div>

            <div class="registration-actions">

                <a href="reviewFeedbackPage.php" class="btn-large back-btn">Back</a>

                <?php if ($status === 'new'): ?>
                    <button type="button" class="btn-large update-btn"
                        onclick="openStatusModal('in_review', 'Start reviewing this feedback?')">Start Review</button>
                    <button type="button" class="btn-large approve-btn"
                        onclick="openStatusModal('resolved', 'Mark this feedback as resolved?')">Mark as Resolved</button>

                <?php elseif ($status === 'in_review'): ?>
                    <button type="button" class="btn-large approve-btn"
                        onclick="openStatusModal('resolved', 'Mark this feedback as resolved?')">Mark as Resolved</button>

                <?php else: ?>
                    <button type="button" class="btn-large update-btn"
                        onclick="openStatusModal('in_review', 'Reopen this feedback for review?')">Reopen</button>
                <?php endif; ?>

            </div>

        </div>

    </main>

    <!-- Confirm status change pop-up -->
    <div id="statusModal" class="modal-overlay">
        <div class="modal-box modal-large">
            <button type="button" class="modal-close" onclick="closeModal('statusModal')">&times;</button>

            <p class="modal-title" id="statusModalTitle">Are you sure?</p>

            <form id="statusForm" action="../controller/reviewFeedbackController.php" method="POST">
                <input type="hidden" name="feedback_id" value="<?php echo $feedback['id']; ?>">
                <input type="hidden" name="new_status" id="newStatusInput" value="">
            </form>

            <div class="modal-actions">
                <button type="button" class="modal-btn modal-cancel" onclick="closeModal('statusModal')">Cancel</button>
                <button type="button" class="modal-btn modal-confirm-approve"
                    onclick="document.getElementById('statusForm').submit();">Confirm</button>
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

        // One shared pop-up: each button tells it which status to set and what to ask
        function openStatusModal(newStatus, question) {
            document.getElementById('newStatusInput').value = newStatus;
            document.getElementById('statusModalTitle').textContent = question;
            openModal('statusModal');
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