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

    public function importStudentsCsv($classId, $file, $enrolledBy)
    {
        if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
            return "NO_FILE";
        }

        $handle = fopen($file['tmp_name'], "r");
        if (!$handle) {
            return "FILE_ERROR";
        }

        $enrolledCount = 0;

        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if (empty($row[0])) {
                continue;
            }
            $identifier = trim($row[0]);

            if (in_array(strtolower($identifier), ['email', 'student_id', 'id'])) {
                continue;
            }

            $student = $this->classEntity->findActiveStudentByIdentifier($identifier);

            if ($student) {
                $res = $this->classEntity->enrollStudentSingle($classId, $student['id'], $enrolledBy);
                if ($res === "SUCCESS_ENROLL") {
                    $enrolledCount++;
                }
            }
        }
        fclose($handle);

        return "SUCCESS_CSV";
    }
}

// ===== HANDLE POST ACTIONS =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $controller = new ManageClassController();
    $currentUserId = $_SESSION['user_id'] ?? 1;
    $action = $_POST['action'];

    if ($action === 'create_class') {
        $res = $controller->createClass($_POST, $currentUserId);
        if ($res === "DUPLICATE_CODE") {
            $_SESSION['flash_error'] = "Cannot create: A class with this Class Code already exists for this module.";
        } elseif ($res === "SCHEDULE_CLASH") {
            $_SESSION['flash_error'] = "Cannot create: Schedule conflict with another class at the same venue and time.";
        } elseif ($res === "SUCCESS_CREATE") {
            $_SESSION['flash_message'] = "Class created successfully.";
        } else {
            $_SESSION['flash_error'] = "Failed to create class.";
        }
    } elseif ($action === 'update_class') {
        $res = $controller->updateClass($_POST['class_id'], $_POST, $currentUserId);
        if ($res === "DUPLICATE_CODE") {
            $_SESSION['flash_error'] = "Cannot update: A class with this Class Code already exists for this module.";
        } elseif ($res === "SCHEDULE_CLASH") {
            $_SESSION['flash_error'] = "Cannot update: Schedule conflict with another class at the same venue and time.";
        } elseif ($res === "SUCCESS_UPDATE") {
            $_SESSION['flash_message'] = "Class updated successfully.";
        } else {
            $_SESSION['flash_error'] = "Failed to update class.";
        }
    } elseif ($action === 'toggle_status') {
        $res = $controller->toggleClassStatus($_POST['class_id'], $_POST['target_status']);
        $_SESSION['flash_message'] = ($res === "SUCCESS_STATUS") ? "Class status updated successfully." : "Failed to change status.";
    } elseif ($action === 'enroll_student_single') {
        $res = $controller->enrollStudentSingle($_POST['class_id'], $_POST['student_id'], $currentUserId);
        if ($res === "CAPACITY_FULL")
            $_SESSION['flash_error'] = "Class capacity reached.";
        elseif ($res === "ALREADY_ENROLLED")
            $_SESSION['flash_error'] = "Student is already enrolled.";
        elseif ($res === "SUCCESS_ENROLL")
            $_SESSION['flash_message'] = "Student enrolled successfully.";
        else
            $_SESSION['flash_error'] = "Failed to enroll student.";
    } elseif ($action === 'remove_student') {
        $res = $controller->removeStudentFromClass($_POST['class_id'], $_POST['student_id']);
        if ($res === "SUCCESS_REMOVE") {
            $_SESSION['flash_message'] = "Student removed from class successfully.";
        } else {
            $_SESSION['flash_error'] = "Failed to remove student.";
        }
    } elseif ($action === 'import_students_csv') {
        $res = $controller->importStudentsCsv($_POST['class_id'], $_FILES['csv_file'] ?? [], $currentUserId);
        if ($res === "SUCCESS_CSV") {
            $_SESSION['flash_message'] = "CSV student enrolment processed successfully.";
        } else {
            $_SESSION['flash_error'] = "Failed to process CSV file.";
        }
    }

    header("Location: ../boundary/manageClassPage.php");
    exit();
}