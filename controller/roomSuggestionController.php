<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../controller/authGuard.php";
requireRole(['course_coordinator']);

require_once "../database/database.php";

header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized.']);
    exit();
}

$userRole = strtolower($_SESSION['user_role'] ?? $_SESSION['role'] ?? '');
if ($userRole !== 'course_coordinator' && $userRole !== 'course coordinator') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized.']);
    exit();
}

$userId = $_SESSION['user_id'] ?? 0;

$database = new Database();
$db = $database->connect();

$universityId = getUniversityIdByUser($db, $userId);
if ($universityId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Could not resolve your university.']);
    exit();
}

$action = $_POST['action'] ?? '';

if ($action === 'find_rooms') {
    handleFindRooms($db, $universityId);
} elseif ($action === 'find_exam_venues') {
    handleFindExamVenues($db, $universityId);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Unknown action.']);
}
exit();


function resolveDateWindow($academicYear, $semester, $startDate, $endDate)
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

function windowsOverlap($newStart, $newEnd, $candStart, $candEnd)
{
    if ($newStart === null || $candStart === null) {
        return true;
    }
    return !($candEnd < $newStart || $candStart > $newEnd);
}

function handleFindRooms($db, $universityId)
{
    $day = trim($_POST['day'] ?? '');
    $startTime = trim($_POST['startTime'] ?? '');
    $endTime = trim($_POST['endTime'] ?? '');
    $capacity = (int) ($_POST['capacity'] ?? 0);
    $academicYear = trim($_POST['academicYear'] ?? '');
    $semester = trim($_POST['semester'] ?? '');
    $startDate = trim($_POST['startDate'] ?? '');
    $endDate = trim($_POST['endDate'] ?? '');

    if ($day === '' || $startTime === '' || $endTime === '' || $capacity <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Please fill in day, start time, end time and capacity.']);
        return;
    }

    if ($startTime >= $endTime) {
        echo json_encode(['status' => 'error', 'message' => 'End time must be later than start time.']);
        return;
    }

    $rooms = findAvailableRooms(
        $db,
        $universityId,
        $day,
        $startTime,
        $endTime,
        $capacity,
        $academicYear,
        $semester,
        $startDate,
        $endDate,
        5
    );

    if (empty($rooms)) {
        $occupied = findOccupiedRooms(
            $db,
            $universityId,
            $day,
            $startTime,
            $endTime,
            $academicYear,
            $semester,
            $startDate,
            $endDate
        );
        $message = "No free classroom/lecture hall/lab fits $capacity students on " . ucfirst($day) . " $startTime-$endTime.";
        if (!empty($occupied)) {
            $message .= " Occupied at that time: " . implode(', ', array_column($occupied, 'roomCode')) . ".";
        }
        echo json_encode([
            'status' => 'no_rooms',
            'message' => $message,
            'rooms' => [],
        ]);
        return;
    }

    foreach ($rooms as &$r) {
        $r['reason'] = buildReason($r, $capacity);
    }
    unset($r);

    echo json_encode([
        'status' => 'ok',
        'message' => 'Found ' . count($rooms) . ' room(s) that fit ' . $capacity . ' students.',
        'rooms' => $rooms,
    ]);
}


function handleFindExamVenues($db, $universityId)
{
    $examDate = trim($_POST['examDate'] ?? '');
    $startTime = trim($_POST['startTime'] ?? '');
    $endTime = trim($_POST['endTime'] ?? '');
    $capacity = (int) ($_POST['capacity'] ?? 0);
    $excludeClassId = (int) ($_POST['excludeClassId'] ?? 0);

    if ($examDate === '' || $startTime === '' || $endTime === '' || $capacity <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Please fill in Exam Date, Start Time, End Time, and Capacity first.']);
        return;
    }

    if ($startTime >= $endTime) {
        echo json_encode(['status' => 'error', 'message' => 'End time must be later than start time.']);
        return;
    }

    $venues = findAvailableExamVenues($db, $universityId, $examDate, $startTime, $endTime, $capacity, $excludeClassId, 5);

    if (empty($venues)) {
        $occupied = findOccupiedExamVenues($db, $examDate, $startTime, $endTime, $excludeClassId);
        $message = "No free lecture hall or classroom fits $capacity students on $examDate $startTime-$endTime.";
        if (!empty($occupied)) {
            $names = array_unique(array_column($occupied, 'venue'));
            $message .= " Occupied at that time: " . implode(', ', $names) . ".";
        }
        echo json_encode([
            'status' => 'no_rooms',
            'message' => $message,
            'rooms' => [],
        ]);
        return;
    }

    foreach ($venues as &$v) {
        $v['reason'] = buildReason($v, $capacity);
    }
    unset($v);

    echo json_encode([
        'status' => 'ok',
        'message' => 'Found ' . count($venues) . ' venue(s) that fit ' . $capacity . ' students.',
        'rooms' => $venues,
    ]);
}


function getUniversityIdByUser($db, $userId)
{
    try {
        $stmt = $db->prepare("SELECT universityId FROM Users WHERE id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int) $row['universityId'] : 0;
    } catch (Exception $e) {
        return 0;
    }
}


function findAvailableRooms(
    $db,
    $universityId,
    $day,
    $startTime,
    $endTime,
    $minCapacity,
    $newAcademicYear = '',
    $newSemester = '',
    $newStartDate = '',
    $newEndDate = '',
    $limit = 5
) {
    try {
        list($newStart, $newEnd) = resolveDateWindow($newAcademicYear, $newSemester, $newStartDate, $newEndDate);

        // Fix: Use COALESCE to fall back to f.name if f.roomCode is NULL
        $sql = "SELECT f.id, COALESCE(NULLIF(f.roomCode, ''), f.name) AS roomCode, f.name, f.location, f.blockFloor, f.capacity, f.description
                FROM Facilities f
                WHERE f.universityId = ?
                  AND f.type IN ('lecture hall', 'classroom', 'lab')
                  AND f.status = 'active'
                  AND f.capacity >= ?
                ORDER BY ABS(f.capacity - ?) ASC, f.capacity ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute([$universityId, $minCapacity, $minCapacity]);
        $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $available = [];
        foreach ($rooms as $room) {
            if (roomHasClash($db, $room['roomCode'], $day, $startTime, $endTime, $newStart, $newEnd)) {
                continue;
            }
            $available[] = $room;
            if (count($available) >= $limit) {
                break;
            }
        }
        return $available;
    } catch (Exception $e) {
        error_log("Find Available Rooms Error: " . $e->getMessage());
        return [];
    }
}


function roomHasClash($db, $roomCode, $day, $startTime, $endTime, $newStart, $newEnd)
{
    try {
        $sql = "SELECT academicYear, semester, startDate, endDate
                FROM Classes
                WHERE room = ? AND dayOfWeek = ? AND status = 'active'
                  AND startTime < ? AND endTime > ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$roomCode, $day, $endTime, $startTime]);
        $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($candidates as $c) {
            list($candStart, $candEnd) = resolveDateWindow(
                $c['academicYear'],
                $c['semester'],
                $c['startDate'],
                $c['endDate']
            );
            if (windowsOverlap($newStart, $newEnd, $candStart, $candEnd)) {
                return true;
            }
        }
        return false;
    } catch (Exception $e) {
        return true;
    }
}


function findOccupiedRooms(
    $db,
    $universityId,
    $day,
    $startTime,
    $endTime,
    $newAcademicYear = '',
    $newSemester = '',
    $newStartDate = '',
    $newEndDate = ''
) {
    try {
        list($newStart, $newEnd) = resolveDateWindow($newAcademicYear, $newSemester, $newStartDate, $newEndDate);

        // Fix: Match either roomCode or name
        $sql = "SELECT DISTINCT COALESCE(NULLIF(f.roomCode, ''), f.name) AS roomCode, f.name, c.className, c.classCode, c.startTime, c.endTime,
                       c.academicYear, c.semester, c.startDate, c.endDate
                FROM Facilities f
                JOIN Classes c ON (c.room = f.roomCode OR c.room = f.name)
                WHERE f.universityId = ?
                  AND f.type IN ('lecture hall', 'classroom', 'lab')
                  AND c.dayOfWeek = ?
                  AND c.status = 'active'
                  AND c.startTime < ?
                  AND c.endTime > ?
                ORDER BY c.startTime ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute([$universityId, $day, $endTime, $startTime]);
        $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $occupied = [];
        $seen = [];
        foreach ($candidates as $c) {
            list($candStart, $candEnd) = resolveDateWindow(
                $c['academicYear'],
                $c['semester'],
                $c['startDate'],
                $c['endDate']
            );
            if (windowsOverlap($newStart, $newEnd, $candStart, $candEnd)) {
                if (!isset($seen[$c['roomCode']])) {
                    $occupied[] = $c;
                    $seen[$c['roomCode']] = true;
                }
            }
        }
        return $occupied;
    } catch (Exception $e) {
        return [];
    }
}


function findAvailableExamVenues($db, $universityId, $examDate, $startTime, $endTime, $minCapacity, $excludeClassId, $limit = 5)
{
    try {
        $sql = "SELECT f.id, COALESCE(NULLIF(f.roomCode, ''), f.name) AS roomCode, f.name, f.location, f.blockFloor, f.capacity, f.description
                FROM Facilities f
                WHERE f.universityId = ?
                  AND f.type IN ('lecture hall', 'classroom')
                  AND f.status = 'active'
                  AND f.capacity >= ?
                  AND NOT EXISTS (
                      SELECT 1 FROM Exam e
                      WHERE e.venue = COALESCE(NULLIF(f.roomCode, ''), f.name)
                        AND e.examDate = ?
                        AND e.startTime < ?
                        AND e.endTime > ?
                        AND (? = 0 OR e.classId != ?)
                  )
                ORDER BY ABS(f.capacity - ?) ASC, f.capacity ASC
                LIMIT " . (int) $limit;

        $stmt = $db->prepare($sql);
        $stmt->execute([
            $universityId,
            $minCapacity,
            $examDate,
            $endTime,
            $startTime,
            $excludeClassId,
            $excludeClassId,
            $minCapacity,
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Find Available Exam Venues Error: " . $e->getMessage());
        return [];
    }
}


function findOccupiedExamVenues($db, $examDate, $startTime, $endTime, $excludeClassId)
{
    try {
        $sql = "SELECT DISTINCT e.venue, c.classCode
                FROM Exam e
                JOIN Classes c ON e.classId = c.id
                WHERE e.examDate = ?
                  AND e.startTime < ?
                  AND e.endTime > ?
                  AND (? = 0 OR e.classId != ?)
                ORDER BY e.startTime ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute([$examDate, $endTime, $startTime, $excludeClassId, $excludeClassId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}


function buildReason($room, $requestedCapacity)
{
    $diff = (int) $room['capacity'] - (int) $requestedCapacity;
    if ($diff <= 5) {
        return 'Near-perfect fit (' . $room['capacity'] . ' seats).';
    }
    if ($diff <= 20) {
        return 'Comfortable fit, ' . $diff . ' spare seats.';
    }
    return 'Large room with ' . $diff . ' spare seats.';
}
?>