<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../database/database.php";
require_once "../entity/timetableEntry.php";

class TeachingController
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
    }

    /**
     * Entry point when lecturer clicks "Teaching".
     * Returns number of assigned courses for the dashboard card.
     * Returns null if staffId is invalid.
     * Returns false on DB error.
     */
    public function loadTeachingView($staffId)
    {
        if (!$this->isValidId($staffId)) {
            return null;
        }

        $sql = "SELECT COUNT(DISTINCT c.id) AS total
                FROM Classes c
                WHERE (c.staffId = :staffId OR c.courseCoordinatorId = :staffId)
                  AND c.status = 'active'";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':staffId', (int) $staffId, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'assignedCount' => $row ? (int) $row['total'] : 0
            ];
        } catch (PDOException $e) {
            error_log("loadTeachingView error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get timetable entries for a staff member.
     * Pulls from TimetableEntries table (personal schedule).
     * Returns List<TimetableEntry>.
     * Returns null if no entries found.
     * Returns false on DB error.
     */
    public function getTimetable($staffId)
    {
        if (!$this->isValidId($staffId)) {
            return null;
        }

        // Combines custom TimetableEntries with assigned Classes
        $sql = "SELECT id, userId, title, dayOfWeek, startTime, endTime, location, createdAt, updatedAt
            FROM (
                SELECT id, userId, title, dayOfWeek, startTime, endTime, location, createdAt, updatedAt
                FROM TimetableEntries
                WHERE userId = :staffId

                UNION ALL

                SELECT c.id, 
                       c.staffId AS userId, 
                       m.code AS title, 
                       c.dayOfWeek, 
                       c.startTime, 
                       c.endTime, 
                       c.room AS location, 
                       c.createdAt, 
                       c.updatedAt
                FROM Classes c
                INNER JOIN Modules m ON c.moduleId = m.id
                WHERE (c.staffId = :staffId OR c.courseCoordinatorId = :staffId)
                  AND c.status = 'active'
            ) AS CombinedTimetable
            ORDER BY FIELD(dayOfWeek,'mon','tue','wed','thu','fri','sat','sun'),
                     startTime ASC";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':staffId', (int) $staffId, PDO::PARAM_INT);
            $stmt->execute();

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($rows)) {
                return null; // No timetable entries found
            }

            $entries = [];
            foreach ($rows as $row) {
                $entries[] = new TimetableEntry(
                    $row['id'],
                    $row['userId'],
                    $row['title'],
                    $row['dayOfWeek'],
                    $row['startTime'],
                    $row['endTime'],
                    $row['location'],
                    $row['createdAt'],
                    $row['updatedAt']
                );
            }
            return $entries;

        } catch (PDOException $e) {
            error_log("getTimetable error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get modules assigned to this staff member (via Classes table).
     * Returns List<array> (simple rows).
     * Returns null if no modules assigned.
     * Returns false on DB error.
     */
    public function getAssignedModules($staffId)
    {
        if (!$this->isValidId($staffId)) {
            return null;
        }

        $sql = "SELECT c.id, c.className, c.room, c.dayOfWeek, c.startTime, c.endTime,
                       c.academicYear, c.semester,
                       m.code AS moduleCode, m.name AS moduleName
                FROM Classes c
                INNER JOIN Modules m ON c.moduleId = m.id
                WHERE (c.staffId = :staffId OR c.courseCoordinatorId = :staffId)
                  AND c.status = 'active'
                ORDER BY c.semester ASC,
                         FIELD(c.dayOfWeek,'mon','tue','wed','thu','fri','sat','sun'),
                         c.startTime ASC";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':staffId', (int) $staffId, PDO::PARAM_INT);
            $stmt->execute();

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($rows)) {
                return null; // "You are not assigned in any modules" flow
            }
            return $rows;

        } catch (PDOException $e) {
            error_log("getAssignedModules error: " . $e->getMessage());
            return false; // "Unable to retrieve assigned modules" flow
        }
    }

    // helper 
    private function isValidId($id)
    {
        return isset($id) && is_numeric($id) && (int) $id > 0;
    }
}
?>