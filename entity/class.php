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

    public function getClasses($searchQuery = '', $facultyId = '', $departmentId = '', $statusFilter = '')
    {
        try {
            $sql = "SELECT c.*, 
                           m.code AS moduleCode, 
                           m.name AS moduleName,
                           f.name AS facultyName,
                           u_staff.fullName AS lecturerName,
                           (SELECT COUNT(*) FROM StudentEnrolments se WHERE se.classId = c.id AND se.status = 'enrolled') AS enrolledCount
                    FROM Classes c
                    JOIN Modules m ON c.moduleId = m.id
                    LEFT JOIN Programmes p ON m.programmeId = p.id
                    LEFT JOIN Faculties f ON p.facultyId = f.id
                    LEFT JOIN Users u_staff ON c.staffId = u_staff.id
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

    public function getExamsByClassId($classId)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT id, examDate, startTime, endTime, venue
                 FROM Exam
                 WHERE classId = ?
                 ORDER BY examDate ASC, startTime ASC"
            );
            $stmt->execute([$classId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
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

    public function getStudentFilesFromFolder($universityId)
    {
        $baseFolder = __DIR__ . "/../csv/";
        if (!is_dir($baseFolder)) {
            return [];
        }

        $uniFolder = $baseFolder . "U" . str_pad((int) $universityId, 2, '0', STR_PAD_LEFT);
        if (!is_dir($uniFolder)) {
            return [];
        }

        $result = [];
        foreach (glob($uniFolder . "/*.csv") as $path) {
            $base = basename($path);
            if (stripos($base, 'StudentList') === false) {
                continue;
            }
            $result[] = [
                'fileName' => $base,
                'fullPath' => $path,
                'uploadedAt' => date('Y-m-d H:i:s', filemtime($path)),
            ];
        }

        usort($result, function ($a, $b) {
            return strcmp($b['uploadedAt'], $a['uploadedAt']);
        });

        return $result;
    }

    public function readStudentFileByFilename($fileName, $universityId)
    {
        $fileName = basename($fileName);

        if (stripos($fileName, 'StudentList') === false) {
            return null;
        }

        $uniFolder = __DIR__ . "/../csv/U" . str_pad((int) $universityId, 2, '0', STR_PAD_LEFT);
        $fullPath = $uniFolder . "/" . $fileName;

        if (!file_exists($fullPath)) {
            return null;
        }

        return ['fileName' => $fileName, 'fullPath' => $fullPath];
    }
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

    public function findLecturerClash($staffId, $dayOfWeek, $startTime, $endTime, $excludeClassId = null)
    {
        if (empty($staffId))
            return null;
        try {
            $sql = "SELECT c.id, c.classCode, c.className, c.dayOfWeek, c.startTime, c.endTime, c.room
                    FROM Classes c
                    WHERE c.staffId = ?
                      AND c.dayOfWeek = ?
                      AND c.status = 'active'
                      AND c.startTime < ?
                      AND c.endTime > ?";
            $params = [$staffId, $dayOfWeek, $endTime, $startTime];
            if ($excludeClassId) {
                $sql .= " AND c.id != ?";
                $params[] = $excludeClassId;
            }
            $sql .= " LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (Exception $e) {
            return null;
        }
    }

    public function findStudentClash($studentId, $targetClassId)
    {
        try {
            $stmt = $this->db->prepare("SELECT dayOfWeek, startTime, endTime FROM Classes WHERE id = ?");
            $stmt->execute([$targetClassId]);
            $target = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$target)
                return null;

            $sql = "SELECT c.id, c.classCode, c.className, c.dayOfWeek, c.startTime, c.endTime
                    FROM StudentEnrolments se
                    JOIN Classes c ON c.id = se.classId
                    WHERE se.studentId = ?
                      AND se.status = 'enrolled'
                      AND c.status = 'active'
                      AND c.dayOfWeek = ?
                      AND c.startTime < ?
                      AND c.endTime > ?
                      AND c.id != ?
                    LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $studentId,
                $target['dayOfWeek'],
                $target['endTime'],
                $target['startTime'],
                $targetClassId,
            ]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (Exception $e) {
            return null;
        }
    }

    public function createClass($data, $coordinatorId)
    {
        try {
            $conflict = $this->checkClassConflict($data['moduleId'], $data['classCode'], $data['dayOfWeek'], $data['room'], $data['startTime'], $data['endTime']);
            if ($conflict === "DUPLICATE_CODE")
                return ['status' => 'DUPLICATE_CODE', 'newId' => 0];
            if ($conflict === "SCHEDULE_CLASH")
                return ['status' => 'SCHEDULE_CLASH', 'newId' => 0];

            if (!empty($data['staffId'])) {
                $lecClash = $this->findLecturerClash($data['staffId'], $data['dayOfWeek'], $data['startTime'], $data['endTime']);
                if ($lecClash) {
                    return ['status' => 'LECTURER_CLASH', 'newId' => 0, 'clash' => $lecClash];
                }
            }

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

            $newClassId = (int) $this->db->lastInsertId();

            if (!empty($data['examDate']) && is_array($data['examDate'])) {
                $this->saveExamPlans($newClassId, $data, $coordinatorId);
            }

            return ['status' => 'SUCCESS_CREATE', 'newId' => $newClassId];
        } catch (Exception $e) {
            error_log("Create Class Error: " . $e->getMessage());
            return ['status' => 'DB_ERROR', 'newId' => 0];
        }
    }

    public function updateClass($classId, $data, $currentUserId)
    {
        try {
            $conflict = $this->checkClassConflict($data['moduleId'], $data['classCode'], $data['dayOfWeek'], $data['room'], $data['startTime'], $data['endTime'], $classId);
            if ($conflict === "DUPLICATE_CODE")
                return ['status' => 'DUPLICATE_CODE'];
            if ($conflict === "SCHEDULE_CLASH")
                return ['status' => 'SCHEDULE_CLASH'];

            if (!empty($data['staffId'])) {
                $lecClash = $this->findLecturerClash($data['staffId'], $data['dayOfWeek'], $data['startTime'], $data['endTime'], $classId);
                if ($lecClash) {
                    return ['status' => 'LECTURER_CLASH', 'clash' => $lecClash];
                }
            }

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

            $this->saveExamPlans($classId, $data, $currentUserId);

            if (!empty($data['apply_to_module'])) {
                $stmt = $this->db->prepare("SELECT moduleId FROM Classes WHERE id = ?");
                $stmt->execute([$classId]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $this->applyExamToModuleClasses(
                        (int) $row['moduleId'],
                        $classId,
                        $data,
                        $currentUserId
                    );
                }
            }

            return ['status' => 'SUCCESS_UPDATE'];
        } catch (Exception $e) {
            error_log("Update Class Error: " . $e->getMessage());
            return ['status' => 'DB_ERROR'];
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

            if ($existing && $existing['status'] === 'enrolled') {
                return "ALREADY_ENROLLED";
            }

            $clash = $this->findStudentClash($studentId, $classId);
            if ($clash) {
                return "STUDENT_CLASH:" . $clash['classCode'];
            }

            if ($existing) {
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

    public function saveExamPlans($classId, $data, $createdBy)
    {
        try {
            $del = $this->db->prepare("DELETE FROM Exam WHERE classId = ?");
            $del->execute([$classId]);

            $dates = $data['examDate'] ?? [];
            $venues = $data['examVenue'] ?? [];
            $starts = $data['examStartTime'] ?? [];
            $ends = $data['examEndTime'] ?? [];

            if (!is_array($dates)) {
                return "SUCCESS_EXAM";
            }

            $ins = $this->db->prepare(
                "INSERT INTO Exam (classId, examDate, startTime, endTime, venue, createdBy, createdAt, updatedAt)
                 VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())"
            );

            foreach ($dates as $i => $d) {
                $d = trim($d);
                $v = trim($venues[$i] ?? '');
                $s = trim($starts[$i] ?? '');
                $e = trim($ends[$i] ?? '');
                if ($d === '' || $s === '' || $e === '' || $v === '') {
                    continue;
                }
                $ins->execute([$classId, $d, $s, $e, $v, $createdBy]);
            }

            return "SUCCESS_EXAM";
        } catch (Exception $ex) {
            error_log("Save Exam Plans Error: " . $ex->getMessage());
            return "DB_ERROR";
        }
    }

    public function applyExamToModuleClasses($moduleId, $excludeClassId, $data, $createdBy)
    {
        try {
            $stmt = $this->db->prepare("SELECT id FROM Classes WHERE moduleId = ? AND id != ?");
            $stmt->execute([$moduleId, $excludeClassId]);
            $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $dates = $data['examDate'] ?? [];
            $venues = $data['examVenue'] ?? [];
            $starts = $data['examStartTime'] ?? [];
            $ends = $data['examEndTime'] ?? [];

            $del = $this->db->prepare("DELETE FROM Exam WHERE classId = ?");
            $ins = $this->db->prepare(
                "INSERT INTO Exam (classId, examDate, startTime, endTime, venue, createdBy, createdAt, updatedAt)
                 VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())"
            );

            $count = 0;
            foreach ($classes as $c) {
                $del->execute([$c['id']]);
                foreach ($dates as $i => $d) {
                    $d = trim($d);
                    $v = trim($venues[$i] ?? '');
                    $s = trim($starts[$i] ?? '');
                    $e = trim($ends[$i] ?? '');
                    if ($d === '' || $s === '' || $e === '' || $v === '') {
                        continue;
                    }
                    $ins->execute([$c['id'], $d, $s, $e, $v, $createdBy]);
                }
                $count++;
            }
            return $count;
        } catch (Exception $e) {
            error_log("applyExamToModuleClasses Error: " . $e->getMessage());
            return 0;
        }
    }
}
?>