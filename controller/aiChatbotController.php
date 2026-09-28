<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../database/database.php";
require_once "../entity/FAQ.php";
require_once "../entity/ChatHistory.php";
require_once "../entity/ChatSession.php";

class AIChatbotController
{
    private $db;
    private $faqEntity;
    private $chatHistory;
    private $chatSession;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
        $this->faqEntity    = new FAQ($this->db);
        $this->chatHistory  = new ChatHistory($this->db);
        $this->chatSession  = new ChatSession($this->db);
    }

    public function getChatHistory($userId)
    {
        return $this->chatHistory->getByUserId($userId);
    }

    public function getChatById($chatId, $userId)
    {
        return $this->chatHistory->getById($chatId, $userId);
    }

    public function clearChatHistory($userId)
    {
        return $this->chatHistory->clearByUserId($userId);
    }

    /**
     * Main entry — 3-layer hybrid + intent handling
     */
    public function askQuestion($userId, $universityId, $question)
    {
        $question = trim($question);
        if ($question === '') {
            return ['answer' => "Please type a question.", 'source' => 'faq'];
        }

        // ---- Intent detection (Option C) ----
        $intent = $this->getIntent($question);
        if ($intent !== null) {
            $response = $this->handleIntent($intent, $userId, $universityId, $question);
            if ($response !== null) {
                $this->saveChat($userId, $question, $response['answer'], $response['source']);
                return $response;
            }
        }

        // ---- Layer 1: Exact FAQ match ----
        $exact = $this->faqEntity->findExactMatch($universityId, $question);
        if ($exact && !empty($exact['answer'])) {
            $this->saveChat($userId, $question, $exact['answer'], 'faq');
            return ['answer' => $exact['answer'], 'source' => 'faq'];
        }

        // ---- Layer 2: TF-IDF similarity ----
        $similar = $this->faqEntity->findSimilarMatch($universityId, $question, 0.25);
        if ($similar && !empty($similar['answer'])) {
            $this->saveChat($userId, $question, $similar['answer'], 'tfidf');
            return ['answer' => $similar['answer'], 'source' => 'tfidf'];
        }

        // ---- Layer 3: Phi-3 on-device ----
        $phiAnswer = $this->askPhi3($question);
        if ($phiAnswer !== null) {
            $this->saveChat($userId, $question, $phiAnswer, 'phi3');
            return ['answer' => $phiAnswer, 'source' => 'phi3'];
        }

        return [
            'answer' => "I'm sorry, I don't have an answer for that question. Please contact support for further assistance.",
            'source' => 'fallback'
        ];
    }

    /* ============================================================
     * INTENT DETECTION
     * ============================================================ */

    private function getIntent($question)
    {
        $q = strtolower($question);

        // Book room
        if (preg_match('/\b(book|reserve|booking|reservation)\b.*\b(room|study room|classroom|facility)\b/', $q)) return 'book_room';

        // Timetable
        if (preg_match('/\b(timetable|schedule|my class(es)?|today\'?s class(es)?)\b/', $q)) return 'timetable';

        // Courses
        if (preg_match('/\b(enrolled|enrolment|my courses?|my modules?|registered courses?)\b/', $q)) return 'courses';

        // Exams
        if (preg_match('/\b(exams?|exam schedule|test schedule|upcoming (exam|test)s?)\b/', $q)) return 'exams';

        // Notifications
        if (preg_match('/\b(notifications?|alerts?|reminders?|unread|inbox)\b/', $q)) return 'notifications';

        // Events
        if (preg_match('/\b(events?|campus events?|upcoming events?|activities)\b/', $q)) return 'events';

        // Feedback
        if (preg_match('/\b(feedback|complain(t)?|suggest(ion)?|review|rate)\b/', $q)) return 'feedback';

        // Profile
        if (preg_match('/\b(profile|account|my info(rmation)?|settings)\b/', $q)) return 'profile';

        // Study groups
        if (preg_match('/\b(study\s*groups?|groups?|join group|create group)\b/', $q)) return 'study_groups';

        // Navigation
        if (preg_match('/\b(where|location|navigate|find|floor plan|directions|how (do i|to) get)\b/', $q)) return 'navigation';

        // Logout
        if (preg_match('/\b(logout|log out|sign out|exit)\b/', $q)) return 'logout';

        return null;
    }

    private function handleIntent($intent, $userId, $universityId, $question)
    {
        switch ($intent) {
            case 'book_room':     return $this->handleBookRoom($universityId);
            case 'timetable':     return $this->handleTimetable($userId);
            case 'courses':       return $this->handleCourses($userId);
            case 'exams':         return $this->handleExams($userId);
            case 'notifications': return $this->handleNotifications($userId);
            case 'events':        return $this->handleEvents($universityId);
            case 'feedback':      return $this->handleFeedback();
            case 'profile':       return $this->handleProfile();
            case 'study_groups':  return $this->handleStudyGroups($userId);
            case 'navigation':    return $this->handleNavigation();
            case 'logout':        return $this->handleLogout();
        }
        return null;
    }

    /* ============================================================
     * INTENT HANDLERS
     * ============================================================ */

    private function handleBookRoom($universityId)
    {
        try {
            $sql = "SELECT f.id, f.name, f.location, f.capacity
                    FROM Facilities f
                    INNER JOIN BookableFacilities bf ON f.id = bf.facilityId
                    WHERE f.universityId = ?
                      AND f.type = 'study room'
                      AND f.status = 'active'
                      AND bf.isBookable = TRUE
                      AND bf.status = 'available'
                    ORDER BY f.name ASC
                    LIMIT 5";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$universityId]);
            $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $rooms = [];
        }

        $answer = "You can book a study room from the Facility Booking page.";
        if (!empty($rooms)) {
            $answer .= " Here are some rooms available now:";
        } else {
            $answer .= " No rooms are currently available, but you can still browse the booking page.";
        }

        return [
            'answer' => $answer,
            'source' => 'faq',
            'rooms' => $rooms,
            'action' => [
                'label' => 'Book a Study Room',
                'url'   => 'bookFacilityPage.php'
            ]
        ];
    }

    private function handleTimetable($userId)
    {
        try {
            $sql = "SELECT title, dayOfWeek, startTime, endTime, location
                    FROM TimetableEntries
                    WHERE userId = ?
                    ORDER BY FIELD(dayOfWeek,'mon','tue','wed','thu','fri','sat','sun'), startTime
                    LIMIT 6";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $entries = [];
        }

        if (empty($entries)) {
            return [
                'answer' => "You don't have any classes in your personal timetable.",
                'source' => 'faq',
                'action' => [
                    'label' => 'View Full Timetable',
                    'url'   => 'academicsPage.php?tab=timetable'
                ]
            ];
        }

        $answer = "Here's a preview of your timetable:";
        return [
            'answer' => $answer,
            'source' => 'faq',
            'timetable' => $entries,
            'action' => [
                'label' => 'View Full Timetable',
                'url'   => 'academicsPage.php?tab=timetable'
            ]
        ];
    }

    private function handleCourses($userId)
    {
        return [
            'answer' => "You can view all your enrolled courses under Academics → Enrolled Courses.",
            'source' => 'faq',
            'action' => [
                'label' => 'View Enrolled Courses',
                'url'   => 'academicsPage.php?tab=courses'
            ]
        ];
    }

    private function handleExams($userId)
    {
        return [
            'answer' => "Your upcoming exams are listed under Academics → Exam Schedule.",
            'source' => 'faq',
            'action' => [
                'label' => 'View Exam Schedule',
                'url'   => 'academicsPage.php?tab=exams'
            ]
        ];
    }

    private function handleNotifications($userId)
    {
        try {
            $sql = "SELECT title, message, createdAt
                    FROM Notifications
                    WHERE userId = ? AND isRead = 0
                    ORDER BY createdAt DESC
                    LIMIT 5";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $notifications = [];
        }

        if (empty($notifications)) {
            return [
                'answer' => "You have no unread notifications.",
                'source' => 'faq',
                'action' => [
                    'label' => 'View All Notifications',
                    'url'   => 'NotificationPage.php'
                ]
            ];
        }

        return [
            'answer' => "You have " . count($notifications) . " recent notifications:",
            'source' => 'faq',
            'notifications' => $notifications,
            'action' => [
                'label' => 'View All Notifications',
                'url'   => 'NotificationPage.php'
            ]
        ];
    }

    private function handleEvents($universityId)
    {
        try {
            $sql = "SELECT id, title, location, startDatetime
                    FROM Events
                    WHERE universityId = ?
                      AND status = 'active'
                      AND startDatetime >= NOW()
                    ORDER BY startDatetime ASC
                    LIMIT 3";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$universityId]);
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $events = [];
        }

        $answer = empty($events)
            ? "There are no upcoming events right now."
            : "Here are the next upcoming events:";

        return [
            'answer' => $answer,
            'source' => 'faq',
            'events' => $events,
            'action' => [
                'label' => 'View Campus Events',
                'url'   => 'viewEventsPage.php'
            ]
        ];
    }

    private function handleFeedback()
    {
        return [
            'answer' => "You can submit your feedback from the Feedback page. It only takes a minute!",
            'source' => 'faq',
            'action' => [
                'label' => 'Submit Feedback',
                'url'   => 'submitFeedbackPage.php'
            ]
        ];
    }

    private function handleProfile()
    {
        return [
            'answer' => "You can view and update your profile from the Manage Profile page.",
            'source' => 'faq',
            'action' => [
                'label' => 'Manage Profile',
                'url'   => 'ManageProfilePage.php'
            ]
        ];
    }

    private function handleStudyGroups($userId)
    {
        return [
            'answer' => "You can create, join, or manage your study groups under Student Interaction.",
            'source' => 'faq',
            'action' => [
                'label' => 'Go to Study Groups',
                'url'   => 'academicsPage.php?tab=interaction'
            ]
        ];
    }

    private function handleNavigation()
    {
        return [
            'answer' => "You can view campus facilities and their locations on the Facility Booking page.",
            'source' => 'faq',
            'action' => [
                'label' => 'Open Campus Navigation',
                'url'   => 'viewFacilitiesPage.php'
            ]
        ];
    }

    private function handleLogout()
    {
        return [
            'answer' => "Click the button below to log out of UniBee.",
            'source' => 'faq',
            'action' => [
                'label' => 'Log Out',
                'url'   => '../controller/logoutController.php'
            ]
        ];
    }

    /* ============================================================
     * CHAT SAVE + PHI-3
     * ============================================================ */

    private function saveChat($userId, $question, $answer, $source)
    {
        $sessionId = $_SESSION['chat_session_id'] ?? null;
        if (!$sessionId) {
            $sessionId = $this->chatSession->startSession($userId);
            $_SESSION['chat_session_id'] = $sessionId;
        }
        $this->chatSession->incrementCount($sessionId);
        return $this->chatHistory->save($userId, $sessionId, $question, $answer, $source);
    }

    private function askPhi3($question)
    {
        $url = 'http://127.0.0.1:5000/ask';

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['question' => $question]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            error_log("Phi-3 error: HTTP $httpCode");
            return null;
        }

        $data = json_decode($response, true);
        return $data['answer'] ?? null;
    }
}

// ===== AJAX ENDPOINTS =====
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {

    header('Content-Type: application/json');
    $controller = new AIChatbotController();
    $userId = $_SESSION['user_id'] ?? null;
    $universityId = $_SESSION['university_id'] ?? null;
    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    switch ($action) {
        case 'ask':
            $question = $_POST['question'] ?? '';
            $result = $controller->askQuestion($userId, $universityId, $question);
            echo json_encode(['ok' => true, 'data' => $result]);
            exit;
        case 'history':
            echo json_encode(['ok' => true, 'data' => $controller->getChatHistory($userId)]);
            exit;
        case 'view_chat':
            $chatId = (int) ($_POST['chatId'] ?? 0);
            echo json_encode(['ok' => true, 'data' => $controller->getChatById($chatId, $userId)]);
            exit;
        case 'clear':
            $result = $controller->clearChatHistory($userId);
            $_SESSION['chat_session_id'] = null;
            echo json_encode(['ok' => $result]);
            exit;
        case 'new_session':
            $_SESSION['chat_session_id'] = null;
            echo json_encode(['ok' => true]);
            exit;
        default:
            echo json_encode(['ok' => false, 'error' => 'Unknown action']);
            exit;
    }
}
?>