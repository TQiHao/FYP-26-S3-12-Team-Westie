<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: loginPage.php");
    exit();
}

require_once "../controller/teachingController.php";

$controller = new TeachingController();
$staffId = $_SESSION['user_id'] ?? null;

$entries = $controller->getTimetable($staffId);

// Build a lookup: [day][hour] = entry
$grid = [
    'mon' => [],
    'tue' => [],
    'wed' => [],
    'thu' => [],
    'fri' => [],
    'sat' => [],
    'sun' => []
];

if (is_array($entries)) {
    foreach ($entries as $e) {
        $day = strtolower($e->getDayOfWeek());
        $hour = (int) date('G', strtotime($e->getStartTime()));
        if (isset($grid[$day])) {
            $grid[$day][$hour] = $e;
        }
    }
}

$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
$keys = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
$hours = range(8, 22); // 8am to 10pm
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personal Timetable - UniBee</title>
    <link rel="stylesheet" href="../style.css">
</head>

<body>
    <script src="../script.js"></script>

    <header class="header">
        <div class="logo-container">
            <img src="../images/uniBeeLogo.png" alt="UniBee Logo">
        </div>
        <div class="welcome-message">
            <span>Welcome back,</span>
            <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong>
        </div>
        <div class="dashboard-header-right">
            <a href="notificationPage.php" class="header-icon">
                <img src="../images/notification.png" alt="Notifications">
            </a>
            <div class="profile-dropdown">
                <div class="profile-container" onclick="toggleDropdown()">
                    <img src="../images/profilePic.png" alt="Profile" class="profile-icon">
                    <img src="../images/dropdown.png" alt="Menu" class="dropdown-arrow">
                </div>
                <div id="profileMenu" class="dropdown-menu">
                    <a href="manageProfilePage.php">Manage Profile</a>
                    <a href="teachingPage.php">Teaching</a>
                    <a href="campusEventsPage.php">University Campus Events</a>
                    <a href="submitFeedbackPage.php">Submit Feedback</a>
                    <a href="../controller/logOutController.php">Log Out</a>
                </div>
            </div>
        </div>
    </header>

    <main class="dashboard">

        <div class="profile-header">
            <a href="TeachingPage.php" class="btn-back">&#8592; Back</a>
            <h2 class="section-label">Personal Timetable</h2>
            <div></div>
        </div>

        <?php if ($entries === false): ?>
            <!-- Error flow -->
            <p class="error-message">Unable to retrieve timetable. Please try again later.</p>

        <?php elseif ($entries === null): ?>
            <!-- Not found flow -->
            <p class="no-notifications">No timetable found.</p>

        <?php else: ?>
            <!-- Normal flow -->
            <table class="timetable-grid">
                <thead>
                    <tr>
                        <th></th>
                        <?php foreach ($days as $d): ?>
                            <th><?php echo $d; ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($hours as $h): ?>
                        <tr>
                            <th><?php echo date('ga', mktime($h, 0, 0)); ?></th>
                            <?php foreach ($keys as $k): ?>
                                <td>
                                    <?php if (isset($grid[$k][$h])): ?>
                                        <?php $e = $grid[$k][$h]; ?>
                                        <div class="timetable-slot">
                                            <?php echo htmlspecialchars($e->getTitle()); ?><br>
                                            <?php echo htmlspecialchars($e->getLocation()); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

    </main>

    <footer>
        <div class="footer-bottom-bar">
            &copy; 2026 UniBee. All rights reserved.
        </div>
    </footer>

</body>

</html>