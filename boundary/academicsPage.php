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

            <!-- ===== STUDENT INTERACTION ===== -->
            <?php elseif ($activeTab === 'interaction'): ?>

                <!-- ===== STUDENT INTERACTION ===== -->
            <?php
            require_once "../controller/studentInteractionController.php";
            $interactionController = new StudentInteractionController();

            $searchKeyword = trim($_GET['keyword'] ?? '');
            $searchActive = $searchKeyword !== '';
            $searchResults = null;

            if ($searchActive) {
                // Server-side search — returns matching groups (with isMember flag)
                $searchResults = $interactionController->searchStudyGroups(
                    $studentId,
                    $_SESSION['university_id'],
                    $searchKeyword
                );
            } else {
                // Normal view — my groups + available groups
                $groupsData = $interactionController->getMyGroupsAndAvailableGroups($studentId);
                $myGroups = $groupsData['myGroups'];
                $availableGroups = $groupsData['availableGroups'];
            }
            ?>

                <!-- Search + Create Bar -->
                <form action="academicsPage.php?tab=interaction" method="GET" class="student-interaction-bar">
                    <input type="hidden" name="tab" value="interaction">
                    <input type="text" name="keyword" placeholder="Search by group name, course, keyword..."
                        value="<?php echo htmlspecialchars($_GET['keyword'] ?? ''); ?>">
                    <button type="submit" class="btn-search-group">Search</button>
                    <a href="createStudyGroupPage.php" class="btn-create-group">+ Create Group</a>
                </form>

                    <?php if ($searchActive): ?>

        <!-- SEARCH RESULTS -->
        <h3 class="section-label" style="margin-top: 25px;">
            Search Results for "<?php echo htmlspecialchars($searchKeyword); ?>"
        </h3>

        <?php if ($searchResults === false): ?>
            <p class="error-message">Unable to search study groups. Please try again later.</p>

        <?php elseif (empty($searchResults)): ?>
            <p class="no-notifications">No study group found matching your search criteria.</p>

        <?php else: ?>
            <div class="group-grid">
                <?php foreach ($searchResults as $row): ?>
                    <div class="group-card">
                        <div class="group-card-header">
                            <h4><?php echo htmlspecialchars($row['name']); ?></h4>
                        </div>
                        <p class="group-meta">
                            <?php echo htmlspecialchars($row['moduleCode']); ?>
                            · <?php echo htmlspecialchars($row['currentMembers']); ?>/<?php echo htmlspecialchars($row['maxMembers']); ?> members
                        </p>
                        <?php if (!empty($row['userRole'])): ?>
                            <p class="group-role"><?php echo htmlspecialchars(ucfirst($row['userRole'])); ?></p>
                        <?php endif; ?>
                        <div class="group-actions">
                            <?php if (($row['userRole'] ?? '') === 'admin'): ?>
                                <a href="updateStudyGroupPage.php?groupId=<?php echo $row['id']; ?>" class="btn-group-action btn-update">Update</a>
                                <a href="viewStudyGroupPage.php?groupId=<?php echo $row['id']; ?>" class="btn-group-action btn-view">View</a>
                                <a href="suspendStudyGroupPage.php?groupId=<?php echo $row['id']; ?>" class="btn-group-action btn-suspend">Suspend</a>
                            <?php else: ?>
                                <a href="viewStudyGroupPage.php?groupId=<?php echo $row['id']; ?>" class="btn-group-action btn-view">View</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    <?php else: ?>

        <!-- MY GROUPS -->
        <h3 class="section-label" style="margin-top: 25px;">My Groups</h3>

        <?php if (empty($myGroups)): ?>
            <p class="no-notifications">You have not joined any study groups yet.</p>
        <?php else: ?>
            <div class="group-grid">
                <?php foreach ($myGroups as $group): ?>
                    <div class="group-card">
                        <div class="group-card-header">
                            <h4><?php echo htmlspecialchars($group->getName()); ?></h4>
                        </div>
                        <p class="group-meta">
                            <?php echo htmlspecialchars($group->getModuleCode()); ?>
                            · <?php echo htmlspecialchars($group->getCurrentMembers()); ?>/<?php echo htmlspecialchars($group->getMaxMembers()); ?> members
                        </p>
                        <p class="group-role">
                            <?php echo htmlspecialchars(ucfirst($group->getUserRole())); ?>
                        </p>
                        <div class="group-actions">
                            <?php if ($group->getUserRole() === 'admin'): ?>
                                <a href="updateStudyGroupPage.php?groupId=<?php echo $group->getId(); ?>" class="btn-group-action btn-update">Update</a>
                                <a href="viewStudyGroupPage.php?groupId=<?php echo $group->getId(); ?>" class="btn-group-action btn-view">View</a>
                                <button type="button" class="btn-group-action btn-suspend"
                                    onclick="confirmSuspend(<?php echo $group->getId(); ?>, '<?php echo htmlspecialchars(addslashes($group->getName())); ?>')">Suspend</button>
                            <?php else: ?>
                                <a href="viewStudyGroupPage.php?groupId=<?php echo $group->getId(); ?>" class="btn-group-action btn-view">View</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- AVAILABLE TO JOIN -->
        <h3 class="section-label" style="margin-top: 35px;">Available to join</h3>

        <?php if (empty($availableGroups)): ?>
            <p class="no-notifications">No groups available to join right now.</p>
        <?php else: ?>
            <div class="group-grid">
                <?php foreach ($availableGroups as $group): ?>
                    <div class="group-card">
                        <div class="group-card-header">
                            <h4><?php echo htmlspecialchars($group->getName()); ?></h4>
                        </div>
                        <p class="group-meta">
                            <?php echo htmlspecialchars($group->getModuleCode()); ?>
                            · <?php echo htmlspecialchars($group->getCurrentMembers()); ?>/<?php echo htmlspecialchars($group->getMaxMembers()); ?> members
                        </p>
                        <div class="group-actions">
                            <a href="viewStudyGroupPage.php?groupId=<?php echo $group->getId(); ?>" class="btn-group-action btn-view">View</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    <?php endif; ?>


        <?php endif; ?>

    </main>

        <!-- Confirmation Modal -->
    <div class="modal-overlay" id="suspendConfirmModal" style="display:none;">
        <div class="modal-box">
            <span class="modal-close" onclick="closeSuspendModal()">&times;</span>
            <p class="modal-message" id="suspendConfirmText"></p>
            <div class="suspend-actions">
                <button type="button" class="btn-suspend-cancel" onclick="closeSuspendModal()">Cancel</button>
                <form id="suspendForm" action="suspendStudyGroupPage.php" method="POST" style="display:inline;">
                    <input type="hidden" name="groupId" id="suspendGroupId">
                    <button type="submit" name="suspend_group" class="btn-suspend-confirm">Suspend</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Group Success Modal -->
    <?php if (isset($_SESSION['group_success'])): ?>
        <div class="modal-overlay" id="groupSuccessModal">
            <div class="modal-box">
                <span class="modal-close" onclick="closeGroupModal()">&times;</span>
                <p class="modal-message"><?php echo htmlspecialchars($_SESSION['group_success']); ?></p>
            </div>
        </div>
        <?php unset($_SESSION['group_success']); ?>
    <?php endif; ?>

    <!-- Group Error Modal -->
    <?php if (isset($_SESSION['group_error'])): ?>
        <div class="modal-overlay" id="groupErrorModal">
            <div class="modal-box">
                <span class="modal-close" onclick="closeGroupErrorModal()">&times;</span>
                <p class="modal-message"><?php echo htmlspecialchars($_SESSION['group_error']); ?></p>
            </div>
        </div>
        <?php unset($_SESSION['group_error']); ?>
    <?php endif; ?>

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
    <script>
        function confirmSuspend(groupId, groupName) {
            document.getElementById("suspendGroupId").value = groupId;
            document.getElementById("suspendConfirmText").innerText =
                "Are you sure you want to suspend " + groupName + "?";
            document.getElementById("suspendConfirmModal").style.display = "flex";
        }

        function closeSuspendModal() {
            document.getElementById("suspendConfirmModal").style.display = "none";
        }

        function closeGroupModal() {
            var modal = document.getElementById("groupSuccessModal");
            if (modal) modal.style.display = "none";
            window.location.reload();
        }

        function closeGroupErrorModal() {
            var modal = document.getElementById("groupErrorModal");
            if (modal) modal.style.display = "none";
        }
    </script>
</body>

</html>