<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: loginPage.php");
    exit();
}

require_once "../controller/teachingController.php";

$controller = new TeachingController();
$staffId = $_SESSION['user_id'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assigned Modules - UniBee</title>
    <link rel="stylesheet" href="../style.css">
</head>

<body>
    <script src="../script.js"></script>

    <!-- Header -->
    <header class="header">
        <div class="logo-container">
            <img src="../images/uniBeeLogo.png" alt="UniBee Logo">
        </div>

        <div class="welcome-message">
            <span>Welcome back,</span>
            <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong>
        </div>

        <div class="dashboard-header-right">
            <a href="NotificationPage.php" class="header-icon">
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
            <a href="teachingPage.php" class="btn-back">&#8592; Back</a>
            <h2 class="section-label">Teaching</h2>
            <div></div>
        </div>

        <!-- Tabs -->
        <div class="teaching-tabs">
            <a href="teachingPage.php?tab=timetable" class="teaching-tab">
                Personal Timetable
            </a>
            <a href="viewAssignedModules.php" class="teaching-tab active">
                Assigned Modules
            </a>
        </div>

        <!-- Assigned Modules Content -->
        <?php
        $modules = $controller->getAssignedModules($staffId);

        if ($modules === false) {
            echo '<p class="error-message">Unable to retrieve assigned modules. Please try again later.</p>';
        } elseif ($modules === null || empty($modules)) {
            echo '<p class="no-notifications">You are not assigned in any modules for the current semester.</p>';
        } else {
            $semesters = array_unique(array_column($modules, 'semester'));
            $semesterLabel = implode(', ', $semesters);
            $academicYear = $modules[0]['academicYear'] ?? '';
            ?>
            <p class="modules-meta">
                Semester <?php echo htmlspecialchars($semesterLabel ?: '-'); ?>,
                <?php echo htmlspecialchars($academicYear); ?>
                &middot; <?php echo count($modules); ?> course<?php echo count($modules) === 1 ? '' : 's'; ?>
            </p>

            <ul class="assigned-modules-list">
                <?php foreach ($modules as $m): ?>
                    <li class="module-card">
                        <div>
                            <h3><?php echo htmlspecialchars($m['moduleCode'] . ' – ' . $m['moduleName']); ?></h3>
                            <p>
                                <?php echo htmlspecialchars($_SESSION['user_name']); ?>,
                                <?php echo htmlspecialchars(ucfirst(substr($m['dayOfWeek'], 0, 3))); ?>
                                <?php echo htmlspecialchars(date('g:ia', strtotime($m['startTime']))); ?> -
                                <?php echo htmlspecialchars(date('g:ia', strtotime($m['endTime']))); ?>
                            </p>
                        </div>
                        <span class="module-room">
                            Room <?php echo htmlspecialchars($m['room']); ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php } ?>

    </main>

    <footer>
        <div class="footer-bottom-bar">
            &copy; 2026 UniBee. All rights reserved.
        </div>
    </footer>

</body>

</html>