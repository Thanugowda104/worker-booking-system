<?php
// customer/booking-payment.php - Pay for completed booking
require_once __DIR__ . '/../includes/auth.php';
requireAuth('customer');
require_once __DIR__ . '/../includes/functions.php';

$user = getUser();
$showNavbar = true;
require_once __DIR__ . '/../includes/header.php';

$bookingId = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT b.*, u.name as worker_name, wp.hourly_rate
    FROM bookings b
    JOIN users u ON b.worker_id = u.id
    LEFT JOIN worker_profiles wp ON b.worker_id = wp.user_id
    WHERE b.id = ?
      AND b.customer_id = ?
      AND b.status = 'completed'
      AND NOT EXISTS (
          SELECT 1 FROM booking_payments bp WHERE bp.booking_id = b.id
      )
");

$stmt->execute([$bookingId, $user['id']]);
$booking = $stmt->fetch();

if (!$booking) {
    flashMessage('danger', 'Booking not found or already paid.');
    redirect('http://localhost/WBS/customer/bookings.php');
}

$error = '';
$success = '';
$receiptNumber = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $method = sanitize($_POST['payment_method'] ?? '');
    $transactionId = sanitize($_POST['transaction_id'] ?? '');

    if (empty($method) || !in_array($method, ['online', 'cash'])) {
        $error = 'Please select a payment method.';
    } else {

        $receiptNumber = 'PAY-' . strtoupper(uniqid());

        // 🟢 FIXED RULE:
        // online = completed
        // cash = pending (worker confirms later)

        $status = ($method === 'online') ? 'completed' : 'pending';
        $paidAt = ($method === 'online') ? date('Y-m-d H:i:s') : null;

        $amount = $booking['estimated_hours'] * $booking['hourly_rate'];

        try {
            $stmt = $pdo->prepare("
                INSERT INTO booking_payments 
                (booking_id, customer_id, worker_id, amount, payment_method, payment_status, transaction_id, receipt_number, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");

            $stmt->execute([
                $bookingId,
                $user['id'],
                $booking['worker_id'],
                $amount,
                $method,
                $status,
                $transactionId ?: null,
                $receiptNumber
            ]);

            if ($method === 'online') {
                $success = 'Payment successful! ₹' . number_format($amount, 2);
            } else {
                $success = 'Cash selected. Worker will confirm payment.';
            }

        } catch (Exception $e) {
            $error = 'Payment failed: ' . $e->getMessage();
        }
    }
}
?>

<div class="container py-5" style="max-width: 640px;">

    <div class="text-center mb-4">
        <div class="display-1 text-primary">
            <i class="bi bi-credit-card"></i>
        </div>
        <h2 class="fw-bold">Pay for Service</h2>
        <p class="text-muted">Booking: <?= $booking['booking_code'] ?></p>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">

            <dl class="row mb-3">
                <dt class="col-sm-5 fw-semibold">Worker</dt>
                <dd class="col-sm-7"><?= sanitize($booking['worker_name']) ?></dd>

                <dt class="col-sm-5 fw-semibold">Service</dt>
                <dd class="col-sm-7">
                    <?= formatDate($booking['scheduled_date']) ?>
                    (<?= $booking['booking_type'] ?>)
                </dd>

                <dt class="col-sm-5 fw-semibold">Hours</dt>
                <dd class="col-sm-7"><?= $booking['estimated_hours'] ?> hrs</dd>

                <dt class="col-sm-5 fw-semibold">Hourly Rate</dt>
                <dd class="col-sm-7">₹<?= number_format($booking['hourly_rate'], 2) ?></dd>

                <dt class="col-sm-5 fw-semibold">Total Amount</dt>
                <dd class="col-sm-7 fw-bold text-primary">
                    ₹<?= number_format($booking['estimated_hours'] * $booking['hourly_rate'], 2) ?>
                </dd>
            </dl>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <?php if ($success): ?>

                <div class="alert alert-success"><?= $success ?></div>

                <?php if ($receiptNumber): ?>
                    <div class="card bg-light border-0">
                        <div class="card-body">
                            <h6 class="fw-bold">Payment Receipt</h6>
                            <p><strong>Receipt No:</strong> <?= $receiptNumber ?></p>
                            <p><strong>Amount:</strong> ₹<?= number_format($booking['estimated_hours'] * $booking['hourly_rate'], 2) ?></p>
                            <p><strong>Method:</strong> <?= strtoupper($_POST['payment_method'] ?? '') ?></p>
                            <p><strong>Date:</strong> <?= date('d M Y H:i') ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <a href="http://localhost/WBS/customer/bookings.php"
                   class="btn btn-primary w-100 mt-3">
                   Back to Bookings
                </a>

            <?php else: ?>

                <form method="POST">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Payment Method</label>

                        <div class="row g-2">

                            <div class="col-6">
                                <input type="radio" class="btn-check" name="payment_method"
                                       id="online" value="online" required>

                                <label class="btn btn-outline-primary w-100 py-3" for="online">
                                    <i class="bi bi-credit-card"></i><br>
                                    Online Payment
                                </label>
                            </div>

                            <div class="col-6">
                                <input type="radio" class="btn-check" name="payment_method"
                                       id="cash" value="cash" required>

                                <label class="btn btn-outline-secondary w-100 py-3" for="cash">
                                    <i class="bi bi-cash"></i><br>
                                    Cash
                                </label>
                            </div>

                        </div>
                    </div>

                    <div class="mb-3" id="transactionRow" style="display:none;">
                        <label class="form-label">Transaction ID / Reference No.</label>
                        <input type="text" name="transaction_id"
                               class="form-control"
                               placeholder="e.g. UPI123456">
                    </div>

                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                        Please pay within 7 days. Cash payments require worker confirmation.
                    </div>

                    <button type="submit" class="btn btn-success w-100 py-2">
                        Pay ₹<?= number_format($booking['estimated_hours'] * $booking['hourly_rate'], 2) ?>
                    </button>

                </form>

            <?php endif; ?>

        </div>
    </div>
</div>

<!-- ✅ FIXED JS -->
<script>
document.addEventListener("DOMContentLoaded", function () {

    const onlineRadio = document.getElementById("online");
    const cashRadio = document.getElementById("cash");
    const transactionRow = document.getElementById("transactionRow");

    function toggleTransaction() {
        if (onlineRadio.checked) {
            transactionRow.style.display = "block";
        } else {
            transactionRow.style.display = "none";
        }
    }

    onlineRadio.addEventListener("change", toggleTransaction);
    cashRadio.addEventListener("change", toggleTransaction);

});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>