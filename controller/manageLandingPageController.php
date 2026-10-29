<?php

require_once "../database/database.php";
require_once "../entity/systemLog.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (
    !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true ||
    ($_SESSION['user_role'] ?? '') !== 'system_admin'
) {
    header("Location: ../boundary/loginPage.php");
    exit();
}

class ManageLandingPageController
{
    private $db;

    public const FEATURE_SECTIONS = [
        'feature_admin'    => 'University Admin',
        'feature_cc'       => 'Course Coordinator',
        'feature_student'  => 'Students',
        'feature_lecturer' => 'Lecturers',
    ];

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
    }

    private function getSection($sectionKey)
    {
        $stmt = $this->db->prepare("SELECT * FROM LandingPageContent WHERE sectionKey = ? LIMIT 1");
        $stmt->execute([$sectionKey]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getEditableContent()
    {
        $hero = $this->getSection('hero');

        $heroTitle = $hero['title'] ?? 'Connect Smarter,<br>Bee Smarter.';
        $heroTitle = preg_replace('/<br\s*\/?>/i', "\n", $heroTitle);
        $heroTitle = html_entity_decode($heroTitle, ENT_QUOTES, 'UTF-8');

        $content = [
            'hero' => [
                'title'    => $heroTitle,
                'subtitle' => $hero['subtitle'] ?? '',
            ],
            'features' => [],
        ];

        foreach (self::FEATURE_SECTIONS as $key => $label) {
            $row = $this->getSection($key);
            $boxes = [];

            if ($row && !empty($row['content'])) {
                $decoded = json_decode($row['content'], true);
                if (is_array($decoded)) {
                    $boxes = $decoded;
                }
            }

            $content['features'][$key] = [
                'label'  => $label,
                'exists' => (bool) $row,   // used by the edit page to match slides to sections
                'title'  => $row['title'] ?? ('Features for ' . $label),
                'boxes'  => $boxes,
            ];
        }

        return $content;
    }

    public function updateContent($heroTitle, $heroSubtitle, $features)
    {
        $heroTitle    = trim($heroTitle);
        $heroSubtitle = trim($heroSubtitle);

        if ($heroTitle === '' || $heroSubtitle === '') {
            return "Hero title and subtitle cannot be empty.";
        }

        if (strlen($heroTitle) > 200 || strlen($heroSubtitle) > 500) {
            return "Hero title or subtitle is too long.";
        }

        $lines = preg_split('/\r\n|\r|\n/', $heroTitle);
        $lines = array_map(function ($line) {
            return htmlspecialchars(trim($line), ENT_QUOTES, 'UTF-8');
        }, array_filter($lines, function ($line) {
            return trim($line) !== '';
        }));
        $heroTitleHtml = implode('<br>', $lines);

        $rows = [
            ['hero', $heroTitleHtml, $heroSubtitle, null],
        ];

        foreach (self::FEATURE_SECTIONS as $key => $label) {
            $section = $features[$key] ?? null;

            if (!is_array($section) || trim($section['title'] ?? '') === '') {
                return "Please fill in the heading for every feature section.";
            }

            $boxes = [];
            foreach (($section['boxes'] ?? []) as $box) {
                $boxTitle = trim($box['title'] ?? '');
                $boxDesc  = trim($box['desc'] ?? '');

                if ($boxTitle === '' || $boxDesc === '') {
                    return "Every feature box needs both a title and a description.";
                }

                if (strlen($boxTitle) > 100 || strlen($boxDesc) > 300) {
                    return "A feature title or description is too long.";
                }

                $boxes[] = ['title' => $boxTitle, 'desc' => $boxDesc];
            }

            if (count($boxes) === 0) {
                return "Each feature section needs at least one feature box.";
            }

            $rows[] = [$key, trim($section['title']), null, json_encode($boxes, JSON_UNESCAPED_UNICODE)];
        }

        // sectionKey is UNIQUE, validate if the content already exist.
        $sql = "INSERT INTO LandingPageContent (sectionKey, title, subtitle, content)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    title    = VALUES(title),
                    subtitle = VALUES(subtitle),
                    content  = VALUES(content)";

        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare($sql);

            foreach ($rows as $row) {
                $stmt->execute($row);
            }

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return "Unable to update the landing page. Please try again.";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_landing'])) {

    $controller = new ManageLandingPageController();

    $result = $controller->updateContent(
        $_POST['heroTitle'] ?? '',
        $_POST['heroSubtitle'] ?? '',
        $_POST['features'] ?? []
    );

    if ($result === true) {
        $_SESSION['landing_success'] = "Landing Page Updated Successfully.";
        SystemLog::record('landing_page', 'info', 'Landing Page Updated', 'The landing page content was updated.');
        header("Location: ../boundary/manageLandingPage.php");
    } else {
        $_SESSION['landing_error'] = $result;
        header("Location: ../boundary/updateLandingPage.php");
    }
    exit();
}