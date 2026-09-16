<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../database/database.php";
require_once "../entity/users.php";
require_once "../entity/UserProfiles.php";

class UpdateProfileController
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
    }

    /**
     * Get user by ID (for pre-filling the form)
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

    /**
     * Update user profile
     * @param int $userId
     * @param string $fullName
     * @param string $contactNumber
     * @param string $address
     * @return bool
     */
    public function updateProfile($userId, $fullName, $contactNumber, $address)
    {
        // Validate input
        if (empty($fullName)) {
            return false;
        }

        try {
            $this->db->beginTransaction();

            // Update Users table
            $sql = "UPDATE Users SET fullName = ?, updatedAt = NOW() WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$fullName, $userId]);

            // Check if profile exists
            $checkSql = "SELECT COUNT(*) FROM UserProfiles WHERE userId = ?";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->execute([$userId]);

            if ($checkStmt->fetchColumn() > 0) {
                // Update existing profile
                $sql = "UPDATE UserProfiles SET contactNumber = ?, address = ?, updatedAt = NOW() WHERE userId = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$contactNumber, $address, $userId]);
            } else {
                // Insert new profile
                $sql = "INSERT INTO UserProfiles (userId, contactNumber, address, createdAt, updatedAt)
                        VALUES (?, ?, ?, NOW(), NOW())";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$userId, $contactNumber, $address]);
            }

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Profile update error: " . $e->getMessage());
            return false;
        }
    }
}

// ===== HANDLE FORM SUBMISSION =====
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_profile'])) {

    $userId = $_SESSION['user_id'];
    $fullName = trim($_POST['fullName']);
    $contactNumber = trim($_POST['contactNumber']);
    $address = trim($_POST['address']);

    $controller = new UpdateProfileController();

    if (empty($fullName)) {
        $_SESSION['profile_error'] = "Please enter valid information.";
    } elseif ($controller->updateProfile($userId, $fullName, $contactNumber, $address)) {
        $_SESSION['profile_success'] = "Profile updated successfully.";
    } else {
        $_SESSION['profile_error'] = "Profile update failed. Please try again later.";
    }

    header("Location: ../boundary/ManageProfilePage.php");
    exit();
}
?>