<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../entity/class.php";

class ManageClassController
{
    private $classEntity;

    public function __construct()
    {
        $this->classEntity = new ClassEntity();
    }

    public function getClasses($searchQuery = '', $facultyId = '', $departmentId = '', $statusFilter = '')
    {
        return $this->classEntity->getClasses($searchQuery, $facultyId, $departmentId, $statusFilter);
    }

    public function getEnrolledStudents($classId)
    {
        return $this->classEntity->getEnrolledStudents($classId);
    }

    public function getAllModules()
    {
        return $this->classEntity->getAllModules();
    }

    public function getLecturers()
    {
        return $this->classEntity->getLecturers();
    }

    public function getStudents()
    {
        return $this->classEntity->getStudents();
    }

    public function getUniversityStudentFiles($universityId)
    {
        return $this->classEntity->getStudentFilesFromFolder($universityId);
    }

    public function createClass($data, $coordinatorId)
    {
        return $this->classEntity->createClass($data, $coordinatorId);
    }

    public function updateClass($classId, $data, $currentUserId)
    {
        return $this->classEntity->updateClass($classId, $data, $currentUserId);
    }

    public function toggleClassStatus($classId, $targetStatus)
    {
        return $this->classEntity->toggleClassStatus($classId, $targetStatus);
    }

    public function enrollStudentSingle($classId, $studentId, $enrolledBy)
    {
        return $this->classEntity->enrollStudentSingle($classId, $studentId, $enrolledBy);
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
}

// ===== POST HANDLERS =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $controller = new ManageClassController();
    $currentUserId = $_SESSION['user_id'] ?? 1;
    $universityId = $_SESSION['university_id'] ?? 0;
    $action = $_POST['action'];

    if ($action === 'create_class') {
        $res = $controller->createClass($_POST, $currentUserId);
        $status = is_array($res) ? ($res['status'] ?? 'DB_ERROR') : $res;
        $newId = is_array($res) ? ($res['newId'] ?? 0) : 0;

        if ($status === "DUPLICATE_CODE") {
            $_SESSION['flash_error'] = "Cannot create: A class with this Class Code already exists for this module.";
        } elseif ($status === "SCHEDULE_CLASH") {
            $_SESSION['flash_error'] = "Cannot create: This room is already booked at the same day/time.";
        } elseif ($status === "LECTURER_CLASH") {
            $c = $res['clash'] ?? null;
            if ($c) {
                $_SESSION['flash_error'] = "Cannot create: The lecturer already teaches "
                    . $c['classCode'] . " on " . ucfirst($c['dayOfWeek']) . " "
                    . substr($c['startTime'], 0, 5) . "-" . substr($c['endTime'], 0, 5) . ".";
            } else {
                $_SESSION['flash_error'] = "Cannot create: The lecturer has another class at that time.";
            }
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
        } elseif ($status === "SCHEDULE_CLASH") {
            $_SESSION['flash_error'] = "Cannot update: This room is already booked at the same day/time.";
        } elseif ($status === "LECTURER_CLASH") {
            $c = $res['clash'] ?? null;
            if ($c) {
                $_SESSION['flash_error'] = "Cannot update: The lecturer already teaches "
                    . $c['classCode'] . " on " . ucfirst($c['dayOfWeek']) . " "
                    . substr($c['startTime'], 0, 5) . "-" . substr($c['endTime'], 0, 5) . ".";
            } else {
                $_SESSION['flash_error'] = "Cannot update: The lecturer has another class at that time.";
            }
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
                    return $s['identifier'];
                }, $result['skippedClash']);
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
    if (isset($_GET['search']) && $_GET['search'] !== '') {
        $returnParams['search'] = $_GET['search'];
    }
    if (isset($_GET['status']) && in_array($_GET['status'], ['', 'active', 'suspended'], true)) {
        $returnParams['status'] = $_GET['status'];
    }
    $redirectQs = $returnParams ? '?' . http_build_query($returnParams) : '';

    header("Location: ../boundary/manageClassPage.php" . $redirectQs);
    exit();
}