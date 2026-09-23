<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../database/database.php";
require_once "../entity/Event.php";

class SearchEventController
{
    private $db;
    private $eventEntity;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
        $this->eventEntity = new Event(null, null, null, null, null, null, null, null, null, null, null, null, $this->db);
    }

    /**
     * Search events by criteria.
     * If keyword is empty, return ALL events.
     * @param int $universityId
     * @param string $keyword
     * @return array|false
     */
    public function searchEvents($universityId, $keyword)
    {
        // If keyword is empty, return ALL events
        if (empty(trim($keyword))) {
            return $this->eventEntity->getEventList($universityId);
        }

        return $this->eventEntity->findEventsByCriteria($universityId, $keyword);
    }
}

// ===== HANDLE SEARCH =====
if (isset($_GET['search'])) {
    $keyword = trim($_GET['keyword'] ?? '');
    $universityId = $_SESSION['university_id'];

    $controller = new SearchEventController();
    $results = $controller->searchEvents($universityId, $keyword);

    if ($results === false) {
        $_SESSION['search_error'] = "Unable to search events. Please try again later.";
    } elseif (empty($results)) {
        $_SESSION['search_no_result'] = "No events found matching your search criteria.";
    }

    // Save results in session to display on the page
    $_SESSION['search_results'] = $results;
    $_SESSION['search_keyword'] = $keyword;

    header("Location: ../boundary/viewEventsPage.php");
    exit();
}
?>