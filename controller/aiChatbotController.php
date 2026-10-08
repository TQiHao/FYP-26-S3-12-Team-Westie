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
        $this->faqEntity = new FAQ($this->db);
        $this->chatHistory = new ChatHistory($this->db);
        $this->chatSession = new ChatSession($this->db);
    }

    public function getChatHistory($userId)
    {
        return $this->chatHistory->getByUserId($userId);
    }

    public function getChatById($chatId, $userId)
    {
        return $this->chatHistory->getById($chatId, $userId);
    }

    public function getChatSessions($userId)
    {
        try {
            $sql = "SELECT 
                        sessionId,
                        MIN(id) AS firstChatId,
                        MAX(createdAt) AS lastActivity
                    FROM ChatHistory
                    WHERE userId = ? AND sessionId IS NOT NULL
                    GROUP BY sessionId
                    ORDER BY lastActivity DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($sessions as &$s) {
                $q = $this->db->prepare("SELECT question FROM ChatHistory WHERE id = ?");
                $q->execute([$s['firstChatId']]);
                $first = $q->fetch(PDO::FETCH_ASSOC);
                $s['title'] = $first ? $first['question'] : 'Untitled';

                $q2 = $this->db->prepare("SELECT id, question FROM ChatHistory WHERE sessionId = ? ORDER BY id ASC");
                $q2->execute([$s['sessionId']]);
                $s['questions'] = $q2->fetchAll(PDO::FETCH_ASSOC);
            }
            unset($s);

            return $sessions;
        } catch (Exception $e) {
            error_log("getChatSessions error: " . $e->getMessage());
            return [];
        }
    }

    public function getChatsBySession($sessionId, $userId)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT id, question, answer, source, createdAt 
                 FROM ChatHistory 
                 WHERE sessionId = ? AND userId = ? 
                 ORDER BY id ASC"
            );
            $stmt->execute([$sessionId, $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    public function clearChatHistory($userId)
    {
        return $this->chatHistory->clearByUserId($userId);
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
            error_log("clearChatSession error: " . $e->getMessage());
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

        $similar = $this->faqEntity->findSimilarMatch($universityId, $question, 0.25);
        if ($similar && !empty($similar['answer'])) {
            $this->saveChat($userId, $question, $similar['answer'], 'tfidf');
            return ['answer' => $similar['answer'], 'source' => 'tfidf'];
        }

        if ($this->isSchoolRelated($question)) {
            $phiAnswer = $this->askPhi3($question);
            if ($phiAnswer !== null) {
                $this->saveChat($userId, $question, $phiAnswer, 'phi3');
                return ['answer' => $phiAnswer, 'source' => 'phi3'];
            }

            return [
                'answer' => "I couldn't reach the AI service right now. Please try again later.",
                'source' => 'fallback'
            ];
        }

        return [
            'answer' => $this->getOffTopicMessage(),
            'source' => 'fallback'
        ];
    }

    /**
     * Role-aware off-topic message.
     */
    private function getOffTopicMessage()
    {
        $role = strtolower($_SESSION['user_role'] ?? $_SESSION['role'] ?? '');

        switch ($role) {
            case 'course_coordinator':
            case 'course coordinator':
                return "I can only help with class management questions. Try asking about creating or managing classes, exam schedules, classroom suggestions, or student enrolment.";

            case 'university_admin':
            case 'university admin':
                return "I can only help with university administration questions. Try asking about university information, facilities, campus events, or user management.";

            case 'student':
            default:
                return "I can only help with school-related questions. Try asking about your courses, exams, timetable, facility booking, campus events, or notifications.";
        }
    }

    private function isSchoolRelated($question)
    {
        $q = strtolower($question);

        $keywords = [
            // Academic
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
            // Exam
            'exam',
            'test',
            'quiz',
            'assessment',
            'grade',
            'mark',
            'result',
            'gpa',
            'score',
            // Time
            'timetable',
            'schedule',
            'attendance',
            'lesson',
            // People
            'lecturer',
            'professor',
            'teacher',
            'tutor',
            'coordinator',
            'staff',
            'student',
            // Location / campus
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
            // Booking
            'book',
            'reserve',
            'booking',
            'gym',
            'slot',
            // Events
            'event',
            'seminar',
            'workshop',
            'activity',
            'orientation',
            'ceremony',
            'talk',
            // System / UniBee
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
            // Money / admin
            'fee',
            'tuition',
            'scholarship',
            'payment',
            'receipt',
        ];

        foreach ($keywords as $kw) {
            if (strpos($q, $kw) !== false) {
                return true;
            }
        }
        return false;
    }

    private function getIntent($question)
    {
        $q = strtolower($question);

        if (preg_match('/\b(create|add|new|manage|make)\b.*\bclass(es)?\b/', $q))
            return 'create_class';

        if (preg_match('/\b(book|reserve|booking|reservation)\b.*\b(room|study room|classroom|facility)\b/', $q))
            return 'book_room';

        if (preg_match('/\b(timetable|schedule|my class(es)?|today\'?s class(es)?)\b/', $q))
            return 'timetable';

        if (preg_match('/\b(enrolled|enrolment|my courses?|my modules?|registered courses?)\b/', $q))
            return 'courses';

        if (preg_match('/\b(exams?|exam schedule|test schedule|upcoming (exam|test)s?)\b/', $q))
            return 'exams';

        if (preg_match('/\b(notifications?|alerts?|reminders?|unread|inbox)\b/', $q))
            return 'notifications';

        if (preg_match('/\b(events?|campus events?|upcoming events?|activities)\b/', $q))
            return 'events';

        if (preg_match('/\b(feedback|complain(t)?|suggest(ion)?|review|rate)\b/', $q))
            return 'feedback';

        if (preg_match('/\b(profile|account|my info(rmation)?|settings)\b/', $q))
            return 'profile';

        if (preg_match('/\b(study\s*groups?|groups?|join group|create group)\b/', $q))
            return 'study_groups';

        if (preg_match('/\b(where|location|navigate|find|floor plan|directions|how (do i|to) get)\b/', $q))
            return 'navigation';

        if (preg_match('/\b(logout|log out|sign out|exit)\b/', $q))
            return 'logout';

        // Guide / features — broad, so checked LAST
        if (preg_match('/\b(guides?|tutorials?|manuals?|features?|capabilities|getting started|what can (you|i) do|show (me )?(the )?features?|list (the )?features?|how (do i|can i|to) use|how to use|use the (system|page|chatbot|website|app)|use this (system|page|chatbot|website|app))\b/i', $q))
            return 'features_guide';
    }

    private function handleIntent($intent, $userId, $universityId, $question)
    {
        switch ($intent) {
            case 'create_class':
                return $this->handleCreateClass($userId, $question);
            case 'book_room':
                return $this->handleBookRoom($universityId);
            case 'timetable':
                return $this->handleTimetable($userId);
            case 'courses':
                return $this->handleCourses($userId);
            case 'exams':
                return $this->handleExams($userId);
            case 'notifications':
                return $this->handleNotifications($userId);
            case 'events':
                return $this->handleEvents($universityId);
            case 'feedback':
                return $this->handleFeedback();
            case 'profile':
                return $this->handleProfile();
            case 'study_groups':
                return $this->handleStudyGroups($userId);
            case 'navigation':
                return $this->handleNavigation();
            case 'logout':
                return $this->handleLogout();
            case 'features_guide':
                return $this->handleFeaturesGuide();
        }
        return null;
    }

    /* ============================================================
     * INTENT HANDLERS
     * ============================================================ */

    private function handleFeaturesGuide()
    {
        $role = strtolower($_SESSION['user_role'] ?? $_SESSION['role'] ?? '');

        if ($role === 'course_coordinator' || $role === 'course coordinator') {
            return [
                'answer' =>
                    "Here's what I can help you with as a Course Coordinator:\n\n" .
                    "• Manage Classes — create, edit, suspend, or search classes\n" .
                    "• Suggest a Classroom — find free rooms for a given day and time\n" .
                    "• Enrol Students — add one by one or import from a CSV file\n" .
                    "• Exam Scheduling — set exam date, time, and venue\n" .
                    "• Assign Lecturers — assign or change a lecturer for a class\n\n" .
                    "Try asking things like:\n" .
                    "• \"Create a class CS201-L5 on Monday 9am to 12pm for 40 students\"\n" .
                    "• \"Show my classes\"\n" .
                    "• \"How do I enrol a student?\"",
                'source' => 'faq'
            ];
        }

        if ($role === 'lecturer') {
            return [
                'answer' =>
                    "Here's what I can help you with as a Lecturer:\n\n" .
                    "• Personal Timetable — view your weekly teaching schedule\n" .
                    "• Assigned Modules — see the modules you teach\n" .
                    "• Campus Events — browse and register for events\n" .
                    "• Notifications — check your latest updates\n\n" .
                    "Try asking things like:\n" .
                    "• \"Show my timetable\"\n" .
                    "• \"What modules am I teaching?\"\n" .
                    "• \"Show upcoming events\"",
                'source' => 'faq'
            ];
        }

        if ($role === 'university_admin' || $role === 'university admin') {
            return [
                'answer' =>
                    "Here's what I can help you with as a University Admin:\n\n" .
                    "• Manage University Info — faculty, programmes, modules, facilities, users\n" .
                    "• Campus Events — create and manage events\n" .
                    "• FAQ Database — manage frequently asked questions\n" .
                    "• Feedback — view feedback from users\n" .
                    "• License — renew or check your university license\n\n" .
                    "Try asking things like:\n" .
                    "• \"How do I upload the student list?\"\n" .
                    "• \"Show upcoming events\"\n" .
                    "• \"How do I renew my license?\"",
                'source' => 'faq'
            ];
        }

        if ($role === 'system_admin' || $role === 'system admin') {
            return [
                'answer' =>
                    "Here's what I can help you with as a System Admin:\n\n" .
                    "• Manage Universities — approve registrations, suspend, or reactivate\n" .
                    "• Landing Page — update public content\n" .
                    "• AI Model — deploy or roll back model versions\n" .
                    "• System Operations — view logs, backup, or restore\n\n" .
                    "Try asking things like:\n" .
                    "• \"Show pending university registrations\"\n" .
                    "• \"View system logs\"\n" .
                    "• \"How do I backup the database?\"",
                'source' => 'faq'
            ];
        }

        return [
            'answer' =>
                "Here's what I can help you with as a Student:\n\n" .
                "• Personal Timetable — view your weekly schedule\n" .
                "• Enrolled Courses — see your registered modules\n" .
                "• Exam Schedule — check upcoming exams\n" .
                "• Facility Booking — book study rooms or gym slots\n" .
                "• Campus Events — browse and register for events\n" .
                "• Study Groups — create or join study groups\n" .
                "• Notifications — check your latest updates\n\n" .
                "Try asking things like:\n" .
                "• \"Show my timetable\"\n" .
                "• \"When is my next exam?\"\n" .
                "• \"Book a study room\"",
            'source' => 'faq'
        ];
    }

    private function handleCreateClass($userId, $question)
    {
        $role = strtolower($_SESSION['user_role'] ?? $_SESSION['role'] ?? '');

        if ($role !== 'course_coordinator' && $role !== 'course coordinator') {
            return [
                'answer' => "Only course coordinators can create or manage classes. If you need help with your enrolled courses, try asking \"show my courses\".",
                'source' => 'faq',
                'action' => [
                    'label' => 'View My Courses',
                    'url' => 'academicsPage.php?tab=courses'
                ]
            ];
        }

        $prefillData = $this->parseClassPrefill($question);

        $url = 'manageClassPage.php';
        if (!empty($prefillData)) {
            $encoded = base64_encode(json_encode($prefillData));
            $url .= '?prefill=' . urlencode($encoded);
        }

        $answer = "I've opened the Manage Classes page for you. ";
        if (!empty($prefillData)) {
            $answer .= "I pre-filled " . count($prefillData) . " field(s) I understood — please review and adjust before saving.";
        } else {
            $answer .= "Click the button below to go there.";
        }

        return [
            'answer' => $answer,
            'source' => 'faq',
            'action' => [
                'label' => 'Go to Manage Classes',
                'url' => $url
            ]
        ];
    }

    private function parseClassPrefill($question)
    {
        $q = $question;
        $data = [];

        if (preg_match('/module\s*(?:name)?\s*(?:is|:)\s*([^,]+?)(?=\s*,|\s+class|\s+it\'?s|\s+suggest|\s+start|\s+end|\s+capacity|\s+academic|\s+assigned|\s+venue|$)/i', $q, $m)) {
            $data['moduleName'] = trim($m[1]);
        }

        if (preg_match('/class\s*code\s*(?:is|:)\s*([^,]+?)(?=\s*,|\s+class|\s+it\'?s|\s+suggest|\s+start|\s+end|\s+capacity|\s+academic|\s+assigned|\s+venue|$)/i', $q, $m)) {
            $data['classCode'] = trim($m[1]);
        }

        if (preg_match('/class\s*name\s*(?:is|:)\s*([^,]+?)(?=\s*,|\s+class|\s+it\'?s|\s+suggest|\s+start|\s+end|\s+capacity|\s+academic|\s+assigned|\s+venue|$)/i', $q, $m)) {
            $data['className'] = trim($m[1]);
        }

        if (preg_match('/\b(monday|tuesday|wednesday|thursday|friday|saturday|sunday|mon|tue|wed|thu|fri|sat|sun)\b/i', $q, $m)) {
            $dayMap = [
                'monday' => 'mon',
                'mon' => 'mon',
                'tuesday' => 'tue',
                'tue' => 'tue',
                'wednesday' => 'wed',
                'wed' => 'wed',
                'thursday' => 'thu',
                'thu' => 'thu',
                'friday' => 'fri',
                'fri' => 'fri',
                'saturday' => 'sat',
                'sat' => 'sat',
                'sunday' => 'sun',
                'sun' => 'sun'
            ];
            $key = strtolower($m[1]);
            if (isset($dayMap[$key])) {
                $data['dayOfWeek'] = $dayMap[$key];
            }
        }

        if (preg_match('/start\s*time\s*(?:is|:)?\s*(\d{1,2}(?::\d{2})?\s*(?:am|pm)?)/i', $q, $m)) {
            $data['startTime'] = $this->normalizeTime($m[1]);
        }

        if (preg_match('/end\s*time\s*(?:is|:)?\s*(\d{1,2}(?::\d{2})?\s*(?:am|pm)?)/i', $q, $m)) {
            $data['endTime'] = $this->normalizeTime($m[1]);
        }

        if (preg_match('/capacity\s*(?:is|:)?\s*(\d+)/i', $q, $m)) {
            $data['capacity'] = (int) $m[1];
        }

        if (preg_match('/academic\s*(?:year|yr)\s*(?:is|:)?\s*([\d]{4}\s*\/\s*[\d]{4})/i', $q, $m)) {
            $data['academicYear'] = str_replace(' ', '', $m[1]);
        }

        if (preg_match('/assigned\s*lecturer\s*(?:is|:)?\s*([^,]+?)(?=\s*,|$)/i', $q, $m)) {
            $data['lecturerName'] = trim($m[1]);
        }

        if (preg_match('/venue\s*(?:is|:)\s*([^,]+?)(?=\s*,|$)/i', $q, $m)) {
            $data['room'] = trim($m[1]);
        }

        return $data;
    }

    private function normalizeTime($t)
    {
        $t = strtolower(trim($t));
        $ampm = null;
        if (strpos($t, 'am') !== false) {
            $ampm = 'am';
            $t = str_replace('am', '', $t);
        }
        if (strpos($t, 'pm') !== false) {
            $ampm = 'pm';
            $t = str_replace('pm', '', $t);
        }
        $t = trim($t);

        if (preg_match('/^(\d{1,2})(?::(\d{2}))?$/', $t, $m)) {
            $h = (int) $m[1];
            $min = $m[2] ?? '00';
            if ($ampm === 'pm' && $h < 12)
                $h += 12;
            if ($ampm === 'am' && $h === 12)
                $h = 0;
            if ($ampm === null && $h >= 1 && $h <= 7)
                $h += 12;
            return str_pad($h, 2, '0', STR_PAD_LEFT) . ':' . $min;
        }
        return $t;
    }

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
                'url' => 'bookFacilityPage.php'
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
                    'url' => 'academicsPage.php?tab=timetable'
                ]
            ];
        }

        return [
            'answer' => "Here's a preview of your timetable:",
            'source' => 'faq',
            'timetable' => $entries,
            'action' => [
                'label' => 'View Full Timetable',
                'url' => 'academicsPage.php?tab=timetable'
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
                'url' => 'academicsPage.php?tab=courses'
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
                'url' => 'academicsPage.php?tab=exams'
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
                    'url' => 'NotificationPage.php'
                ]
            ];
        }

        return [
            'answer' => "You have " . count($notifications) . " recent notifications:",
            'source' => 'faq',
            'notifications' => $notifications,
            'action' => [
                'label' => 'View All Notifications',
                'url' => 'NotificationPage.php'
            ]
        ];
    }

    private function handleEvents($universityId)
    {
        try {
            $sql = "SELECT e.id, e.title, e.startDatetime,
                           f.location AS location
                    FROM Events e
                    INNER JOIN BookableFacilities bf ON e.facilityId = bf.id
                    INNER JOIN Facilities f ON bf.facilityId = f.id
                    WHERE e.universityId = ?
                      AND e.status = 'active'
                      AND e.startDatetime >= NOW()
                    ORDER BY e.startDatetime ASC
                    LIMIT 3";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$universityId]);
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("handleEvents error: " . $e->getMessage());
            $events = [];
        }

        $answer = empty($events)
            ? "There are no upcoming events right now."
            : "Here are the next upcoming events:";

        return [
            'answer'  => $answer,
            'source'  => 'faq',
            'events'  => $events,
            'action'  => [
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
                'url' => 'submitFeedbackPage.php'
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
                'url' => 'ManageProfilePage.php'
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
                'url' => 'academicsPage.php?tab=interaction'
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
                'url' => 'viewFacilitiesPage.php'
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
                'url' => '../controller/logoutController.php'
            ]
        ];
    }

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
        $raw = $data['answer'] ?? null;

        if ($raw === null) {
            return null;
        }

        return $this->cleanAiResponse($raw);
    }

    /**
     * Strip markdown formatting from AI responses so they display as plain text.
     */
    private function cleanAiResponse($text)
    {
        // Remove code fences
        $text = preg_replace('/^```[\w]*\s*$/m', '', $text);

        // Strip heading markers (### Heading -> Heading)
        $text = preg_replace('/^#{1,6}\s+/m', '', $text);

        // **bold** -> bold
        $text = preg_replace('/\*\*(.+?)\*\*/', '$1', $text);

        // *italic* -> italic (only when standalone)
        $text = preg_replace('/(?<!\*)\*(?!\s)(.+?)(?<!\s)\*(?!\*)/', '$1', $text);

        // __bold__ -> bold
        $text = preg_replace('/__(.+?)__/', '$1', $text);

        // `code` -> code
        $text = preg_replace('/`([^`]+)`/', '$1', $text);

        // Collapse 3+ blank lines to 2
        $text = preg_replace("/\n{3,}/", "\n\n", $text);

        return trim($text);
    }
}

// ===== AJAX ENDPOINTS =====
if (
    !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
) {

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

        case 'history':
            echo json_encode(['ok' => true, 'data' => $controller->getChatHistory($userId)]);
            exit;

        case 'view_chat':
            $chatId = (int) ($_POST['chatId'] ?? 0);
            echo json_encode(['ok' => true, 'data' => $controller->getChatById($chatId, $userId)]);
            exit;

        case 'sessions':
            echo json_encode(['ok' => true, 'data' => $controller->getChatSessions($userId)]);
            exit;

        case 'view_session':
            $sid = (int) ($_POST['sessionId'] ?? 0);
            $_SESSION['chat_session_id'] = $sid;
            echo json_encode(['ok' => true, 'data' => $controller->getChatsBySession($sid, $userId)]);
            exit;

        case 'clear':
            $result = $controller->clearChatHistory($userId);
            $_SESSION['chat_session_id'] = null;
            echo json_encode(['ok' => $result]);
            exit;

        case 'clear_session':
            $sid = (int) ($_POST['sessionId'] ?? 0);
            if ($sid <= 0) {
                echo json_encode(['ok' => false, 'error' => 'No session specified']);
                exit;
            }
            $result = $controller->clearChatSession($sid, $userId);
            if (($_SESSION['chat_session_id'] ?? null) == $sid) {
                $_SESSION['chat_session_id'] = null;
            }
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