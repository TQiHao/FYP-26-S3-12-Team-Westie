<?php

require_once __DIR__ . "/../database/database.php";

/**
 * Entity for the SystemLogs table. Any controller can call SystemLog::record() to save an event.
 *
 *   SystemLog::record('authentication', 'info', 'Login', 'User logged in');
 */
class SystemLog
{
    public const SEVERITIES = ['info', 'warning', 'error'];

    /**
     * @param string      $category
     * @param string      $severity
     * @param string      $action
     * @param string      $description details
     * @param string|null $source
     */
    public static function record($category, $severity, $action, $description = '', $source = null)
    {
        try {
            if (!in_array($severity, self::SEVERITIES, true)) {
                $severity = 'info';
            }

            // Read who is logged in
            $session = (session_status() === PHP_SESSION_ACTIVE) ? $_SESSION : [];

            // Only System Admin activity is recorded
            if (!empty($session['user_role']) && $session['user_role'] !== 'system_admin') {
                return;
            }

            if (!empty($session['user_name'])) {
                $role = str_replace('_', ' ', $session['user_role'] ?? 'user');
                $description .= ' (by ' . $session['user_name'] . ', ' . $role . ')';
            }

            if ($source === null) {
                $source = basename($_SERVER['SCRIPT_NAME'] ?? 'unknown');
            }

            $database = new Database();
            $db = $database->connect();

            $stmt = $db->prepare(
                "INSERT INTO SystemLogs (category, severity, action, description, source)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                substr($category, 0, 100),
                $severity,
                substr($action, 0, 255),
                $description,
                substr($source, 0, 255),
            ]);
        } catch (Throwable $e) {
            error_log("SystemLog::record failed: " . $e->getMessage());
        }
    }
    
    // ------------------------------------------------------------------
    // Reading the log (System Admin > System Operation > System Logs)
    // ------------------------------------------------------------------

    /** Every category that appears in the log, for the filter drop-down */
    public static function getCategories()
    {
        $database = new Database();
        $db = $database->connect();

        $sql = "SELECT DISTINCT category FROM SystemLogs
                WHERE category IS NOT NULL AND category <> ''
                ORDER BY category ASC";

        return $db->query($sql)->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * One page of log entries, newest first. $filters holds severity ('' = any),
     * category ('' = any), dateFrom / dateTo (YYYY-MM-DD, '' = none) and search text.
     */
    public static function search($filters, $page, $perPage)
    {
        $database = new Database();
        $db = $database->connect();

        $where  = [];
        $params = [];

        if ($filters['severity'] !== '') {
            $where[]  = "severity = ?";
            $params[] = $filters['severity'];
        }

        if ($filters['category'] !== '') {
            $where[]  = "category = ?";
            $params[] = $filters['category'];
        }

        if ($filters['dateFrom'] !== '') {
            $where[]  = "createdAt >= ?";
            $params[] = $filters['dateFrom'] . ' 00:00:00';
        }

        if ($filters['dateTo'] !== '') {
            $to = DateTime::createFromFormat('Y-m-d', $filters['dateTo']);
            $where[]  = "createdAt < ?";
            $params[] = $to->modify('+1 day')->format('Y-m-d') . ' 00:00:00';
        }

        if ($filters['search'] !== '') {
            $where[]  = "(action LIKE ? OR description LIKE ?)";
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
        }

        $whereSql = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";

        $countStmt = $db->prepare("SELECT COUNT(*) FROM SystemLogs $whereSql");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $totalPages = max(1, (int) ceil($total / $perPage));
        $page       = min(max(1, (int) $page), $totalPages);
        $offset     = ($page - 1) * $perPage;

        $sql = "SELECT id, category, severity, action, description, createdAt
                FROM SystemLogs
                $whereSql
                ORDER BY createdAt DESC, id DESC
                LIMIT " . (int) $perPage . " OFFSET " . $offset;

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        return [
            'rows'       => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total'      => $total,
            'page'       => $page,
            'totalPages' => $totalPages,
        ];
    }
}