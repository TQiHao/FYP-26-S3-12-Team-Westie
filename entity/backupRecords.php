<?php

require_once __DIR__ . "/../database/database.php";

/**
 * Entity for the BackupRecords table: the list of database backups the System Admin has made.
 * Every method opens its own connection, so it keeps working after a restore replaces the data.
 */
class BackupRecords
{
    private static function connect()
    {
        $database = new Database();
        return $database->connect();
    }

    /** One page of backups, newest first, with the name of the admin who made each one */
    public static function getPage($page, $perPage)
    {
        $db = self::connect();

        $total = (int) $db->query("SELECT COUNT(*) FROM BackupRecords")->fetchColumn();

        $totalPages = max(1, (int) ceil($total / $perPage));
        $page       = min(max(1, (int) $page), $totalPages);
        $offset     = ($page - 1) * $perPage;

        $sql = "SELECT b.id, b.fileName, b.backupType, b.status, b.createdAt, b.restoredAt,
                       sa.fullName AS createdByName
                FROM BackupRecords b
                LEFT JOIN SystemAdmins sa ON sa.id = b.createdBy
                ORDER BY b.createdAt DESC, b.id DESC
                LIMIT " . (int) $perPage . " OFFSET " . $offset;

        return [
            'rows'       => $db->query($sql)->fetchAll(PDO::FETCH_ASSOC),
            'total'      => $total,
            'page'       => $page,
            'totalPages' => $totalPages,
        ];
    }

    /** One backup record (id, file name, status), or false */
    public static function getById($backupId)
    {
        $stmt = self::connect()->prepare("SELECT id, fileName, status FROM BackupRecords WHERE id = ?");
        $stmt->execute([$backupId]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        return $record;
    }

    /** Add a backup to the list and return its new id */
    public static function insert($fileName, $type, $status, $adminId)
    {
        $db = self::connect();

        $stmt = $db->prepare(
            "INSERT INTO BackupRecords (fileName, filePath, backupType, status, createdBy, createdAt)
            VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$fileName, 'backups/' . $fileName, $type, $status,
                        self::existingAdminId($db, $adminId), date('Y-m-d H:i:s')]);

        return (int) $db->lastInsertId();
    }

    /** Note who restored this backup and when */
    public static function markRestored($backupId, $adminId)
    {
        $db = self::connect();

        $stmt = $db->prepare("UPDATE BackupRecords SET restoredBy = ?, restoredAt = ? WHERE id = ?");
        $stmt->execute([$adminId, date('Y-m-d H:i:s'), $backupId]);
    }

    /** The folder where the database server is installed (used to find mysqldump), or null */
    public static function getServerBaseDir()
    {
        try {
            $baseDir = self::connect()->query("SELECT @@basedir")->fetchColumn();
            return $baseDir ?: null;
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * A copy of the backup list and the system log list.
     * A restore overwrites every table, so these two are saved first and put back afterwards.
     */
    public static function snapshot()
    {
        $db = self::connect();

        return [
            'backups' => $db->query("SELECT * FROM BackupRecords")->fetchAll(PDO::FETCH_ASSOC),
            'logs'    => $db->query("SELECT * FROM SystemLogs")->fetchAll(PDO::FETCH_ASSOC),
        ];
    }

    /** Put the saved backup list and log list back after a restore */
    public static function rebuild($snapshot)
    {
        $db = self::connect();

        $adminIds = $db->query("SELECT id FROM SystemAdmins")->fetchAll(PDO::FETCH_COLUMN);

        $db->beginTransaction();
        try {
            $db->exec("DELETE FROM BackupRecords");
            $db->exec("DELETE FROM SystemLogs");

            foreach ($snapshot['backups'] as $row) {
                // Admin ids that no longer exist would break the foreign key, so blank them
                foreach (['createdBy', 'restoredBy'] as $column) {
                    if ($row[$column] !== null && !in_array($row[$column], $adminIds)) {
                        $row[$column] = null;
                    }
                }
                self::insertRow($db, 'BackupRecords', $row);
            }

            foreach ($snapshot['logs'] as $row) {
                self::insertRow($db, 'SystemLogs', $row);
            }

            $db->commit();
            return true;
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("BackupRecords::rebuild failed: " . $e->getMessage());
            return false;
        }
    }

    // Foreign keys reject an admin id that does not exist (possible after restoring an old backup)
    private static function existingAdminId($db, $adminId)
    {
        $stmt = $db->prepare("SELECT id FROM SystemAdmins WHERE id = ?");
        $stmt->execute([$adminId]);
        $found = $stmt->fetchColumn();
        $stmt->closeCursor();

        return $found ?: null;
    }

    private static function insertRow($db, $table, $row)
    {
        $columns      = array_keys($row);
        $placeholders = implode(',', array_fill(0, count($columns), '?'));
        $sql = "INSERT INTO $table (" . implode(',', $columns) . ") VALUES ($placeholders)";
        $db->prepare($sql)->execute(array_values($row));
    }
}