<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: loginPage.php");
    exit();
}

require_once "../controller/teachingController.php";
require_once "../controller/viewEventsController.php";

$controller = new TeachingController();
$eventController = new ViewEventsController();
$staffId = $_SESSION['user_id'] ?? null;

// Tab logic
$activeTab = ($_GET['tab'] ?? 'timetable') === 'modules' ? 'modules' : 'timetable';

// Week & Date Selection Logic
$today = new DateTime();

if (isset($_GET['date']) && !empty($_GET['date'])) {
    try {
        $refDate = new DateTime($_GET['date']);
    } catch (Exception $e) {
        $refDate = clone $today;
    }
} else {
    $refDate = clone $today;
    $weekOffset = isset($_GET['week']) ? (int) $_GET['week'] : 0;
    if ($weekOffset !== 0) {
        $refDate->modify(($weekOffset > 0 ? '+' : '') . $weekOffset . ' weeks');
    }
}

// Calculate Monday and Sunday of the selected week
$dayOfWeekNum = (int) $refDate->format('N'); // 1 (Mon) to 7 (Sun)
$monday = clone $refDate;
$monday->modify('-' . ($dayOfWeekNum - 1) . ' days')->setTime(0, 0, 0);
$sunday = clone $monday;
$sunday->modify('+6 days')->setTime(23, 59, 59);

// Adjacent week dates for navigation buttons
$prevMonday = clone $monday;
$prevMonday->modify('-1 week');
$nextMonday = clone $monday;
$nextMonday->modify('+1 week');

$prevDateParam = $prevMonday->format('Y-m-d');
$nextDateParam = $nextMonday->format('Y-m-d');
$selectedDateValue = $monday->format('Y-m-d');
$weekRangeText = $monday->format('M j, Y') . ' – ' . $sunday->format('M j, Y');

// Semester Date Filter (Sept 1, 2026 to Nov 30, 2026)
$semStart = new DateTime('2026-09-01');
$semEnd = new DateTime('2026-11-30');

// Active semester check
$isWithinSemester = ($sunday >= $semStart && $monday <= $semEnd);

// Fetch regular class timetable entries
$entries = $isWithinSemester ? $controller->getTimetable($staffId) : [];

// Fetch registered campus events for this user
$registeredEvents = method_exists($eventController, 'getUserRegisteredEvents')
    ? $eventController->getUserRegisteredEvents($staffId)
    : [];

$keys = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
$weekDates = [];
$currentDay = clone $monday;

for ($i = 0; $i < 7; $i++) {
    $weekDates[$keys[$i]] = [
        'dayName' => $currentDay->format('D'),
        'dateStr' => $currentDay->format('M j')
    ];
    $currentDay->modify('+1 day');
}

$grid = [
    'mon' => [],
    'tue' => [],
    'wed' => [],
    'thu' => [],
    'fri' => [],
    'sat' => [],
    'sun' => []
];

// 1. Load class timetable entries into grid
if (is_array($entries)) {
    foreach ($entries as $e) {
        $day = strtolower($e->getDayOfWeek());
        $startHour = (int) date('G', strtotime($e->getStartTime()));
        $endHour = (int) date('G', strtotime($e->getEndTime()));
        $duration = max(1, $endHour - $startHour);

        if (isset($grid[$day])) {
            $grid[$day][$startHour] = [
                'title' => $e->getTitle(),
                'location' => 'Room ' . $e->getLocation(),
                'time' => date('g:ia', strtotime($e->getStartTime())) . ' - ' . date('g:ia', strtotime($e->getEndTime())),
                'type' => 'class',
                'duration' => $duration
            ];
        }
    }
}

// 2. Load registered campus events for the selected week into grid
if (is_array($registeredEvents)) {
    foreach ($registeredEvents as $evt) {
        $startDt = new DateTime($evt['startDatetime'] ?? $evt['startTime'] ?? '');
        $endDt = new DateTime($evt['endDatetime'] ?? $evt['endTime'] ?? '');

        // Verify event falls within currently displayed week
        if ($startDt >= $monday && $startDt <= $sunday) {
            $day = strtolower($startDt->format('D'));
            $startHour = (int) $startDt->format('G');
            $endHour = (int) $endDt->format('G');
            $duration = max(1, $endHour - $startHour);

            if (isset($grid[$day])) {
                $grid[$day][$startHour] = [
                    'title' => $evt['title'] ?? 'Registered Event',
                    'location' => $evt['location'] ?? 'Campus',
                    'time' => $startDt->format('g:ia') . ' - ' . $endDt->format('g:ia'),
                    'type' => 'event',
                    'duration' => $duration
                ];
            }
        }
    }
}

$hours = range(8, 22); // 8:00 AM to 10:00 PM
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teaching - UniBee</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        /* Header Layout: Centered Title with Absolute Left Back Button */
        .profile-header {
            position: relative;
            display: flex;
            align-items: center;
            width: 100%;
            min-height: 45px;
            margin: 15px 0 25px 0;
        }

        .btn-back {
            position: absolute;
            left: 0;
            margin: 0;
            z-index: 10;
        }

        .profile-header .section-label {
            position: absolute;
            left: 70%;
            transform: translateX(-50%);
            margin: 0 !important;
            text-align: center !important;
            white-space: nowrap;
        }

        .timetable-controls {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 15px 0;
            background: #ffffff;
            padding: 12px 20px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .timetable-nav-btn {
            background-color: #f1f5f9;
            color: #1e293b;
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
            transition: background 0.2s;
        }

        .timetable-nav-btn:hover {
            background-color: #e2e8f0;
        }

        .date-picker-wrapper {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .week-range-title {
            font-size: 15px;
            font-weight: bold;
            color: #0f172a;
        }

        .date-input {
            padding: 6px 10px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 14px;
            color: #1e293b;
            cursor: pointer;
            outline: none;
        }

        .date-input:focus {
            border-color: #ffd84d;
        }

        .event-slot {
            width: 100%;
            height: 100%;
            box-sizing: border-box;
            background-color: #BAE6FD;
            border-radius: 4px;
            padding: 6px 4px;
            font-size: 12px;
            font-weight: bold;
            color: #111111;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }
    </style>
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

        <!-- Top Header with Perfectly Centered Title -->
        <div class="profile-header">
            <a href="lecturerDashboardPage.php" class="btn-back">&#8592; Back</a>
            <h2 class="section-label">Teaching</h2>
        </div>

        <!-- Tabs -->
        <div class="teaching-tabs">
            <a href="teachingPage.php?tab=timetable"
                class="teaching-tab <?php echo $activeTab === 'timetable' ? 'active' : ''; ?>">
                Personal Timetable
            </a>
            <a href="viewAssignedModules.php" class="teaching-tab">
                Assigned Modules
            </a>
        </div>

        <?php if ($activeTab === 'timetable'): ?>

            <!-- Calendar & Date Navigation Bar -->
            <div class="timetable-controls">
                <a href="teachingPage.php?tab=timetable&date=<?php echo $prevDateParam; ?>" class="timetable-nav-btn">&larr;
                    Previous Week</a>

                <div class="date-picker-wrapper">
                    <span class="week-range-title"><?php echo htmlspecialchars($weekRangeText); ?></span>
                    <input type="date" class="date-input" value="<?php echo $selectedDateValue; ?>"
                        onchange="jumpToWeek(this.value)">
                </div>

                <a href="teachingPage.php?tab=timetable&date=<?php echo $nextDateParam; ?>" class="timetable-nav-btn">Next
                    Week &rarr;</a>
            </div>

            <?php if (!$isWithinSemester && empty($registeredEvents)): ?>
                <p class="no-notifications">No classes scheduled for this week. (Semester 1 active from Sept 2026 to Nov 2026)
                </p>
            <?php elseif ($entries === false): ?>
                <p class="error-message">Unable to retrieve timetable. Please try again later.</p>
            <?php else: ?>
                <!-- Interactive Timetable Grid -->
                <table class="timetable-grid">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <?php foreach ($keys as $k): ?>
                                <th>
                                    <?php echo $weekDates[$k]['dayName']; ?><br>
                                    <small
                                        style="font-weight: normal; color: #64748b;"><?php echo $weekDates[$k]['dateStr']; ?></small>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $skipCell = [
                            'mon' => [],
                            'tue' => [],
                            'wed' => [],
                            'thu' => [],
                            'fri' => [],
                            'sat' => [],
                            'sun' => []
                        ];

                        foreach ($hours as $h): ?>
                            <tr>
                                <th><?php echo date('g:i A', mktime($h, 0, 0)); ?></th>
                                <?php foreach ($keys as $k): ?>
                                    <?php
                                    if (!empty($skipCell[$k][$h])) {
                                        continue;
                                    }

                                    if (isset($grid[$k][$h])):
                                        $item = $grid[$k][$h];
                                        $duration = $item['duration'];

                                        for ($d = 1; $d < $duration; $d++) {
                                            $skipCell[$k][$h + $d] = true;
                                        }

                                        $rowspanAttr = $duration > 1 ? ' rowspan="' . $duration . '"' : '';
                                        $slotClass = ($item['type'] === 'event') ? 'timetable-slot event-slot' : 'timetable-slot';
                                        ?>
                                        <td<?php echo $rowspanAttr; ?>>
                                            <div
                                                class="<?php echo ($item['type'] === 'event') ? 'timetable-slot event-slot' : 'timetable-slot'; ?>">
                                                <strong><?php echo htmlspecialchars($item['title']); ?></strong><br>
                                                <span><?php echo htmlspecialchars($item['location']); ?></span><br>
                                                <small><?php echo htmlspecialchars($item['time']); ?></small>
                                            </div>
                                            </td>
                                        <?php else: ?>
                                            <td></td>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

        <?php endif; ?>

    </main>

    <footer>
        <div class="footer-bottom-bar">
            &copy; 2026 UniBee. All rights reserved.
        </div>
    </footer>

    <script>
        function jumpToWeek(selectedDate) {
            if (selectedDate) {
                window.location.href = 'teachingPage.php?tab=timetable&date=' + selectedDate;
            }
        }
    </script>
</body>

</html>