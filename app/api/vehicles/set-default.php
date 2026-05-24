<?php
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../app/includes/api_helpers.php';

api_require_auth();
header('Content-Type: application/json');
api_require_method('POST');

$body    = json_decode(file_get_contents('php://input'), true);
$id      = isset($body['id']) ? (int)$body['id'] : 0;
$user_id = (int)$_SESSION['user_id'];

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid vehicle ID']);
    exit;
}

$stmt = $conn->prepare("SELECT id FROM vehicles WHERE id = ? AND user_id = ?");
$stmt->bind_param('ii', $id, $user_id);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    $stmt->close();
    http_response_code(404);
    echo json_encode(['error' => 'Vehicle not found']);
    exit;
}
$stmt->close();

$conn->begin_transaction();

try {
    $stmt = $conn->prepare("UPDATE vehicles SET is_default = 0 WHERE user_id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("UPDATE vehicles SET is_default = 1 WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
