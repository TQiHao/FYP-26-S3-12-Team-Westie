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

    private function resolveDateWindow($academicYear, $semester, $startDate, $endDate)
    {
        if (!empty($startDate) && !empty($endDate)) {
            return [$startDate, $endDate];
        }
        if (empty($academicYear)) {
            return [null, null];
        }
        if (!preg_match('/(\d{4})\s*\/\s*(\d{4})/', $academicYear, $m)) {
            return [null, null];
        }
        $startYear = (int) $m[1];
        $endYear = (int) $m[2];

        if (!preg_match('/([a-zA-Z]{3,})\s*(?:-|to|until)\s*([a-zA-Z]{3,})/i', $semester, $m)) {
            return [null, null];
        }
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
        if ($sM === 0 || $eM === 0) {
            return [null, null];
        }

        $validMonths = [];
        if ($sM <= $eM) {
            for ($i = $sM; $i <= $eM; $i++)
                $validMonths[] = $i;
        } else {
            for ($i = $sM; $i <= 12; $i++)
                $validMonths[] = $i;
            for ($i = 1; $i <= $eM; $i++)
                $validMonths[] = $i;
        }

        $firstMonth = $validMonths[0];
        $lastMonth = end($validMonths);

        if ($firstMonth >= 8) {
            $startY = $startYear;
            $endY = ($lastMonth >= 8) ? $startYear : $endYear;
        } else {
            $startY = $endYear;
            $endY = $endYear;
        }

        $startStr = sprintf('%04d-%02d-01', $startY, $firstMonth);
        $lastDay = (int) date('t', strtotime(sprintf('%04d-%02d-01', $endY, $lastMonth)));
        $endStr = sprintf('%04d-%02d-%02d', $endY, $lastMonth, $lastDay);

        return [$startStr, $endStr];
    }

    private function windowsOverlap($newStart, $newEnd, $candStart, $candEnd)
    {
        if ($newStart === null || $candStart === null) {
            return true;
        }
        return !($candEnd < $newStart || $candStart > $newEnd);
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

    public function getAllModules($universityId)
    {
        try {
            $sql = "SELECT m.id, m.code, m.name 
                    FROM Modules m
                    JOIN Programmes p ON m.programmeId = p.id
                    JOIN Faculties f ON p.facultyId = f.id
                    WHERE f.universityId = ?
                    ORDER BY m.code ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$universityId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    public function getLecturers($universityId)
    {
        try {
            $stmt = $this->db->prepare("SELECT id, fullName, email FROM Users WHERE role = 'lecturer' AND status = 'active' AND universityId = ? ORDER BY fullName ASC");
            $stmt->execute([$universityId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    public function getStudents($universityId)
    {
        try {
            $stmt = $this->db->prepare("SELECT id, fullName, email FROM Users WHERE role = 'student' AND status = 'active' AND universityId = ? ORDER BY fullName ASC");
            $stmt->execute([$universityId]);
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

    public function checkClassConflict(
        $moduleId,
        $classCode,
        $dayOfWeek,
        $room,
        $startTime,
        $endTime,
        $academicYear = '',
        $semester = '',
        $startDate = '',
        $endDate = '',
        $excludeClassId = null
    ) {
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

        list($newStart, $newEnd) = $this->resolveDateWindow($academicYear, $semester, $startDate, $endDate);

        $sqlClash = "SELECT id, academicYear, semester, startDate, endDate
                     FROM Classes
                     WHERE room = ? AND dayOfWeek = ? AND status = 'active'
                       AND startTime < ? AND endTime > ?";
        $paramsClash = [$room, $dayOfWeek, $endTime, $startTime];
        if ($excludeClassId) {
            $sqlClash .= " AND id != ?";
            $paramsClash[] = $excludeClassId;
        }
        $stmtClash = $this->db->prepare($sqlClash);
        $stmtClash->execute($paramsClash);
        $candidates = $stmtClash->fetchAll(PDO::FETCH_ASSOC);

        foreach ($candidates as $c) {
            list($candStart, $candEnd) = $this->resolveDateWindow(
                $c['academicYear'],
                $c['semester'],
                $c['startDate'],
                $c['endDate']
            );
            if ($this->windowsOverlap($newStart, $newEnd, $candStart, $candEnd)) {
                return "SCHEDULE_CLASH";
            }
        }

        return "OK";
    }

    public function findLecturerClash(
        $staffId,
        $dayOfWeek,
        $startTime,
        $endTime,
        $academicYear = '',
        $semester = '',
        $startDate = '',
        $endDate = '',
        $excludeClassId = null
    ) {
        if (empty($staffId)) {
            return null;
        }
        try {
            list($newStart, $newEnd) = $this->resolveDateWindow($academicYear, $semester, $startDate, $endDate);

            $sql = "SELECT c.id, c.classCode, c.className, c.dayOfWeek, c.startTime, c.endTime, c.room,
                           c.academicYear, c.semester, c.startDate, c.endDate
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
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($candidates as $c) {
                list($candStart, $candEnd) = $this->resolveDateWindow(
                    $c['academicYear'],
                    $c['semester'],
                    $c['startDate'],
                    $c['endDate']
                );
                if ($this->windowsOverlap($newStart, $newEnd, $candStart, $candEnd)) {
                    return $c;
                }
            }
            return null;
        } catch (Exception $e) {
            return null;
        }
    }

    public function findStudentClash($studentId, $targetClassId)
    {
        try {
            $stmt = $this->db->prepare("SELECT dayOfWeek, startTime, endTime, academicYear, semester, startDate, endDate
                                        FROM Classes WHERE id = ?");
            $stmt->execute([$targetClassId]);
            $target = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$target) {
                return null;
            }

            list($newStart, $newEnd) = $this->resolveDateWindow(
                $target['academicYear'],
                $target['semester'],
                $target['startDate'],
                $target['endDate']
            );

            $sql = "SELECT c.id, c.classCode, c.className, c.dayOfWeek, c.startTime, c.endTime,
                           c.academicYear, c.semester, c.startDate, c.endDate
                    FROM StudentEnrolments se
                    JOIN Classes c ON c.id = se.classId
                    WHERE se.studentId = ?
                      AND se.status = 'enrolled'
                      AND c.status = 'active'
                      AND c.dayOfWeek = ?
                      AND c.startTime < ?
                      AND c.endTime > ?
                      AND c.id != ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $studentId,
                $target['dayOfWeek'],
                $target['endTime'],
                $target['startTime'],
                $targetClassId,
            ]);
            $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($candidates as $c) {
                list($candStart, $candEnd) = $this->resolveDateWindow(
                    $c['academicYear'],
                    $c['semester'],
                    $c['startDate'],
                    $c['endDate']
                );
                if ($this->windowsOverlap($newStart, $newEnd, $candStart, $candEnd)) {
                    return $c;
                }
            }
            return null;
        } catch (Exception $e) {
            return null;
        }
    }

    public function createClass($data, $coordinatorId)
    {
        try {
            $conflict = $this->checkClassConflict(
                $data['moduleId'],
                $data['classCode'],
                $data['dayOfWeek'],
                $data['room'],
                $data['startTime'],
                $data['endTime'],
                $data['academicYear'] ?? '',
                $data['semester'] ?? '',
                $data['startDate'] ?? '',
                $data['endDate'] ?? ''
            );
            if ($conflict === "DUPLICATE_CODE") {
                return ['status' => 'DUPLICATE_CODE', 'newId' => 0];
            }
            if ($conflict === "SCHEDULE_CLASH") {
                return ['status' => 'SCHEDULE_CLASH', 'newId' => 0];
            }

            if (!empty($data['staffId'])) {
                $lecClash = $this->findLecturerClash(
                    $data['staffId'],
                    $data['dayOfWeek'],
                    $data['startTime'],
                    $data['endTime'],
                    $data['academicYear'] ?? '',
                    $data['semester'] ?? '',
                    $data['startDate'] ?? '',
                    $data['endDate'] ?? ''
                );
                if ($lecClash) {
                    return ['status' => 'LECTURER_CLASH', 'newId' => 0, 'clash' => $lecClash];
                }
            }

            $sql = "INSERT INTO Classes (moduleId, courseCoordinatorId, staffId, className, classCode,
                        dayOfWeek, startTime, endTime, room, capacity, academicYear, semester,
                        startDate, endDate, status, createdAt, updatedAt)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW(), NOW())";
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
                $data['semester'] ?? '1',
                !empty($data['startDate']) ? $data['startDate'] : NULL,
                !empty($data['endDate']) ? $data['endDate'] : NULL,
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
            $conflict = $this->checkClassConflict(
                $data['moduleId'],
                $data['classCode'],
                $data['dayOfWeek'],
                $data['room'],
                $data['startTime'],
                $data['endTime'],
                $data['academicYear'] ?? '',
                $data['semester'] ?? '',
                $data['startDate'] ?? '',
                $data['endDate'] ?? '',
                $classId
            );
            if ($conflict === "DUPLICATE_CODE") {
                return ['status' => 'DUPLICATE_CODE'];
            }
            if ($conflict === "SCHEDULE_CLASH") {
                return ['status' => 'SCHEDULE_CLASH'];
            }

            if (!empty($data['staffId'])) {
                $lecClash = $this->findLecturerClash(
                    $data['staffId'],
                    $data['dayOfWeek'],
                    $data['startTime'],
                    $data['endTime'],
                    $data['academicYear'] ?? '',
                    $data['semester'] ?? '',
                    $data['startDate'] ?? '',
                    $data['endDate'] ?? '',
                    $classId
                );
                if ($lecClash) {
                    return ['status' => 'LECTURER_CLASH', 'clash' => $lecClash];
                }
            }

            $sql = "UPDATE Classes 
                    SET moduleId = ?, staffId = ?, className = ?, classCode = ?, dayOfWeek = ?,
                        startTime = ?, endTime = ?, room = ?, capacity = ?, academicYear = ?,
                        semester = ?, startDate = ?, endDate = ?, updatedAt = NOW()
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
                !empty($data['startDate']) ? $data['startDate'] : NULL,
                !empty($data['endDate']) ? $data['endDate'] : NULL,
                $classId,
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
            $sql = "SELECT u.id 
                    FROM Users u 
                    LEFT JOIN Students s ON u.id = s.userId 
                    WHERE (u.email = ? OR s.studentId = ?) 
                      AND u.role = 'student' 
                      AND u.status = 'active'";
            $stmt = $this->db->prepare($sql);
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