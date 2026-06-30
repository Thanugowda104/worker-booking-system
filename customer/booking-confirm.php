<?php
// customer/booking-confirm.php
require_once __DIR__ . '/../includes/auth.php';
requireAuth('customer');
require_once __DIR__ . '/../includes/functions.php';
$user = getUser();
$showNavbar = true;
require_once __DIR__ . '/../includes/header.php';

$bookingId = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT b.*, u.name as worker_name, sc.name as service, wp.hourly_rate FROM bookings b JOIN users u ON b.worker_id = u.id LEFT JOIN service_categories sc ON b.category_id = sc.id LEFT JOIN worker_profiles wp ON b.worker_id = wp.user_id WHERE b.id = ? AND b.customer_id = ?");
$stmt->execute([$bookingId, $user['id']]);
$booking = $stmt->fetch();
?>
<div class="container py-5" style="max-width: 640px;">
    <?php if ($booking): ?>
    <div class="text-center mb-4">
        <div class="display-1 text-success"><i class="bi bi-check-circle-fill"></i></div>
        <h2 class="fw-bold">Booking Confirmed!</h2>
        <p class="text-muted">Your service has been booked successfully.</p>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <dl class="row mb-2">
                <dt class="col-sm-4 fw-semibold">Booking Code</dt><dd class="col-sm-8"><?= $booking['booking_code'] ?></dd>
                <dt class="col-sm-4 fw-semibold">Service</dt><dd class="col-sm-8"><?= sanitize($booking['service'] ?? '-') ?></dd>
                <dt class="col-sm-4 fw-semibold">Worker</dt><dd class="col-sm-8"><?= sanitize($booking['worker_name']) ?></dd>
                <dt class="col-sm-4 fw-semibold">Rate</dt><dd class="col-sm-8">₹<?= number_format($booking['hourly_rate'], 2) ?>/hr</dd>
                <dt class="col-sm-4 fw-semibold">Scheduled</dt>
                <dd class="col-sm-8"><?= $booking['scheduled_date'] ? formatDate($booking['scheduled_date']) . ' ' . $booking['scheduled_start'] . ' - ' . $booking['scheduled_end'] : 'Custom request' ?></dd>
                <dt class="col-sm-4 fw-semibold">Type</dt><dd class="col-sm-8"><?= ucfirst($booking['booking_type']) ?></dd>
                <dt class="col-sm-4 fw-semibold">Total</dt><dd class="col-sm-8"><?= formatCurrency($booking['total_amount'] ?? 0) ?></dd>
                <dt class="col-sm-4 fw-semibold">Status</dt><dd class="col-sm-8"><?= statusBadge($booking['status']) ?></dd>
            </dl>
        </div>
    </div>
    <div class="d-flex gap-2 mt-4">
        <a href="http://localhost/WBS/customer/bookings.php" class="btn btn-outline-primary flex-fill">My Bookings</a>
        <a href="http://localhost/WBS/customer/dashboard.php" class="btn btn-primary flex-fill">Book Another</a>
    </div>
    <?php else: ?>
    <div class="text-center py-5"><h3>Booking not found</h3><p class="text-muted">The booking may have been cancelled or does not belong to you.</p><a href="http://localhost/WBS/customer/dashboard.php" class="btn btn-primary">Back to Dashboard</a></div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>