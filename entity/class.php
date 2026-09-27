<?php
require_once "../database/database.php";

class ClassEntity
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
    }

    /**
     * Get classes filtered by Search and Status
     */
    public function getClasses($searchQuery = '', $facultyId = '', $departmentId = '', $statusFilter = '')
    {
        try {
            $sql = "SELECT c.*, 
                           m.code AS moduleCode, 
                           m.name AS moduleName,
                           f.name AS facultyName,
                           u_staff.fullName AS lecturerName,
                           (SELECT COUNT(*) FROM StudentEnrolments se WHERE se.classId = c.id AND se.status = 'enrolled') AS enrolledCount,
                           e.examDate, e.startTime AS examStartTime, e.endTime AS examEndTime, e.venue AS examVenue
                    FROM Classes c
                    JOIN Modules m ON c.moduleId = m.id
                    LEFT JOIN Programmes p ON m.programmeId = p.id
                    LEFT JOIN Faculties f ON p.facultyId = f.id
                    LEFT JOIN Users u_staff ON c.staffId = u_staff.id
                    LEFT JOIN Exam e ON e.classId = c.id
                    WHERE 1=1";

            $params = [];

            if (!empty($searchQuery)) {
                $sql .= " AND (c.className LIKE ? OR c.classCode LIKE ? OR m.name LIKE ? OR m.code LIKE ?)";
                $term = "%" . $searchQuery . "%";
                $params = array_merge($params, [$term, $term, $term, $term]);
            }

            if (!empty($statusFilter)) {
                $sql .= " AND c.status = ?";
                $params[] = $statusFilter;
            }

            $sql .= " ORDER BY c.id DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Get Classes Error: " . $e->getMessage());
            return [];
        }
    }

    public function getEnrolledStudents($classId)
    {
        try {
            $stmt = $this->db->prepare("SELECT u.id, u.fullName, u.email, se.enrolmentDate 
                                       FROM StudentEnrolments se 
                                       JOIN Users u ON se.studentId = u.id 
                                       WHERE se.classId = ? AND se.status = 'enrolled' 
                                       ORDER BY u.fullName ASC");
            $stmt->execute([$classId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    public function getAllModules()
    {
        try {
            $stmt = $this->db->prepare("SELECT id, code, name FROM Modules ORDER BY code ASC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    public function getLecturers()
    {
        try {
            $stmt = $this->db->prepare("SELECT id, fullName, email FROM Users WHERE role = 'lecturer' AND status = 'active' ORDER BY fullName ASC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    public function getStudents()
    {
        try {
            $stmt = $this->db->prepare("SELECT id, fullName, email FROM Users WHERE role = 'student' AND status = 'active' ORDER BY fullName ASC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Check duplicate code or venue/schedule collision
     */
    public function checkClassConflict($moduleId, $classCode, $dayOfWeek, $room, $startTime, $endTime, $excludeClassId = null)
    {
        $sqlDup = "SELECT id FROM Classes WHERE moduleId = ? AND classCode = ?";
        $paramsDup = [$moduleId, $classCode];
        if ($excludeClassId) {
            $sqlDup .= " AND id != ?";
            $paramsDup[] = $excludeClassId;
        }
        $stmtDup = $this->db->prepare($sqlDup);
        $stmtDup->execute($paramsDup);
        if ($stmtDup->fetch()) {
            return "DUPLICATE_CODE";
        }

        $sqlClash = "SELECT id FROM Classes WHERE room = ? AND dayOfWeek = ? AND status = 'active' AND (startTime < ? AND endTime > ?)";
        $paramsClash = [$room, $dayOfWeek, $endTime, $startTime];
        if ($excludeClassId) {
            $sqlClash .= " AND id != ?";
            $paramsClash[] = $excludeClassId;
        }
        $stmtClash = $this->db->prepare($sqlClash);
        $stmtClash->execute($paramsClash);
        if ($stmtClash->fetch()) {
            return "SCHEDULE_CLASH";
        }

        return "OK";
    }

    public function createClass($data, $coordinatorId)
    {
        try {
            $conflict = $this->checkClassConflict($data['moduleId'], $data['classCode'], $data['dayOfWeek'], $data['room'], $data['startTime'], $data['endTime']);
            if ($conflict === "DUPLICATE_CODE")
                return "DUPLICATE_CODE";
            if ($conflict === "SCHEDULE_CLASH")
                return "SCHEDULE_CLASH";

            $sql = "INSERT INTO Classes (moduleId, courseCoordinatorId, staffId, className, classCode, dayOfWeek, startTime, endTime, room, capacity, academicYear, semester, status, createdAt, updatedAt)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW(), NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['moduleId'],
                $coordinatorId,
                !empty($data['staffId']) ? $data['staffId'] : NULL,
                $data['className'],
                $data['classCode'],
                $data['dayOfWeek'],
                $data['startTime'],
                $data['endTime'],
                $data['room'],
                $data['capacity'],
                $data['academicYear'] ?? '2026/2027',
                $data['semester'] ?? '1'
            ]);

            $newClassId = $this->db->lastInsertId();

            if (!empty($data['examDate'])) {
                $this->saveExamPlan($newClassId, $data['examDate'], $data['examStartTime'], $data['examEndTime'], $data['examVenue'], $coordinatorId);
            }

            return "SUCCESS_CREATE";
        } catch (Exception $e) {
            return "DB_ERROR";
        }
    }

    public function updateClass($classId, $data, $currentUserId)
    {
        try {
            $conflict = $this->checkClassConflict($data['moduleId'], $data['classCode'], $data['dayOfWeek'], $data['room'], $data['startTime'], $data['endTime'], $classId);
            if ($conflict === "DUPLICATE_CODE")
                return "DUPLICATE_CODE";
            if ($conflict === "SCHEDULE_CLASH")
                return "SCHEDULE_CLASH";

            $sql = "UPDATE Classes 
                    SET moduleId = ?, staffId = ?, className = ?, classCode = ?, dayOfWeek = ?, startTime = ?, endTime = ?, room = ?, capacity = ?, academicYear = ?, semester = ?, updatedAt = NOW()
                    WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['moduleId'],
                !empty($data['staffId']) ? $data['staffId'] : NULL,
                $data['className'],
                $data['classCode'],
                $data['dayOfWeek'],
                $data['startTime'],
                $data['endTime'],
                $data['room'],
                $data['capacity'],
                $data['academicYear'] ?? '2026/2027',
                $data['semester'] ?? '1',
                $classId
            ]);

            if (!empty($data['examDate'])) {
                $this->saveExamPlan($classId, $data['examDate'], $data['examStartTime'], $data['examEndTime'], $data['examVenue'], $currentUserId);
            }

            return "SUCCESS_UPDATE";
        } catch (Exception $e) {
            return "DB_ERROR";
        }
    }

    public function toggleClassStatus($classId, $targetStatus)
    {
        try {
            $stmt = $this->db->prepare("UPDATE Classes SET status = ?, updatedAt = NOW() WHERE id = ?");
            $stmt->execute([$targetStatus, $classId]);
            return "SUCCESS_STATUS";
        } catch (Exception $e) {
            return "DB_ERROR";
        }
    }

    public function enrollStudentSingle($classId, $studentId, $enrolledBy)
    {
        try {
            $capStmt = $this->db->prepare("SELECT capacity, (SELECT COUNT(*) FROM StudentEnrolments WHERE classId = ? AND status = 'enrolled') AS currentEnrolled FROM Classes WHERE id = ?");
            $capStmt->execute([$classId, $classId]);
            $classData = $capStmt->fetch(PDO::FETCH_ASSOC);

            if ($classData && $classData['currentEnrolled'] >= $classData['capacity']) {
                return "CAPACITY_FULL";
            }

            $checkStmt = $this->db->prepare("SELECT id, status FROM StudentEnrolments WHERE studentId = ? AND classId = ?");
            $checkStmt->execute([$studentId, $classId]);
            $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                if ($existing['status'] === 'enrolled') {
                    return "ALREADY_ENROLLED";
                }
                $upd = $this->db->prepare("UPDATE StudentEnrolments SET status = 'enrolled', enrolledBy = ?, enrolmentDate = NOW() WHERE id = ?");
                $upd->execute([$enrolledBy, $existing['id']]);
                return "SUCCESS_ENROLL";
            }

            $ins = $this->db->prepare("INSERT INTO StudentEnrolments (studentId, classId, enrolledBy, enrolmentDate, status) VALUES (?, ?, ?, NOW(), 'enrolled')");
            $ins->execute([$studentId, $classId, $enrolledBy]);
            return "SUCCESS_ENROLL";
        } catch (Exception $e) {
            return "DB_ERROR";
        }
    }

    public function removeStudentFromClass($classId, $studentId)
    {
        try {
            $stmt = $this->db->prepare("UPDATE StudentEnrolments SET status = 'dropped' WHERE classId = ? AND studentId = ? AND status = 'enrolled'");
            $stmt->execute([$classId, $studentId]);
            return "SUCCESS_REMOVE";
        } catch (Exception $e) {
            return "DB_ERROR";
        }
    }

    public function findActiveStudentByIdentifier($identifier)
    {
        try {
            $stmt = $this->db->prepare("SELECT id FROM Users WHERE (email = ? OR id = ?) AND role = 'student' AND status = 'active'");
            $stmt->execute([$identifier, $identifier]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return null;
        }
    }

    public function saveExamPlan($classId, $examDate, $startTime, $endTime, $venue, $createdBy)
    {
        try {
            $check = $this->db->prepare("SELECT id FROM Exam WHERE classId = ?");
            $check->execute([$classId]);
            if ($check->fetch(PDO::FETCH_ASSOC)) {
                $sql = "UPDATE Exam SET examDate = ?, startTime = ?, endTime = ?, venue = ?, createdBy = ?, updatedAt = NOW() WHERE classId = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$examDate, $startTime, $endTime, $venue, $createdBy, $classId]);
            } else {
                $sql = "INSERT INTO Exam (classId, examDate, startTime, endTime, venue, createdBy, createdAt, updatedAt) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$classId, $examDate, $startTime, $endTime, $venue, $createdBy]);
            }
            return "SUCCESS_EXAM";
        } catch (Exception $e) {
            error_log("Save Exam Error: " . $e->getMessage());
            return "DB_ERROR";
        }
    }
}