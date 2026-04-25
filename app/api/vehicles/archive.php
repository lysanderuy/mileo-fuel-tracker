<?php
require_once __DIR__ . '/../../../config/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);

$id       = isset($body['id'])       ? (int)$body['id']       : 0;
$archived = isset($body['archived']) ? (bool)$body['archived'] : true;

if ($id <= 0) {
    http_response_code(422);
    echo json_encode(['error' => 'Invalid vehicle ID.']);
    exit;
}

$user_id     = (int)$_SESSION['user_id'];
$is_archived = $archived ? 1 : 0;

$check = $conn->prepare("SELECT id FROM vehicles WHERE id = ? AND user_id = ?");
$check->bind_param('ii', $id, $user_id);
$check->execute();
$found = $check->get_result()->fetch_assoc();
$check->close();

if (!$found) {
    http_response_code(404);
    echo json_encode(['error' => 'Vehicle not found.']);
    exit;
}

$stmt = $conn->prepare("UPDATE vehicles SET is_archived = ? WHERE id = ? AND user_id = ?");
$stmt->bind_param('iii', $is_archived, $id, $user_id);
$stmt->execute();
$stmt->close();

echo json_encode(['success' => true, 'is_archived' => (bool)$is_archived]);
