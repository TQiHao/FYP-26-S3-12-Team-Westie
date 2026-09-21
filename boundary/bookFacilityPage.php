<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: loginPage.php");
    exit();
}

require_once "../controller/BookFacilityController.php";

$controller = new BookFacilityController();

// Load both study rooms and gym slots for the sidebar
$studyRooms = $controller->getBookableFacilitiesByName($_SESSION['university_id'], 'Study Room');
$gymSlots   = $controller->getBookableFacilitiesByName($_SESSION['university_id'], 'Gym');

// If a facility is selected, load the booking form
$selectedFacility = null;
if (isset($_GET['facilityId'])) {
    $selectedFacility = $controller->getBookingForm($_GET['facilityId']);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Facilities - UniBee</title>
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
        <a href="BookFacilityPage.php" class="facilities-tab active">Book Facilities</a>
        <a href="ViewMyBookingPage.php" class="facilities-tab">View My Booking</a>
        <a href="CancelMyBookingPage.php" class="facilities-tab">Cancel My Booking</a>
    </nav>

    <!-- Main Content -->
    <main class="dashboard">

        <?php
        if (isset($_SESSION['booking_success'])) {
            echo '<div class="success-message">' . $_SESSION['booking_success'] . '</div>';
            unset($_SESSION['booking_success']);
        }
        if (isset($_SESSION['booking_error'])) {
            echo '<div class="error-message">' . $_SESSION['booking_error'] . '</div>';
            unset($_SESSION['booking_error']);
        }
        ?>

        <?php if ($studyRooms === false && $gymSlots === false): ?>
            <p class="error-message">Unable to load facilities, please try again.</p>

        <?php else: ?>

            <div class="book-layout">

                <!-- LEFT SIDEBAR -->
                <aside class="book-sidebar">

                    <!-- Study Rooms -->
                    <h3>Study Rooms</h3>
                    <?php if ($studyRooms && !empty($studyRooms)): ?>
                        <?php foreach ($studyRooms as $sr): ?>
                            <a href="bookFacilityPage.php?facilityId=<?php echo urlencode($sr['facilityId']); ?>"
                               class="book-sidebar-item <?php echo (isset($_GET['facilityId']) && $_GET['facilityId'] == $sr['facilityId']) ? 'active' : ''; ?>">
                                <?php echo htmlspecialchars($sr['name']); ?>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="book-empty">No study rooms available.</p>
                    <?php endif; ?>

                    <!-- Gym Slots -->
                    <h3>Gym Slots</h3>
                    <?php if ($gymSlots && !empty($gymSlots)): ?>
                        <?php foreach ($gymSlots as $gs): ?>
                            <a href="BookGymSlotPage.php?facilityId=<?php echo urlencode($gs['facilityId']); ?>"
                               class="book-sidebar-item">
                                <?php echo htmlspecialchars($gs['name']); ?>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="book-empty">No gym slots available.</p>
                    <?php endif; ?>

                </aside>

                <!-- RIGHT PANEL -->
                <section class="book-panel">

                    <?php if ($selectedFacility): ?>

                        <h2><?php echo htmlspecialchars($selectedFacility['name']); ?></h2>
                        <p class="book-subtitle">
                            <?php echo htmlspecialchars($selectedFacility['location'] ?? ''); ?>
                            · Capacity <?php echo htmlspecialchars($selectedFacility['capacity'] ?? ''); ?>
                        </p>

                        <form action="../controller/BookFacilityController.php" method="POST" class="book-form">

                            <input type="hidden" name="facilityId" value="<?php echo htmlspecialchars($selectedFacility['facilityId']); ?>">

                            <div class="book-form-row">
                                <div class="form-group">
                                    <label for="bookingDate">Date</label>
                                    <input type="date" id="bookingDate" name="bookingDate" required>
                                </div>

                                <div class="form-group">
                                    <label for="startTime">Time slot</label>
                                    <input type="time" id="startTime" name="startTime" required>
                                    <input type="hidden" id="endTime" name="endTime">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="purpose">Purpose</label>
                                <input type="text" id="purpose" name="purpose" placeholder="Enter purpose" required>
                            </div>

                            <div class="book-form-actions">
                                <button type="submit" name="confirm_booking" class="btn-confirm">Confirm Booking</button>
                                <a href="BookFacilityPage.php" class="btn-cancel">Cancel</a>
                            </div>

                        </form>

                    <?php else: ?>
                        <div class="book-placeholder">
                            <p>Select a facility from the left to begin booking.</p>
                        </div>
                    <?php endif; ?>

                </section>

            </div>

        <?php endif; ?>

    </main>

    <!-- Footer -->
    <footer>
        <div class="footer-bottom-bar">
            &copy; 2026 UniBee. All rights reserved.
        </div>
    </footer>

    <script>
        // Auto-fill endTime based on startTime + 1 hour
        document.addEventListener("DOMContentLoaded", function () {
            var startTime = document.getElementById("startTime");
            var endTime = document.getElementById("endTime");

            if (startTime && endTime) {
                startTime.addEventListener("change", function () {
                    if (this.value) {
                        var parts = this.value.split(":");
                        var hours = parseInt(parts[0]) + 1;
                        var minutes = parts[1];
                        var newTime = (hours < 10 ? "0" + hours : hours) + ":" + minutes;
                        endTime.value = newTime;
                    }
                });
            }
        });
    </script>

</body>
</html>