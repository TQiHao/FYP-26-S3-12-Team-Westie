<?php

require_once "../entity/feedback.php";
require_once "../entity/systemLog.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../controller/authGuard.php";
requireRole(['system_admin']);

class ReviewFeedbackController
{
    public const PER_PAGE = 10;

    public const STATUSES = ['new', 'in_review', 'resolved'];

    private const TRANSITIONS = [
        'new'       => ['in_review', 'resolved'],
        'in_review' => ['resolved'],
        'resolved'  => ['in_review'],
    ];

    public function getSummary()
    {
        return Feedback::getSummary();
    }

    public function getUniversitiesForFilter()
    {
        return Feedback::getUniversitiesForFilter();
    }

    public function getFeedbackList($filters, $page)
    {
        $rating = (int) $filters['rating'];

        $clean = [
            'status'     => in_array($filters['status'], self::STATUSES, true) ? $filters['status'] : '',
            'rating'     => ($rating >= 1 && $rating <= 5) ? $rating : 0,
            'university' => max(0, (int) $filters['university']),
            'search'     => trim($filters['search']),
        ];

        return Feedback::search($clean, $page, self::PER_PAGE);
    }

    public function getFeedbackDetails($feedbackId)
    {
        return Feedback::getDetails($feedbackId);
    }

    public function updateStatus($feedbackId, $newStatus)
    {
        if (!in_array($newStatus, self::STATUSES, true)) {
            return "invalid_status";
        }

        $current = Feedback::getDetails($feedbackId);
        if (!$current) {
            return "not_found";
        }

        if (!in_array($newStatus, self::TRANSITIONS[$current['status']] ?? [], true)) {
            return "invalid_transition";
        }

        return Feedback::updateStatus($feedbackId, $newStatus);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['feedback_id'], $_POST['new_status'])) {

    $controller = new ReviewFeedbackController();
    $feedbackId = (int) $_POST['feedback_id'];

    $result = $controller->updateStatus($feedbackId, $_POST['new_status']);

    if ($result === true) {
        $labels = ['in_review' => 'marked as In Review', 'resolved' => 'marked as Resolved'];
        $_SESSION['feedback_review_success'] = "Feedback " . ($labels[$_POST['new_status']] ?? 'updated') . " successfully.";
        SystemLog::record('feedback', 'info', 'Feedback Status Changed',
            'FB-' . str_pad($feedbackId, 4, '0', STR_PAD_LEFT) . ' ' . ($labels[$_POST['new_status']] ?? 'updated') . '.');
    } elseif ($result === "invalid_transition") {
        $_SESSION['feedback_review_error'] = "That status change is not allowed for this feedback.";
    } else {
        $_SESSION['feedback_review_error'] = "Unable to update this feedback. Please try again.";
    }

    header("Location: ../boundary/viewFeedbackPage.php?id=" . $feedbackId);
    exit();
}