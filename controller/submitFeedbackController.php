<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../database/database.php";
require_once "../entity/Feedback.php";

class SubmitFeedbackController
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
    }

    /**
     * Create feedback record
     * @param int $userId
     * @param int $universityId
     * @param string $message
     * @return string|false Returns success message or false on failure
     */
    public function createFeedback($userId, $universityId, $message)
    {
        // Alt flow: Missing required field
        if (empty($userId) || empty($universityId) || empty($message)) {
            return null; // <-- matches "return Null" in sequence diagram
        }

        try {
            $sql = "INSERT INTO Feedback (userId, universityId, category, message, status, createdAt, updatedAt)
                    VALUES (?, ?, 'general', ?, 'new', NOW(), NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $universityId, $message]);

            return "Feedback submitted successfully.";

        } catch (Exception $e) {
            error_log("Feedback submission error: " . $e->getMessage());
            return null; // <-- matches "return Null" in sequence diagram
        }
    }
}

// ===== HANDLE FORM SUBMISSION =====
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['submit_feedback'])) {

    $userId = $_SESSION['user_id'];
    $universityId = $_SESSION['university_id'];
    $message = trim($_POST['message']);

    $controller = new SubmitFeedbackController();
    $result = $controller->createFeedback($userId, $universityId, $message);

    if ($result === null) {
        $_SESSION['feedback_error'] = "Missing required field.";
    } else {
        $_SESSION['feedback_success'] = $result;
    }

    header("Location: ../boundary/SubmitFeedbackPage.php");
    exit();
}
?>