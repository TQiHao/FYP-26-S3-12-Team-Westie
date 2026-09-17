<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../database/database.php";
require_once "../entity/Notification.php";

class NotificationController
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
    }

    /**
     * Get all notifications for a user
     * @param int $userId
     * @return array|false Returns List<Notification> or false on error
     */
    public function getNotification($userId)
    {
        try {
            $sql = "SELECT id, userId, type, title, message, isRead, createdAt
                    FROM Notifications
                    WHERE userId = ?
                    ORDER BY createdAt DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $notifications;

        } catch (Exception $e) {
            error_log("Notification fetch error: " . $e->getMessage());
            return false;  // <-- Alt flow: return Null
        }
    }
}
?>