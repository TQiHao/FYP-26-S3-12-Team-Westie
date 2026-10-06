<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../entity/universities.php';
require_once __DIR__ . '/../database/database.php';

class RenewLicenseController
{
    private $db;
    private $universityId;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
        $this->universityId = (int) ($_SESSION['university_id'] ?? 0);
    }

    public function getUniversity()
    {
        if (!$this->universityId) {
            return null;
        }

        $stmt = $this->db->prepare("SELECT * FROM Universities WHERE id = ?");
        $stmt->execute([$this->universityId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        return new Universities(
            $row['id'],
            $row['name'],
            $row['institutionType'],
            $row['country'],
            $row['postalCode'],
            $row['status'],
            $row['suspensionReason'],
            $row['registrationDate'],
            $row['updatedAt'],
            $row['subscriptionPlan'],
            $this->db
        );
    }

    public function renewLicense($subscriptionPlan)
    {
        $university = $this->getUniversity();
        if (!$university) {
            return ['success' => false, 'message' => 'University not found.'];
        }

        $durationYears = (int) $subscriptionPlan;
        if (!in_array($durationYears, [1, 2, 5], true)) {
            return ['success' => false, 'message' => 'Invalid renewal duration selected.'];
        }

        $ok = $university->renewLicense($durationYears);
        if ($ok) {
            return [
                'success' => true,
                'message' => 'License renewed successfully for ' . $durationYears . ' year(s).'
            ];
        }

        return ['success' => false, 'message' => 'Failed to renew license. Please try again.'];
    }
}