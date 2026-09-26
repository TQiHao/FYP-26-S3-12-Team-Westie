<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Session Access Check for Course Coordinator
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: loginPage.php");
    exit();
}

$userRole = strtolower($_SESSION['user_role'] ?? $_SESSION['role'] ?? '');
if ($userRole !== 'course_coordinator' && $userRole !== 'course coordinator') {
    header("Location: loginPage.php");
    exit();
}

require_once "../controller/ManageClassController.php";

$controller = new ManageClassController();

$searchQuery = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';

$classList = $controller->getClasses($searchQuery, '', '', $statusFilter);
$modulesList = $controller->getAllModules();
$lecturersList = $controller->getLecturers();
$studentsList = $controller->getStudents();

// Attach enrolled student list to each class
foreach ($classList as &$cls) {
    $cls['enrolledStudents'] = $controller->getEnrolledStudents($cls['id']);
}
unset($cls);

// Group classes by Module
$groupedModules = [];
foreach ($classList as $cls) {
    $modId = $cls['moduleId'];
    if (!isset($groupedModules[$modId])) {
        // Format Academic Year (e.g. 2026/2027 -> AY26/27)
        $ayRaw = $cls['academicYear'] ?? '2026/2027';
        if (preg_match('/^20(\d{2})\/20(\d{2})$/', $ayRaw, $matches)) {
            $ayFormatted = 'AY' . $matches[1] . '/' . $matches[2];
        } else {
            $ayFormatted = 'AY ' . $ayRaw;
        }

        $semRaw = $cls['semester'] ?? '1';
        $semFormatted = is_numeric($semRaw) ? 'Semester ' . $semRaw : $semRaw;

        $groupedModules[$modId] = [
            'code' => $cls['moduleCode'],
            'name' => $cls['moduleName'],
            'academicYear' => $ayFormatted,
            'semester' => $semFormatted,
            'classes' => []
        ];
    }
    $groupedModules[$modId]['classes'][] = $cls;
}

// Sort classes within each module: Active first (0), Inactive/Suspended last (1)
foreach ($groupedModules as &$modGroup) {
    usort($modGroup['classes'], function ($a, $b) {
        $statusA = strtolower($a['status']) === 'active' ? 0 : 1;
        $statusB = strtolower($b['status']) === 'active' ? 0 : 1;
        return $statusA <=> $statusB;
    });
}
unset($modGroup);

$flashMessage = $_SESSION['flash_message'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_message'], $_SESSION['flash_error']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Classes - UniBee</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        * {
            box-sizing: border-box;
        }

        .page-container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 40px;
            box-sizing: border-box;
        }

        .filter-bar {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
            flex-wrap: wrap;
            box-sizing: border-box;
        }

        .search-box {
            display: flex;
            gap: 10px;
            flex: 1;
            width: 100%;
        }

        .search-box input {
            flex: 1;
            padding: 10px 16px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 14px;
        }

        .search-box select {
            padding: 10px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 14px;
        }

        .btn-primary-small {
            background-color: #2563eb;
            color: #fff;
            padding: 10px 18px;
            border-radius: 8px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
        }

        .btn-primary-small:hover {
            background-color: #1d4ed8;
        }

        .btn-secondary-small {
            background-color: #f1f5f9;
            color: #334155;
            padding: 10px 16px;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            cursor: pointer;
            font-weight: 500;
        }

        .btn-secondary-small:hover {
            background-color: #e2e8f0;
        }

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

        .module-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 24px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            width: 100%;
            box-sizing: border-box;
        }

        .empty-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 60px 20px;
            margin-bottom: 24px;
            text-align: center;
            color: #64748b;
            font-size: 16px;
            width: 100%;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            box-sizing: border-box;
        }

        .module-card:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.08);
            transform: translateY(-2px);
        }

        .table-responsive {
            width: 100%;
            overflow: hidden;
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .admin-table th {
            background-color: #f8fafc;
            color: #1e293b;
            font-size: 14px;
            font-weight: 700;
            padding: 12px 10px;
            border-bottom: 2px solid #e2e8f0;
            text-align: left;
            box-sizing: border-box;
        }

        .admin-table td {
            padding: 12px 10px;
            text-align: left;
            border-bottom: 1px solid #f1f5f9;
            font-size: 13px;
            vertical-align: middle;
            overflow-wrap: break-word;
            word-break: break-word;
            box-sizing: border-box;
        }

        .col-module {
            width: 24%;
        }

        .col-code {
            width: 15%;
        }

        .col-schedule {
            width: 23%;
        }

        .col-status {
            width: 11%;
        }

        .col-actions {
            width: 27%;
        }

        .module-info-cell {
            vertical-align: top;
            padding-right: 14px;
            border-right: 1px solid #f1f5f9;
            font-size: 15px;
            font-weight: 700;
            color: #1e293b;
            line-height: 1.4;
        }

        .module-info-sub {
            display: block;
            font-weight: 500;
            color: #64748b;
            font-size: 13px;
            margin-top: 4px;
        }

        .admin-table tr:hover {
            background-color: #f8fafc;
        }

        .row-suspended {
            background-color: #fafafa;
        }

        .action-cell-btns {
            display: flex;
            gap: 5px;
            align-items: center;
            width: 100%;
        }

        .action-btn-sm {
            padding: 6px 8px;
            font-size: 12px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-weight: 500;
            white-space: nowrap;
            text-align: center;
            box-sizing: border-box;
            flex: 1;
        }

        .btn-view {
            background-color: #0284c7;
            color: #fff;
        }

        .btn-view:hover {
            background-color: #0369a1;
        }

        .btn-edit {
            background-color: #3b82f6;
            color: #fff;
        }

        .btn-edit:hover {
            background-color: #2563eb;
        }

        .btn-suspend {
            background-color: #ef4444;
            color: #fff;
        }

        .btn-suspend:hover {
            background-color: #dc2626;
        }

        .btn-activate {
            background-color: #10b981;
            color: #fff;
        }

        .btn-activate:hover {
            background-color: #059669;
        }

        .btn-remove-sm {
            background-color: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fca5a5;
            padding: 3px 8px;
            font-size: 11px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
        }

        .btn-remove-sm:hover {
            background-color: #ef4444;
            color: #ffffff;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-align: center;
            width: 100%;
            max-width: 75px;
        }

        .status-active {
            background-color: #dcfce7;
            color: #15803d;
        }

        .status-suspended {
            background-color: #fee2e2;
            color: #b91c1c;
        }

        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-card {
            background: #ffffff;
            width: 100%;
            max-width: 680px;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 10px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .form-group-full {
            grid-column: span 2;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 4px;
            color: #334155;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
        }

        .info-group {
            margin-bottom: 14px;
        }

        .info-group label {
            font-size: 12px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 700;
        }

        .info-group p {
            font-size: 15px;
            color: #1e293b;
            margin-top: 4px;
            font-weight: 500;
        }

        .student-list-box {
            min-height: 50px;
            max-height: 160px;
            overflow-y: auto;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 6px;
            background: #f8fafc;
        }

        .student-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 10px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 13px;
            color: #1e293b;
        }

        .student-item:last-child {
            border-bottom: none;
        }

        .enrol-box {
            margin-top: 10px;
            background: #f1f5f9;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
        }

        .tab-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }

        .tab-btn {
            padding: 4px 10px;
            font-size: 12px;
            border-radius: 4px;
            border: 1px solid #cbd5e1;
            background: #fff;
            cursor: pointer;
        }

        .tab-btn.active {
            background: #2563eb;
            color: #fff;
            border-color: #2563eb;
        }

        .csv-guide-box {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 10px 12px;
            margin-top: 10px;
            font-size: 12px;
            color: #334155;
        }

        .csv-guide-box code {
            background: #e2e8f0;
            padding: 2px 5px;
            border-radius: 4px;
            font-weight: bold;
        }

        .alert-box {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-success {
            background: #dcfce7;
            border: 1px solid #86efac;
            color: #166534;
        }

        .alert-danger {
            background: #fee2e2;
            border: 1px solid #fca5a5;
            color: #991b1b;
        }
    </style>
</head>

<body>
    <script src="../script.js"></script>

    <!-- Header -->
    <header class="header">
        <div class="logo-container">
            <a href="courseCoordinatorDashboardPage.php">
                <img src="../images/uniBeeLogo.png" alt="UniBee Logo">
            </a>
        </div>

        <div class="welcome-message">
            <span>Welcome back,</span>
            <strong><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Course Coordinator'); ?></strong>
        </div>

        <div class="dashboard-header-right">
            <div class="profile-dropdown">
                <div class="profile-container" onclick="toggleDropdown()">
                    <img src="../images/profilePic.png" alt="Profile" class="profile-icon">
                    <img src="../images/dropdown.png" alt="Menu" class="dropdown-arrow">
                </div>

                <div id="profileMenu" class="dropdown-menu">
                    <a href="manageClassPage.php">Manage Classes</a>
                    <a href="AIChatbotPage.php">AI Chatbot</a>
                    <a href="submitFeedbackPage.php">Submit Feedback</a>
                    <a href="../controller/logoutController.php">Log Out</a>
                </div>
            </div>
        </div>
    </header>

    <main class="page-container">

        <!-- Back Button & Title -->
        <div class="profile-header">
            <a href="courseCoordinatorDashboardPage.php" class="btn-back">&#8592; Back</a>
            <h2 class="section-label">Manage Classes</h2>
        </div>

        <!-- Flash Messages -->
        <?php if ($flashMessage): ?>
            <div class="alert-box alert-success"><?php echo htmlspecialchars($flashMessage); ?></div>
        <?php endif; ?>
        <?php if ($flashError): ?>
            <div class="alert-box alert-danger"><?php echo htmlspecialchars($flashError); ?></div>
        <?php endif; ?>

        <!-- Search Bar with Filter -->
        <div class="filter-bar">
            <form method="GET" action="manageClassPage.php" class="search-box">
                <input type="text" name="search" placeholder="Search class code, module name, or schedule..."
                    value="<?php echo htmlspecialchars($searchQuery); ?>">
                <select name="status">
                    <option value="">All Statuses</option>
                    <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="suspended" <?php echo $statusFilter === 'suspended' ? 'selected' : ''; ?>>Suspended
                    </option>
                </select>
                <button type="submit" class="btn-secondary-small">Filter</button>
            </form>

            <button type="button" class="btn-primary-small" onclick="openCreateModal()">+ Create New Class</button>
        </div>

        <!-- Modules & Classes Section -->
        <?php if (empty($groupedModules)): ?>
            <div class="empty-card">
                No classes found matching your criteria.
            </div>
        <?php else: ?>
            <?php foreach ($groupedModules as $mod): ?>
                <div class="module-card">
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th class="col-module">Module & Semester</th>
                                    <th class="col-code">Class Code</th>
                                    <th class="col-schedule">Schedule & Venue</th>
                                    <th class="col-status">Status</th>
                                    <th class="col-actions">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $classCount = count($mod['classes']);
                                foreach ($mod['classes'] as $index => $cls):
                                    ?>
                                    <tr class="<?php echo strtolower($cls['status']) !== 'active' ? 'row-suspended' : ''; ?>">
                                        <?php if ($index === 0): ?>
                                            <td rowspan="<?php echo $classCount; ?>" class="module-info-cell col-module">
                                                <div><?php echo htmlspecialchars($mod['code'] . " - " . $mod['name']); ?></div>
                                                <span
                                                    class="module-info-sub">(<?php echo htmlspecialchars($mod['academicYear'] . ", " . $mod['semester']); ?>)</span>
                                            </td>
                                        <?php endif; ?>
                                        <td class="col-code">
                                            <strong><?php echo htmlspecialchars($cls['classCode']); ?></strong>
                                        </td>
                                        <td class="col-schedule">
                                            <?php echo ucfirst($cls['dayOfWeek'] ?? '-'); ?>
                                            (<?php echo htmlspecialchars(substr($cls['startTime'] ?? '', 0, 5)); ?> -
                                            <?php echo htmlspecialchars(substr($cls['endTime'] ?? '', 0, 5)); ?>)<br>
                                            <span
                                                style="color: #64748b; font-size: 13px;"><?php echo htmlspecialchars($cls['room'] ?? 'TBA'); ?></span>
                                        </td>
                                        <td class="col-status">
                                            <?php if (strtolower($cls['status']) === 'active'): ?>
                                                <span class="status-badge status-active">Active</span>
                                            <?php else: ?>
                                                <span class="status-badge status-suspended">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="col-actions">
                                            <div class="action-cell-btns">
                                                <button type="button" class="action-btn-sm btn-view"
                                                    onclick='openViewModal(<?php echo json_encode($cls); ?>)'>View</button>

                                                <button type="button" class="action-btn-sm btn-edit"
                                                    onclick='openEditModal(<?php echo json_encode($cls); ?>)'>Edit</button>

                                                <?php if (strtolower($cls['status']) === 'active'): ?>
                                                    <button type="button" class="action-btn-sm btn-suspend"
                                                        onclick="toggleStatus(<?php echo $cls['id']; ?>, 'suspended')">Suspend</button>
                                                <?php else: ?>
                                                    <button type="button" class="action-btn-sm btn-activate"
                                                        onclick="toggleStatus(<?php echo $cls['id']; ?>, 'active')">Activate</button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>

    <!-- Hidden Status Form -->
    <form id="statusForm" action="../controller/ManageClassController.php" method="POST" style="display:none;">
        <input type="hidden" name="action" value="toggle_status">
        <input type="hidden" name="class_id" id="statusClassId">
        <input type="hidden" name="target_status" id="statusTarget">
    </form>

    <!-- VIEW CLASS MODAL (Read-Only) -->
    <div id="viewModal" class="modal-overlay">
        <div class="modal-card">
            <div class="modal-header">
                <h3 id="viewClassTitle">Class Details</h3>
                <span style="cursor:pointer; font-size: 20px;" onclick="closeModal('viewModal')">&times;</span>
            </div>
            <div id="viewModalBody">
                <div class="info-group">
                    <label>Class Code</label>
                    <p id="viewClassCode"></p>
                </div>
                <div class="info-group">
                    <label>Assigned Lecturer</label>
                    <p id="viewLecturerName"></p>
                </div>
                <div class="info-group">
                    <label>Enrolled Students (<span id="viewEnrolledRatio">0/0</span>)</label>
                    <div class="student-list-box" id="viewStudentList"></div>
                </div>
                <div class="info-group">
                    <label>Exam Details</label>
                    <p id="viewExamInfo"></p>
                </div>
            </div>
            <div style="margin-top: 20px; text-align: right;">
                <button type="button" class="btn-primary-small" onclick="switchToEditFromView()">Edit Class
                    Info</button>
                <button type="button" class="btn-secondary-small" onclick="closeModal('viewModal')">Close</button>
            </div>
        </div>
    </div>

    <!-- EDIT / CREATE CLASS MODAL -->
    <div id="classModal" class="modal-overlay">
        <div class="modal-card">
            <div class="modal-header">
                <h3 id="classModalTitle">Edit Class</h3>
                <span style="cursor:pointer; font-size: 20px;" onclick="closeModal('classModal')">&times;</span>
            </div>
            <form action="../controller/ManageClassController.php" method="POST">
                <input type="hidden" name="action" id="classFormAction" value="update_class">
                <input type="hidden" name="class_id" id="classId">

                <div class="form-grid">
                    <div class="form-group form-group-full">
                        <label>Module</label>
                        <select name="moduleId" id="modalModuleId" required>
                            <option value="">Select Module</option>
                            <?php foreach ($modulesList as $m): ?>
                                <option value="<?php echo $m['id']; ?>">
                                    <?php echo htmlspecialchars($m['code'] . " - " . $m['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Class Code</label>
                        <input type="text" name="classCode" id="modalClassCode" required>
                    </div>

                    <div class="form-group">
                        <label>Class Name</label>
                        <input type="text" name="className" id="modalClassName" required>
                    </div>

                    <div class="form-group">
                        <label>Day Of Week</label>
                        <select name="dayOfWeek" id="modalDayOfWeek" required>
                            <option value="mon">Monday</option>
                            <option value="tue">Tuesday</option>
                            <option value="wed">Wednesday</option>
                            <option value="thu">Thursday</option>
                            <option value="fri">Friday</option>
                            <option value="sat">Saturday</option>
                            <option value="sun">Sunday</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Venue</label>
                        <input type="text" name="room" id="modalRoom" required>
                    </div>

                    <div class="form-group">
                        <label>Start Time</label>
                        <input type="time" name="startTime" id="modalStartTime" required>
                    </div>

                    <div class="form-group">
                        <label>End Time</label>
                        <input type="time" name="endTime" id="modalEndTime" required>
                    </div>

                    <div class="form-group">
                        <label>Capacity</label>
                        <input type="number" name="capacity" id="modalCapacity" min="1" required>
                    </div>

                    <div class="form-group">
                        <label>Academic Year</label>
                        <input type="text" name="academicYear" id="modalAcademicYear" placeholder="e.g. 2026/2027"
                            required>
                    </div>

                    <div class="form-group">
                        <label>Semester / Term</label>
                        <input type="text" name="semester" id="modalSemester" list="semesterList"
                            placeholder="e.g. 1, 2, Special Term 1" required>
                        <datalist id="semesterList">
                            <option value="1">Semester 1</option>
                            <option value="2">Semester 2</option>
                            <option value="3">Semester 3</option>
                            <option value="Special Term 1">Special Term 1</option>
                            <option value="Special Term 2">Special Term 2</option>
                            <option value="Summer Term">Summer Term</option>
                        </datalist>
                    </div>

                    <div class="form-group form-group-full">
                        <label>Assign/Change Lecturer</label>
                        <select name="staffId" id="modalStaffId">
                            <option value="">-- Unassigned --</option>
                            <?php foreach ($lecturersList as $lec): ?>
                                <option value="<?php echo $lec['id']; ?>">
                                    <?php echo htmlspecialchars($lec['fullName']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Enrolled Students View & Add Student Section -->
                <div id="modalStudentsSection"
                    style="margin-top: 18px; padding-top: 14px; border-top: 1px dashed #cbd5e1;">
                    <h4 style="margin-bottom: 8px; color: #1e293b;">Enrolled Students (<span
                            id="modalEnrolledRatio">0/40</span>)</h4>
                    <div class="student-list-box" id="modalStudentList"></div>

                    <!-- Enrolment Controls -->
                    <div class="enrol-box" id="enrolBox">
                        <div style="font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 8px;">Add Student
                            to Class</div>
                        <div class="tab-buttons">
                            <button type="button" class="tab-btn active" id="btnTabSingle"
                                onclick="switchEnrolTab('single')">Single Student</button>
                            <button type="button" class="tab-btn" id="btnTabCsv" onclick="switchEnrolTab('csv')">Import
                                CSV</button>
                        </div>

                        <!-- Single Enrolment Sub-form -->
                        <div id="tabSingleContent">
                            <div style="display: flex; gap: 8px; align-items: center;">
                                <select id="singleStudentId"
                                    style="flex: 1; padding: 6px 10px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 13px;">
                                    <option value="">-- Select Student --</option>
                                    <?php foreach ($studentsList as $st): ?>
                                        <option value="<?php echo $st['id']; ?>">
                                            <?php echo htmlspecialchars($st['fullName'] . " (" . $st['email'] . ")"); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" class="btn-primary-small"
                                    style="padding: 6px 14px; font-size: 13px;" onclick="submitSingleEnrol()">+
                                    Enroll</button>
                            </div>
                        </div>

                        <!-- CSV Import Sub-form -->
                        <div id="tabCsvContent" style="display: none;">
                            <div style="display: flex; gap: 8px; align-items: center;">
                                <input type="file" id="csvFileInput" accept=".csv" style="flex: 1; font-size: 12px;">
                                <button type="button" class="btn-primary-small"
                                    style="padding: 6px 14px; font-size: 13px;" onclick="submitCsvEnrol()">Upload &
                                    Enroll</button>
                            </div>

                            <div class="csv-guide-box">
                                <strong>CSV Format Instructions:</strong>
                                <ul style="margin: 4px 0 6px 16px; padding: 0;">
                                    <li>First column should contain student <code>email</code> or
                                        <code>student_id</code>.
                                    </li>
                                    <li>Optional header row: <code>email</code> or <code>student_id</code>
                                        (automatically skipped).</li>
                                </ul>
                                <button type="button" onclick="downloadCsvTemplate()" class="btn-secondary-small"
                                    style="padding: 3px 8px; font-size: 11px;">Download Sample CSV Template</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Exam Schedule Section -->
                <div style="margin-top: 18px; padding-top: 14px; border-top: 1px dashed #cbd5e1;">
                    <h4 style="margin-bottom: 10px; color: #1e293b;">Exam Schedule</h4>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Exam Date</label>
                            <input type="date" name="examDate" id="modalExamDate">
                        </div>
                        <div class="form-group">
                            <label>Exam Venue</label>
                            <input type="text" name="examVenue" id="modalExamVenue" placeholder="e.g. Hall A">
                        </div>
                        <div class="form-group">
                            <label>Exam Start Time</label>
                            <input type="time" name="examStartTime" id="modalExamStartTime">
                        </div>
                        <div class="form-group">
                            <label>Exam End Time</label>
                            <input type="time" name="examEndTime" id="modalExamEndTime">
                        </div>
                    </div>
                </div>

                <div style="margin-top: 20px; text-align: right; display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn-secondary-small" onclick="closeModal('classModal')">Cancel</button>
                    <button type="submit" class="btn-primary-small">Save Changes</button>
                </div>
            </form>

            <!-- Hidden separate post form for enrolment and removal -->
            <form id="enrolPostForm" action="../controller/ManageClassController.php" method="POST"
                enctype="multipart/form-data" style="display: none;">
                <input type="hidden" name="action" id="enrolFormAction" value="enroll_student_single">
                <input type="hidden" name="class_id" id="enrolClassId">
                <input type="hidden" name="student_id" id="enrolStudentId">
                <input type="file" name="csv_file" id="enrolCsvFile">
            </form>
        </div>
    </div>

    <footer>
        <div class="footer-bottom-bar">
            &copy; 2026 UniBee. All rights reserved.
        </div>
    </footer>

    <script>
        let currentSelectedClassData = null;
        let currentEnrolledCount = 0;

        function openModal(id) {
            document.getElementById(id).classList.add('active');
        }

        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
        }

        function updateRatioDisplay() {
            const cap = document.getElementById('modalCapacity').value || 0;
            document.getElementById('modalEnrolledRatio').textContent = currentEnrolledCount + '/' + cap;
        }

        document.getElementById('modalCapacity').addEventListener('input', function () {
            updateRatioDisplay();
        });

        function switchEnrolTab(tab) {
            if (tab === 'single') {
                document.getElementById('tabSingleContent').style.display = 'block';
                document.getElementById('tabCsvContent').style.display = 'none';
                document.getElementById('btnTabSingle').classList.add('active');
                document.getElementById('btnTabCsv').classList.remove('active');
            } else {
                document.getElementById('tabSingleContent').style.display = 'none';
                document.getElementById('tabCsvContent').style.display = 'block';
                document.getElementById('btnTabSingle').classList.remove('active');
                document.getElementById('btnTabCsv').classList.add('active');
            }
        }

        function submitSingleEnrol() {
            const stId = document.getElementById('singleStudentId').value;
            if (!stId) {
                alert('Please select a student.');
                return;
            }
            document.getElementById('enrolFormAction').value = 'enroll_student_single';
            document.getElementById('enrolClassId').value = document.getElementById('classId').value;
            document.getElementById('enrolStudentId').value = stId;
            document.getElementById('enrolPostForm').submit();
        }

        function removeStudent(classId, studentId, studentName) {
            if (confirm(`Are you sure you want to remove ${studentName || 'this student'} from this class?`)) {
                document.getElementById('enrolFormAction').value = 'remove_student';
                document.getElementById('enrolClassId').value = classId;
                document.getElementById('enrolStudentId').value = studentId;
                document.getElementById('enrolPostForm').submit();
            }
        }

        function submitCsvEnrol() {
            const fileInput = document.getElementById('csvFileInput');
            if (!fileInput.files || fileInput.files.length === 0) {
                alert('Please choose a CSV file first.');
                return;
            }
            document.getElementById('enrolFormAction').value = 'import_students_csv';
            document.getElementById('enrolClassId').value = document.getElementById('classId').value;

            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(fileInput.files[0]);
            document.getElementById('enrolCsvFile').files = dataTransfer.files;

            document.getElementById('enrolPostForm').submit();
        }

        function downloadCsvTemplate() {
            const csvContent = "data:text/csv;charset=utf-8,email\nstudent1@unibee.edu\nstudent2@unibee.edu";
            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", "student_import_template.csv");
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        /* VIEW MODAL (PURELY READ-ONLY: NO REMOVE BUTTON) */
        function openViewModal(data) {
            currentSelectedClassData = data;
            document.getElementById('viewClassTitle').textContent = data.moduleCode + " - " + data.classCode;
            document.getElementById('viewClassCode').textContent = data.classCode;
            document.getElementById('viewLecturerName').textContent = data.lecturerName || 'Unassigned';

            const count = (data.enrolledStudents ? data.enrolledStudents.length : 0);
            document.getElementById('viewEnrolledRatio').textContent = count + '/' + (data.capacity || 0);

            const viewStudentContainer = document.getElementById('viewStudentList');
            viewStudentContainer.innerHTML = '';
            if (data.enrolledStudents && data.enrolledStudents.length > 0) {
                data.enrolledStudents.forEach(st => {
                    const div = document.createElement('div');
                    div.className = 'student-item';

                    const textSpan = document.createElement('span');
                    textSpan.textContent = st.fullName + ' (' + st.email + ')';

                    div.appendChild(textSpan);
                    viewStudentContainer.appendChild(div);
                });
            } else {
                viewStudentContainer.innerHTML = '<div style="color: #64748b; padding: 6px; font-size: 13px;">No enrolled students found.</div>';
            }

            if (data.examDate) {
                document.getElementById('viewExamInfo').textContent = data.examDate + " (" + (data.examStartTime || '') + " - " + (data.examEndTime || '') + ") @ " + (data.examVenue || 'TBA');
            } else {
                document.getElementById('viewExamInfo').textContent = 'No Exam Scheduled';
            }

            openModal('viewModal');
        }

        function switchToEditFromView() {
            closeModal('viewModal');
            if (currentSelectedClassData) {
                openEditModal(currentSelectedClassData);
            }
        }

        function openCreateModal() {
            document.getElementById('classFormAction').value = 'create_class';
            document.getElementById('classId').value = '';
            document.getElementById('classModalTitle').textContent = 'Create New Class';
            document.getElementById('modalModuleId').value = '';
            document.getElementById('modalClassCode').value = '';
            document.getElementById('modalClassName').value = '';
            document.getElementById('modalRoom').value = '';
            document.getElementById('modalStartTime').value = '';
            document.getElementById('modalEndTime').value = '';
            document.getElementById('modalCapacity').value = '40';
            document.getElementById('modalAcademicYear').value = '2026/2027';
            document.getElementById('modalSemester').value = '1';
            document.getElementById('modalStaffId').value = '';
            document.getElementById('modalExamDate').value = '';
            document.getElementById('modalExamVenue').value = '';
            document.getElementById('modalExamStartTime').value = '';
            document.getElementById('modalExamEndTime').value = '';

            document.getElementById('modalStudentsSection').style.display = 'none';
            openModal('classModal');
        }

        /* EDIT MODAL (HAS REMOVE BUTTON) */
        function openEditModal(data) {
            currentSelectedClassData = data;
            document.getElementById('classFormAction').value = 'update_class';
            document.getElementById('classId').value = data.id;
            document.getElementById('classModalTitle').textContent = 'Edit Class (' + data.classCode + ')';
            document.getElementById('modalModuleId').value = data.moduleId;
            document.getElementById('modalClassCode').value = data.classCode;
            document.getElementById('modalClassName').value = data.className || data.classCode;
            document.getElementById('modalDayOfWeek').value = data.dayOfWeek;
            document.getElementById('modalRoom').value = data.room;
            document.getElementById('modalStartTime').value = data.startTime;
            document.getElementById('modalEndTime').value = data.endTime;
            document.getElementById('modalCapacity').value = data.capacity || 40;
            document.getElementById('modalAcademicYear').value = data.academicYear || '2026/2027';
            document.getElementById('modalSemester').value = data.semester || '1';
            document.getElementById('modalStaffId').value = data.staffId || '';
            document.getElementById('modalExamDate').value = data.examDate || '';
            document.getElementById('modalExamVenue').value = data.examVenue || '';
            document.getElementById('modalExamStartTime').value = data.examStartTime || '';
            document.getElementById('modalExamEndTime').value = data.examEndTime || '';

            currentEnrolledCount = data.enrolledStudents ? data.enrolledStudents.length : 0;
            updateRatioDisplay();

            const modalStudentContainer = document.getElementById('modalStudentList');
            modalStudentContainer.innerHTML = '';
            if (data.enrolledStudents && data.enrolledStudents.length > 0) {
                data.enrolledStudents.forEach(st => {
                    const div = document.createElement('div');
                    div.className = 'student-item';

                    const textSpan = document.createElement('span');
                    textSpan.textContent = st.fullName + ' (' + st.email + ')';

                    const removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.className = 'btn-remove-sm';
                    removeBtn.textContent = 'Remove';
                    removeBtn.onclick = function () { removeStudent(data.id, st.id, st.fullName); };

                    div.appendChild(textSpan);
                    div.appendChild(removeBtn);
                    modalStudentContainer.appendChild(div);
                });
            } else {
                modalStudentContainer.innerHTML = '<div style="color: #64748b; padding: 6px; font-size: 13px;">No enrolled students found.</div>';
            }

            document.getElementById('modalStudentsSection').style.display = 'block';
            switchEnrolTab('single');
            openModal('classModal');
        }

        function toggleStatus(classId, targetStatus) {
            if (confirm('Are you sure you want to ' + targetStatus + ' this class?')) {
                document.getElementById('statusClassId').value = classId;
                document.getElementById('statusTarget').value = targetStatus;
                document.getElementById('statusForm').submit();
            }
        }
    </script>
</body>

</html>