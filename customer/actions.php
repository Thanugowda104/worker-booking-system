<?php
// customer/actions.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn() || $_SESSION['role'] !== 'customer') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user = getUser();

/* =========================
   READ INPUT PROPERLY
========================= */
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) $input = [];

$action = $input['action']
    ?? $_GET['action']
    ?? $_POST['action']
    ?? '';

/* =========================
   FETCH WORKERS
========================= */
if ($action === 'fetch_workers') {

    $catId = (int)($_GET['category_id'] ?? 0);

    $stmt = $pdo->prepare("
        SELECT 
            u.id,
            u.name,
            wp.hourly_rate,
            wp.experience_years,
            wp.avg_rating
        FROM users u
        JOIN worker_profiles wp ON u.id = wp.user_id
        WHERE u.role = 'worker'
          AND u.status = 'active'
          AND wp.is_verified = 1
          AND wp.category_id = ?
        ORDER BY wp.avg_rating DESC
    ");

    $stmt->execute([$catId]);
    echo json_encode($stmt->fetchAll());
    exit;
}

/* =========================
   FETCH SLOTS
========================= */
if ($action === 'fetch_slots') {

    $workerId = (int)($_GET['worker_id'] ?? 0);
    $date = $_GET['date'] ?? '';

    $stmt = $pdo->prepare("
        SELECT start_time, end_time
        FROM availability
        WHERE worker_id = ?
          AND available_date = ?
          AND is_booked = 0
        ORDER BY start_time
    ");

    $stmt->execute([$workerId, $date]);
    echo json_encode($stmt->fetchAll());
    exit;
}

/* =========================
   CREATE BOOKING (FIXED)
========================= */
if ($action === 'create_booking') {

    $d = $input['data'] ?? [];

    $workerId   = (int)($d['worker_id'] ?? 0);
    $categoryId = (int)($d['category_id'] ?? 0); // ✅ FIX IMPORTANT

    $bookingType = $d['booking_type'] ?? 'request';

    $scheduledDate  = sanitize($d['date'] ?? '');
    $scheduledStart = sanitize($d['start'] ?? '');
    $scheduledEnd   = sanitize($d['end'] ?? '');
    $notes          = sanitize($d['notes'] ?? '');

    // worker rate
    $stmt = $pdo->prepare("SELECT hourly_rate FROM worker_profiles WHERE user_id = ?");
    $stmt->execute([$workerId]);
    $worker = $stmt->fetch();

    if (!$worker) {
        echo json_encode(['success' => false, 'message' => 'Worker not found']);
        exit;
    }

    $bookingCode = generateBookingCode($pdo);

    // duplicate check
    $check = $pdo->prepare("
        SELECT id FROM bookings
        WHERE customer_id = ? AND worker_id = ? AND booking_type = ?
          AND scheduled_date = ? AND scheduled_start = ?
          AND status IN ('pending', 'accepted')
    ");

    $check->execute([
        $user['id'],
        $workerId,
        $bookingType,
        $scheduledDate ?: null,
        $scheduledStart ?: null
    ]);

    if ($check->fetch()) {
        echo json_encode(['success' => false, 'message' => 'You already have a booking for this slot.']);
        exit;
    }

    // calculate cost
    $estimatedHours = ($scheduledStart && $scheduledEnd)
        ? round((strtotime($scheduledEnd) - strtotime($scheduledStart)) / 3600, 2)
        : 0;

    $total = $estimatedHours * $worker['hourly_rate'];

    // mark slot booked
    if ($bookingType === 'slot' && $scheduledStart) {
        $stmt = $pdo->prepare("
            UPDATE availability
            SET is_booked = 1
            WHERE worker_id = ?
              AND available_date = ?
              AND start_time = ?
              AND is_booked = 0
        ");
        $stmt->execute([$workerId, $scheduledDate, $scheduledStart]);
    }

    // ✅ FIXED INSERT (category_id ADDED)
    $stmt = $pdo->prepare("
        INSERT INTO bookings
        (booking_code, customer_id, worker_id, category_id, booking_type, status,
         scheduled_date, scheduled_start, scheduled_end,
         estimated_hours, total_amount, notes)
        VALUES (?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $bookingCode,
        $user['id'],
        $workerId,
        $categoryId,   // ✅ IMPORTANT FIX
        $bookingType,
        $scheduledDate ?: null,
        $scheduledStart ?: null,
        $scheduledEnd ?: null,
        $estimatedHours ?: null,
        $total ?: null,
        $notes
    ]);

    echo json_encode([
        'success' => true,
        'booking_id' => $pdo->lastInsertId()
    ]);
    exit;
}

/* =========================
   CANCEL BOOKING
========================= */
if ($action === 'cancel_booking') {

    $bookingId = (int)($input['booking_id'] ?? 0);

    $stmt = $pdo->prepare("
        SELECT * FROM bookings
        WHERE id = ? AND customer_id = ?
          AND status IN ('pending','accepted')
    ");

    $stmt->execute([$bookingId, $user['id']]);

    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Booking not found']);
        exit;
    }

    $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?")
        ->execute([$bookingId]);

    echo json_encode(['success' => true]);
    exit;
}

/* =========================
   SUBMIT REVIEW
========================= */
if ($action === 'submit_review') {

    $bookingId = (int)($input['booking_id'] ?? 0);
    $rating    = (int)($input['rating'] ?? 0);
    $comment   = sanitize($input['comment'] ?? '');

    if ($rating < 1 || $rating > 5) {
        echo json_encode(['success' => false, 'message' => 'Invalid rating']);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT * FROM bookings
        WHERE id = ? AND customer_id = ?
          AND status = 'completed'
    ");

    $stmt->execute([$bookingId, $user['id']]);
    $booking = $stmt->fetch();

    if (!$booking) {
        echo json_encode(['success' => false, 'message' => 'Cannot review this booking']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        $pdo->prepare("
            INSERT INTO reviews (booking_id, customer_id, worker_id, rating, comment)
            VALUES (?, ?, ?, ?, ?)
        ")->execute([
            $bookingId,
            $user['id'],
            $booking['worker_id'],
            $rating,
            $comment
        ]);

        $stmt = $pdo->prepare("
            SELECT AVG(rating) as avg
            FROM reviews
            WHERE worker_id = ?
        ");

        $stmt->execute([$booking['worker_id']]);
        $avg = $stmt->fetch()['avg'];

        $pdo->prepare("
            UPDATE worker_profiles
            SET avg_rating = ?
            WHERE user_id = ?
        ")->execute([$avg, $booking['worker_id']]);

        $pdo->commit();

        echo json_encode(['success' => true]);

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

/* =========================
   DEFAULT
========================= */
echo json_encode([
    'success' => false,
    'message' => 'Unknown action: ' . $action
]);