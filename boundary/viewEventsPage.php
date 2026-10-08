<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    $_SESSION['login_error'] = "Session expired. Please log in again.";
    header("Location: loginPage.php");
    exit();
}

require_once "../controller/viewEventsController.php";

$userRole = strtolower($_SESSION['user_role'] ?? $_SESSION['role'] ?? 'student');
$isLecturer = in_array($userRole, ['lecturer', 'staff', 'teacher']);
$dashboardPage = $isLecturer ? 'lecturerDashboardPage.php' : 'studentDashboardPage.php';

$controller = new ViewEventsController();
$universityId = $_SESSION['university_id'] ?? 1;
$userId = $_SESSION['user_id'] ?? null;

$events = $controller->getEventList($universityId);
$registeredIds = $controller->getUserRegisteredEventIds($userId);

$eventsToDisplay = $_SESSION['search_results'] ?? $events;
unset($_SESSION['search_results']);

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
    <style>
        .event-actions {
            display: grid;
            grid-template-columns: 90px 110px 110px;
            gap: 10px;
            align-items: center;
            flex-shrink: 0;
        }

        .event-actions>* {
            width: 100%;
            box-sizing: border-box;
        }

        .btn-event-view,
        .btn-event-nav,
        .btn-event-register,
        .btn-event-registered,
        .btn-event-past {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 0;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
            border: none;
            text-decoration: none;
            cursor: pointer;
            transition: 0.2s ease;
            box-sizing: border-box;
        }

        .btn-event-view {
            background-color: #e0e0e0;
            color: #222;
        }

        .btn-event-view:hover {
            background-color: #cfcfcf;
        }

        .btn-event-nav {
            background-color: var(--bg-yellow);
            color: #222;
        }

        .btn-event-nav:hover {
            background-color: #e6c23a;
            text-decoration: none;
        }

        .btn-event-register {
            background-color: var(--bg-yellow);
            color: #222;
        }

        .btn-event-register:hover {
            background-color: #e6c23a;
        }

        .btn-event-registered {
            background-color: #c8f7c5;
            color: #1e7e34;
            cursor: not-allowed;
        }

        .btn-event-past {
            background-color: #e0e0e0;
            color: #888;
            cursor: not-allowed;
        }

        .event-register-form {
            display: contents;
        }

        @media (max-width: 768px) {
            .event-actions {
                grid-template-columns: 1fr 1fr 1fr;
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <script src="../script.js"></script>

    <header class="header">
        <div class="logo-container">
            <a href="<?php echo $dashboardPage; ?>">
                <img src="../images/uniBeeLogo.png" alt="UniBee Logo">
            </a>
        </div>

        <div class="welcome-message">
            <span>Welcome back,</span>
            <strong><?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?></strong>
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
                    <?php if ($isLecturer): ?>
                        <a href="teachingPage.php">Teaching</a>
                        <a href="viewEventsPage.php">University Campus Events</a>
                        <a href="submitFeedbackPage.php">Submit Feedback</a>
                    <?php else: ?>
                        <a href="AIChatbotPage.php">AI Chatbot</a>
                        <a href="AcademicsPage.php">Academics</a>
                        <a href="viewFacilitiesPage.php">Facility Booking</a>
                        <a href="viewEventsPage.php">University Campus Event</a>
                    <?php endif; ?>
                    <a href="../controller/logOutController.php">Log Out</a>
                </div>
            </div>
        </div>
    </header>

    <main class="dashboard">

        <div class="profile-header"
            style="max-width: 900px; margin: 25px auto 15px auto; display:flex; align-items:center; justify-content:space-between;">
            <a href="<?php echo $dashboardPage; ?>" class="btn-back">&#8592; Back</a>
            <h2 class="section-label" style="margin: 0;">Campus Events</h2>
            <div style="width: 80px;"></div>
        </div>

        <form action="../controller/searchEventController.php" method="GET" class="event-search-bar">
            <input type="text" id="eventSearchInput" name="keyword"
                placeholder="Search events by name, date, or venue..."
                value="<?php echo htmlspecialchars($searchKeywordValue); ?>">
            <button type="submit" name="search" class="btn-search">Search</button>
        </form>

        <input type="hidden" id="searchPerformed" value="<?php echo $searchPerformed; ?>">

        <?php
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
            <p class="error-message">Unable to retrieve events. Please try again later.</p>

        <?php elseif (empty($eventsToDisplay)): ?>
            <p class="no-notifications">No upcoming events available.</p>

        <?php else: ?>

            <div class="event-list">

                <?php foreach ($eventsToDisplay as $event): ?>

                    <?php
                    $startDate = strtotime($event['startDatetime']);
                    $endDate = strtotime($event['endDatetime']);

                    $month = date('M', $startDate);
                    $day = date('d', $startDate);
                    $time = date('g:iA', $startDate) . ' - ' . date('g:iA', $endDate);

                    $isPast = $startDate < time();
                    $isRegistered = in_array($event['id'], $registeredIds);
                    ?>

                    <div class="event-item" data-title="<?php echo htmlspecialchars(strtolower($event['title'])); ?>">

                        <div class="event-date-badge">
                            <span class="event-month"><?php echo $month; ?></span>
                            <span class="event-day"><?php echo $day; ?></span>
                        </div>

                        <div class="event-info">
                            <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                            <p><?php echo htmlspecialchars($time); ?> · <?php echo htmlspecialchars($event['location']); ?></p>
                        </div>

                        <div class="event-actions">

                            <button type="button" class="btn-event-view"
                                onclick='openEventView(<?php echo htmlspecialchars(json_encode($event), ENT_QUOTES); ?>)'>
                                View
                            </button>

                            <a href="viewEventNavigationPage.php?eventId=<?php echo urlencode($event['id']); ?>"
                                class="btn-event-nav">
                                Navigate
                            </a>

                            <?php if ($isPast): ?>
                                <button class="btn-event-past" disabled>Past Event</button>
                            <?php elseif ($isRegistered): ?>
                                <button class="btn-event-registered" disabled>Registered</button>
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

    <div class="modal-overlay" id="eventViewModal" style="display:none;">
        <div class="modal-box" style="padding: 30px 35px; text-align: left; max-width: 560px;">
            <span class="modal-close" onclick="closeEventView()">&times;</span>
            <h3 id="eventViewTitle" style="margin:0 0 10px; font-size:1.15rem;"></h3>
            <p id="eventViewMeta" style="color:#666; font-size:0.85rem; margin:0 0 14px;"></p>
            <p id="eventViewDesc" style="font-size:0.9rem; line-height:1.5; margin:0 0 20px; color:#333;"></p>
            <div style="text-align: right;">
                <button type="button" onclick="closeEventView()"
                    style="padding: 9px 22px; border: none; border-radius: 20px; background: #e0e0e0; color: #333; font-weight: 700; cursor: pointer;">
                    Close
                </button>
            </div>
        </div>
    </div>

    <?php if (isset($_SESSION['register_success'])): ?>
        <div class="modal-overlay" id="successModal">
            <div class="modal-box">
                <span class="modal-close" onclick="closeModal()">&times;</span>
                <p class="modal-message"><?php echo htmlspecialchars($_SESSION['register_success']); ?></p>
            </div>
        </div>
        <?php unset($_SESSION['register_success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['register_error'])): ?>
        <div class="modal-overlay" id="errorModal">
            <div class="modal-box">
                <span class="modal-close" onclick="closeErrorModal()">&times;</span>
                <p class="modal-message"><?php echo htmlspecialchars($_SESSION['register_error']); ?></p>
            </div>
        </div>
        <?php unset($_SESSION['register_error']); ?>
    <?php endif; ?>

    <footer>
        <div class="footer-bottom-bar">
            &copy; 2026 UniBee. All rights reserved.
        </div>
    </footer>

    <script>
        function closeModal() {
            var modal = document.getElementById("successModal");
            if (modal) modal.style.display = "none";
        }

        function closeErrorModal() {
            var modal = document.getElementById("errorModal");
            if (modal) modal.style.display = "none";
        }

        function openEventView(data) {
            document.getElementById('eventViewTitle').textContent = data.title || '';
            var start = new Date((data.startDatetime || '').replace(' ', 'T'));
            var end = new Date((data.endDatetime || '').replace(' ', 'T'));
            var opts = { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' };
            var meta = start.toLocaleString('en-SG', opts) + ' — ' + end.toLocaleTimeString('en-SG', { hour: 'numeric', minute: '2-digit' });
            if (data.location) meta += ' · ' + data.location;
            document.getElementById('eventViewMeta').textContent = meta;
            document.getElementById('eventViewDesc').textContent = data.description || 'No description provided.';
            document.getElementById('eventViewModal').style.display = 'flex';
        }

        function closeEventView() {
            document.getElementById('eventViewModal').style.display = 'none';
        }

        setTimeout(function () {
            var success = document.getElementById("successModal");
            var error = document.getElementById("errorModal");
            if (success) success.style.display = "none";
            if (error) error.style.display = "none";
        }, 3000);

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