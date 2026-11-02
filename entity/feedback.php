<?php

require_once __DIR__ . "/../database/database.php";

class Feedback
{
    private $id;
    private $userId;
    private $universityId;
    private $category;
    private $message;
    private $rating;
    private $status;
    private $createdAt;
    private $updatedAt;

    public function __construct(
        $id = null,
        $userId = null,
        $universityId = null,
        $category = null,
        $message = null,
        $rating = null,
        $status = null,
        $createdAt = null,
        $updatedAt = null
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->universityId = $universityId;
        $this->category = $category;
        $this->message = $message;
        $this->rating = $rating;
        $this->status = $status;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    // Getters
    public function getId()
    {
        return $this->id;
    }
    public function getUserId()
    {
        return $this->userId;
    }
    public function getUniversityId()
    {
        return $this->universityId;
    }
    public function getCategory()
    {
        return $this->category;
    }
    public function getMessage()
    {
        return $this->message;
    }
    public function getRating()
    {
        return $this->rating;
    }
    public function getStatus()
    {
        return $this->status;
    }
    public function getCreatedAt()
    {
        return $this->createdAt;
    }
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    // Setters
    public function setCategory($category)
    {
        $this->category = $category;
    }
    public function setMessage($message)
    {
        $this->message = $message;
    }
    public function setRating($rating)
    {
        $this->rating = $rating;
    }
    public function setStatus($status)
    {
        $this->status = $status;
    }
    
    // ------------------------------------------------------------------
    // System Admin: review feedback
    // ------------------------------------------------------------------

    private static function connect()
    {
        $database = new Database();
        return $database->connect();
    }

    /** Totals for the summary cards at the top of the Review Feedback page */
    public static function getSummary()
    {
        $sql = "SELECT
                    COUNT(*)                                   AS total,
                    COALESCE(SUM(status = 'new'), 0)           AS newCount,
                    COALESCE(SUM(status = 'in_review'), 0)     AS inReviewCount,
                    COALESCE(SUM(status = 'resolved'), 0)      AS resolvedCount,
                    COALESCE(AVG(rating), 0)                   AS avgRating
                FROM Feedback";

        return self::connect()->query($sql)->fetch(PDO::FETCH_ASSOC);
    }

    /** Universities that have sent feedback (for the filter drop-down) */
    public static function getUniversitiesForFilter()
    {
        $sql = "SELECT DISTINCT un.id, un.name
                FROM Feedback f
                JOIN Universities un ON un.id = f.universityId
                ORDER BY un.name ASC";

        return self::connect()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * One page of feedback. $filters holds status ('' = any), rating (0 = any),
     * university (0 = any) and search text ('' = none).
     */
    public static function search($filters, $page, $perPage)
    {
        $db     = self::connect();
        $where  = [];
        $params = [];

        if ($filters['status'] !== '') {
            $where[]  = "f.status = ?";
            $params[] = $filters['status'];
        }

        if ($filters['rating'] > 0) {
            $where[]  = "f.rating = ?";
            $params[] = $filters['rating'];
        }

        if ($filters['university'] > 0) {
            $where[]  = "f.universityId = ?";
            $params[] = $filters['university'];
        }

        if ($filters['search'] !== '') {
            $where[]  = "(f.message LIKE ? OR u.fullName LIKE ?)";
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
        }

        $whereSql = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";

        $from = "FROM Feedback f
                 JOIN Users u         ON u.id  = f.userId
                 JOIN Universities un ON un.id = f.universityId
                 $whereSql";

        $countStmt = $db->prepare("SELECT COUNT(*) $from");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $totalPages = max(1, (int) ceil($total / $perPage));
        $page       = min(max(1, (int) $page), $totalPages);
        $offset     = ($page - 1) * $perPage;

        $sql = "SELECT f.id, f.message, f.rating, f.category, f.status, f.createdAt,
                       u.fullName, u.role, un.name AS universityName
                $from
                ORDER BY f.createdAt DESC, f.id DESC
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

    /** One feedback entry with who sent it and from which university, or false */
    public static function getDetails($feedbackId)
    {
        $sql = "SELECT f.id, f.message, f.rating, f.category, f.status, f.createdAt, f.updatedAt,
                       u.fullName, u.email, u.role,
                       un.id AS universityId, un.name AS universityName
                FROM Feedback f
                JOIN Users u         ON u.id  = f.userId
                JOIN Universities un ON un.id = f.universityId
                WHERE f.id = ?";

        $stmt = self::connect()->prepare($sql);
        $stmt->execute([$feedbackId]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /** Change the status of one feedback entry */
    public static function updateStatus($feedbackId, $newStatus)
    {
        $stmt = self::connect()->prepare("UPDATE Feedback SET status = ? WHERE id = ?");

        return $stmt->execute([$newStatus, $feedbackId]);
    }
}
?>