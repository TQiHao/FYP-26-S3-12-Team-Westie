<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../controller/authGuard.php";
require_once "../entity/class.php";
require_once "../database/database.php";

class ManageClassController
{
    private $classEntity;

    public function __construct()
    {
        requireRole(['course_coordinator']);
        $this->classEntity = new ClassEntity();
    }

    public function getClasses($searchQuery = '', $facultyId = '', $departmentId = '', $statusFilter = '')
    {
        return $this->classEntity->getClasses($searchQuery, $facultyId, $departmentId, $statusFilter);
    }

    public function getModulesForCoordinator($universityId, $coordinatorEmail, $userId)
    {
        try {
            $allModules = $this->classEntity->getAllModules($universityId);
            if (empty($allModules)) {
                return []; // No modules exist in the database at all
            }

            $db = (new Database())->connect();

            // 1. If email is missing from session, fetch it from the Users table
            if (empty($coordinatorEmail) && $userId > 0) {
                try {
                    $stmt = $db->prepare("SELECT email FROM Users WHERE id = ?");
                    $stmt->execute([$userId]);
                    $coordinatorEmail = $stmt->fetchColumn();
                } catch (Exception $e) {
                    // Ignore, fallback will trigger
                }
            }

            if (empty($coordinatorEmail)) {
                return $allModules; // Fallback: show all if we can't find email
            }

            // 2. Detect the Programme table name (Programmes vs Programs)
            $tableName = 'Programmes';
            try {
                $db->query("SELECT 1 FROM Programmes LIMIT 1");
            } catch (Exception $e) {
                try {
                    $db->query("SELECT 1 FROM Programs LIMIT 1");
                    $tableName = 'Programs';
                } catch (Exception $e2) {
                    return $allModules; // Table not found, fallback to all
                }
            }

            // 3. Detect the correct email column
            $emailCol = 'courseCoordinatorEmail';
            try {
                $colsStmt = $db->query("SHOW COLUMNS FROM {$tableName}");
                $cols = $colsStmt->fetchAll(PDO::FETCH_COLUMN);

                if (!in_array('courseCoordinatorEmail', $cols)) {
                    if (in_array('coordinatorEmail', $cols))
                        $emailCol = 'coordinatorEmail';
                    elseif (in_array('coordinator_email', $cols))
                        $emailCol = 'coordinator_email';
                    elseif (in_array('email', $cols))
                        $emailCol = 'email';
                    else
                        $emailCol = $cols[1] ?? 'courseCoordinatorEmail';
                }
            } catch (Exception $e) {
                // Ignore column check errors
            }

            // 4. Fetch the programmes assigned to this coordinator (Case-Insensitive)
            $stmt = $db->prepare("SELECT code FROM {$tableName} WHERE LOWER({$emailCol}) = LOWER(?)");
            $stmt->execute([trim($coordinatorEmail)]);
            $assignedProgrammes = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // FALLBACK: If no programmes are found for this email, show ALL modules
            if (empty($assignedProgrammes)) {
                return $allModules;
            }

            // 5. Filter modules
            $filteredModules = [];
            foreach ($allModules as $mod) {
                foreach ($assignedProgrammes as $progCode) {
                    $cleanModCode = strtolower(str_replace(' ', '', $mod['code']));
                    $cleanProgCode = strtolower(str_replace(' ', '', $progCode));

                    // Match if module starts with programme code (e.g., "bcy101" starts with "bcy")
                    if (!empty($cleanProgCode) && strpos($cleanModCode, $cleanProgCode) === 0) {
                        $filteredModules[] = $mod;
                        break;
                    }
                }
            }

            // If filtering somehow resulted in 0 modules (e.g. codes don't match), fallback to all
            if (empty($filteredModules)) {
                return $allModules;
            }

            return $filteredModules;

        } catch (Exception $e) {
            error_log("getModulesForCoordinator error: " . $e->getMessage());
            return $this->classEntity->getAllModules($universityId); // Final fallback
        }
    }

    public function getEnrolledStudents($classId)
    {
        return $this->classEntity->getEnrolledStudents($classId);
    }

    public function getExamsByClassId($classId)
    {
        return $this->classEntity->getExamsByClassId($classId);
    }

    public function getLecturers($universityId)
    {
        return $this->classEntity->getLecturers($universityId);
    }

    public function getStudents($universityId)
    {
        return $this->classEntity->getStudents($universityId);
    }

    public function getUniversityStudentFiles($universityId)
    {
        return $this->classEntity->getStudentFilesFromFolder($universityId);
    }

    public function createClass($data, $coordinatorId)
    {
        $result = $this->classEntity->createClass($data, $coordinatorId);

        if (is_array($result) && ($result['status'] ?? '') === 'SUCCESS_CREATE') {
            $newId = (int) ($result['newId'] ?? 0);
            $staffId = !empty($data['staffId']) ? (int) $data['staffId'] : 0;

            if ($newId > 0 && $staffId > 0) {
                $this->notifyLecturerAssignment($staffId, $newId);
            }

            if ($newId > 0 && $this->hasExamData($data)) {
                $this->notifyExamSchedule($newId);
            }
        }

        return $result;
    }

    public function updateClass($classId, $data, $currentUserId)
    {
        $oldStaffId = 0;
        try {
            $db = (new Database())->connect();
            $stmt = $db->prepare("SELECT staffId FROM Classes WHERE id = ?");
            $stmt->execute([$classId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $oldStaffId = (int) ($row['staffId'] ?? 0);
            }
        } catch (Exception $e) {
            error_log("Fetch old staffId error: " . $e->getMessage());
        }

        $result = $this->classEntity->updateClass($classId, $data, $currentUserId);

        if (is_array($result) && ($result['status'] ?? '') === 'SUCCESS_UPDATE') {
            $newStaffId = !empty($data['staffId']) ? (int) $data['staffId'] : 0;
            if ($newStaffId > 0 && $newStaffId !== $oldStaffId) {
                $this->notifyLecturerAssignment($newStaffId, $classId);
            }

            if ($this->hasExamData($data)) {
                $this->notifyExamSchedule($classId);
            }
        }

        return $result;
    }

    public function toggleClassStatus($classId, $targetStatus)
    {
        $result = $this->classEntity->toggleClassStatus($classId, $targetStatus);

        if ($result === "SUCCESS_STATUS") {
            $this->notifyClassStatusChange($classId, $targetStatus);
        }

        return $result;
    }

    public function enrollStudentSingle($classId, $studentId, $enrolledBy)
    {
        $result = $this->classEntity->enrollStudentSingle($classId, $studentId, $enrolledBy);

        if ($result === "SUCCESS_ENROLL") {
            $this->notifyStudentEnrolment($studentId, $classId);
        }

        return $result;
    }

    public function removeStudentFromClass($classId, $studentId)
    {
        return $this->classEntity->removeStudentFromClass($classId, $studentId);
    }

    public function enrolFromFile($classId, $fileName, $universityId, $enrolledBy)
    {
        $file = $this->classEntity->readStudentFileByFilename($fileName, $universityId);
        if (!$file) {
            return ['error' => 'FILE_NOT_FOUND', 'enrolled' => 0, 'skippedClash' => [], 'skippedMissing' => 0];
        }

        $handle = fopen($file['fullPath'], "r");
        if (!$handle) {
            return ['error' => 'FILE_ERROR', 'enrolled' => 0, 'skippedClash' => [], 'skippedMissing' => 0];
        }

        $enrolledCount = 0;
        $skippedClash = [];
        $skippedMissing = 0;

        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if (empty($row[0]))
                continue;
            $identifier = trim($row[0]);

            if (in_array(strtolower($identifier), ['email', 'student_id', 'id']))
                continue;

            $student = $this->classEntity->findActiveStudentByIdentifier($identifier);
            if (!$student) {
                $skippedMissing++;
                continue;
            }

            $res = $this->classEntity->enrollStudentSingle($classId, $student['id'], $enrolledBy);
            if ($res === "SUCCESS_ENROLL") {
                $enrolledCount++;
                $this->notifyStudentEnrolment($student['id'], $classId);
            } elseif (strpos($res, "STUDENT_CLASH:") === 0) {
                $skippedClash[] = [
                    'identifier' => $identifier,
                    'clashWith' => substr($res, strlen("STUDENT_CLASH:")),
                ];
            }
        }
        fclose($handle);

        return [
            'error' => null,
            'enrolled' => $enrolledCount,
            'skippedClash' => $skippedClash,
            'skippedMissing' => $skippedMissing,
        ];
    }

    public static function isSemesterActive($checkDateStr, $academicYear, $semesterText)
    {
        $time = strtotime($checkDateStr);
        if (!$time)
            return true;
        $checkMonth = (int) date('n', $time);
        $checkYear = (int) date('Y', $time);

        $startYear = 0;
        $endYear = 0;
        if (preg_match('/(\d{4})\s*\/\s*(\d{4})/', $academicYear, $m)) {
            $startYear = (int) $m[1];
            $endYear = (int) $m[2];
        } else {
            return true;
        }

        $validMonths = [];
        if (preg_match('/([a-zA-Z]{3,})\s*(?:-|to|until)\s*([a-zA-Z]{3,})/i', $semesterText, $m)) {
            $months = [
                'jan' => 1,
                'feb' => 2,
                'mar' => 3,
                'apr' => 4,
                'may' => 5,
                'jun' => 6,
                'jul' => 7,
                'aug' => 8,
                'sep' => 9,
                'oct' => 10,
                'nov' => 11,
                'dec' => 12
            ];
            $sM = $months[strtolower(substr($m[1], 0, 3))] ?? 0;
            $eM = $months[strtolower(substr($m[2], 0, 3))] ?? 0;

            if ($sM && $eM) {
                if ($sM <= $eM) {
                    for ($i = $sM; $i <= $eM; $i++)
                        $validMonths[] = $i;
                } else {
                    for ($i = $sM; $i <= 12; $i++)
                        $validMonths[] = $i;
                    for ($i = 1; $i <= $eM; $i++)
                        $validMonths[] = $i;
                }
            }
        }

        if (empty($validMonths))
            return true;

        if (!in_array($checkMonth, $validMonths))
            return false;

        if ($validMonths[0] >= 8) {
            if ($checkYear !== $startYear && $checkYear !== $endYear)
                return false;
            if ($validMonths[0] >= 8 && end($validMonths) <= 5) {
                if ($checkMonth >= 8 && $checkYear !== $startYear)
                    return false;
                if ($checkMonth <= 5 && $checkYear !== $endYear)
                    return false;
            } else {
                if ($checkYear !== $startYear)
                    return false;
            }
        } else {
            if ($checkYear !== $endYear)
                return false;
        }

        return true;
    }

    public function validateDatesAgainstSemester($data)
    {
        $ay = trim($data['academicYear'] ?? '');
        $sem = trim($data['semester'] ?? '');

        $datesToCheck = [];
        if (!empty($data['startDate']))
            $datesToCheck[] = $data['startDate'];
        if (!empty($data['endDate']))
            $datesToCheck[] = $data['endDate'];

        if (!empty($data['examDate']) && is_array($data['examDate'])) {
            foreach ($data['examDate'] as $d) {
                if (!empty($d))
                    $datesToCheck[] = $d;
            }
        }

        if (empty($ay) || empty($datesToCheck))
            return null;

        foreach ($datesToCheck as $dateStr) {
            if (!self::isSemesterActive($dateStr, $ay, $sem)) {
                return "Date ({$dateStr}) is outside the allowed period for {$sem} ({$ay}).";
            }
        }
        return null;
    }

    private function hasExamData($data)
    {
        if (empty($data['examDate']) || !is_array($data['examDate'])) {
            return false;
        }
        foreach ($data['examDate'] as $d) {
            if (trim($d) !== '') {
                return true;
            }
        }
        return false;
    }

    private function getClassInfo($classId)
    {
        try {
            $db = (new Database())->connect();
            $stmt = $db->prepare(
                "SELECT c.className, c.classCode, c.dayOfWeek, c.startTime, c.endTime, c.room,
                        c.academicYear, c.semester, c.staffId,
                        m.name AS moduleName, m.code AS moduleCode
                 FROM Classes c
                 JOIN Modules m ON m.id = c.moduleId
                 WHERE c.id = ?"
            );
            $stmt->execute([$classId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("getClassInfo error: " . $e->getMessage());
            return null;
        }
    }

    private function buildTermLabel($info)
    {
        $ay = trim($info['academicYear'] ?? '');
        $sem = preg_replace('/^Semester\s+/i', '', trim($info['semester'] ?? ''));
        $parts = [];
        if ($ay !== '') {
            $parts[] = 'AY' . $ay;
        }
        if ($sem !== '') {
            $parts[] = 'Semester ' . $sem;
        }
        return $parts ? ' (' . implode(', ', $parts) . ')' : '';
    }

    private function notifyStudentEnrolment($studentId, $classId)
    {
        $info = $this->getClassInfo($classId);
        if (!$info)
            return;

        $title = "New module enrolment: " . $info['moduleCode'] . " - " . $info['moduleName'];
        $message = "You have been enrolled in " . $info['moduleCode'] . " - " . $info['moduleName']
            . " (Class " . $info['classCode'] . ")" . $this->buildTermLabel($info) . ".";

        $this->insertNotification($studentId, 'class', $title, $message);
    }

    private function notifyLecturerAssignment($staffId, $classId)
    {
        $info = $this->getClassInfo($classId);
        if (!$info)
            return;

        $title = "New module assignment: " . $info['moduleCode'] . " - " . $info['classCode'];
        $message = "You have been assigned to teach " . $info['moduleCode'] . " - " . $info['moduleName']
            . " (Class " . $info['classCode'] . ") on " . ucfirst($info['dayOfWeek'])
            . " " . substr($info['startTime'], 0, 5) . "-" . substr($info['endTime'], 0, 5)
            . " in Room " . $info['room'] . $this->buildTermLabel($info) . ".";

        $this->insertNotification($staffId, 'class', $title, $message);
    }

    private function notifyClassStatusChange($classId, $targetStatus)
    {
        $info = $this->getClassInfo($classId);
        if (!$info)
            return;

        $label = ($targetStatus === 'suspended') ? 'suspended' : 'reactivated';
        $title = "Class " . $label . ": " . $info['classCode'];
        $baseMessage = "Class " . $info['classCode'] . " (" . $info['moduleCode'] . " - " . $info['moduleName']
            . ")" . $this->buildTermLabel($info) . " has been " . $label . " by the Course Coordinator.";

        try {
            $db = (new Database())->connect();
            $stmt = $db->prepare("SELECT studentId FROM StudentEnrolments WHERE classId = ? AND status = 'enrolled'");
            $stmt->execute([$classId]);
            $students = $stmt->fetchAll(PDO::FETCH_COLUMN);

            foreach ($students as $sid) {
                $this->insertNotification((int) $sid, 'class', $title, $baseMessage);
            }

            if (!empty($info['staffId'])) {
                $this->insertNotification((int) $info['staffId'], 'class', $title, $baseMessage);
            }
        } catch (Exception $e) {
            error_log("notifyClassStatusChange error: " . $e->getMessage());
        }
    }

    private function notifyExamSchedule($classId)
    {
        $info = $this->getClassInfo($classId);
        if (!$info)
            return;

        $exams = $this->classEntity->getExamsByClassId($classId);
        if (empty($exams))
            return;

        $db = (new Database())->connect();

        $firstExam = $exams[0];
        $examCount = count($exams);
        $firstDate = date('d M Y', strtotime($firstExam['examDate']));

        if ($examCount === 1) {
            $title = "Exam scheduled: " . $info['moduleCode'] . " - " . $firstDate;
        } else {
            $title = "Exams scheduled: " . $info['moduleCode'] . " - " . $examCount . " exams";
        }

        $lines = [];
        foreach ($exams as $ex) {
            $dateStr = date('d M Y', strtotime($ex['examDate']));
            $timeStr = substr($ex['startTime'], 0, 5) . "-" . substr($ex['endTime'], 0, 5);
            $lines[] = "- " . $dateStr . " (" . $timeStr . ") at " . $ex['venue'];
        }

        $message = "Exam schedule for " . $info['moduleCode'] . " - " . $info['moduleName']
            . " (Class " . $info['classCode'] . ")" . $this->buildTermLabel($info) . ":\n"
            . implode("\n", $lines);

        try {
            $stmt = $db->prepare("SELECT studentId FROM StudentEnrolments WHERE classId = ? AND status = 'enrolled'");
            $stmt->execute([$classId]);
            $students = $stmt->fetchAll(PDO::FETCH_COLUMN);

            foreach ($students as $sid) {
                $this->insertNotification((int) $sid, 'exam', $title, $message);
            }

            if (!empty($info['staffId'])) {
                $this->insertNotification((int) $info['staffId'], 'exam', $title, $message);
            }
        } catch (Exception $e) {
            error_log("notifyExamSchedule error: " . $e->getMessage());
        }
    }

    private function insertNotification($userId, $type, $title, $message)
    {
        try {
            $db = (new Database())->connect();
            $check = $db->prepare("SELECT COUNT(*) FROM Notifications WHERE userId = ? AND title = ?");
            $check->execute([$userId, $title]);
            if ((int) $check->fetchColumn() > 0) {
                return;
            }
            $ins = $db->prepare("INSERT INTO Notifications (userId, type, title, message) VALUES (?, ?, ?, ?)");
            $ins->execute([$userId, $type, $title, $message]);
        } catch (Exception $e) {
            error_log("insertNotification error: " . $e->getMessage());
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    requireRole(['course_coordinator']);

    $controller = new ManageClassController();
    $currentUserId = $_SESSION['user_id'] ?? 1;
    $universityId = $_SESSION['university_id'] ?? 0;
    $action = $_POST['action'];

    if ($action === 'create_class' || $action === 'update_class') {
        $validationError = $controller->validateDatesAgainstSemester($_POST);
        if ($validationError) {
            $_SESSION['flash_error'] = "Cannot " . ($action === 'create_class' ? 'create' : 'update') . ": " . $validationError;
            $_SESSION['reopen_class_form'] = ($action === 'create_class') ? 'create' : 'edit';
            $_SESSION['class_form_data'] = $_POST;

            $returnParams = [];
            if (isset($_GET['search']) && $_GET['search'] !== '')
                $returnParams['search'] = $_GET['search'];
            if (isset($_GET['status']) && in_array($_GET['status'], ['', 'active', 'suspended'], true))
                $returnParams['status'] = $_GET['status'];
            $redirectQs = $returnParams ? '?' . http_build_query($returnParams) : '';

            header("Location: ../boundary/manageClassPage.php" . $redirectQs);
            exit();
        }
    }

    if ($action === 'create_class') {
        $res = $controller->createClass($_POST, $currentUserId);
        $status = is_array($res) ? ($res['status'] ?? 'DB_ERROR') : $res;
        $newId = is_array($res) ? ($res['newId'] ?? 0) : 0;

        if ($status === "DUPLICATE_CODE") {
            $_SESSION['flash_error'] = "Cannot create: A class with this Class Code already exists for this module.";
            $_SESSION['reopen_class_form'] = 'create';
            $_SESSION['class_form_data'] = $_POST;
        } elseif ($status === "SCHEDULE_CLASH") {
            $_SESSION['flash_error'] = "Cannot create: This room is already booked at the same day/time.";
            $_SESSION['reopen_class_form'] = 'create';
            $_SESSION['class_form_data'] = $_POST;
        } elseif ($status === "LECTURER_CLASH") {
            $c = $res['clash'] ?? null;
            if ($c) {
                $_SESSION['flash_error'] = "Cannot create: The lecturer already teaches " . $c['classCode'] . " on " . ucfirst($c['dayOfWeek']) . " " . substr($c['startTime'], 0, 5) . "-" . substr($c['endTime'], 0, 5) . ".";
            } else {
                $_SESSION['flash_error'] = "Cannot create: The lecturer has another class at that time.";
            }
            $_SESSION['reopen_class_form'] = 'create';
            $_SESSION['class_form_data'] = $_POST;
        } elseif ($status === "SUCCESS_CREATE" && $newId > 0) {
            $_SESSION['flash_message'] = "Class created successfully.";
            $_SESSION['auto_open_edit'] = $newId;
        } else {
            $_SESSION['flash_error'] = "Failed to create class.";
        }
    } elseif ($action === 'update_class') {
        $res = $controller->updateClass($_POST['class_id'], $_POST, $currentUserId);
        $status = is_array($res) ? ($res['status'] ?? 'DB_ERROR') : $res;

        if ($status === "DUPLICATE_CODE") {
            $_SESSION['flash_error'] = "Cannot update: A class with this Class Code already exists for this module.";
            $_SESSION['reopen_class_form'] = 'edit';
            $_SESSION['class_form_data'] = $_POST;
        } elseif ($status === "SCHEDULE_CLASH") {
            $_SESSION['flash_error'] = "Cannot update: This room is already booked at the same day/time.";
            $_SESSION['reopen_class_form'] = 'edit';
            $_SESSION['class_form_data'] = $_POST;
        } elseif ($status === "LECTURER_CLASH") {
            $c = $res['clash'] ?? null;
            if ($c) {
                $_SESSION['flash_error'] = "Cannot update: The lecturer already teaches " . $c['classCode'] . " on " . ucfirst($c['dayOfWeek']) . " " . substr($c['startTime'], 0, 5) . "-" . substr($c['endTime'], 0, 5) . ".";
            } else {
                $_SESSION['flash_error'] = "Cannot update: The lecturer has another class at that time.";
            }
            $_SESSION['reopen_class_form'] = 'edit';
            $_SESSION['class_form_data'] = $_POST;
        } elseif ($status === "SUCCESS_UPDATE") {
            $_SESSION['flash_message'] = "Class updated successfully.";
        } else {
            $_SESSION['flash_error'] = "Failed to update class.";
        }
    } elseif ($action === 'toggle_status') {
        $res = $controller->toggleClassStatus($_POST['class_id'], $_POST['target_status']);
        $_SESSION['flash_message'] = ($res === "SUCCESS_STATUS") ? "Class status updated successfully." : "Failed to change status.";
    } elseif ($action === 'enroll_student_single') {
        $res = $controller->enrollStudentSingle($_POST['class_id'], $_POST['student_id'], $currentUserId);
        if ($res === "CAPACITY_FULL") {
            $_SESSION['flash_error'] = "Class capacity reached.";
        } elseif ($res === "ALREADY_ENROLLED") {
            $_SESSION['flash_error'] = "Student is already enrolled.";
        } elseif (strpos($res, "STUDENT_CLASH:") === 0) {
            $clashClass = substr($res, strlen("STUDENT_CLASH:"));
            $_SESSION['flash_error'] = "Cannot enroll: This student is already in $clashClass at the same day/time.";
        } elseif ($res === "SUCCESS_ENROLL") {
            $_SESSION['flash_message'] = "Student enrolled successfully.";
        } else {
            $_SESSION['flash_error'] = "Failed to enroll student.";
        }
    } elseif ($action === 'remove_student') {
        $res = $controller->removeStudentFromClass($_POST['class_id'], $_POST['student_id']);
        if ($res === "SUCCESS_REMOVE") {
            $_SESSION['flash_message'] = "Student removed from class successfully.";
        } else {
            $_SESSION['flash_error'] = "Failed to remove student.";
        }
    } elseif ($action === 'enrol_from_file') {
        $fileName = trim($_POST['file_name'] ?? '');
        $result = $controller->enrolFromFile($_POST['class_id'], $fileName, $universityId, $currentUserId);

        if ($result['error'] === 'FILE_NOT_FOUND') {
            $_SESSION['flash_error'] = "The selected file no longer exists.";
        } elseif ($result['error']) {
            $_SESSION['flash_error'] = "Failed to process the file.";
        } else {
            $msg = "Enrolled {$result['enrolled']} student(s).";
            if (!empty($result['skippedClash'])) {
                $names = array_map(function ($s) {
                    return $s['identifier']; }, $result['skippedClash']);
                $msg .= " Skipped " . count($names) . " due to clash: " . implode(', ', array_slice($names, 0, 5));
                if (count($names) > 5)
                    $msg .= " (+" . (count($names) - 5) . " more)";
                $msg .= ".";
            }
            if (!empty($result['skippedMissing'])) {
                $msg .= " {$result['skippedMissing']} identifier(s) not found.";
            }
            $_SESSION['flash_message'] = $msg;
        }
    }

    $returnParams = [];
    if (isset($_GET['search']) && $_GET['search'] !== '')
        $returnParams['search'] = $_GET['search'];
    if (isset($_GET['status']) && in_array($_GET['status'], ['', 'active', 'suspended'], true))
        $returnParams['status'] = $_GET['status'];
    $redirectQs = $returnParams ? '?' . http_build_query($returnParams) : '';

    header("Location: ../boundary/manageClassPage.php" . $redirectQs);
    exit();
}