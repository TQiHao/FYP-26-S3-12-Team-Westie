<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: loginPage.php");
    exit();
}

require_once "../controller/viewMyBookingController.php";

$controller = new ViewMyBookingController();
$bookings = $controller->getMyBookings($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View My Booking - UniBee</title>
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

    <!-- Facilities Nav Tabs -->
    <nav class="facilities-nav">
        <a href="ViewFacilitiesPage.php" class="facilities-tab">View Available Facilities</a>
        <a href="FacilityLocationTrackingPage.php" class="facilities-tab">Facility Location Tracking</a>
        <a href="bookFacilityPage.php" class="facilities-tab">Book Facilities</a>
        <a href="viewMyBookingPage.php" class="facilities-tab active">View My Booking</a>
        <a href="CancelMyBookingPage.php" class="facilities-tab">Cancel My Booking</a>
    </nav>

    <!-- Main Content -->
    <main class="dashboard">

        <div class="booking-header">
            <h2 class="section-label">My Bookings</h2>
        </div>

        <?php if ($bookings === false): ?>
            <!-- Alt flow: unable to load -->
            <p class="error-message">Unable to load bookings, please try again later.</p>

        <?php elseif (empty($bookings)): ?>
            <!-- Alt flow: no bookings -->
            <p class="no-notifications">You have no facility bookings.</p>

        <?php else: ?>

            <div class="booking-list">

                <?php foreach ($bookings as $booking): ?>

                    <?php
                    // Determine status badge class
                    $statusClass = 'status-pending';
                    $statusLabel = ucfirst($booking['status']);

                    if ($booking['status'] === 'confirmed') {
                        $statusClass = 'status-confirmed';
                    } elseif ($booking['status'] === 'cancelled') {
                        $statusClass = 'status-cancelled';
                    } elseif ($booking['status'] === 'completed') {
                        $statusClass = 'status-completed';
                    }
                    ?>

                    <div class="booking-item">

                        <div class="booking-info">
                            <h3><?php echo htmlspecialchars($booking['facilityName']); ?></h3>
                            <p class="booking-meta">
                                <?php echo htmlspecialchars(date('D, d M Y', strtotime($booking['bookingDate']))); ?>
                                · <?php echo htmlspecialchars(date('g:i A', strtotime($booking['startTime']))); ?>
                                – <?php echo htmlspecialchars(date('g:i A', strtotime($booking['endTime']))); ?>
                            </p>
                            <p class="booking-purpose">
                                Purpose: <?php echo htmlspecialchars($booking['purpose']); ?>
                            </p>
                        </div>

                        <div class="booking-status">
                            <span class="status-badge <?php echo $statusClass; ?>">
                                <?php echo $statusLabel; ?>
                            </span>
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