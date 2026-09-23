<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../database/database.php";
require_once "../entity/Event.php";

class ViewEventsController
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
     * Get all active events for a university
     * @param int $universityId
     * @return array|false
     */
    public function getEventList($universityId)
    {
        return $this->eventEntity->getEventList($universityId);
    }
}
?>