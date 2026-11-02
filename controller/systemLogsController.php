<?php

require_once "../entity/systemLog.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../controller/authGuard.php";
requireRole(['system_admin']);

class SystemLogsController
{
    public const PER_PAGE = 15;
    public const SEVERITIES = ['info', 'warning', 'error'];

    public function getCategories()
    {
        return SystemLog::getCategories();
    }

    public function getLogs($filters, $page)
    {
        $clean = [
            'severity' => in_array($filters['severity'], self::SEVERITIES, true) ? $filters['severity'] : '',
            'category' => trim($filters['category']) !== '' ? $filters['category'] : '',
            'dateFrom' => $this->validDate($filters['dateFrom']),
            'dateTo'   => $this->validDate($filters['dateTo']),
            'search'   => trim($filters['search']),
        ];

        return SystemLog::search($clean, $page, self::PER_PAGE);
    }

    // Dates must be real YYYY-MM-DD dates, otherwise they are ignored.
    private function validDate($value)
    {
        $date = DateTime::createFromFormat('Y-m-d', $value);

        return ($date && $date->format('Y-m-d') === $value) ? $value : '';
    }
}