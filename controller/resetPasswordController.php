<?php

require_once "../database/Database.php";
require_once "../entity/Users.php";

session_start();

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

        $sql = "SELECT id, email FROM Users WHERE email = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $_SESSION['reset_error'] = "Email not found. Please check and try again.";
            return false;
        }

        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Invalidate existing tokens
        $sql = "UPDATE PasswordResetTokens SET isUsed = TRUE WHERE userId = ? AND isUsed = FALSE";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$user['id']]);

        // Store new token
        $sql = "INSERT INTO PasswordResetTokens (userId, token, expiresAt) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([$user['id'], $token, $expiresAt]);

        if (!$result) {
            $_SESSION['reset_error'] = "Unable to generate reset link. Please try again.";
            return false;
        }

        $resetLink = "http://localhost/FYP-26-S3-12-Team-Westie/boundary/ResetPasswordPage.php?token=" . $token;
        error_log("Password reset link for $email: $resetLink");

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

        $sql = "SELECT prt.*, u.id as userId, u.email, u.fullName 
                FROM PasswordResetTokens prt
                JOIN Users u ON prt.userId = u.id
                WHERE prt.token = ? AND prt.isUsed = FALSE";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$token]);
        $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);

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

        $sql = "UPDATE Users SET passwordHash = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([$passwordHash, $tokenData['userId']]);

        if (!$result) {
            $_SESSION['reset_confirm_error'] = "Unable to reset password. Please try again.";
            return false;
        }

        $sql = "UPDATE PasswordResetTokens SET isUsed = TRUE WHERE token = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$token]);

        $_SESSION['reset_confirm_success'] = "Password successfully reset. You can now log in with your new password.";
        return true;
    }
}

// Handle request reset form submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['request_reset'])) {
    $email = trim($_POST['email']);
    $controller = new ResetPasswordController();
    $controller->requestReset($email);
    header("Location: ../boundary/ResetPasswordPage.php");
    exit();
}

// Handle reset password form submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['reset_password'])) {
    $token = trim($_POST['token']);
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    $controller = new ResetPasswordController();
    $controller->resetPassword($token, $newPassword, $confirmPassword);
    header("Location: ../boundary/ResetPasswordPage.php?token=" . urlencode($token));
    exit();
}
?>
