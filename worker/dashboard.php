<?php
// worker/dashboard.php
require_once __DIR__ . '/../includes/auth.php';
$user = getUser();
if (!$user || $user['role'] !== 'worker' || $user['payment_status'] !== 'paid') {
    if (!$user || $user['role'] !== 'worker') {
        header('Location: http://localhost/WBS/auth/login.php');
        exit;
    }
    flashMessage('warning', 'Please complete your registration payment first.');
    redirect('http://localhost/WBS/payment.php?user_id=' . $user['id']);
}
require_once __DIR__ . '/../includes/functions.php';
$showNavbar = true;
require_once __DIR__ . '/../includes/header.php';

$stats = [];
$stmt = $pdo->prepare("SELECT COUNT(*) as c FROM bookings WHERE worker_id = ?");
$stmt->execute([$user['id']]);
$stats['total'] = $stmt->fetch()['c'];

foreach (['pending','accepted','rejected','completed'] as $s) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as c FROM bookings WHERE worker_id = ? AND status = ?");
    $stmt->execute([$user['id'], $s]);
    $stats[$s] = $stmt->fetch()['c'];
}
?>
<div class="container-fluid py-4">
    <h2 class="mb-4">Worker Dashboard</h2>
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-4">
                    <i class="bi bi-briefcase fs-1 text-primary mb-2"></i>
                    <h3 class="fw-bold"><?= $stats['total'] ?></h3>
                    <p class="text-muted mb-0">Total Jobs</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-4">
                    <i class="bi bi-clock fs-1 text-warning mb-2"></i>
                    <h3 class="fw-bold"><?= $stats['pending'] ?></h3>
                    <p class="text-muted mb-0">Pending</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-4">
                    <i class="bi bi-check-circle fs-1 text-success mb-2"></i>
                    <h3 class="fw-bold"><?= $stats['accepted'] ?></h3>
                    <p class="text-muted mb-0">Accepted</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-4">
                    <i class="bi bi-x-circle fs-1 text-danger mb-2"></i>
                    <h3 class="fw-bold"><?= $stats['rejected'] ?></h3>
                    <p class="text-muted mb-0">Rejected</p>
                </div>
            </div>
        </div>
    </div>
    <div class="row g-3">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 fw-bold">Recent Applicants</div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light"><tr><th>Code</th><th>Customer</th><th>Status</th><th>Date</th></tr></thead>
                        <tbody>
                            <?php
                            $stmt = $pdo->prepare("SELECT b.booking_code, u.name as customer, b.status, b.created_at FROM bookings b JOIN users u ON b.customer_id = u.id WHERE b.worker_id = ? ORDER BY b.created_at DESC LIMIT 5");
                            $stmt->execute([$user['id']]);
                            while ($row = $stmt->fetch()): ?>
                            <tr>
                                <td><a href="http://localhost/WBS/worker/bookings.php#booking-<?= $row['booking_code'] ?>" class="text-decoration-none"><?= $row['booking_code'] ?></a></td>
                                <td><?= sanitize($row['customer']) ?></td>
                                <td><?= statusBadge($row['status']) ?></td>
                                <td><?= formatDate($row['created_at']) ?></td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="fw-bold">Quick Actions</h5>
                    <div class="d-grid gap-2 mt-3">
                        <a href="http://localhost/WBS/worker/bookings.php" class="btn btn-outline-primary"><i class="bi bi-list"></i> View All Bookings</a>
                        <a href="http://localhost/WBS/worker/availability.php" class="btn btn-outline-success"><i class="bi bi-calendar-plus"></i> Manage Availability</a>
                        <a href="http://localhost/WBS/worker/profile.php" class="btn btn-outline-secondary"><i class="bi bi-person"></i> Edit Profile</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>