<?php

require_once "../database/database.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../boundary/LoginPage.php");
    exit();
}

class SystemAdminManageProfileController
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
    }

    //Get a system admin's profile for display.
    public function getProfile($systemAdminId)
    {
        $sql = "SELECT
                    sa.id,
                    sa.email,
                    sa.fullName,
                    sap.department,
                    sap.position,
                    sap.contactNumber
                FROM SystemAdmins sa
                LEFT JOIN SystemAdminProfiles sap ON sap.systemAdminId = sa.id
                WHERE sa.id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$systemAdminId]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);

        return $profile ?: null;
    }

    //Update a system admin's profile.
    public function updateProfile($systemAdminId, $fullName, $department, $position, $contactNumber)
    {
        $fullName = trim($fullName);

        if (empty($fullName)) {
            return "missing_name";
        }

        try {
            $this->db->beginTransaction();

            $sql = "UPDATE SystemAdmins SET fullName = ?, updatedAt = NOW() WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$fullName, $systemAdminId]);

            $checkSql = "SELECT COUNT(*) FROM SystemAdminProfiles WHERE systemAdminId = ?";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->execute([$systemAdminId]);

            if ($checkStmt->fetchColumn() > 0) {
                $sql = "UPDATE SystemAdminProfiles
                        SET department = ?, position = ?, contactNumber = ?, updatedAt = NOW()
                        WHERE systemAdminId = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$department, $position, $contactNumber, $systemAdminId]);
            } else {
                $sql = "INSERT INTO SystemAdminProfiles
                            (systemAdminId, department, position, contactNumber, createdAt, updatedAt)
                        VALUES (?, ?, ?, ?, NOW(), NOW())";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$systemAdminId, $department, $position, $contactNumber]);
            }

            $this->db->commit();
            return true;

        } catch (PDOException $e) {
            $this->db->rollBack();
            return "error";
        }
    }
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_profile'])) {

    $systemAdminId = $_SESSION['user_id'];
    $fullName = trim($_POST['fullName'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $contactNumber = trim($_POST['contactNumber'] ?? '');

    $controller = new SystemAdminManageProfileController();
    $result = $controller->updateProfile($systemAdminId, $fullName, $department, $position, $contactNumber);

    if ($result === true) {
        $_SESSION['user_name'] = $fullName;
        $_SESSION['sysadmin_profile_success'] = "Profile Updated Successfully.";
    } elseif ($result === "missing_name") {
        $_SESSION['sysadmin_profile_error'] = "Please enter your full name.";
        header("Location: ../boundary/updateSystemAdminProfilePage.php");
        exit();
    } else {
        $_SESSION['sysadmin_profile_error'] = "Profile update failed. Please try again later.";
    }

    header("Location: ../boundary/systemAdminProfilePage.php");
    exit();
}