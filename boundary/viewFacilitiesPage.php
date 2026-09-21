<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: loginPage.php");
    exit();
}

require_once "../controller/ViewFacilitiesController.php";

$controller = new ViewFacilitiesController();
$facilities = $controller->getAvailableFacilities($_SESSION['university_id']);

// Group facilities by type
$groupedFacilities = [];
if ($facilities !== false && !empty($facilities)) {
    foreach ($facilities as $facility) {
        $type = ucwords(str_replace('_', ' ', $facility['type']));
        $groupedFacilities[$type][] = $facility;
    }
}

// Map type to detail page
function getDetailPage($type) {
    $map = [
        'study room' => 'ViewStudyRoomPage.php',
        'gym'        => 'ViewGymSlotPage.php',
    ];
    return $map[strtolower($type)] ?? 'ViewFacilitiesPage.php';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Available Facilities - UniBee</title>
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
        <a href="ViewFacilitiesPage.php" class="facilities-tab active">View Available Facilities</a>
        <a href="FacilityLocationTrackingPage.php" class="facilities-tab">Facility Location Tracking</a>
        <a href="BookFacilityPage.php" class="facilities-tab">Book Facilities</a>
        <a href="ViewMyBookingPage.php" class="facilities-tab">View My Booking</a>
        <a href="CancelMyBookingPage.php" class="facilities-tab">Cancel My Booking</a>
    </nav>

    <!-- Main Content -->
    <main class="dashboard">

        <?php if ($facilities === false): ?>
            <p class="error-message">Unable to load facilities information, please try again.</p>

        <?php elseif (empty($facilities)): ?>
            <p class="no-notifications">No facilities are currently available.</p>

        <?php else: ?>
            <div class="facilities-grid">

                <?php foreach ($groupedFacilities as $type => $items): ?>
                    <?php
                    // Get the raw type from the first item
                    $rawType = $items[0]['type'];
                    ?>

                    <a href="<?php echo getDetailPage($rawType); ?>" class="facility-card-link">
                        <div class="facility-card">
                            <div class="facility-card-header">
                                <img src="../images/facilityBooking.png" alt="Icon" class="facility-icon">
                                <div>
                                    <h3><?php echo htmlspecialchars($type); ?></h3>
                                    <p><?php echo count($items); ?> available now</p>
                                </div>
                            </div>
                        </div>
                    </a>

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