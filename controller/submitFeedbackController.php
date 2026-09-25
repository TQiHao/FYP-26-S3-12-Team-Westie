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
     * @param int $rating
     * @return string|null Returns success message or null on failure
     */
    public function createFeedback($userId, $universityId, $message, $rating)
    {
        // Alt flow: Missing required field
        if (empty($userId) || empty($universityId) || empty($message) || empty($rating)) {
            return null;
        }

        // Validate rating (1–5)
        $rating = (int) $rating;
        if ($rating < 1 || $rating > 5) {
            return null;
        }

        try {
            $sql = "INSERT INTO Feedback (userId, universityId, category, message, rating, status, createdAt, updatedAt)
                    VALUES (?, ?, 'general', ?, ?, 'new', NOW(), NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $universityId, $message, $rating]);

            return "Feedback submitted successfully.";

        } catch (Exception $e) {
            error_log("Feedback submission error: " . $e->getMessage());
            return null;
        }
    }
}

// ===== HANDLE FORM SUBMISSION =====
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['submit_feedback'])) {

    $userId = $_SESSION['user_id'];
    $universityId = $_SESSION['university_id'];
    $message = trim($_POST['message']);
    $rating = $_POST['rating'] ?? null;

    $controller = new SubmitFeedbackController();
    $result = $controller->createFeedback($userId, $universityId, $message, $rating);

    if ($result === null) {
        $_SESSION['feedback_error'] = "Please provide a rating and feedback message.";
    } else {
        $_SESSION['feedback_success'] = $result;
    }

    header("Location: ../boundary/submitFeedbackPage.php");
    exit();
}
?>