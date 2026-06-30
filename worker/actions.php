<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

/* =========================
   AUTH CHECK (FIXED)
========================= */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'worker') {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized'
    ]);
    exit;
}

$userId = (int) $_SESSION['user_id'];

/* =========================
   INPUT HANDLING
========================= */
$raw = file_get_contents('php://input');
$input = json_decode($raw, true);

if (!is_array($input)) {
    $input = $_POST ?? [];
}

$action = $input['action'] ?? '';

/* =========================
   UPDATE BOOKING STATUS
========================= */
if ($action === 'update_booking') {

    $bookingId = (int)($input['booking_id'] ?? 0);
    $status = $input['status'] ?? '';

    $allowed = ['accepted', 'rejected', 'completed'];

    if (!in_array($status, $allowed)) {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit;
    }

    /* =========================
       CHECK BOOKING OWNERSHIP
    ========================= */
    $stmt = $pdo->prepare("
        SELECT * FROM bookings
        WHERE id = ? AND worker_id = ?
    ");
    $stmt->execute([$bookingId, $userId]);
    $booking = $stmt->fetch();

    if (!$booking) {
        echo json_encode(['success' => false, 'message' => 'Booking not found']);
        exit;
    }

    /* =========================
       SLOT LOGIC (NON-BLOCKING FIX)
    ========================= */
    if ($status === 'accepted' && $booking['booking_type'] === 'slot') {

        if (!empty($booking['scheduled_date']) && !empty($booking['scheduled_start'])) {

            $stmt = $pdo->prepare("
                SELECT id FROM availability
                WHERE worker_id = ?
                AND available_date = ?
                AND start_time = ?
                AND is_booked = 0
                LIMIT 1
            ");

            $stmt->execute([
                $userId,
                $booking['scheduled_date'],
                $booking['scheduled_start']
            ]);

            $slot = $stmt->fetch();

            /* 
              IMPORTANT FIX:
              ❌ DO NOT BLOCK ACCEPT IF SLOT NOT FOUND
              ✔ Just skip slot locking
            */
            if ($slot) {
                $pdo->prepare("
                    UPDATE availability
                    SET is_booked = 1
                    WHERE id = ?
                ")->execute([$slot['id']]);
            }
        }
    }

    /* =========================
       UPDATE BOOKING STATUS
    ========================= */
    $stmt = $pdo->prepare("
        UPDATE bookings
        SET status = ?
        WHERE id = ?
    ");
    $stmt->execute([$status, $bookingId]);

    echo json_encode([
        'success' => true,
        'message' => 'Booking updated successfully'
    ]);
    exit;
}

/* =========================
   DEFAULT
========================= */
echo json_encode([
    'success' => false,
    'message' => 'Unknown action'
]);