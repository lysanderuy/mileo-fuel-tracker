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
$id   = isset($body['id']) ? (int)$body['id'] : 0;

if ($id <= 0) {
    http_response_code(422);
    echo json_encode(['error' => 'Invalid vehicle ID.']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];

$check = $conn->prepare("SELECT id, is_archived FROM vehicles WHERE id = ? AND user_id = ?");
$check->bind_param('ii', $id, $user_id);
$check->execute();
$vehicle = $check->get_result()->fetch_assoc();
$check->close();

if (!$vehicle) {
    http_response_code(404);
    echo json_encode(['error' => 'Vehicle not found.']);
    exit;
}

if (!(bool)$vehicle['is_archived']) {
    http_response_code(422);
    echo json_encode(['error' => 'Only archived vehicles can be deleted.']);
    exit;
}

$stmt = $conn->prepare("DELETE FROM vehicles WHERE id = ? AND user_id = ?");
$stmt->bind_param('ii', $id, $user_id);
$stmt->execute();
$stmt->close();

echo json_encode(['success' => true]);
