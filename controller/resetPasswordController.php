<?php

require_once __DIR__ . "/../database/database.php";
require_once __DIR__ . "../entity/users.php";
require_once __DIR__ . "../entity/systemLog.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Singapore');

class ResetPasswordController
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
    }

    // Step 1: Request password reset
    public function requestReset($email)
    {
        if (empty($email)) {
            $_SESSION['reset_error'] = "Please enter your email address.";
            return false;
        }

        // Checks for System Admins first, then university users.
        $account = $this->findAccountByEmail($email);

        if (!$account) {
            $_SESSION['reset_error'] = "Email not found. Please check and try again.";
            return false;
        }

        // A suspended System Admin must not be able to change the account's credentials
        if ($account['accountType'] === 'system_admin' && $account['status'] === 'suspended') {
            $_SESSION['reset_error'] = "Your account has been suspended. Please contact support.";
            return false;
        }

        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

        if ($account['accountType'] === 'system_admin') {
            $sql = "UPDATE SystemAdminResetTokens SET isUsed = TRUE WHERE adminId = ? AND isUsed = FALSE";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$account['id']]);

            $sql = "INSERT INTO SystemAdminResetTokens (adminId, tokenHash, expiresAt) VALUES (?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$account['id'], hash('sha256', $token), $expiresAt]);
        } else {
            $sql = "UPDATE PasswordResetTokens SET isUsed = TRUE WHERE userId = ? AND isUsed = FALSE";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$account['id']]);

            $sql = "INSERT INTO PasswordResetTokens (userId, token, expiresAt) VALUES (?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$account['id'], $token, $expiresAt]);
        }

        if (!$result) {
            $_SESSION['reset_error'] = "Unable to generate reset link. Please try again.";
            return false;
        }

        $resetLink = "http://localhost/FYP-26-S3-12-Team-Westie/boundary/resetPasswordPage.php?token=" . $token;
        error_log("Password reset link for $email: $resetLink");

        // System Admin activity belongs in the System Logs
        if ($account['accountType'] === 'system_admin') {
            SystemLog::record('authentication', 'info', 'Password Reset Requested',
                "A password reset link was requested for the system admin account $email.");
        }

        $_SESSION['reset_success'] = "A password reset link has been sent to your email.";
        return true;
    }

    // Step 2: Validate reset token
    public function validateToken($token)
    {
        if (empty($token)) {
            $_SESSION['reset_confirm_error'] = "Invalid reset link.";
            return false;
        }

        // First look for a System Admin token
        $tokenData = false;
        try {
            $sql = "SELECT t.id AS tokenId, t.expiresAt, a.id AS userId, a.email, a.fullName
                    FROM SystemAdminResetTokens t
                    JOIN SystemAdmins a ON t.adminId = a.id
                    WHERE t.tokenHash = ? AND t.isUsed = FALSE";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([hash('sha256', $token)]);
            $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log("validateToken: " . $e->getMessage());
        }

        if ($tokenData) {
            $tokenData['accountType'] = 'system_admin';
        } else {
            // Otherwise it must be a university user's token
            $sql = "SELECT prt.*, u.id as userId, u.email, u.fullName 
                    FROM PasswordResetTokens prt
                    JOIN Users u ON prt.userId = u.id
                    WHERE prt.token = ? AND prt.isUsed = FALSE";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$token]);
            $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($tokenData) {
                $tokenData['accountType'] = 'user';
            }
        }

        if (!$tokenData) {
            $_SESSION['reset_confirm_error'] = "The reset link has expired. Please request a new one.";
            return false;
        }

        $expiresAt = strtotime($tokenData['expiresAt']);
        if ($expiresAt < time()) {
            $_SESSION['reset_confirm_error'] = "The reset link has expired. Please request a new one.";
            return false;
        }

        return $tokenData;
    }

    // Step 3: Reset password
    public function resetPassword($token, $newPassword, $confirmPassword)
    {
        $tokenData = $this->validateToken($token);
        if (!$tokenData) {
            return false;
        }

        if (empty($newPassword) || empty($confirmPassword)) {
            $_SESSION['reset_confirm_error'] = "Please enter and confirm your new password.";
            return false;
        }

        if ($newPassword !== $confirmPassword) {
            $_SESSION['reset_confirm_error'] = "Passwords do not match. Please try again.";
            return false;
        }

        if (strlen($newPassword) < 8 || !preg_match('/[a-zA-Z]/', $newPassword) || !preg_match('/[0-9]/', $newPassword)) {
            $_SESSION['reset_confirm_error'] = "Password must be at least 8 characters long and include letters and numbers.";
            return false;
        }

        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $isAdmin = $tokenData['accountType'] === 'system_admin';

        // The new password and the "token used" flag change together or not at all
        $this->db->beginTransaction();
        try {
            if ($isAdmin) {
                $stmt = $this->db->prepare("UPDATE SystemAdmins SET passwordHash = ? WHERE id = ?");
                $stmt->execute([$passwordHash, $tokenData['userId']]);

                // Use up every open reset link for this admin, not just this one
                $stmt = $this->db->prepare("UPDATE SystemAdminResetTokens SET isUsed = TRUE WHERE adminId = ?");
                $stmt->execute([$tokenData['userId']]);
            } else {
                $stmt = $this->db->prepare("UPDATE Users SET passwordHash = ? WHERE id = ?");
                $stmt->execute([$passwordHash, $tokenData['userId']]);

                $stmt = $this->db->prepare("UPDATE PasswordResetTokens SET isUsed = TRUE WHERE token = ?");
                $stmt->execute([$token]);
            }

            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("resetPassword failed: " . $e->getMessage());
            $_SESSION['reset_confirm_error'] = "Unable to reset password. Please try again.";
            return false;
        }

        if ($isAdmin) {
            SystemLog::record('authentication', 'info', 'Password Reset',
                "The password of the system admin account {$tokenData['email']} was reset.");
        }

        $_SESSION['reset_confirm_success'] = "Password successfully reset. You can now log in with your new password.";
        return true;
    }

    // Find the account behind an email. System Admins are checked first, like the login page does.
    private function findAccountByEmail($email)
    {
        $stmt = $this->db->prepare("SELECT id, email, status FROM SystemAdmins WHERE email = ?");
        $stmt->execute([$email]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ($admin) {
            $admin['accountType'] = 'system_admin';
            return $admin;
        }

        $stmt = $this->db->prepare("SELECT id, email FROM Users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ($user) {
            $user['accountType'] = 'user';
            $user['status'] = null;
            return $user;
        }

        return false;
    }
}

// Handle request reset form submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['request_reset'])) {
    $email = trim($_POST['email']);
    $controller = new ResetPasswordController();
    $controller->requestReset($email);
    header("Location: ../boundary/resetPasswordPage.php");
    exit();
}

// Handle reset password form submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['reset_password'])) {
    $token = trim($_POST['token']);
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    $controller = new ResetPasswordController();
    $controller->resetPassword($token, $newPassword, $confirmPassword);
    header("Location: ../boundary/resetPasswordPage.php?token=" . urlencode($token));
    exit();
}
?>
