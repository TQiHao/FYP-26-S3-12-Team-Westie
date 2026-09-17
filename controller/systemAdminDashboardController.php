<?php
require_once "../entity/users.php";

class SystemAdminDashboardController
{
   public function showDashboard($userId)
    {
        // Get user information
        $admin = $this->getSystemAdmin($userId);

        return $admin;
    }

    private function getSystemAdmin($userId)
    {
        // Database retrieval will be implemented later
        return null;
    }
}
