<?php
// worker/bookings.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$user = getUser();

if (!$user || $user['role'] !== 'worker') {
    header('Location: http://localhost/WBS/auth/login.php');
    exit;
}

$showNavbar = true;
require_once __DIR__ . '/../includes/header.php';

$status = $_GET['status'] ?? 'all';
$validStatus = ['all','pending','accepted','rejected','completed'];

if (!in_array($status, $validStatus)) {
    $status = 'all';
}

$sql = "SELECT b.*, u.name as customer, sc.name as service
        FROM bookings b
        JOIN users u ON b.customer_id = u.id
        LEFT JOIN service_categories sc ON b.category_id = sc.id
        WHERE b.worker_id = ?";

$params = [$user['id']];

if ($status !== 'all') {
    $sql .= " AND b.status = ?";
    $params[] = $status;
}

$sql .= " ORDER BY b.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();
?>

<div class="container-fluid py-4">
    <h2 class="mb-4">My Bookings</h2>

    <ul class="nav nav-pills mb-4">
        <?php foreach ($validStatus as $s): ?>
            <li class="nav-item">
                <a class="nav-link <?= $status === $s ? 'active' : '' ?>"
                   href="?status=<?= $s ?>">
                    <?= ucfirst($s) ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive mobile-table">

                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Code</th>
                            <th>Customer</th>
                            <th>Service</th>
                            <th>Type</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Action</th>
                            <th>Payment</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php foreach ($bookings as $b): ?>
                        <tr id="row-<?= $b['id'] ?>">

                            <td class="fw-bold">
                                <?= htmlspecialchars($b['booking_code']) ?>
                            </td>

                            <td><?= htmlspecialchars($b['customer']) ?></td>

                            <td><?= htmlspecialchars($b['service'] ?? '-') ?></td>

                            <td>
                                <span class="badge bg-secondary">
                                    <?= ucfirst($b['booking_type']) ?>
                                </span>
                            </td>

                            <td>
                                <?php if ($b['scheduled_date']): ?>
                                    <?= htmlspecialchars($b['scheduled_date']) ?><br>
                                    <small class="text-muted">
                                        <?= htmlspecialchars($b['scheduled_start']) ?> - <?= htmlspecialchars($b['scheduled_end']) ?>
                                    </small>
                                <?php else: ?>
                                    <small class="text-muted">Request</small>
                                <?php endif; ?>
                            </td>

                            <td><?= statusBadge($b['status']) ?></td>

                            <!-- ACTION -->
                            <td>
                                <?php if ($b['status'] === 'pending'): ?>

                                    <button class="btn btn-success btn-sm action-btn"
                                            data-id="<?= $b['id'] ?>"
                                            data-status="accepted">
                                        Accept
                                    </button>

                                    <button class="btn btn-danger btn-sm action-btn"
                                            data-id="<?= $b['id'] ?>"
                                            data-status="rejected">
                                        Reject
                                    </button>

                                <?php elseif ($b['status'] === 'accepted'): ?>

                                    <button class="btn btn-primary btn-sm action-btn"
                                            data-id="<?= $b['id'] ?>"
                                            data-status="completed">
                                        Complete
                                    </button>

                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>

                            <!-- PAYMENT FIXED -->
                            <td>
                                <?php
                                $bp = $pdo->prepare("
                                    SELECT payment_status, amount, payment_method
                                    FROM booking_payments
                                    WHERE booking_id = ?
                                    ORDER BY id DESC LIMIT 1
                                ");
                                $bp->execute([$b['id']]);
                                $payment = $bp->fetch();

                                if ($payment && $payment['payment_status'] === 'completed'): ?>
                                    <span class="badge bg-success">
                                        Paid ₹<?= number_format($payment['amount'], 2) ?>
                                    </span>

                                <?php elseif ($payment && $payment['payment_status'] === 'pending'): ?>
                                    <span class="badge bg-warning text-dark">
                                        Cash Pending
                                    </span>

                                <?php else: ?>
                                    <span class="badge bg-secondary">Not Paid</span>
                                <?php endif; ?>
                            </td>

                        </tr>
                    <?php endforeach; ?>
                    </tbody>

                </table>

            </div>

            <?php if (empty($bookings)): ?>
                <div class="text-center py-5 text-muted">
                    No bookings found
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {

    document.querySelectorAll(".action-btn").forEach(btn => {
        btn.addEventListener("click", function () {

            const id = this.dataset.id;
            const status = this.dataset.status;

            if (!confirm("Are you sure you want to " + status + " this booking?")) return;

            fetch("http://localhost/WBS/worker/actions.php", {
                method: "POST",
                headers: {"Content-Type": "application/json"},
                body: JSON.stringify({
                    action: "update_booking",
                    booking_id: id,
                    status: status
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || "Failed");
                }
            })
            .catch(() => alert("Server error"));

        });
    });

});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>