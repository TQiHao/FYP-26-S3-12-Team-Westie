<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../controller/authGuard.php";
requireRole(['lecturer']);

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

    public function getTimetable($staffId)
    {
        if (!$this->isValidId($staffId)) {
            return null;
        }

        // ADDED: c.startDate, c.endDate
        $sql = "SELECT id, userId, title, dayOfWeek, startTime, endTime, location, createdAt, updatedAt, academicYear, semester, startDate, endDate
            FROM (
                SELECT id, userId, title, dayOfWeek, startTime, endTime, location, createdAt, updatedAt, NULL AS academicYear, NULL AS semester, NULL AS startDate, NULL AS endDate
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
                       c.updatedAt,
                       c.academicYear,
                       c.semester,
                       c.startDate,
                       c.endDate
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
                return null;
            }

            $entries = [];
            foreach ($rows as $row) {
                $entry = new TimetableEntry(
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
                if (!empty($row['academicYear'])) {
                    $entry->academicYear = $row['academicYear'];
                    $entry->semester = $row['semester'];
                }
                // Attach startDate and endDate dynamically
                $entry->startDate = $row['startDate'];
                $entry->endDate = $row['endDate'];

                $entries[] = $entry;
            }
            return $entries;

        } catch (PDOException $e) {
            error_log("getTimetable error: " . $e->getMessage());
            return false;
        }
    }

    public function getAssignedModules($staffId)
    {
        if (!$this->isValidId($staffId)) {
            return null;
        }

        $sql = "SELECT c.id, c.className, c.room, c.dayOfWeek, c.startTime, c.endTime,
                       c.academicYear, c.semester, c.startDate, c.endDate,
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
                return null;
            }
            return $rows;

        } catch (PDOException $e) {
            error_log("getAssignedModules error: " . $e->getMessage());
            return false;
        }
    }

    private function isValidId($id)
    {
        return isset($id) && is_numeric($id) && (int) $id > 0;
    }
}
?>