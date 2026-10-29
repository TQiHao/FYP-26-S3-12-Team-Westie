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

require_once "../controller/systemLogsController.php";

$controller = new SystemLogsController();

$filters = [
    'severity' => $_GET['severity'] ?? '',
    'category' => $_GET['category'] ?? '',
    'dateFrom' => $_GET['dateFrom'] ?? '',
    'dateTo'   => $_GET['dateTo'] ?? '',
    'search'   => $_GET['search'] ?? '',
];
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;

$categories = $controller->getCategories();
$result     = $controller->getLogs($filters, $page);

$logRows    = $result['rows'];
$page       = $result['page'];
$totalPages = $result['totalPages'];
$totalRows  = $result['total'];

$hasFilters = false;
foreach ($filters as $value) {
    if (trim($value) !== '') {
        $hasFilters = true;
    }
}

function pageUrl($filters, $page)
{
    $query = array_filter($filters, function ($value) {
        return trim($value) !== '';
    });
    $query['page'] = $page;
    return 'viewSystemLogsPage.php?' . http_build_query($query);
}

function prettyCategory($category)
{
    return ucwords(str_replace('_', ' ', (string) $category));
}

$severityLabels  = ['info' => 'Info', 'warning' => 'Warning', 'error' => 'Error'];
$severityClasses = ['info' => 'status-new', 'warning' => 'status-pending', 'error' => 'status-suspended'];

$firstShown = $totalRows === 0 ? 0 : (($page - 1) * SystemLogsController::PER_PAGE) + 1;
$lastShown  = $firstShown === 0 ? 0 : $firstShown + count($logRows) - 1;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>System Logs - UniBee</title>

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
                    <a href="../controller/logoutController.php">Log Out</a>
                </div>

            </div>
        </div>

    </header>

    <main class="admin-table-page feedback-page">

        <a href="systemOperationPage.php" class="admin-edit-back">&#8249; Back</a>

        <h3 class="section-label">System Logs</h3>

        <form method="GET" action="viewSystemLogsPage.php" class="feedback-filters">

            <input type="text" name="search" class="filter-search"
                placeholder="Search action or description"
                value="<?php echo htmlspecialchars($filters['search']); ?>">

            <select name="severity">
                <option value="">All Severities</option>
                <?php foreach ($severityLabels as $value => $label): ?>
                    <option value="<?php echo $value; ?>" <?php echo $filters['severity'] === $value ? 'selected' : ''; ?>>
                        <?php echo $label; ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="category">
                <option value="">All Categories</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo htmlspecialchars($category); ?>"
                        <?php echo $filters['category'] === $category ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars(prettyCategory($category)); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label class="filter-date">From
                <input type="date" name="dateFrom" value="<?php echo htmlspecialchars($filters['dateFrom']); ?>">
            </label>

            <label class="filter-date">To
                <input type="date" name="dateTo" value="<?php echo htmlspecialchars($filters['dateTo']); ?>">
            </label>

            <button type="submit" class="filter-btn">Filter</button>

            <?php if ($hasFilters): ?>
                <a href="viewSystemLogsPage.php" class="filter-reset">Reset</a>
            <?php endif; ?>
        </form>

        <section class="table-section">
            <div class="table-wrapper">
                <table class="admin-table logs-table">
                    <thead>
                        <tr>
                            <th>LID</th>
                            <th>Date &amp; Time</th>
                            <th>Category</th>
                            <th>Severity</th>
                            <th>Action</th>
                            <th>Description</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (empty($logRows)): ?>
                            <tr>
                                <td colspan="6" class="empty-row">
                                    <?php echo $hasFilters ? 'No logs match your filters.' : 'No system logs have been recorded yet.'; ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logRows as $row): ?>
                                <tr>
                                    <td>LOG-<?php echo str_pad($row['id'], 4, '0', STR_PAD_LEFT); ?></td>
                                    <td class="wrap-cell">
                                        <?php echo date('d M Y', strtotime($row['createdAt'])); ?>
                                        <span class="cell-sub"><?php echo date('g:i:s A', strtotime($row['createdAt'])); ?></span>
                                    </td>
                                    <td class="wrap-cell"><?php echo htmlspecialchars(prettyCategory($row['category'])); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $severityClasses[$row['severity']] ?? ''; ?>">
                                            <?php echo $severityLabels[$row['severity']] ?? htmlspecialchars((string) $row['severity']); ?>
                                        </span>
                                    </td>
                                    <td class="wrap-cell"><?php echo htmlspecialchars((string) $row['action']); ?></td>
                                    <td class="wrap-cell message-cell"><?php echo htmlspecialchars((string) $row['description']); ?></td>
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