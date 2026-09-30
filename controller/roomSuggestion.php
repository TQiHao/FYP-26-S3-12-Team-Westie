<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../database/database.php";
require_once "../entity/OllamaClient.php";

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
} elseif ($action === 'ask_ai') {
    handleAskAi($db, $universityId);
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


function handleAskAi($db, $universityId)
{
    $question = trim($_POST['question'] ?? '');
    if ($question === '') {
        echo json_encode(['status' => 'error', 'message' => 'Please type a question.']);
        return;
    }

    $parsed = parseNaturalQuestion($question);

    if (!$parsed) {
        echo json_encode([
            'status' => 'error',
            'message' => "I couldn't pick out the day/time/capacity. Try: \"Find a room on Friday 3pm to 6pm for 43 students\"."
        ]);
        return;
    }

    $day = $parsed['day'];
    $startTime = $parsed['startTime'];
    $endTime = $parsed['endTime'];
    $capacity = $parsed['capacity'];

    $rooms = findAvailableRooms($db, $universityId, $day, $startTime, $endTime, $capacity, 5);

    if (empty($rooms)) {
        $occupied = findOccupiedRooms($db, $universityId, $day, $startTime, $endTime);
        $message = "Understood: " . ucfirst($day) . " $startTime-$endTime for $capacity students. "
            . "But no free classroom fits these constraints.";
        if (!empty($occupied)) {
            $message .= " Occupied at that time: " . implode(', ', array_column($occupied, 'roomCode')) . ".";
        }
        echo json_encode([
            'status' => 'no_rooms',
            'message' => $message,
            'parsed' => $parsed,
            'rooms' => [],
        ]);
        return;
    }

    foreach ($rooms as &$r) {
        $r['reason'] = buildReason($r, $capacity);
    }
    unset($r);

    $summary = "Understood: " . ucfirst($day) . " $startTime-$endTime for $capacity students. "
        . "Here are " . count($rooms) . " classroom(s) that fit.";

    echo json_encode([
        'status' => 'ok',
        'message' => $summary,
        'parsed' => $parsed,
        'rooms' => $rooms,
    ]);
}


function parseNaturalQuestion($q)
{
    $q = strtolower($q);

    // ---- Day ----
    $dayMap = [
        'monday' => 'mon',
        'mon' => 'mon',
        'tuesday' => 'tue',
        'tue' => 'tue',
        'tues' => 'tue',
        'wednesday' => 'wed',
        'wed' => 'wed',
        'thursday' => 'thu',
        'thu' => 'thu',
        'thur' => 'thu',
        'thurs' => 'thu',
        'friday' => 'fri',
        'fri' => 'fri',
        'saturday' => 'sat',
        'sat' => 'sat',
        'sunday' => 'sun',
        'sun' => 'sun',
    ];
    $day = null;
    foreach ($dayMap as $word => $code) {
        if (preg_match('/\b' . $word . '\b/', $q)) {
            $day = $code;
            break;
        }
    }

    // ---- Times (range) ----
    // Support: "3pm to 6pm", "3pm-6pm", "3:30pm to 6:00pm", "15:00-18:00"
    $startTime = null;
    $endTime = null;

    if (preg_match('/\b(\d{1,2})(?::(\d{2}))?\s*(am|pm)?\s*(?:to|-|until|till)\s*(\d{1,2})(?::(\d{2}))?\s*(am|pm)?\b/i', $q, $m)) {
        $startTime = to24Hour($m[1], $m[2] ?? '00', $m[3] ?? null);
        $endTime = to24Hour($m[4], $m[5] ?? '00', $m[6] ?? null);

        // If no meridiem on end but on start, inherit (3pm-6 => 6pm)
        if (empty($m[6]) && !empty($m[3])) {
            $endTime = to24Hour($m[4], $m[5] ?? '00', $m[3]);
        }
    }

    // ---- Capacity ----
    // "for 43 students", "43 ppl", "capacity 60"
    $capacity = null;
    if (preg_match('/\b(?:for|capacity|about|around)?\s*(\d{1,3})\s*(?:students?|ppl|people|seats?|persons?)?\b/', $q, $m)) {
        $capacity = (int) $m[1];
    }
    // More specific first
    if (preg_match('/\bfor\s+(\d{1,3})\b/', $q, $m)) {
        $capacity = (int) $m[1];
    }

    if (!$day || !$startTime || !$endTime || !$capacity) {
        return null;
    }

    if ($startTime >= $endTime) {
        return null;
    }

    return [
        'day' => $day,
        'startTime' => $startTime,
        'endTime' => $endTime,
        'capacity' => $capacity,
    ];
}


function to24Hour($hour, $minute, $meridiem)
{
    $h = (int) $hour;
    $min = str_pad((int) $minute, 2, '0', STR_PAD_LEFT);

    if ($meridiem === 'pm' && $h < 12) {
        $h += 12;
    } elseif ($meridiem === 'am' && $h === 12) {
        $h = 0;
    }

    // Heuristic: if no meridiem and hour <= 7, assume PM (school hours 8-22)
    if ($meridiem === null && $h >= 1 && $h <= 7) {
        $h += 12;
    }

    return str_pad($h, 2, '0', STR_PAD_LEFT) . ':' . $min;
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


function normalizeTime($t)
{
    $t = trim($t);
    if (preg_match('/^(\d{1,2}):(\d{2})/', $t, $m)) {
        return str_pad($m[1], 2, '0', STR_PAD_LEFT) . ':' . $m[2];
    }
    return $t;
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