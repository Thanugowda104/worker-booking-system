<?php
// payment.php - Worker registration payment (online only)
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (!isLoggedIn()) {
    $userId = $_GET['user_id'] ?? 0;
    if (!$userId) redirect('http://localhost/WBS/auth/register.php');
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'worker' AND payment_status = 'pending'");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    if (!$user) redirect('http://localhost/WBS/auth/register.php');
} else {
    $user = getUser();
    if ($user['role'] !== 'worker' || $user['payment_status'] === 'paid') {
        redirect('http://localhost/WBS/worker/dashboard.php');
    }
    $userId = $_SESSION['user_id'];
}

$error = '';
$success = '';
$receiptNumber = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $transactionId = sanitize($_POST['transaction_id'] ?? '');
    $receiptNumber = 'RCP-' . strtoupper(uniqid());
    $paidAt = date('Y-m-d H:i:s');
    
    try {
        $stmt = $pdo->prepare("INSERT INTO payments (user_id, amount, payment_method, payment_status, transaction_id, receipt_number, paid_at) VALUES (?, 50.00, 'online', 'completed', ?, ?, ?)");
        $stmt->execute([$userId, $transactionId ?: null, $receiptNumber, $paidAt]);
        
        $stmt = $pdo->prepare("UPDATE users SET payment_status = 'paid', status = 'active' WHERE id = ?");
        $stmt->execute([$userId]);
        $stmt = $pdo->prepare("UPDATE worker_profiles SET is_verified = 1 WHERE user_id = ?");
        $stmt->execute([$userId]);
        $success = 'Payment successful! Your account is now active.';
    } catch (Exception $e) {
        $error = 'Payment processing failed. Please try again.';
    }
}

$showNavbar = false;
require_once __DIR__ . '/includes/header.php';
?>
<div class="auth-split min-vh-100 d-flex">
    <div class="auth-brand d-none d-lg-flex flex-column justify-content-between p-5 text-white">
        <div>
            <h2 class="fw-bold"><i class="bi bi-calendar-check"></i> WorkerBook</h2>
            <p class="lead mt-3">Complete your registration</p>
        </div>
        <div>
            <p class="small opacity-75">One-time fee: ₹50<br>Start accepting bookings instantly</p>
        </div>
    </div>
    <div class="auth-form flex-grow-1 d-flex align-items-center justify-content-center p-4 bg-light">
        <div class="card shadow-sm border-0" style="max-width: 500px; width: 100%;">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <h3 class="fw-bold">Registration Fee</h3>
                    <p class="text-muted">Welcome, <?= sanitize($user['name']) ?>!</p>
                    <div class="display-4 fw-bold text-primary">₹50</div>
                    <p class="text-muted">One-time online payment to start accepting bookings</p>
                </div>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success"><?= $success ?></div>
                    <?php if ($receiptNumber): ?>
                        <div class="card bg-light border-0">
                            <div class="card-body">
                                <h6 class="fw-bold">Payment Receipt</h6>
                                <p class="mb-1"><strong>Receipt No:</strong> <?= $receiptNumber ?></p>
                                <p class="mb-1"><strong>Amount:</strong> ₹50.00</p>
                                <p class="mb-1"><strong>Status:</strong> Completed</p>
                                <p class="mb-0"><strong>Date:</strong> <?= date('d M Y H:i') ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                    <a href="http://localhost/WBS/auth/login.php" class="btn btn-primary w-100 mt-3">Proceed to Login</a>
                <?php else: ?>
                    <form method="POST" novalidate>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Transaction ID / Reference No.</label>
                            <input type="text" name="transaction_id" class="form-control" placeholder="e.g. UPI123456 or Bank Ref No." required>
                        </div>
                        <div class="alert alert-info"><i class="bi bi-info-circle"></i> Complete the payment of ₹50 via UPI/Card and enter the transaction reference below. Your account will be activated automatically.</div>
                        <button type="submit" class="btn btn-success w-100 py-2">Pay ₹50 & Activate Account</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>