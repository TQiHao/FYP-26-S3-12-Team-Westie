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
        // Database retrieval will be implemented later
        return null;
    }

    /**
     * Get the next upcoming class for a student.
     * @param int $studentId
     * @return array|null|false
     */
    public function getNextClass($studentId)
    {
        // Guard: invalid ID
        if (!$studentId || !is_numeric($studentId)) {
            return null;
        }

        try {
            // Get today's short day (mon, tue, wed, ...)
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

            // 1. Look for a class TODAY that hasn't started yet
            foreach ($classes as $c) {
                if (strtolower($c['dayOfWeek']) === $today && $c['startTime'] > $now) {
                    return $c;
                }
            }

            // 2. No more classes today — find the next class on a later day
            for ($i = 1; $i <= 7; $i++) {
                $nextDayIndex = ($todayIndex + $i) % 7;
                $nextDay = $dayOrder[$nextDayIndex];

                foreach ($classes as $c) {
                    if (strtolower($c['dayOfWeek']) === $nextDay) {
                        return $c;
                    }
                }
            }

            return null; // No upcoming class this week

        } catch (PDOException $e) {
            error_log("getNextClass error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get unread notification count + latest notification for a user.
     * @param int $userId
     * @return array ['unreadCount' => int, 'latest' => array|null] | false
     */
    public function getNotificationSummary($userId)
    {
        if (!$userId || !is_numeric($userId)) {
            return null;
        }

        try {
            // Unread count
            $sql = "SELECT COUNT(*) FROM Notifications
                    WHERE userId = ? AND isRead = 0";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            $unreadCount = (int) $stmt->fetchColumn();

            // Latest notification
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

    /**
     * Get the next upcoming campus event for a university.
     * @param int $universityId
     * @return array|null|false
     */
    public function getNextEvent($universityId)
    {
        if (!$universityId || !is_numeric($universityId)) {
            return null;
        }

        try {
            $sql = "SELECT title, location, startDatetime
                    FROM Events
                    WHERE universityId = ?
                      AND status = 'active'
                      AND startDatetime >= NOW()
                    ORDER BY startDatetime ASC
                    LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$universityId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;

        } catch (PDOException $e) {
            error_log("getNextEvent error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Count how many classes the student is actively enrolled in.
     * @param int $studentId
     * @return int
     */
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

    /**
     * Count how many active (pending or confirmed) facility bookings the student has.
     * @param int $userId
     * @return int
     */
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

    /**
     * Count how many study groups the student is currently an active member of.
     * @param int $studentId
     * @return int
     */
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

    /**
     * Count upcoming active events for a university.
     * @param int $universityId
     * @return int
     */
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