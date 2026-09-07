<?php

require_once "../database/database.php";
require_once "../entity/users.php";

session_start();

class LoginController
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
    }

    /**
     * Validate user credentials
     * @param string $email
     * @param string $password
     * @return array|false Returns user data if valid, false otherwise
     */
    public function validateCredentials($email, $password)
    {
        // Check for blank input
        if (empty($email) || empty($password)) {
            $_SESSION['login_error'] = "Please enter your email and password.";
            return false;
        }

        // Query user by email
        $sql = "SELECT id, universityId, email, passwordHash, fullName, role, status 
                FROM Users 
                WHERE email = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Check if user exists
        if (!$user) {
            $_SESSION['login_error'] = "Invalid email or password. Please try again.";
            return false;
        }

        // Verify password
        if (!password_verify($password, $user['passwordHash'])) {
            $_SESSION['login_error'] = "Invalid email or password. Please try again.";
            return false;
        }

        // Check if account is suspended
        if ($user['status'] === 'suspended') {
            $_SESSION['login_error'] = "Your account has been suspended. Please contact support.";
            return false;
        }

        // Login successful - return user data
        return $user;
    }

    /**
     * Handle user login and session creation
     * @param array $userData
     * @return void
     */
    public function loginUser($userData)
    {
        // Regenerate session ID for security
        session_regenerate_id(true);

        // Store user data in session
        $_SESSION['user_id'] = $userData['id'];
        $_SESSION['university_id'] = $userData['universityId'];
        $_SESSION['user_email'] = $userData['email'];
        $_SESSION['user_name'] = $userData['fullName'];
        $_SESSION['user_role'] = $userData['role'];
        $_SESSION['user_status'] = $userData['status'];
        $_SESSION['logged_in'] = true;

        // Update last login timestamp
        $this->updateLastLogin($userData['id']);

        // Handle "Remember for 30 days"
        if (isset($_POST['remember']) && $_POST['remember'] == 1) {
            $token = bin2hex(random_bytes(32));
            setcookie('remember_token', $token, time() + (86400 * 30), '/', '', false, true);
            // Store token in database for persistent login (optional)
        }

        $_SESSION['login_success'] = "Welcome back, " . $userData['fullName'] . "!";
    }

    /**
     * Update last login timestamp
     * @param int $userId
     * @return void
     */
    private function updateLastLogin($userId)
    {
        $sql = "UPDATE Users SET lastLogin = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
    }

    /**
     * Redirect user based on role
     * @param string $role
     * @return void
     */
    public function redirectBasedOnRole($role)
    {
        switch ($role) {
            case 'student':
                header("Location: ../boundary/studentDashboardPage.php");
                break;
            case 'lecturer':
                header("Location: ../boundary/lecturerDashboardPage.php");
                break;
            case 'course_coordinator':
                header("Location: ../boundary/courseCoordinatorDashboardPage.php");
                break;
            case 'university_admin':
                header("Location: ../boundary/universityAdminDashboardPage.php");
                break;
            case 'system_admin':
                header("Location: ../boundary/systemAdminDashboardPage.php");
                break;
            default:
                header("Location: ../boundary/dashboardPage.php");
                break;
        }
        exit();
    }
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['login'])) {

    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $controller = new LoginController();
    $userData = $controller->validateCredentials($email, $password);

    if ($userData !== false) {
        $controller->loginUser($userData);
        $controller->redirectBasedOnRole($userData['role']);
    } else {
        // Redirect back to login page with error
        header("Location: ../boundary/LoginPage.php");
        exit();
    }
}
