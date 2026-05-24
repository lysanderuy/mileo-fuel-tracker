<?php
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../app/includes/api_helpers.php';

api_require_auth();
header('Content-Type: application/json');
api_require_method('POST');

$body = json_decode(file_get_contents('php://input'), true);
$id   = isset($body['id']) ? (int)$body['id'] : 0;

if ($id <= 0) {
    http_response_code(422);
    echo json_encode(['error' => 'Invalid vehicle ID.']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("SELECT id FROM vehicles WHERE id = ? AND user_id = ?");
$stmt->bind_param('ii', $id, $user_id);
$stmt->execute();
$vehicle = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$vehicle) {
    http_response_code(404);
    echo json_encode(['error' => 'Vehicle not found.']);
    exit;
}

$stmt = $conn->prepare("DELETE FROM vehicles WHERE id = ? AND user_id = ?");
$stmt->bind_param('ii', $id, $user_id);
$stmt->execute();
$stmt->close();

echo json_encode(['success' => true]);
