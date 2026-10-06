<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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


function handleFindRooms($db, $universityId)
{
    $day = trim($_POST['day'] ?? '');
    $startTime = trim($_POST['startTime'] ?? '');
    $endTime = trim($_POST['endTime'] ?? '');
    $capacity = (int) ($_POST['capacity'] ?? 0);

    if ($day === '' || $startTime === '' || $endTime === '' || $capacity <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Please fill in day, start time, end time and capacity.']);
        return;
    }

    if ($startTime >= $endTime) {
        echo json_encode(['status' => 'error', 'message' => 'End time must be later than start time.']);
        return;
    }

    $rooms = findAvailableRooms($db, $universityId, $day, $startTime, $endTime, $capacity, 5);

    if (empty($rooms)) {
        $occupied = findOccupiedRooms($db, $universityId, $day, $startTime, $endTime);
        $message = "No free classroom fits $capacity students on " . ucfirst($day) . " $startTime-$endTime.";
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
        'message' => 'Found ' . count($rooms) . ' classroom(s) that fit ' . $capacity . ' students.',
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
        $message = "No free venue fits $capacity students on $examDate $startTime-$endTime.";
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


function findAvailableRooms($db, $universityId, $day, $startTime, $endTime, $minCapacity, $limit = 5)
{
    try {
        $sql = "SELECT f.id, f.roomCode, f.name, f.location, f.blockFloor, f.capacity, f.description
                FROM Facilities f
                WHERE f.universityId = ?
                  AND f.type = 'classroom'
                  AND f.status = 'active'
                  AND f.roomCode IS NOT NULL
                  AND f.capacity >= ?
                  AND NOT EXISTS (
                      SELECT 1 FROM Classes c
                      WHERE c.room = f.roomCode
                        AND c.dayOfWeek = ?
                        AND c.status = 'active'
                        AND c.startTime < ?
                        AND c.endTime > ?
                  )
                ORDER BY ABS(f.capacity - ?) ASC, f.capacity ASC
                LIMIT " . (int) $limit;

        $stmt = $db->prepare($sql);
        $stmt->execute([$universityId, $minCapacity, $day, $endTime, $startTime, $minCapacity]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Find Available Rooms Error: " . $e->getMessage());
        return [];
    }
}


function findOccupiedRooms($db, $universityId, $day, $startTime, $endTime)
{
    try {
        $sql = "SELECT DISTINCT f.roomCode, f.name, c.className, c.classCode, c.startTime, c.endTime
                FROM Facilities f
                JOIN Classes c ON c.room = f.roomCode
                WHERE f.universityId = ?
                  AND f.type = 'classroom'
                  AND c.dayOfWeek = ?
                  AND c.status = 'active'
                  AND c.startTime < ?
                  AND c.endTime > ?
                ORDER BY c.startTime ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute([$universityId, $day, $endTime, $startTime]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}


function findAvailableExamVenues($db, $universityId, $examDate, $startTime, $endTime, $minCapacity, $excludeClassId, $limit = 5)
{
    try {
        $sql = "SELECT f.id, f.roomCode, f.name, f.location, f.blockFloor, f.capacity, f.description
                FROM Facilities f
                WHERE f.universityId = ?
                  AND f.type IN ('lecture hall', 'classroom')
                  AND f.status = 'active'
                  AND f.roomCode IS NOT NULL
                  AND f.capacity >= ?
                  AND NOT EXISTS (
                      SELECT 1 FROM Exam e
                      WHERE e.venue = f.roomCode
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