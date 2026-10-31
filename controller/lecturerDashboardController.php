<?php
require_once "../entity/users.php";
require_once "../database/database.php";

date_default_timezone_set('Asia/Singapore');

class LecturerDashboardController
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
    }

    public function getUpcomingEventsCount($universityId)
    {
        try {
            $sql = "SELECT COUNT(*) FROM Events 
                    WHERE universityId = ? 
                      AND status = 'active' 
                      AND endDatetime >= NOW()";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$universityId]);
            return (int) $stmt->fetchColumn();
        } catch (Exception $e) {
            error_log("getUpcomingEventsCount error: " . $e->getMessage());
            return 0;
        }
    }

    public function getNextClass($userId)
    {
        try {
            $sql = "SELECT title, dayOfWeek, startTime, endTime, location 
                    FROM (
                        SELECT title, dayOfWeek, startTime, endTime, location 
                        FROM TimetableEntries 
                        WHERE userId = :staffId

                        UNION ALL

                        SELECT m.code AS title, 
                               c.dayOfWeek, 
                               c.startTime, 
                               c.endTime, 
                               c.room AS location
                        FROM Classes c
                        INNER JOIN Modules m ON c.moduleId = m.id
                        WHERE (c.staffId = :staffId OR c.courseCoordinatorId = :staffId)
                          AND c.status = 'active'
                    ) AS CombinedTimetable";

            $stmt = $this->db->prepare($sql);
            $stmt->execute(['staffId' => $userId]);
            $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($classes)) {
                return null;
            }

            $daysMap = ['mon' => 1, 'tue' => 2, 'wed' => 3, 'thu' => 4, 'fri' => 5, 'sat' => 6, 'sun' => 7];
            $currentDayNum = (int) date('N');
            $currentTimeStr = date('H:i:s');

            $nextClass = null;
            $minTimeDiff = PHP_INT_MAX;

            foreach ($classes as $c) {
                $dayStr = strtolower(trim($c['dayOfWeek']));
                $classDayNum = $daysMap[$dayStr] ?? 1;
                $classTime = $c['startTime'];

                $dayDiff = $classDayNum - $currentDayNum;
                if ($dayDiff < 0 || ($dayDiff === 0 && $classTime < $currentTimeStr)) {
                    $dayDiff += 7;
                }

                $timeDiffSeconds = ($dayDiff * 86400) + (strtotime($classTime) - strtotime($currentTimeStr));

                if ($timeDiffSeconds < $minTimeDiff) {
                    $minTimeDiff = $timeDiffSeconds;
                    $c['nextDate'] = date('Y-m-d', strtotime("+$dayDiff days"));
                    $nextClass = $c;
                }
            }

            return $nextClass;
        } catch (Exception $e) {
            error_log("getNextClass error: " . $e->getMessage());
            return null;
        }
    }

    public function getNotificationSummary($userId)
    {
        try {
            $countSql = "SELECT COUNT(*) FROM Notifications WHERE userId = ? AND isRead = 0";
            $countStmt = $this->db->prepare($countSql);
            $countStmt->execute([$userId]);
            $unreadCount = (int) $countStmt->fetchColumn();

            // FIXED: Added id ASC to match Notification Page sorting
            $latestSql = "SELECT title, message FROM Notifications WHERE userId = ? ORDER BY createdAt DESC, id ASC LIMIT 1";
            $latestStmt = $this->db->prepare($latestSql);
            $latestStmt->execute([$userId]);
            $latestNoti = $latestStmt->fetch(PDO::FETCH_ASSOC);

            return [
                'unreadCount' => $unreadCount,
                'latestMessage' => $latestNoti ? ($latestNoti['title'] ?? $latestNoti['message']) : 'No new notifications'
            ];
        } catch (Exception $e) {
            return [
                'unreadCount' => 0,
                'latestMessage' => 'No new notifications'
            ];
        }
    }

    public function getNextUpcomingEvent($userId, $universityId)
    {
        try {
            $sql = "SELECT e.title, f.location, e.startDatetime 
                    FROM Events e
                    JOIN EventRegistrations er ON er.eventId = e.id
                    INNER JOIN BookableFacilities bf ON e.facilityId = bf.id
                    INNER JOIN Facilities f ON bf.facilityId = f.id
                    WHERE e.universityId = ? 
                      AND e.status = 'active' 
                      AND e.startDatetime >= NOW() 
                      AND er.userId = ?
                      AND er.status IN ('registered', 'attended')
                    ORDER BY e.startDatetime ASC 
                    LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$universityId, $userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (Exception $e) {
            error_log("getNextUpcomingEvent error: " . $e->getMessage());
            return null;
        }
    }
}
?>