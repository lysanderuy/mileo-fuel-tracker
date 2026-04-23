<?php
require_once __DIR__ . '/../../../config/db.php';

$name     = trim($_POST['name'] ?? '');
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (!$name || !$email || !$password) {
    $_SESSION['flash_error'] = 'Please fill in all fields.';
    $_SESSION['flash_form']  = ['name' => $name, 'email' => $email];
    header('Location: ?page=signup');
    exit;
}

if (strlen($password) < 8) {
    $_SESSION['flash_error'] = 'Password must be at least 8 characters.';
    $_SESSION['flash_form']  = ['name' => $name, 'email' => $email];
    header('Location: ?page=signup');
    exit;
}

$stmt = $conn->prepare('SELECT id FROM users WHERE email = ?');
$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->store_result();
$exists = $stmt->num_rows > 0;
$stmt->close();

if ($exists) {
    $_SESSION['flash_error'] = 'An account with that email already exists.';
    $_SESSION['flash_form']  = ['name' => $name, 'email' => $email];
    header('Location: ?page=signup');
    exit;
}

$hash = password_hash($password, PASSWORD_BCRYPT);
$stmt = $conn->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)');
$stmt->bind_param('sss', $name, $email, $hash);

if ($stmt->execute()) {
    $_SESSION['user_id']   = $conn->insert_id;
    $_SESSION['user_name'] = $name;
    $stmt->close();
    header('Location: ?page=dashboard');
    exit;
}

$stmt->close();
$_SESSION['flash_error'] = 'Registration failed. Please try again.';
$_SESSION['flash_form']  = ['name' => $name, 'email' => $email];
header('Location: ?page=signup');
exit;
