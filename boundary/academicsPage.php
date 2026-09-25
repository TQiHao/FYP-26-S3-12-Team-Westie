<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: loginPage.php");
    exit();
}

require_once "../controller/academicsController.php";
require_once "../controller/viewEventsController.php";

$controller = new AcademicsController();
$eventController = new ViewEventsController();
$studentId = $_SESSION['user_id'] ?? null;

// Tab logic — allow 4 tabs
$allowedTabs = ['timetable', 'courses', 'exams', 'interaction'];
$requestedTab = $_GET['tab'] ?? 'timetable';
$activeTab = in_array($requestedTab, $allowedTabs) ? $requestedTab : 'timetable';

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

$dayOfWeekNum = (int) $refDate->format('N');
$monday = clone $refDate;
$monday->modify('-' . ($dayOfWeekNum - 1) . ' days')->setTime(0, 0, 0);
$sunday = clone $monday;
$sunday->modify('+6 days')->setTime(23, 59, 59);

$prevMonday = clone $monday;
$prevMonday->modify('-1 week');
$nextMonday = clone $monday;
$nextMonday->modify('+1 week');

$prevDateParam = $prevMonday->format('Y-m-d');
$nextDateParam = $nextMonday->format('Y-m-d');
$selectedDateValue = $monday->format('Y-m-d');
$weekRangeText = $monday->format('M j, Y') . ' – ' . $sunday->format('M j, Y');

$semStart = new DateTime('2026-09-01');
$semEnd = new DateTime('2026-11-30');
$isWithinSemester = ($sunday >= $semStart && $monday <= $semEnd);

// Fetch class timetable entries
$entries = null;
if ($activeTab === 'timetable' && $isWithinSemester) {
    $entries = $controller->getTimetable($studentId);
}

// Fetch registered campus events for this student
$registeredEvents = method_exists($eventController, 'getUserRegisteredEvents')
    ? $eventController->getUserRegisteredEvents($studentId)
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
    'mon' => [], 'tue' => [], 'wed' => [], 'thu' => [],
    'fri' => [], 'sat' => [], 'sun' => []
];

// 1. Load class timetable entries into grid (each slot is an array)
if (is_array($entries)) {
    foreach ($entries as $e) {
        $day = strtolower($e->getDayOfWeek());

        $startParts = explode(':', $e->getStartTime());
        $endParts   = explode(':', $e->getEndTime());

        $startHour = (int) $startParts[0];
        $endHour   = (int) $endParts[0];
        $endMinute = (int) ($endParts[1] ?? 0);

        $duration = $endHour - $startHour;
        if ($endMinute > 0) {
            $duration += 1;
        }
        $duration = max(1, $duration);

        if (isset($grid[$day])) {
            $grid[$day][$startHour][] = [
                'title'    => $e->getTitle(),
                'location' => 'Room ' . $e->getLocation(),
                'time'     => date('g:ia', strtotime($e->getStartTime())) . ' - ' . date('g:ia', strtotime($e->getEndTime())),
                'type'     => 'class',
                'duration' => $duration
            ];
        }
    }
}

// 2. Load registered campus events for the current week (each slot is an array)
if (is_array($registeredEvents)) {
    foreach ($registeredEvents as $evt) {
        $startDt = new DateTime($evt['startDatetime'] ?? '');
        $endDt   = new DateTime($evt['endDatetime'] ?? '');

        if ($startDt >= $monday && $startDt <= $sunday) {
            $day = strtolower($startDt->format('D'));
            $startHour = (int) $startDt->format('G');
            $endHour   = (int) $endDt->format('G');
            $duration  = max(1, $endHour - $startHour);

            if (isset($grid[$day])) {
                $grid[$day][$startHour][] = [
                    'title'    => $evt['title'] ?? 'Registered Event',
                    'location' => $evt['location'] ?? 'Campus',
                    'time'     => $startDt->format('g:ia') . ' - ' . $endDt->format('g:ia'),
                    'type'     => 'event',
                    'duration' => $duration
                ];
            }
        }
    }
}

$hours = range(8, 22);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academics - UniBee</title>
    <link rel="stylesheet" href="../style.css">
    <style>
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

        /* ===== STACKED CELL FOR OVERLAPPING SLOTS ===== */
        .timetable-cell-stack {
            display: flex;
            flex-direction: column;
            gap: 4px;
            height: 100%;
        }

        .timetable-cell-stack .timetable-slot,
        .timetable-cell-stack .event-slot {
            flex: 1;
            min-height: 40px;
            padding: 6px 4px;
            font-size: 11px;
            line-height: 1.2;
            border-radius: 4px;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        /* Yellow class slot */
        .timetable-slot {
            background-color: #FFD84D;
            color: #111;
            font-weight: bold;
        }

        /* Blue event slot */
        .timetable-slot.event-slot {
            background-color: #BAE6FD;
            color: #111;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <script src="../script.js"></script>

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
                    <a href="academicsPage.php">Academics</a>
                    <a href="viewFacilitiesPage.php">Facility Booking</a>
                    <a href="viewEventsPage.php">University Campus Event</a>
                    <a href="../controller/logoutController.php">Log Out</a>
                </div>
            </div>
        </div>
    </header>

    <main class="dashboard">

        <div class="profile-header">
            <a href="studentDashboardPage.php" class="btn-back">&#8592; Back</a>
            <h2 class="section-label">Academics</h2>
            <div></div>
        </div>

        <!-- Tabs -->
        <div class="teaching-tabs">
            <a href="academicsPage.php?tab=timetable"
               class="teaching-tab <?php echo $activeTab === 'timetable' ? 'active' : ''; ?>">
                Personal Timetable
            </a>

            <a href="academicsPage.php?tab=courses"
               class="teaching-tab <?php echo $activeTab === 'courses' ? 'active' : ''; ?>">
                Enrolled Courses
            </a>

            <a href="academicsPage.php?tab=exams"
               class="teaching-tab <?php echo $activeTab === 'exams' ? 'active' : ''; ?>">
                Exam Schedule
            </a>

            <a href="academicsPage.php?tab=interaction"
               class="teaching-tab <?php echo $activeTab === 'interaction' ? 'active' : ''; ?>">
                Student Interaction
            </a>
        </div>

        <?php if ($activeTab === 'timetable'): ?>

            <!-- ===== PERSONAL TIMETABLE ===== -->
            <div class="timetable-controls">
                <a href="academicsPage.php?tab=timetable&date=<?php echo $prevDateParam; ?>" class="timetable-nav-btn">&larr;
                    Previous Week</a>

                <div class="date-picker-wrapper">
                    <span class="week-range-title"><?php echo htmlspecialchars($weekRangeText); ?></span>
                    <input type="date" class="date-input" value="<?php echo $selectedDateValue; ?>"
                           onchange="jumpToWeek(this.value)">
                </div>

                <a href="academicsPage.php?tab=timetable&date=<?php echo $nextDateParam; ?>" class="timetable-nav-btn">Next
                    Week &rarr;</a>
            </div>

            <?php if (!$isWithinSemester && empty($registeredEvents)): ?>
                <p class="no-notifications">No classes scheduled for this week. (Semester 1 active from Sept 2026 to Nov 2026)</p>
            <?php elseif ($entries === false): ?>
                <p class="error-message">Unable to retrieve timetable. Please try again later.</p>
            <?php else: ?>
                <table class="timetable-grid">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <?php foreach ($keys as $k): ?>
                                <th>
                                    <?php echo $weekDates[$k]['dayName']; ?><br>
                                    <small style="font-weight: normal; color: #64748b;"><?php echo $weekDates[$k]['dateStr']; ?></small>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $skipCell = [
                            'mon' => [], 'tue' => [], 'wed' => [], 'thu' => [],
                            'fri' => [], 'sat' => [], 'sun' => []
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
                                        $items = $grid[$k][$h];

                                        // Determine the longest duration among stacked items
                                        $maxDuration = 1;
                                        foreach ($items as $it) {
                                            if ($it['duration'] > $maxDuration) {
                                                $maxDuration = $it['duration'];
                                            }
                                        }

                                        for ($d = 1; $d < $maxDuration; $d++) {
                                            $skipCell[$k][$h + $d] = true;
                                        }

                                        $rowspanAttr = $maxDuration > 1 ? ' rowspan="' . $maxDuration . '"' : '';
                                        $hasClash = count($items) > 1;
                                        ?>
                                        <td<?php echo $rowspanAttr; ?>>
                                            <div class="timetable-cell-stack<?php echo $hasClash ? ' has-clash' : ''; ?>">
                                                <?php foreach ($items as $item): ?>
                                                    <div class="<?php echo ($item['type'] === 'event') ? 'timetable-slot event-slot' : 'timetable-slot'; ?>">
                                                        <strong><?php echo htmlspecialchars($item['title']); ?></strong>
                                                        <span><?php echo htmlspecialchars($item['location']); ?></span>
                                                        <small><?php echo htmlspecialchars($item['time']); ?></small>
                                                    </div>
                                                <?php endforeach; ?>
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

        <?php elseif ($activeTab === 'courses'): ?>

            <!-- ===== ENROLLED COURSES ===== -->
            <?php
            $enrolledCourses = $controller->getEnrolledCourses($studentId);
            ?>

            <?php if ($enrolledCourses === false): ?>
                <p class="error-message">Unable to retrieve enrolled courses. Please try again later.</p>

            <?php elseif (empty($enrolledCourses)): ?>
                <p class="no-notifications">You are not enrolled in any courses for the current semester.</p>

            <?php else: ?>

                <p class="semester-label">
                    Semester <?php echo htmlspecialchars($enrolledCourses[0]['moduleSemester'] ?? '-'); ?>,
                    <?php echo count($enrolledCourses); ?> courses
                </p>

                <div class="enrolled-courses-list">
                    <?php foreach ($enrolledCourses as $course): ?>
                        <div class="enrolled-course-card">
                            <div class="course-left">
                                <h3>
                                    <?php echo htmlspecialchars($course['moduleCode']); ?>
                                    -
                                    <?php echo htmlspecialchars($course['moduleName']); ?>
                                </h3>
                                <p>
                                    <?php echo htmlspecialchars($course['lecturerName'] ?? 'Not Assigned'); ?>,
                                    <?php echo htmlspecialchars(ucfirst($course['dayOfWeek'])); ?>
                                    <?php echo htmlspecialchars(date('g:iA', strtotime($course['startTime']))); ?>
                                    -
                                    <?php echo htmlspecialchars(date('g:iA', strtotime($course['endTime']))); ?>
                                </p>
                            </div>
                            <div class="course-right">
                                <span class="course-room">
                                    Room <?php echo htmlspecialchars($course['room']); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            <?php endif; ?>

        <?php elseif ($activeTab === 'exams'): ?>

            <!-- ===== EXAM SCHEDULE ===== -->
            <?php
            $exams = $controller->getExamSchedule($studentId);
            ?>

            <?php if ($exams === false): ?>
                <p class="error-message">Unable to retrieve exam schedule. Please try again later.</p>

            <?php elseif (empty($exams)): ?>
                <p class="no-notifications">No upcoming exam scheduled.</p>

            <?php else: ?>

                <p class="semester-label">
                    <?php echo count($exams); ?> upcoming exam<?php echo count($exams) > 1 ? 's' : ''; ?>
                </p>

                <div class="exam-list">
                    <?php foreach ($exams as $exam): ?>
                        <?php
                        $examDate = strtotime($exam['examDate']);
                        $month = date('M', $examDate);
                        $day   = date('d', $examDate);
                        $time  = date('g:iA', strtotime($exam['startTime'])) . ' - ' . date('g:iA', strtotime($exam['endTime']));
                        ?>
                        <div class="exam-item">
                            <div class="exam-date-badge">
                                <span class="exam-month"><?php echo $month; ?></span>
                                <span class="exam-day"><?php echo $day; ?></span>
                            </div>
                            <div class="exam-info">
                                <h3>
                                    <?php echo htmlspecialchars($exam['moduleCode']); ?>
                                    -
                                    <?php echo htmlspecialchars($exam['moduleName']); ?>
                                </h3>
                                <p><?php echo htmlspecialchars($time); ?> · <?php echo htmlspecialchars($exam['venue']); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            <?php endif; ?>

        <?php elseif ($activeTab === 'interaction'): ?>

            <!-- ===== STUDENT INTERACTION ===== -->
            <p class="no-notifications">Student Interaction — to be coded next.</p>

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
                window.location.href = 'academicsPage.php?tab=timetable&date=' + selectedDate;
            }
        }
    </script>
</body>

</html>