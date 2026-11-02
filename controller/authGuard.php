<?php

/* Shared access check for pages and controllers. */

function requireRole(array $allowedRoles)
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $role = strtolower($_SESSION['user_role'] ?? '');

    if (empty($_SESSION['logged_in']) || !in_array($role, $allowedRoles, true)) {
        denyAccess(401, 'You are not allowed to do this.', true);
    }

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && !isSameOriginRequest()) {
        denyAccess(403, 'This request did not come from UniBee.', false);
    }
}

function isSameOriginRequest()
{
    $source = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? '';

    if ($source === '') {
        return true;
    }

    $sourceHost = parse_url($source, PHP_URL_HOST);
    $sourcePort = parse_url($source, PHP_URL_PORT);
    $thisHost   = $_SERVER['HTTP_HOST'] ?? '';

    if (!$sourceHost) {
        return false;
    }

    $from = strtolower($sourceHost . ($sourcePort ? ':' . $sourcePort : ''));

    return $from === strtolower($thisHost);
}

function denyAccess($status, $message, $redirectToLogin)
{
    $wantsJson = stripos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false
        || ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';

    if ($wantsJson || !$redirectToLogin) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode(['error' => $message]);
        exit();
    }

    header("Location: ../boundary/loginPage.php");
    exit();
}