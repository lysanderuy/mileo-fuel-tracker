<?php
require_once __DIR__ . '/../../../config/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

// Check if column exists
$check = $conn->query("SHOW COLUMNS FROM vehicles LIKE 'is_default'");
if (!$check || $check->num_rows === 0) {
    http_response_code(500);
    echo json_encode(['error' => 'Database migration required: is_default column missing.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$id = isset($data['id']) ? (int)$data['id'] : 0;
$user_id = (int)$_SESSION['user_id'];

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid vehicle ID']);
    exit;
}

// Check if vehicle belongs to user
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

// Start transaction to update default status
$conn->begin_transaction();

try {
    // Reset all vehicles for this user
    $stmt = $conn->prepare("UPDATE vehicles SET is_default = 0 WHERE user_id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->close();

    // Set new default
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
