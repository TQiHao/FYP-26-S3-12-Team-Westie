<?php

require_once __DIR__ . "/../database/database.php";

/**
 * Entity for the SystemLogs table. Any controller can call SystemLog::record() to save an event.
 *
 *   SystemLog::record('authentication', 'info', 'Login', 'User logged in');
 */
class SystemLog
{
    public const SEVERITIES = ['info', 'warning', 'error'];

    /**
     * @param string      $category
     * @param string      $severity
     * @param string      $action
     * @param string      $description details
     * @param string|null $source
     */
    public static function record($category, $severity, $action, $description = '', $source = null)
    {
        try {
            if (!in_array($severity, self::SEVERITIES, true)) {
                $severity = 'info';
            }

            // Read who is logged in
            $session = (session_status() === PHP_SESSION_ACTIVE) ? $_SESSION : [];

            // Only System Admin activity is recorded
            if (!empty($session['user_role']) && $session['user_role'] !== 'system_admin') {
                return;
            }

            if (!empty($session['user_name'])) {
                $role = str_replace('_', ' ', $session['user_role'] ?? 'user');
                $description .= ' (by ' . $session['user_name'] . ', ' . $role . ')';
            }

            if ($source === null) {
                $source = basename($_SERVER['SCRIPT_NAME'] ?? 'unknown');
            }

            $database = new Database();
            $db = $database->connect();

            $stmt = $db->prepare(
                "INSERT INTO SystemLogs (category, severity, action, description, source)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                substr($category, 0, 100),
                $severity,
                substr($action, 0, 255),
                $description,
                substr($source, 0, 255),
            ]);
        } catch (Throwable $e) {
            error_log("SystemLog::record failed: " . $e->getMessage());
        }
    }
}