<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../database/database.php";

class NotificationController
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
    }

    private function checkAndGenerateReminders($userId)
    {
        try {
            // 1. Event starting within 3 days (must be registered)
            $eventSql = "SELECT e.id, e.title, e.startDatetime, e.location 
                         FROM EventRegistrations er
                         JOIN Events e ON er.eventId = e.id
                         WHERE er.userId = ? 
                           AND er.status = 'registered'
                           AND e.startDatetime BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 3 DAY)";
            $stmt = $this->db->prepare($eventSql);
            $stmt->execute([$userId]);
            $nearEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($nearEvents as $ev) {
                $title = "Upcoming Event Reminder: " . $ev['title'];
                $msg = "Rmb to join the event '" . $ev['title'] . "' at " . $ev['location'] . " on " . date('M d, g:i A', strtotime($ev['startDatetime'])) . ".";

                $checkSql = "SELECT COUNT(*) FROM Notifications WHERE userId = ? AND title = ?";
                $checkStmt = $this->db->prepare($checkSql);
                $checkStmt->execute([$userId, $title]);

                if ((int) $checkStmt->fetchColumn() === 0) {
                    $insSql = "INSERT INTO Notifications (userId, type, title, message) VALUES (?, 'event', ?, ?)";
                    $insStmt = $this->db->prepare($insSql);
                    $insStmt->execute([$userId, $title, $msg]);
                }
            }

            // 2. Class starting within next 5 hours
            $classSql = "SELECT className, startTime, room, dayOfWeek FROM Classes WHERE staffId = ? AND status = 'active'";
            $stmtClass = $this->db->prepare($classSql);
            $stmtClass->execute([$userId]);
            $allClasses = $stmtClass->fetchAll(PDO::FETCH_ASSOC);

            $daysMap = ['mon' => 1, 'tue' => 2, 'wed' => 3, 'thu' => 4, 'fri' => 5, 'sat' => 6, 'sun' => 7];
            $currentDayNum = (int) date('N');
            $now = new DateTime();
            $fiveHoursLater = new DateTime('+5 hours');

            foreach ($allClasses as $cls) {
                $dayStr = strtolower(trim($cls['dayOfWeek']));
                $classDayNum = $daysMap[$dayStr] ?? 1;
                $dayDiff = $classDayNum - $currentDayNum;
                if ($dayDiff < 0) {
                    $dayDiff += 7;
                }

                $classDateStr = date('Y-m-d', strtotime("+$dayDiff days"));
                $classDt = new DateTime($classDateStr . ' ' . $cls['startTime']);

                if ($classDt >= $now && $classDt <= $fiveHoursLater) {
                    $title = "Lecture Conduct Reminder: " . $cls['className'];
                    $msg = "Reminder: Conduct lecture '" . $cls['className'] . "' at " . date('g:i A', strtotime($cls['startTime'])) . " in Room " . $cls['room'] . ".";

                    $checkSql = "SELECT COUNT(*) FROM Notifications WHERE userId = ? AND title = ? AND DATE(createdAt) = CURDATE()";
                    $checkStmt = $this->db->prepare($checkSql);
                    $checkStmt->execute([$userId, $title]);

                    if ((int) $checkStmt->fetchColumn() === 0) {
                        $insSql = "INSERT INTO Notifications (userId, type, title, message) VALUES (?, 'class', ?, ?)";
                        $insStmt = $this->db->prepare($insSql);
                        $insStmt->execute([$userId, $title, $msg]);
                    }
                }
            }

            // 3. System update (AI model deployed within last 7 days)
            $sysSql = "SELECT modelName, version, deployedAt FROM AIModelVersions 
                       WHERE status = 'deployed' 
                         AND deployedAt IS NOT NULL 
                         AND deployedAt >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            $sysStmt = $this->db->prepare($sysSql);
            $sysStmt->execute();
            $sysUpdates = $sysStmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($sysUpdates as $up) {
                $title = "System Update: " . $up['modelName'] . " " . $up['version'];
                $msg = $up['modelName'] . " " . $up['version'] . " has been deployed on " . date('M d, Y', strtotime($up['deployedAt'])) . ".";

                $checkSql = "SELECT COUNT(*) FROM Notifications WHERE userId = ? AND title = ?";
                $checkStmt = $this->db->prepare($checkSql);
                $checkStmt->execute([$userId, $title]);

                if ((int) $checkStmt->fetchColumn() === 0) {
                    $insSql = "INSERT INTO Notifications (userId, type, title, message) VALUES (?, 'system', ?, ?)";
                    $insStmt = $this->db->prepare($insSql);
                    $insStmt->execute([$userId, $title, $msg]);
                }
            }
        } catch (Exception $e) {
            error_log("Reminder generation error: " . $e->getMessage());
        }
    }

    public function getNotification($userId)
    {
        try {
            if ($userId) {
                $this->checkAndGenerateReminders($userId);
            }

            $sql = "SELECT id, userId, type, title, message, isRead, createdAt
                    FROM Notifications
                    WHERE userId = ?
                    ORDER BY createdAt DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("Notification fetch error: " . $e->getMessage());
            return false;
        }
    }
}