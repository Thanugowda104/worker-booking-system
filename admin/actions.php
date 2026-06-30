<?php
// admin/actions.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

if ($action === 'toggle_verify') {
    $workerId = (int)($input['user_id'] ?? 0);
    $stmt = $pdo->prepare("UPDATE worker_profiles SET is_verified = IF(is_verified = 1, 0, 1) WHERE user_id = ?");
    $stmt->execute([$workerId]);
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'toggle_block') {
    $workerId = (int)($input['user_id'] ?? 0);
    $currentStatus = sanitize($input['status'] ?? 'active');
    $newStatus = $currentStatus === 'blocked' ? 'active' : 'blocked';
    $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
    $stmt->execute([$newStatus, $workerId]);
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'get_receipt') {
    $receiptNumber = sanitize($input['receipt_number'] ?? '');
    $stmt = $pdo->prepare("SELECT p.*, u.name, u.email FROM payments p JOIN users u ON p.user_id = u.id WHERE p.receipt_number = ?");
    $stmt->execute([$receiptNumber]);
    $receipt = $stmt->fetch();
    if ($receipt) {
        echo json_encode(['success' => true, 'receipt' => $receipt]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Receipt not found']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action']);
