<?php

require_once "../entity/user.php";

class StudentDashboardController
{
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
