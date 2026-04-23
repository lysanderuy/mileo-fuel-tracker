<?php
session_start();

$api = $_GET['api'] ?? null;

if ($api !== null) {
    $allowed_api = ['auth/signup', 'auth/login', 'auth/logout'];

    if (!in_array($api, $allowed_api)) {
        http_response_code(404);
        echo "404 Not Found";
        exit;
    }

    include __DIR__ . '/../app/api/' . $api . '.php';
    exit;
}

$page = $_GET['page'] ?? 'landing';

$allowed_pages = ['landing', 'login', 'signup', 'logout', 'dashboard'];

if (!in_array($page, $allowed_pages)) {
    http_response_code(404);
    echo "404 Not Found";
    exit;
}

if ($page === 'dashboard' && !isset($_SESSION['user_id'])) {
    header('Location: ?page=login');
    exit;
}

if (in_array($page, ['login', 'signup']) && isset($_SESSION['user_id'])) {
    header('Location: ?page=dashboard');
    exit;
}

include __DIR__ . '/../app/pages/' . $page . '.php';
