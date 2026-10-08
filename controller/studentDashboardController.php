<?php

require_once "../database/database.php";
require_once "../entity/users.php";

class StudentDashboardController
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
    }

    public function showDashboard($userId)
    {
        $user = $this->getUser($userId);
        return $user;
    }

    private function getUser($userId)
    {
        return null;
    }

    public function getNextClass($studentId)
    {
        if (!$studentId || !is_numeric($studentId)) {
            return null;
        }

        try {
            $today = strtolower(date('D'));
            $now = date('H:i:s');

            $dayOrder = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
            $todayIndex = array_search($today, $dayOrder);

            $sql = "SELECT title, dayOfWeek, startTime, endTime, location
                    FROM TimetableEntries
                    WHERE userId = ?
                    ORDER BY FIELD(dayOfWeek,'mon','tue','wed','thu','fri','sat','sun'),
                             startTime ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$studentId]);
            $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($classes)) {
                return null;
            }

            foreach ($classes as $c) {
                if (strtolower($c['dayOfWeek']) === $today && $c['startTime'] > $now) {
                    $c['nextDate'] = date('Y-m-d');
                    return $c;
                }
            }

            for ($i = 1; $i <= 7; $i++) {
                $nextDayIndex = ($todayIndex + $i) % 7;
                $nextDay = $dayOrder[$nextDayIndex];

                foreach ($classes as $c) {
                    if (strtolower($c['dayOfWeek']) === $nextDay) {
                        $c['nextDate'] = date('Y-m-d', strtotime("+$i days"));
                        return $c;
                    }
                }
            }

            return null;

        } catch (PDOException $e) {
            error_log("getNextClass error: " . $e->getMessage());
            return false;
        }
    }

    public function getNotificationSummary($userId)
    {
        if (!$userId || !is_numeric($userId)) {
            return null;
        }

        try {
            $sql = "SELECT COUNT(*) FROM Notifications
                    WHERE userId = ? AND isRead = 0";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            $unreadCount = (int) $stmt->fetchColumn();

            $sql = "SELECT title, message, createdAt
                    FROM Notifications
                    WHERE userId = ?
                    ORDER BY createdAt DESC
                    LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            $latest = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'unreadCount' => $unreadCount,
                'latest' => $latest ?: null
            ];

        } catch (PDOException $e) {
            error_log("getNotificationSummary error: " . $e->getMessage());
            return false;
        }
    }

    public function getNextEvent($studentId, $universityId)
    {
        if (!$studentId || !is_numeric($studentId)) {
            return null;
        }
        if (!$universityId || !is_numeric($universityId)) {
            return null;
        }

        try {
            $sql = "SELECT e.title, e.startDatetime,
                        f.location AS location
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
            $stmt->execute([$universityId, $studentId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;

        } catch (PDOException $e) {
            error_log("getNextEvent error: " . $e->getMessage());
            return false;
        }
    }

    public function getEnrolledCoursesCount($studentId)
    {
        if (!$studentId || !is_numeric($studentId)) {
            return 0;
        }

        try {
            $sql = "SELECT COUNT(*)
                    FROM StudentEnrolments
                    WHERE studentId = ? AND status = 'enrolled'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$studentId]);
            return (int) $stmt->fetchColumn();

        } catch (PDOException $e) {
            error_log("getEnrolledCoursesCount error: " . $e->getMessage());
            return 0;
        }
    }

    public function getActiveBookingsCount($userId)
    {
        if (!$userId || !is_numeric($userId)) {
            return 0;
        }

        try {
            $sql = "SELECT COUNT(*)
                    FROM FacilityBookings
                    WHERE userId = ? AND status IN ('pending', 'confirmed')";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            return (int) $stmt->fetchColumn();

        } catch (PDOException $e) {
            error_log("getActiveBookingsCount error: " . $e->getMessage());
            return 0;
        }
    }

    public function getGroupsJoinedCount($studentId)
    {
        if (!$studentId || !is_numeric($studentId)) {
            return 0;
        }

        try {
            $sql = "SELECT COUNT(*)
                    FROM StudyGroupMembers
                    WHERE studentId = ? AND status = 'active'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$studentId]);
            return (int) $stmt->fetchColumn();

        } catch (PDOException $e) {
            error_log("getGroupsJoinedCount error: " . $e->getMessage());
            return 0;
        }
    }

    public function getUpcomingEventsCount($universityId)
    {
        if (!$universityId || !is_numeric($universityId)) {
            return 0;
        }

        try {
            $sql = "SELECT COUNT(*)
                    FROM Events
                    WHERE universityId = ?
                      AND status = 'active'
                      AND endDatetime >= NOW()";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$universityId]);
            return (int) $stmt->fetchColumn();

        } catch (PDOException $e) {
            error_log("getUpcomingEventsCount error: " . $e->getMessage());
            return 0;
        }
    }
}
?>