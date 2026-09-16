<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../database/database.php";
require_once "../entity/users.php";
require_once "../entity/UserProfiles.php";

class ManageProfileController
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
    }

    /**
     * Get user and profile data by user ID
     * @param int $userId
     * @return array|false
     */
    public function getUserById($userId)
    {
        $sql = "SELECT u.id, u.email, u.fullName, u.role, u.status,
                       up.contactNumber, up.address
                FROM Users u
                LEFT JOIN UserProfiles up ON u.id = up.userId
                WHERE u.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>