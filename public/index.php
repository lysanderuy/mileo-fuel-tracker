<?php
session_start();

$api = $_GET['api'] ?? null;

if ($api !== null) {
    $allowed_api = [
        'auth/signup',
        'auth/login',
        'auth/logout',
        'vehicles/list',
        'vehicles/create',
        'vehicles/update',
        'vehicles/archive',
        'vehicles/delete',
        'vehicles/set-default',
        'fuel-logs/create',
        'fuel-logs/get',
        'fuel-logs/list',
        'fuel-logs/update',
        'fuel-logs/delete',
        'fuel-logs/detail',
        'dashboard/summary',
    ];

    if (!in_array($api, $allowed_api, true)) {
        http_response_code(404);
        echo "404 Not Found";
        exit;
    }

    include __DIR__ . '/../app/api/' . $api . '.php';
    exit;
}

$page = $_GET['page'] ?? 'landing';

$allowed_pages = ['landing', 'login', 'signup', 'logout', 'dashboard', 'vehicles', 'history', 'fuel-log-detail'];

if (!in_array($page, $allowed_pages)) {
    http_response_code(404);
    echo "404 Not Found";
    exit;
}

if (in_array($page, ['dashboard', 'vehicles', 'history', 'fuel-log-detail'], true) && !isset($_SESSION['user_id'])) {
    header('Location: ?page=login');
    exit;
}

if (in_array($page, ['login', 'signup']) && isset($_SESSION['user_id'])) {
    header('Location: ?page=dashboard');
    exit;
}

include __DIR__ . '/../app/pages/' . $page . '.php';
