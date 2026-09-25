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

    /**
     * Generate dynamic notifications for upcoming joined events & teaching lectures
     */
    private function checkAndGenerateReminders($userId)
    {
        try {
            // 1. Check joined events starting within the next 3 days
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

                // Prevent duplicate notifications
                $checkSql = "SELECT COUNT(*) FROM Notifications WHERE userId = ? AND title = ?";
                $checkStmt = $this->db->prepare($checkSql);
                $checkStmt->execute([$userId, $title]);

                if ((int) $checkStmt->fetchColumn() === 0) {
                    $insSql = "INSERT INTO Notifications (userId, type, title, message) VALUES (?, 'event', ?, ?)";
                    $insStmt = $this->db->prepare($insSql);
                    $insStmt->execute([$userId, $title, $msg]);
                }
            }

            // 2. Check teaching classes scheduled for today
            $todayDay = strtolower(date('D')); // e.g. 'mon', 'tue'
            $classSql = "SELECT className, startTime, room FROM Classes WHERE staffId = ? AND LOWER(dayOfWeek) = ?";
            $stmtClass = $this->db->prepare($classSql);
            $stmtClass->execute([$userId, $todayDay]);
            $todayClasses = $stmtClass->fetchAll(PDO::FETCH_ASSOC);

            foreach ($todayClasses as $cls) {
                $title = "Lecture Conduct Reminder: " . $cls['className'];
                $msg = "Reminder: You have to conduct lecture '" . $cls['className'] . "' today at " . date('g:i A', strtotime($cls['startTime'])) . " in Room " . $cls['room'] . ".";

                $checkSql = "SELECT COUNT(*) FROM Notifications WHERE userId = ? AND title = ? AND DATE(createdAt) = CURDATE()";
                $checkStmt = $this->db->prepare($checkSql);
                $checkStmt->execute([$userId, $title]);

                if ((int) $checkStmt->fetchColumn() === 0) {
                    $insSql = "INSERT INTO Notifications (userId, type, title, message) VALUES (?, 'class', ?, ?)";
                    $insStmt = $this->db->prepare($insSql);
                    $insStmt->execute([$userId, $title, $msg]);
                }
            }
        } catch (Exception $e) {
            error_log("Reminder generation error: " . $e->getMessage());
        }
    }

    /**
     * Get all notifications for a user
     */
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
?>