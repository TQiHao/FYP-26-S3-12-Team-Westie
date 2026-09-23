<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: loginPage.php");
    exit();
}

require_once "../controller/viewEventsController.php";

$controller = new ViewEventsController();
$events = $controller->getEventList($_SESSION['university_id']);

// If search results are set, use them; otherwise show all events
$eventsToDisplay = $_SESSION['search_results'] ?? $events;
unset($_SESSION['search_results']);

// Capture and clear the search keyword
$searchKeywordValue = $_SESSION['search_keyword'] ?? '';
$searchPerformed = !empty($searchKeywordValue) ? '1' : '0';
unset($_SESSION['search_keyword']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>University Campus Events - UniBee</title>
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
                    <a href="viewFacilitiesPage.php">Facility Booking</a>
                    <a href="viewEventsPage.php">University Campus Event</a>
                    <a href="../controller/logoutController.php">Log Out</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="dashboard">

        <h2 class="section-label" style="max-width: 900px; margin: 25px auto 15px auto;">Campus Event</h2>

        <!-- Search Bar (Server-side) -->
        <form action="../controller/searchEventController.php" method="GET" class="event-search-bar">
            <input type="text"
                   id="eventSearchInput"
                   name="keyword"
                   placeholder="Search events by name, date, or venue..."
                   value="<?php echo htmlspecialchars($searchKeywordValue); ?>">
            <button type="submit" name="search" class="btn-search">Search</button>
        </form>

        <!-- Hidden flag: 1 if a search was performed -->
        <input type="hidden" id="searchPerformed" value="<?php echo $searchPerformed; ?>">

        <?php
        // Show any search-related messages
        if (isset($_SESSION['search_error'])) {
            echo '<div class="error-message" style="max-width:900px;margin:0 auto 15px auto;">'
                . $_SESSION['search_error'] . '</div>';
            unset($_SESSION['search_error']);
        }
        if (isset($_SESSION['search_no_result'])) {
            echo '<div class="no-notifications" style="max-width:900px;margin:0 auto 15px auto;">'
                . $_SESSION['search_no_result'] . '</div>';
            unset($_SESSION['search_no_result']);
        }
        ?>

        <?php if ($eventsToDisplay === false): ?>
            <!-- Alt flow: unable to load -->
            <p class="error-message">Unable to retrieve events. Please try again later.</p>

        <?php elseif (empty($eventsToDisplay)): ?>
            <!-- Alt flow: no events -->
            <p class="no-notifications">No upcoming events available.</p>

        <?php else: ?>

            <div class="event-list">

                <?php foreach ($eventsToDisplay as $event): ?>

                    <?php
                    $startDate = strtotime($event['startDatetime']);
                    $endDate   = strtotime($event['endDatetime']);

                    $month = date('M', $startDate);
                    $day   = date('d', $startDate);
                    $time  = date('g:iA', $startDate) . ' - ' . date('g:iA', $endDate);

                    // Check if event is past
                    $isPast = $startDate < time();
                    ?>

                    <div class="event-item" data-title="<?php echo htmlspecialchars(strtolower($event['title'])); ?>">

                        <!-- Date badge -->
                        <div class="event-date-badge">
                            <span class="event-month"><?php echo $month; ?></span>
                            <span class="event-day"><?php echo $day; ?></span>
                        </div>

                        <!-- Event info -->
                        <div class="event-info">
                            <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                            <p><?php echo htmlspecialchars($time); ?> · <?php echo htmlspecialchars($event['location']); ?></p>
                        </div>

                        <!-- Action buttons -->
                        <div class="event-actions">

                            <a href="viewEventNavigationPage.php?eventId=<?php echo urlencode($event['id']); ?>"
                               class="btn-event-nav">
                                Navigate
                            </a>

                            <?php if ($isPast): ?>
                                <button class="btn-event-past" disabled>Past Event</button>
                            <?php else: ?>
                                <form action="../controller/registerEventController.php" method="POST" class="event-register-form">
                                    <input type="hidden" name="eventId" value="<?php echo htmlspecialchars($event['id']); ?>">
                                    <button type="submit" name="register_event" class="btn-event-register">Register</button>
                                </form>
                            <?php endif; ?>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </main>

    <!-- Success Modal -->
    <?php if (isset($_SESSION['register_success'])): ?>
        <div class="modal-overlay" id="successModal">
            <div class="modal-box">
                <span class="modal-close" onclick="closeModal()">&times;</span>
                <p class="modal-message"><?php echo htmlspecialchars($_SESSION['register_success']); ?></p>
            </div>
        </div>
        <?php unset($_SESSION['register_success']); ?>
    <?php endif; ?>

    <!-- Error Modal -->
    <?php if (isset($_SESSION['register_error'])): ?>
        <div class="modal-overlay" id="errorModal">
            <div class="modal-box">
                <span class="modal-close" onclick="closeErrorModal()">&times;</span>
                <p class="modal-message"><?php echo htmlspecialchars($_SESSION['register_error']); ?></p>
            </div>
        </div>
        <?php unset($_SESSION['register_error']); ?>
    <?php endif; ?>

    <!-- Footer -->
    <footer>
        <div class="footer-bottom-bar">
            &copy; 2026 UniBee. All rights reserved.
        </div>
    </footer>

    <script>
        // Modal close functions
        function closeModal() {
            var modal = document.getElementById("successModal");
            if (modal) modal.style.display = "none";
        }

        function closeErrorModal() {
            var modal = document.getElementById("errorModal");
            if (modal) modal.style.display = "none";
        }

        // Auto-close modals after 3 seconds
        setTimeout(function () {
            var success = document.getElementById("successModal");
            var error = document.getElementById("errorModal");
            if (success) success.style.display = "none";
            if (error) error.style.display = "none";
        }, 3000);

        // Auto-reload when search input becomes empty
        document.addEventListener("DOMContentLoaded", function () {
            var searchInput = document.getElementById("eventSearchInput");
            var searchFlag = document.getElementById("searchPerformed");
            var debounceTimer = null;

            var cameFromSearch = searchFlag && searchFlag.value === "1";

            searchInput.addEventListener("input", function () {
                if (debounceTimer) clearTimeout(debounceTimer);

                debounceTimer = setTimeout(function () {
                    if (searchInput.value.trim() === "" && cameFromSearch) {
                        window.location.href = "viewEventsPage.php";
                    }
                }, 600);
            });
        });
    </script>

</body>
</html>