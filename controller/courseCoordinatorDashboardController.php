<?php

require_once "../database/database.php";
require_once "../entity/users.php";

class CCDashboardController
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
    }

    public function showDashboard($userId)
    {
        // Get user information
        $user = $this->getUser($userId);

        return $user;
    }

    private function getUser($userId)
    {
        // Database retrieval will be implemented later
        return null;
    }
}
