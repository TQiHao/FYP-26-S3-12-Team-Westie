<?php

require_once "../entity/landingPageContent.php";
require_once "../entity/systemLog.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../controller/authGuard.php";
requireRole(['system_admin']);

class ManageLandingPageController
{
    public const FEATURE_SECTIONS = [
        'feature_admin'    => 'University Admin',
        'feature_cc'       => 'Course Coordinator',
        'feature_student'  => 'Students',
        'feature_lecturer' => 'Lecturers',
    ];

    public function getEditableContent()
    {
        $hero = LandingPageContent::getSection('hero');

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
            $row = LandingPageContent::getSection($key);
            $boxes = [];

            if ($row && !empty($row['content'])) {
                $decoded = json_decode($row['content'], true);
                if (is_array($decoded)) {
                    $boxes = $decoded;
                }
            }

            $content['features'][$key] = [
                'label'  => $label,
                'exists' => (bool) $row,
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

        if (!LandingPageContent::saveSections($rows)) {
            return "Unable to update the landing page. Please try again.";
        }

        return true;
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