<?php
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../app/includes/api_helpers.php';

api_require_auth();
header('Content-Type: application/json');
api_require_method('POST');

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

$stmt = $conn->prepare("SELECT id FROM vehicles WHERE id = ? AND user_id = ?");
$stmt->bind_param('ii', $id, $user_id);
$stmt->execute();
$found = $stmt->get_result()->fetch_assoc();
$stmt->close();

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
