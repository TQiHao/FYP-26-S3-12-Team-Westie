<?php

require_once "../database/database.php";
require_once "../entity/systemLog.php";

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

class ReviewFeedbackController
{
    private $db;

    public const PER_PAGE = 10;

    public const STATUSES = ['new', 'in_review', 'resolved'];

    private const TRANSITIONS = [
        'new'       => ['in_review', 'resolved'],
        'in_review' => ['resolved'],
        'resolved'  => ['in_review'],
    ];

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
    }

    public function getSummary()
    {
        $sql = "SELECT
                    COUNT(*)                                   AS total,
                    COALESCE(SUM(status = 'new'), 0)           AS newCount,
                    COALESCE(SUM(status = 'in_review'), 0)     AS inReviewCount,
                    COALESCE(SUM(status = 'resolved'), 0)      AS resolvedCount,
                    COALESCE(AVG(rating), 0)                   AS avgRating
                FROM Feedback";

        return $this->db->query($sql)->fetch(PDO::FETCH_ASSOC);
    }

    public function getUniversitiesForFilter()
    {
        $sql = "SELECT DISTINCT un.id, un.name
                FROM Feedback f
                JOIN Universities un ON un.id = f.universityId
                ORDER BY un.name ASC";

        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getFeedbackList($filters, $page)
    {
        $where  = [];
        $params = [];

        if (in_array($filters['status'], self::STATUSES, true)) {
            $where[]  = "f.status = ?";
            $params[] = $filters['status'];
        }

        $rating = (int) $filters['rating'];
        if ($rating >= 1 && $rating <= 5) {
            $where[]  = "f.rating = ?";
            $params[] = $rating;
        }

        $universityId = (int) $filters['university'];
        if ($universityId > 0) {
            $where[]  = "f.universityId = ?";
            $params[] = $universityId;
        }

        $search = trim($filters['search']);
        if ($search !== '') {
            $where[]  = "(f.message LIKE ? OR u.fullName LIKE ?)";
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }

        $whereSql = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";

        $from = "FROM Feedback f
                 JOIN Users u         ON u.id  = f.userId
                 JOIN Universities un ON un.id = f.universityId
                 $whereSql";

        $countStmt = $this->db->prepare("SELECT COUNT(*) $from");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $totalPages = max(1, (int) ceil($total / self::PER_PAGE));
        $page       = min(max(1, (int) $page), $totalPages);
        $offset     = ($page - 1) * self::PER_PAGE;

        $sql = "SELECT f.id, f.message, f.rating, f.category, f.status, f.createdAt,
                       u.fullName, u.role, un.name AS universityName
                $from
                ORDER BY f.createdAt DESC, f.id DESC
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

    public function getFeedbackDetails($feedbackId)
    {
        $sql = "SELECT f.id, f.message, f.rating, f.category, f.status, f.createdAt, f.updatedAt,
                       u.fullName, u.email, u.role,
                       un.id AS universityId, un.name AS universityName
                FROM Feedback f
                JOIN Users u         ON u.id  = f.userId
                JOIN Universities un ON un.id = f.universityId
                WHERE f.id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$feedbackId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateStatus($feedbackId, $newStatus)
    {
        if (!in_array($newStatus, self::STATUSES, true)) {
            return "invalid_status";
        }

        $current = $this->getFeedbackDetails($feedbackId);
        if (!$current) {
            return "not_found";
        }

        if (!in_array($newStatus, self::TRANSITIONS[$current['status']] ?? [], true)) {
            return "invalid_transition";
        }

        $stmt = $this->db->prepare("UPDATE Feedback SET status = ? WHERE id = ?");
        return $stmt->execute([$newStatus, $feedbackId]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['feedback_id'], $_POST['new_status'])) {

    $controller = new ReviewFeedbackController();
    $feedbackId = (int) $_POST['feedback_id'];

    $result = $controller->updateStatus($feedbackId, $_POST['new_status']);

    if ($result === true) {
        $labels = ['in_review' => 'marked as In Review', 'resolved' => 'marked as Resolved'];
        $_SESSION['feedback_review_success'] = "Feedback " . ($labels[$_POST['new_status']] ?? 'updated') . " successfully.";
        SystemLog::record('feedback', 'info', 'Feedback Status Changed',
            'FB-' . str_pad($feedbackId, 4, '0', STR_PAD_LEFT) . ' ' . ($labels[$_POST['new_status']] ?? 'updated') . '.');
    } elseif ($result === "invalid_transition") {
        $_SESSION['feedback_review_error'] = "That status change is not allowed for this feedback.";
    } else {
        $_SESSION['feedback_review_error'] = "Unable to update this feedback. Please try again.";
    }

    header("Location: ../boundary/viewFeedbackPage.php?id=" . $feedbackId);
    exit();
}