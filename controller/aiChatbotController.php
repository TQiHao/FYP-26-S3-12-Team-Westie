<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../controller/authGuard.php";
requireRole(['student', 'lecturer', 'course_coordinator', 'university_admin', 'system_admin']);

require_once "../database/database.php";
require_once "../entity/faq.php";
require_once "../entity/chatHistory.php";
require_once "../entity/chatSession.php";

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
        $this->faqEntity = new FAQ($this->db);
        $this->chatHistory = new ChatHistory($this->db);
        $this->chatSession = new ChatSession($this->db);
    }

    public function getChatSessions($userId)
    {
        try {
            $sql = "SELECT sessionId, MIN(id) AS firstChatId, MAX(createdAt) AS lastActivity
                    FROM ChatHistory
                    WHERE userId = ? AND sessionId IS NOT NULL
                    GROUP BY sessionId ORDER BY lastActivity DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($sessions as &$s) {
                $q = $this->db->prepare("SELECT question FROM ChatHistory WHERE id = ?");
                $q->execute([$s['firstChatId']]);
                $first = $q->fetch(PDO::FETCH_ASSOC);
                $s['title'] = $first ? $this->decryptText($first['question']) : 'Untitled';

                $q2 = $this->db->prepare("SELECT id, question FROM ChatHistory WHERE sessionId = ? ORDER BY id ASC");
                $q2->execute([$s['sessionId']]);
                $questions = $q2->fetchAll(PDO::FETCH_ASSOC);

                foreach ($questions as &$question) {
                    $question['question'] = $this->decryptText($question['question']);
                }
                $s['questions'] = $questions;
            }
            return $sessions;
        } catch (Exception $e) {
            return [];
        }
    }

    public function getChatsBySession($sessionId, $userId)
    {
        try {
            $stmt = $this->db->prepare("SELECT id, question, answer, source, createdAt FROM ChatHistory WHERE sessionId = ? AND userId = ? ORDER BY id ASC");
            $stmt->execute([$sessionId, $userId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as &$row) {
                $row['question'] = $this->decryptText($row['question']);
                $row['answer'] = $this->decryptText($row['answer']);
            }
            return $rows;
        } catch (Exception $e) {
            return [];
        }
    }

    public function clearChatSession($sessionId, $userId)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM ChatHistory WHERE sessionId = ? AND userId = ?");
            $stmt->execute([$sessionId, $userId]);
            $stmt2 = $this->db->prepare("DELETE FROM AIChatbotSession WHERE id = ? AND userId = ?");
            $stmt2->execute([$sessionId, $userId]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function askQuestion($userId, $universityId, $question)
    {
        $question = trim($question);
        if ($question === '') {
            return ['answer' => "Please type a question.", 'source' => 'faq'];
        }

        $intent = $this->getIntent($question);
        if ($intent !== null) {
            $response = $this->handleIntent($intent, $userId, $universityId, $question);
            if ($response !== null) {
                $this->saveChat($userId, $question, $response['answer'], $response['source']);
                return $response;
            }
        }

        $exact = $this->faqEntity->findExactMatch($universityId, $question);
        if ($exact && !empty($exact['answer'])) {
            $this->saveChat($userId, $question, $exact['answer'], 'faq');
            return ['answer' => $exact['answer'], 'source' => 'faq'];
        }

        $similar = $this->faqEntity->findSimilarMatch($universityId, $question, 0.45);
        if ($similar && !empty($similar['answer'])) {
            $this->saveChat($userId, $question, $similar['answer'], 'tfidf');
            return ['answer' => $similar['answer'], 'source' => 'tfidf'];
        }

        if ($this->isSchoolRelated($question)) {
            $phiAnswer = $this->askPhi3($question);
            if ($phiAnswer !== null) {
                $this->saveChat($userId, $question, $phiAnswer, 'Local AI');
                return ['answer' => $phiAnswer, 'source' => 'Local AI'];
            }

            return [
                'answer' => "I couldn't reach the local AI service right now. Please try again later.",
                'source' => 'fallback'
            ];
        }

        return [
            'answer' => $this->getOffTopicMessage(),
            'source' => 'fallback'
        ];
    }

    private function getOffTopicMessage()
    {
        $role = strtolower($_SESSION['user_role'] ?? $_SESSION['role'] ?? '');
        switch ($role) {
            case 'course_coordinator':
            case 'course coordinator':
                return "I can only help with class management questions.";
            case 'university_admin':
            case 'university admin':
                return "I can only help with university administration questions.";
            case 'student':
            default:
                return "I can only help with school-related questions.";
        }
    }

    private function isSchoolRelated($question)
    {
        $q = strtolower($question);
        $keywords = [
            'class',
            'course',
            'module',
            'subject',
            'lecture',
            'tutorial',
            'lab',
            'semester',
            'academic',
            'enrol',
            'enroll',
            'study',
            'assignment',
            'homework',
            'credit',
            'programme',
            'program',
            'faculty',
            'major',
            'university',
            'uni',
            'school',
            'college',
            'exam',
            'test',
            'quiz',
            'assessment',
            'grade',
            'mark',
            'result',
            'gpa',
            'score',
            'timetable',
            'schedule',
            'attendance',
            'lesson',
            'lecturer',
            'professor',
            'teacher',
            'tutor',
            'coordinator',
            'staff',
            'student',
            'campus',
            'room',
            'hall',
            'building',
            'floor',
            'facility',
            'classroom',
            'library',
            'canteen',
            'cafeteria',
            'parking',
            'wifi',
            'floor plan',
            'book',
            'reserve',
            'booking',
            'gym',
            'slot',
            'event',
            'seminar',
            'workshop',
            'activity',
            'orientation',
            'ceremony',
            'talk',
            'unibee',
            'account',
            'login',
            'log in',
            'password',
            'profile',
            'feedback',
            'notification',
            'chatbot',
            'help',
            'support',
            'guide',
            'feature',
            'explain',
            'tutorial',
            'fee',
            'tuition',
            'scholarship',
            'payment',
            'receipt',
            'hi',
            'hello',
            'hey',
            'greetings',
            'cyber',
            'cybersecurity',
            'security',
            'computer',
            'network',
            'database',
            'software',
            'hardware',
            'tech',
            'technology',
            'algorithm',
            'programming',
            'code',
            'coding',
            'it',
            'information'
        ];
        foreach ($keywords as $kw) {
            if (strpos($q, $kw) !== false)
                return true;
        }
        return false;
    }

    private function getIntent($question)
    {
        $q = strtolower($question);

        if (preg_match('/^(hi|hello|hey|greetings|good\s*(morning|afternoon|evening|day)|howdy|sup)\b/i', $q))
            return 'greeting';
        if (preg_match('/\b(logout|log out|sign out|exit)\b/', $q))
            return 'logout';
        if (preg_match('/\b(feedback|rate|rating|comment|stars?)\b/', $q))
            return 'feedback';
        if (preg_match('/\b(renew|license)\b/', $q))
            return 'renew_license';
        if (preg_match('/\b(manage|upload)\b.*\b(university info|document|information)\b/', $q))
            return 'manage_info';
        if (preg_match('/\bmanage\b.*\bfacilit(y|ies)\b/', $q))
            return 'manage_facilities';
        if (preg_match('/\bmanage\b.*\bevents?\b/', $q))
            return 'manage_events';
        if (preg_match('/\bmanage\b.*\bfaq\b|\bfaq\b/', $q))
            return 'manage_faq';
        if (preg_match('/\b(how many|active|count)\b.*\bclass(es)?\b/', $q))
            return 'active_classes_count';
        if (preg_match('/\b(create|add|make|manage)\b.*\bclass(es)?\b/', $q))
            return 'manage_classes';
        if (preg_match('/\b(book|reserve)\b.*\b(study room|room|facilit(y|ies)|gym)\b/', $q))
            return 'book_facility';
        if (preg_match('/\b(my|upcoming)\b.*\bevents?\b/', $q))
            return 'events';
        if (preg_match('/\b(my|check)\b.*\bnotif\w*\b|\balerts?\b/', $q))
            return 'notifications';
        if (preg_match('/\b(my|manage)\b.*\b(profile|account)\b/', $q))
            return 'profile';
        if (preg_match('/\b(timetable|schedule)\b|\bmy class(es)?\b|\btoday\'?s class(es)?\b/', $q))
            return 'timetable';
        if (preg_match('/\b(enrolled|enrolment)\b|\bmy courses?\b|\bmy modules?\b/', $q))
            return 'courses';
        if (preg_match('/\b(exams?|exam schedule|test schedule)\b|\bupcoming (exam|test)s?\b/', $q))
            return 'exams';
        if (preg_match('/\bstudy\s*groups?\b|\bjoin group\b/', $q))
            return 'study_groups';
        if (preg_match('/\b(where|location|navigate|find|floor plan|directions)\b/', $q))
            return 'navigation';

        return null;
    }

    private function handleIntent($intent, $userId, $universityId, $question)
    {
        $role = strtolower($_SESSION['user_role'] ?? $_SESSION['role'] ?? '');

        if (in_array($intent, ['book_facility', 'events', 'notifications', 'profile', 'courses', 'exams', 'study_groups', 'timetable']) && $role !== 'student') {
            return ['answer' => "This feature is designed for students. Please use your dashboard for role-specific features.", 'source' => 'faq'];
        }

        if (in_array($intent, ['renew_license', 'manage_info', 'manage_events', 'manage_facilities', 'manage_faq']) && $role !== 'university_admin' && $role !== 'university admin') {
            return ['answer' => "This feature is only available for University Admins.", 'source' => 'faq'];
        }

        if (in_array($intent, ['manage_classes', 'active_classes_count']) && $role !== 'course_coordinator' && $role !== 'course coordinator') {
            return ['answer' => "This feature is only available for Course Coordinators.", 'source' => 'faq'];
        }

        switch ($intent) {
            case 'greeting':
                $greeting = "Hello " . ($_SESSION['user_name'] ?? 'there') . "! I'm your UniBee AI assistant. ";
                if ($role === 'student') {
                    $greeting .= "How can I help you with your studies today?";
                } elseif ($role === 'lecturer') {
                    $greeting .= "How can I help you with your teaching tasks today?";
                } elseif ($role === 'course_coordinator' || $role === 'course coordinator') {
                    $greeting .= "How can I help you with your class management today?";
                } elseif ($role === 'university_admin' || $role === 'university admin') {
                    $greeting .= "How can I help you with university administration today?";
                } else {
                    $greeting .= "How can I help you today?";
                }
                return ['answer' => $greeting, 'source' => 'faq'];

            case 'book_facility':
                return ['answer' => "You can book a study room or gym slot from the Facility Booking page.", 'source' => 'faq', 'action' => ['label' => 'Go to Facility Booking', 'url' => 'viewFacilitiesPage.php']];
            case 'events':
                return ['answer' => "You can view your upcoming events here.", 'source' => 'faq', 'action' => ['label' => 'View Campus Events', 'url' => 'viewEventsPage.php']];
            case 'notifications':
                return ['answer' => "You can check your notifications here.", 'source' => 'faq', 'action' => ['label' => 'View Notifications', 'url' => 'notificationPage.php']];
            case 'profile':
                return ['answer' => "You can manage your profile here.", 'source' => 'faq', 'action' => ['label' => 'Manage Profile', 'url' => 'manageProfilePage.php']];

            case 'feedback':
                $rating = 0;
                $msg = '';

                if (preg_match('/(\d+)\s*(?:star|stars|rating)/i', $question, $m)) {
                    $r = (int) $m[1];
                    if ($r >= 1 && $r <= 5)
                        $rating = $r;
                }

                if (preg_match('/(?:says\s+that|saying|message\s*(?:is|:)|comment\s*(?:is|:)|feedback\s*(?:is|:)|:\s*)(.+)/i', $question, $m)) {
                    $msg = trim($m[1]);
                } else {
                    $clean = preg_replace('/(\d+)\s*(?:star|stars|rating)/i', '', $question);
                    $clean = preg_replace('/i\s+want\s+to\s+submit\s+feedback/i', '', $clean);
                    $clean = preg_replace('/(?:says\s+that|saying|message|comment|feedback)/i', '', $clean);
                    $clean = preg_replace('/[,\s]+/', ' ', $clean);
                    $msg = trim($clean, " ,.:;-");
                }

                $url = 'submitFeedbackPage.php';
                $params = [];
                if ($rating > 0)
                    $params[] = "rating=$rating";
                if (!empty($msg))
                    $params[] = "message=" . urlencode($msg);
                if (!empty($params))
                    $url .= '?' . implode('&', $params);
                return ['answer' => "I've opened the feedback form for you. Please review and submit it.", 'source' => 'faq', 'action' => ['label' => 'Go to Feedback Form', 'url' => $url]];

            case 'renew_license':
                return ['answer' => "You can renew the university license here.", 'source' => 'faq', 'action' => ['label' => 'Renew License', 'url' => 'renewLicensePage.php']];
            case 'manage_info':
                return ['answer' => "You can manage university information and upload documents here.", 'source' => 'faq', 'action' => ['label' => 'Manage University Info', 'url' => 'manageUniversityInformationPage.php']];
            case 'manage_events':
                return ['answer' => "You can manage university events here.", 'source' => 'faq', 'action' => ['label' => 'Manage Events', 'url' => 'manageUniversityEventPage.php']];
            case 'manage_facilities':
                return ['answer' => "You can manage bookable facilities here.", 'source' => 'faq', 'action' => ['label' => 'Manage Bookable Facilities', 'url' => 'manageBookableFacilitiesPage.php']];
            case 'manage_faq':
                return ['answer' => "You can manage the FAQ database here.", 'source' => 'faq', 'action' => ['label' => 'Manage FAQ', 'url' => 'manageFAQDatabasePage.php']];
            case 'manage_classes':
                $prefill = [];

                if (preg_match('/\b([A-Za-z]{2,4}\d{3}(?:-[A-Za-z]\d)?)\b/', $question, $m)) {
                    $prefill['classCode'] = strtoupper($m[1]);
                    $prefill['className'] = strtoupper($m[1]);
                }

                if (preg_match('/\b(mon|tue|wed|thu|fri|sat|sun)(day)?\b/i', $question, $m)) {
                    $prefill['dayOfWeek'] = strtolower(substr($m[1], 0, 3));
                }

                if (preg_match('/(\d{1,2})(?::(\d{2}))?\s*(am|pm)?\s*(?:to|-|until)\s*(\d{1,2})(?::(\d{2}))?\s*(am|pm)?/i', $question, $m)) {
                    $startHour = (int) $m[1];
                    $startMin = $m[2] ? $m[2] : '00';
                    $startAmPm = strtolower($m[3]);

                    $endHour = (int) $m[4];
                    $endMin = $m[5] ? $m[5] : '00';
                    $endAmPm = strtolower($m[6]);

                    if ($startAmPm === 'pm' && $startHour < 12)
                        $startHour += 12;
                    if ($startAmPm === 'am' && $startHour == 12)
                        $startHour = 0;
                    if ($endAmPm === 'pm' && $endHour < 12)
                        $endHour += 12;
                    if ($endAmPm === 'am' && $endHour == 12)
                        $endHour = 0;

                    $prefill['startTime'] = sprintf('%02d:%s', $startHour, $startMin);
                    $prefill['endTime'] = sprintf('%02d:%s', $endHour, $endMin);
                }

                if (preg_match('/\b(\d+)\s*student/i', $question, $m) || preg_match('/capacity\s*(?:of|is)?\s*(\d+)/i', $question, $m)) {
                    $prefill['capacity'] = (int) $m[1];
                }

                if (preg_match('/\bsemester\s*(\d)\b/i', $question, $m)) {
                    $prefill['semester'] = $m[1];
                }

                $url = 'manageClassPage.php';
                $msg = "You can create and manage classes from the Manage Classes page.";
                $actionLabel = 'Go to Manage Class'; // Default Button Label

                if (!empty($prefill)) {
                    $encodedData = urlencode(base64_encode(json_encode($prefill)));
                    $url .= '?prefill=' . $encodedData;
                    $msg = "I have extracted the details and pre-filled the class form for you. Click the button below to review, select a room, and save the class.";
                    $actionLabel = 'Open Pre-filled Class Form'; // Only change to this if data was found
                }

                return ['answer' => $msg, 'source' => 'faq', 'action' => ['label' => $actionLabel, 'url' => $url]];

            case 'active_classes_count':
                try {
                    $stmt = $this->db->prepare("SELECT COUNT(*) FROM Classes WHERE status = 'active'");
                    $stmt->execute();
                    $count = $stmt->fetchColumn();
                    return ['answer' => "There are currently {$count} active classes in the system.", 'source' => 'db'];
                } catch (Exception $e) {
                    return ['answer' => "Unable to fetch active class count right now.", 'source' => 'fallback'];
                }
            case 'timetable':
                return $this->handleTimetable($userId);
            case 'courses':
                return ['answer' => "You can view all your enrolled courses under Academics → Enrolled Courses.", 'source' => 'faq', 'action' => ['label' => 'View Enrolled Courses', 'url' => 'academicsPage.php?tab=courses']];
            case 'exams':
                return ['answer' => "Your upcoming exams are listed under Academics → Exam Schedule.", 'source' => 'faq', 'action' => ['label' => 'View Exam Schedule', 'url' => 'academicsPage.php?tab=exams']];
            case 'study_groups':
                return ['answer' => "You can create, join, or manage your study groups under Student Interaction.", 'source' => 'faq', 'action' => ['label' => 'Go to Study Groups', 'url' => 'academicsPage.php?tab=interaction']];
            case 'navigation':
                return ['answer' => "You can view campus facilities and their locations on the Facility Location Tracking page.", 'source' => 'faq', 'action' => ['label' => 'Open Campus Navigation', 'url' => 'facilityLocationTrackingPage.php']];
            case 'logout':
                return ['answer' => "Click the button below to log out of UniBee.", 'source' => 'faq', 'action' => ['label' => 'Log Out', 'url' => '../controller/logOutController.php']];
        }
        return null;
    }

    private function handleTimetable($userId)
    {
        try {
            $sql = "SELECT CONCAT(m.code, ' - ', m.name) AS title, c.dayOfWeek, c.startTime, c.endTime, c.room AS location
                    FROM StudentEnrolments se
                    INNER JOIN Classes c ON c.id = se.classId
                    INNER JOIN Modules m ON m.id = c.moduleId
                    WHERE se.studentId = ? AND se.status = 'enrolled' AND c.status = 'active'
                    ORDER BY FIELD(c.dayOfWeek,'mon','tue','wed','thu','fri','sat','sun'), c.startTime ASC
                    LIMIT 6";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $entries = [];
        }

        if (empty($entries)) {
            return ['answer' => "You don't have any classes in your personal timetable.", 'source' => 'faq', 'action' => ['label' => 'View Full Timetable', 'url' => 'academicsPage.php?tab=timetable']];
        }

        return ['answer' => "Here's a preview of your timetable:", 'source' => 'faq', 'timetable' => $entries, 'action' => ['label' => 'View Full Timetable', 'url' => 'academicsPage.php?tab=timetable']];
    }

    private function saveChat($userId, $question, $answer, $source)
    {
        $sessionId = $_SESSION['chat_session_id'] ?? null;
        if (!$sessionId) {
            $sessionId = $this->chatSession->startSession($userId);
            $_SESSION['chat_session_id'] = $sessionId;
        }
        $this->chatSession->incrementCount($sessionId);

        $encryptedQuestion = $this->encryptText($question);
        $encryptedAnswer = $this->encryptText($answer);

        return $this->chatHistory->save($userId, $sessionId, $encryptedQuestion, $encryptedAnswer, $source);
    }

    private function encryptText($text)
    {
        if (empty($text))
            return $text;
        $key = "UniBeeSecretKey2026";
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted = openssl_encrypt($text, 'aes-256-cbc', $key, 0, $iv);
        return base64_encode($encrypted . '::' . $iv);
    }

    private function decryptText($text)
    {
        if (empty($text))
            return $text;
        $key = "UniBeeSecretKey2026";
        $parts = explode('::', base64_decode($text));
        if (count($parts) < 2)
            return $text;
        return openssl_decrypt($parts[0], 'aes-256-cbc', $key, 0, $parts[1]);
    }

    private function askPhi3($question)
    {
        $baseUrl = 'https://bbh0327l-unibee-ai.hf.space';
        $apiName = '/respond';
        $eventId = null;

        $postUrl = $baseUrl . '/gradio_api/call' . $apiName;
        $postData = json_encode(["data" => [$question]]);

        $ch = curl_init($postUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            return "DEBUG (POST): HTTP Code: " . $httpCode . " | cURL Error: " . $curlError . " | Response: " . $response;
        }

        $result = json_decode($response, true);
        $eventId = $result['event_id'] ?? null;

        if (!$eventId) {
            return "DEBUG (POST): Could not find 'event_id' in response. Response was: " . $response;
        }

        $getUrl = $baseUrl . '/gradio_api/call' . $apiName . '/' . $eventId;

        $ch = curl_init($getUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Accept: text/event-stream"
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            return "DEBUG (GET): HTTP Code: " . $httpCode . " | cURL Error: " . $curlError . " | Response: " . $response;
        }

        $lines = explode("\n", $response);
        foreach ($lines as $line) {
            if (strpos($line, 'data: ') === 0) {
                $jsonStr = substr($line, 6);
                $dataArr = json_decode($jsonStr, true);

                if (is_array($dataArr) && isset($dataArr[0])) {
                    return trim($dataArr[0]);
                }
            }
        }

        return "DEBUG (GET): Could not parse the response. Raw response: " . $response;
    }
}

if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');
    $controller = new AIChatbotController();
    $userId = $_SESSION['user_id'] ?? null;
    $universityId = $_SESSION['university_id'] ?? null;
    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    switch ($action) {
        case 'ask':
            $_SESSION['chat_last_active'] = time();
            $question = $_POST['question'] ?? '';
            $result = $controller->askQuestion($userId, $universityId, $question);
            echo json_encode(['ok' => true, 'data' => $result]);
            exit;
        case 'sessions':
            echo json_encode(['ok' => true, 'data' => $controller->getChatSessions($userId)]);
            exit;
        case 'view_session':
            $sid = (int) ($_POST['sessionId'] ?? 0);
            $_SESSION['chat_session_id'] = $sid;
            echo json_encode(['ok' => true, 'data' => $controller->getChatsBySession($sid, $userId)]);
            exit;
        case 'clear_session':
            $sid = (int) ($_POST['sessionId'] ?? 0);
            $result = $controller->clearChatSession($sid, $userId);
            if (($_SESSION['chat_session_id'] ?? null) == $sid)
                $_SESSION['chat_session_id'] = null;
            echo json_encode(['ok' => $result]);
            exit;
        case 'new_session':
            $_SESSION['chat_session_id'] = null;
            echo json_encode(['ok' => true]);
            exit;
    }
}
?>