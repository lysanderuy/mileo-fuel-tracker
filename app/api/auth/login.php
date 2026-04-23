<?php
require_once __DIR__ . '/../../../config/db.php';

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (!$email || !$password) {
    $_SESSION['flash_error'] = 'Please fill in all fields.';
    $_SESSION['flash_form']  = ['email' => $email];
    header('Location: ?page=login');
    exit;
}

$stmt = $conn->prepare('SELECT id, name, password FROM users WHERE email = ?');
$stmt->bind_param('s', $email);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($row && password_verify($password, $row['password'])) {
    $_SESSION['user_id']   = $row['id'];
    $_SESSION['user_name'] = $row['name'];
    header('Location: ?page=dashboard');
    exit;
}

$_SESSION['flash_error'] = 'Invalid email or password.';
$_SESSION['flash_form']  = ['email' => $email];
header('Location: ?page=login');
exit;
