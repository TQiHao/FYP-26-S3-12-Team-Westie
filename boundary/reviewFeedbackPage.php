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

$controller = new ReviewFeedbackController();

$filters = [
    'status'     => $_GET['status'] ?? '',
    'rating'     => $_GET['rating'] ?? '',
    'university' => $_GET['university'] ?? '',
    'search'     => $_GET['search'] ?? '',
];
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;

$summary      = $controller->getSummary();
$universities = $controller->getUniversitiesForFilter();
$result       = $controller->getFeedbackList($filters, $page);

$feedbackRows = $result['rows'];
$page         = $result['page'];
$totalPages   = $result['totalPages'];
$totalRows    = $result['total'];

$hasFilters = ($filters['status'] !== '' || $filters['rating'] !== '' ||
    $filters['university'] !== '' || trim($filters['search']) !== '');

function pageUrl($filters, $page)
{
    $query = array_filter($filters, function ($value) {
        return trim($value) !== '';
    });
    $query['page'] = $page;
    return 'reviewFeedbackPage.php?' . http_build_query($query);
}

function renderStars($rating)
{
    $rating = (int) $rating;
    return '<span class="stars" aria-label="' . $rating . ' out of 5">'
        . str_repeat('&#9733;', $rating)
        . '<span class="stars-empty">' . str_repeat('&#9733;', 5 - $rating) . '</span></span>';
}

$statusLabels  = ['new' => 'New', 'in_review' => 'In Review', 'resolved' => 'Resolved'];
$statusClasses = ['new' => 'status-new', 'in_review' => 'status-pending', 'resolved' => 'status-active'];

$firstShown = $totalRows === 0 ? 0 : (($page - 1) * ReviewFeedbackController::PER_PAGE) + 1;
$lastShown  = $firstShown === 0 ? 0 : $firstShown + count($feedbackRows) - 1;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Review Feedback - UniBee</title>

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

        <a href="systemAdminDashboardPage.php" class="admin-edit-back">&#8249; Back</a>

        <h3 class="section-label">Review Feedback</h3>

        <div class="feedback-summary">
            <a href="reviewFeedbackPage.php" class="summary-card">
                <span class="summary-number"><?php echo (int) $summary['total']; ?></span>
                <span class="summary-label">Total Feedback</span>
            </a>
            <a href="reviewFeedbackPage.php?status=new" class="summary-card summary-new">
                <span class="summary-number"><?php echo (int) $summary['newCount']; ?></span>
                <span class="summary-label">New</span>
            </a>
            <a href="reviewFeedbackPage.php?status=in_review" class="summary-card summary-review">
                <span class="summary-number"><?php echo (int) $summary['inReviewCount']; ?></span>
                <span class="summary-label">In Review</span>
            </a>
            <a href="reviewFeedbackPage.php?status=resolved" class="summary-card summary-resolved">
                <span class="summary-number"><?php echo (int) $summary['resolvedCount']; ?></span>
                <span class="summary-label">Resolved</span>
            </a>
            <div class="summary-card summary-static">
                <span class="summary-number"><?php echo number_format((float) $summary['avgRating'], 1); ?> <span class="stars">&#9733;</span></span>
                <span class="summary-label">Average Rating</span>
            </div>
        </div>

        <form method="GET" action="reviewFeedbackPage.php" class="feedback-filters">

            <input type="text" name="search" class="filter-search"
                placeholder="Search message or name"
                value="<?php echo htmlspecialchars($filters['search']); ?>">

            <select name="status">
                <option value="">All Statuses</option>
                <?php foreach ($statusLabels as $value => $label): ?>
                    <option value="<?php echo $value; ?>" <?php echo $filters['status'] === $value ? 'selected' : ''; ?>>
                        <?php echo $label; ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="rating">
                <option value="">All Ratings</option>
                <?php for ($r = 5; $r >= 1; $r--): ?>
                    <option value="<?php echo $r; ?>" <?php echo (string) $filters['rating'] === (string) $r ? 'selected' : ''; ?>>
                        <?php echo $r; ?> star<?php echo $r > 1 ? 's' : ''; ?>
                    </option>
                <?php endfor; ?>
            </select>

            <select name="university">
                <option value="">All Universities</option>
                <?php foreach ($universities as $uni): ?>
                    <option value="<?php echo $uni['id']; ?>" <?php echo (string) $filters['university'] === (string) $uni['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($uni['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="filter-btn">Filter</button>

            <?php if ($hasFilters): ?>
                <a href="reviewFeedbackPage.php" class="filter-reset">Reset</a>
            <?php endif; ?>
        </form>

        <!-- Feedback table -->
        <section class="table-section">
            <div class="table-wrapper">
                <table class="admin-table feedback-table">
                    <thead>
                        <tr>
                            <th>FID</th>
                            <th>University</th>
                            <th>Submitted By</th>
                            <th>Rating</th>
                            <th>Message</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (empty($feedbackRows)): ?>
                            <tr>
                                <td colspan="8" class="empty-row">
                                    <?php echo $hasFilters ? 'No feedback matches your filters.' : 'No feedback has been submitted yet.'; ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($feedbackRows as $row): ?>
                                <?php
                                $message = trim($row['message'] ?? '');
                                $preview = mb_strlen($message) > 70 ? mb_substr($message, 0, 70) . '...' : $message;
                                ?>
                                <tr>
                                    <td>FB-<?php echo str_pad($row['id'], 4, '0', STR_PAD_LEFT); ?></td>
                                    <td class="wrap-cell"><?php echo htmlspecialchars($row['universityName']); ?></td>
                                    <td class="wrap-cell">
                                        <?php echo htmlspecialchars($row['fullName']); ?>
                                        <span class="cell-sub"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $row['role']))); ?></span>
                                    </td>
                                    <td><?php echo renderStars($row['rating']); ?></td>
                                    <td class="wrap-cell message-cell"><?php echo $preview !== '' ? htmlspecialchars($preview) : '—'; ?></td>
                                    <td><?php echo date('d M Y', strtotime($row['createdAt'])); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $statusClasses[$row['status']] ?? ''; ?>">
                                            <?php echo $statusLabels[$row['status']] ?? htmlspecialchars($row['status']); ?>
                                        </span>
                                    </td>
                                    <td class="action-cell">
                                        <a href="viewFeedbackPage.php?id=<?php echo $row['id']; ?>" class="action-btn view-btn">View</a>
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
                            <a href="<?php echo htmlspecialchars(pageUrl($filters, $page - 1)); ?>">&lsaquo; Prev</a>
                        <?php endif; ?>

                        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                            <a href="<?php echo htmlspecialchars(pageUrl($filters, $p)); ?>"
                                class="<?php echo $p === $page ? 'current' : ''; ?>"><?php echo $p; ?></a>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <a href="<?php echo htmlspecialchars(pageUrl($filters, $page + 1)); ?>">Next &rsaquo;</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>

    </main>

    <footer>
        <div class="footer-bottom-bar">
            &copy; 2026 UniBee. All rights reserved.
        </div>
    </footer>

</body>

</html>