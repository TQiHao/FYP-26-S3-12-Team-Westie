<?php
session_start();

$selectedType = $_GET['type'] ?? ''; // 'studyroom' or 'gym'

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    $_SESSION['login_error'] = "Session expired. Please log in again.";
    header("Location: loginPage.php");
    exit();
}

require_once "../controller/bookFacilityController.php";

$controller = new BookFacilityController();

// Load both study rooms and gym slots for the sidebar
$studyRooms = $controller->getBookableFacilitiesByName($_SESSION['university_id'], 'study room');
$gymSlots = $controller->getBookableFacilitiesByName($_SESSION['university_id'], 'gym');

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
                    <a href="aiChatbotPage.php">AI Chatbot</a>
                    <a href="academicsPage.php">Academics</a>
                    <a href="viewFacilitiesPage.php">Facility Booking</a>
                    <a href="viewEventsPage.php">University Campus Event</a>
                    <a href="../controller/logOutController.php">Log Out</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Facilities Nav Tabs -->
    <nav class="facilities-nav">
        <a href="ViewFacilitiesPage.php" class="facilities-tab active">View Available Facilities</a>
        <a href="FacilityLocationTrackingPage.php" class="facilities-tab">Facility Location Tracking</a>
        <a href="ViewMyBookingPage.php" class="facilities-tab">View My Booking</a>
    </nav>

    <!-- Main Content -->
    <main class="dashboard">

        <div class="facility-back-bar facility-back-bar--wide">
            <a href="ViewFacilitiesPage.php" class="btn-back">&#8592; Back</a>
        </div>

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
                    <?php if ($selectedType === '' || $selectedType === 'studyroom'): ?>
                        
                        <h3 style="<?php echo ($selectedType === 'studyroom') ? 'color: #f5c400;' : ''; ?>">Study Rooms</h3>
                        
                        <?php if ($studyRooms && !empty($studyRooms)): ?>
                            <?php foreach ($studyRooms as $sr): ?>
                                <a href="bookFacilityPage.php?type=studyroom&facilityId=<?php echo urlencode($sr['bookableFacilityId']); ?>"
                                    class="book-sidebar-item <?php echo (isset($_GET['facilityId']) && $_GET['facilityId'] == $sr['bookableFacilityId']) ? 'active' : ''; ?>">
                                    <?php echo htmlspecialchars($sr['name']); ?>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="book-empty">No study rooms available.</p>
                        <?php endif; ?>

                    <?php endif; ?>

                    <!-- Gym Slots -->
                    <?php if ($selectedType === '' || $selectedType === 'gym'): ?>
                        
                        <h3 style="<?php echo ($selectedType === 'gym') ? 'color: #f5c400;' : ''; ?>">Gym Slots</h3>
                        
                        <?php if ($gymSlots && !empty($gymSlots)): ?>
                        <?php foreach ($gymSlots as $gs): ?>
                            <a href="bookFacilityPage.php?type=gym&facilityId=<?php echo urlencode($gs['bookableFacilityId']); ?>"
                                class="book-sidebar-item <?php echo (isset($_GET['facilityId']) && $_GET['facilityId'] == $gs['bookableFacilityId']) ? 'active' : ''; ?>">
                                <?php echo htmlspecialchars($gs['name']); ?>
                            </a>
                        <?php endforeach; ?>
                        <?php else: ?>
                            <p class="book-empty">No gym slots available.</p>
                        <?php endif; ?>

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

                            <input type="hidden" name="facilityId" value="<?php echo htmlspecialchars($_GET['facilityId'] ?? ''); ?>">
                            <input type="hidden" name="type" value="<?php echo htmlspecialchars($selectedType); ?>">
                            <input type="hidden" id="slotDuration"
                                value="<?php echo (int) ($selectedFacility['slotDuration'] ?? 60); ?>">

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
                                <a href="BookFacilityPage.php?type=<?php echo urlencode($selectedType); ?>" class="btn-cancel">Cancel</a>
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
        document.addEventListener("DOMContentLoaded", function () {
            var startTime = document.getElementById("startTime");
            var endTime = document.getElementById("endTime");
            var slotDurationEl = document.getElementById("slotDuration");

            if (!startTime || !endTime) return;

            var slotDuration = slotDurationEl ? (parseInt(slotDurationEl.value, 10) || 60) : 60;

            function formatTime(totalMinutes) {
                var hours = Math.floor(totalMinutes / 60);
                var minutes = totalMinutes % 60;
                return (hours < 10 ? "0" + hours : hours) + ":" + (minutes < 10 ? "0" + minutes : minutes);
            }

            startTime.addEventListener("change", function () {
                if (!this.value) {
                    endTime.value = "";
                    return;
                }
                var parts = this.value.split(":");
                var startMinutes = parseInt(parts[0], 10) * 60 + parseInt(parts[1], 10);
                var endMinutes = startMinutes + slotDuration;

                if (endMinutes >= 24 * 60) {
                    endMinutes = 23 * 60 + 59;
                }

                endTime.value = formatTime(endMinutes);
            });
        });
    </script>

</body>

</html>