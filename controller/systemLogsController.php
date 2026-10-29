<?php

require_once __DIR__ . "/../database/database.php";
require_once __DIR__ . "/../entity/systemLog.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (
    !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true ||
    ($_SESSION['user_role'] ?? '') !== 'system_admin'
) {
    header("Location: ../boundary/loginPage.php");
    exit();
}

class SystemLogsController
{
    private $db;

    public const PER_PAGE = 15;
    public const SEVERITIES = ['info', 'warning', 'error'];

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
    }

    public function getCategories()
    {
        $sql = "SELECT DISTINCT category FROM SystemLogs
                WHERE category IS NOT NULL AND category <> ''
                ORDER BY category ASC";

        return $this->db->query($sql)->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getLogs($filters, $page)
    {
        $where  = [];
        $params = [];

        if (in_array($filters['severity'], self::SEVERITIES, true)) {
            $where[]  = "severity = ?";
            $params[] = $filters['severity'];
        }

        if (trim($filters['category']) !== '') {
            $where[]  = "category = ?";
            $params[] = $filters['category'];
        }

        // Dates must be real YYYY-MM-DD dates, otherwise they are ignored.
        $from = DateTime::createFromFormat('Y-m-d', $filters['dateFrom']);
        if ($from && $from->format('Y-m-d') === $filters['dateFrom']) {
            $where[]  = "createdAt >= ?";
            $params[] = $filters['dateFrom'] . ' 00:00:00';
        }

        $to = DateTime::createFromFormat('Y-m-d', $filters['dateTo']);
        if ($to && $to->format('Y-m-d') === $filters['dateTo']) {
            $where[]  = "createdAt < ?";
            $params[] = $to->modify('+1 day')->format('Y-m-d') . ' 00:00:00';
        }

        $search = trim($filters['search']);
        if ($search !== '') {
            $where[]  = "(action LIKE ? OR description LIKE ?)";
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }

        $whereSql = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM SystemLogs $whereSql");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $totalPages = max(1, (int) ceil($total / self::PER_PAGE));
        $page       = min(max(1, (int) $page), $totalPages);
        $offset     = ($page - 1) * self::PER_PAGE;

        $sql = "SELECT id, category, severity, action, description, createdAt
                FROM SystemLogs
                $whereSql
                ORDER BY createdAt DESC, id DESC
                LIMIT " . self::PER_PAGE . " OFFSET " . $offset;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return [
            'rows'       => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total'      => $total,
            'page'       => $page,
            'totalPages' => $totalPages,
        ];
    }
}