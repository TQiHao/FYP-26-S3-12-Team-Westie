<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: loginPage.php");
    exit();
}

require_once "../controller/ViewGymSlotController.php";

$controller = new ViewGymSlotController();
$gymSlots = $controller->getAvailableGymSlots($_SESSION['university_id']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Gym Slots - UniBee</title>
    <link rel="stylesheet" href="../style.css">
</head>

<body>
    <script src="../script.js"></script>

    <!-- Header -->
    <header class="header">
        <div class="logo-container">
            <a href="studentDashboardPage.php">
            <img src="../images/uniBeeLogo.png" alt="UniBee Logo">
            </a>
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
                    <a href="ManageProfilePage.php">Manage Profile</a>
                    <a href="AIChatbotPage.php">AI Chatbot</a>
                    <a href="AcademicsPage.php">Academics</a>
                    <a href="ViewFacilitiesPage.php">Facility Booking</a>
                    <a href="CampusEventsPage.php">University Campus Event</a>
                    <a href="../controller/logoutController.php">Log Out</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="dashboard">

        <!-- Back Button + Date/Time Filters (same row) -->
        <div class="studyroom-top-bar">

            <a href="ViewFacilitiesPage.php" class="studyroom-back-btn">&#8592; Back</a>

            <div class="facility-filters">
                <div class="filter-item">
                    <img src="../images/calendar.png" alt="Calendar" class="filter-icon">
                    <span><?php echo date('d M'); ?></span>
                </div>
                <div class="filter-item">
                    <img src="../images/clock.png" alt="Clock" class="filter-icon">
                    <span>2:00pm</span>
                </div>
            </div>

        </div>

        <!-- Gym Slots List -->
        <?php if ($gymSlots === false): ?>
            <p class="error-message">Unable to load gym slot information, please try again.</p>

        <?php elseif (empty($gymSlots)): ?>
            <p class="no-notifications">No gym slots are currently available.</p>

        <?php else: ?>

            <div class="facility-list">

                <?php foreach ($gymSlots as $slot): ?>

                    <div class="facility-list-item">

                        <div class="facility-list-info">
                            <h3><?php echo htmlspecialchars($slot['name']); ?></h3>
                            <p>
                                <?php echo htmlspecialchars($slot['location'] ?? ''); ?>
                                <?php if (!empty($slot['capacity'])): ?>
                                    · Capacity <?php echo htmlspecialchars($slot['capacity']); ?>
                                <?php endif; ?>
                            </p>
                        </div>

                        <div class="facility-status">
                            <span class="status-badge available">Available</span>
                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </main>

    <!-- Footer -->
    <footer>
        <div class="footer-bottom-bar">
            &copy; 2026 UniBee. All rights reserved.
        </div>
    </footer>
</body>
</html>