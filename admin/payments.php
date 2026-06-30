<?php
// admin/payments.php - Worker payments & receipts (online only)
require_once __DIR__ . '/../includes/auth.php';
requireAuth('admin');
require_once __DIR__ . '/../includes/functions.php';

$user = getUser();
$showNavbar = true;
require_once __DIR__ . '/../includes/header.php';

$status = $_GET['status'] ?? 'all';
$validStatus = ['all','completed','pending','failed'];

if (!in_array($status, $validStatus)) {
    $status = 'all';
}

$sql = "SELECT p.*, u.name as worker_name, u.email, u.status as user_status 
        FROM payments p 
        JOIN users u ON p.user_id = u.id 
        WHERE u.role = 'worker'";

$params = [];

if ($status !== 'all') {
    $sql .= " AND p.payment_status = ?";
    $params[] = $status;
}

$sql .= " ORDER BY p.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$payments = $stmt->fetchAll();
?>

<div class="container-fluid py-4">
    <h2 class="mb-4">Worker Payments & Receipts</h2>

    <ul class="nav nav-pills mb-4" id="payTabs">
        <li class="nav-item"><a class="nav-link <?= $status === 'all' ? 'active' : '' ?>" href="?status=all">All</a></li>
        <li class="nav-item"><a class="nav-link <?= $status === 'completed' ? 'active' : '' ?>" href="?status=completed">Completed</a></li>
        <li class="nav-item"><a class="nav-link <?= $status === 'pending' ? 'active' : '' ?>" href="?status=pending">Pending</a></li>
    </ul>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Receipt No</th>
                            <th>Worker</th>
                            <th>Email</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($payments as $p): ?>
                        <tr>
                            <td class="fw-semibold"><?= $p['receipt_number'] ?></td>
                            <td><?= sanitize($p['worker_name']) ?></td>
                            <td><?= sanitize($p['email']) ?></td>
                            <td>₹<?= number_format($p['amount'], 2) ?></td>
                            <td><?= statusBadge($p['payment_status']) ?></td>
                            <td>
                                <?= $p['paid_at'] ? formatDate($p['paid_at']) : formatDate($p['created_at']) ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary view-receipt"
                                    data-receipt="<?= htmlspecialchars($p['receipt_number'], ENT_QUOTES) ?>">
                                    Receipt
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>

                </table>
            </div>

            <?php if (empty($payments)): ?>
                <div class="text-center py-5 text-muted">No payment records found.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="receiptModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Payment Receipt</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body" id="receiptBody"></div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="window.print()">Print</button>
            </div>

        </div>
    </div>
</div>

<!-- ✅ FIXED JS -->
<script>
document.addEventListener("DOMContentLoaded", function () {

    document.querySelectorAll(".view-receipt").forEach(btn => {
        btn.addEventListener("click", function () {

            const receiptNumber = this.dataset.receipt;

            fetch("actions.php", {
                method: "POST",
                headers: {"Content-Type": "application/json"},
                body: JSON.stringify({
                    action: "get_receipt",
                    receipt_number: receiptNumber
                })
            })
            .then(r => r.json())
            .then(data => {

                if (!data.success) {
                    alert(data.message || "Error loading receipt");
                    return;
                }

                const d = data.receipt;

                document.getElementById("receiptBody").innerHTML = `
                    <div class="border p-4 bg-light rounded">

                        <div class="text-center mb-3">
                            <h4 class="fw-bold">WorkerBook</h4>
                            <p class="mb-0">Worker Booking System</p>
                        </div>

                        <hr>

                        <dl class="row">
                            <dt class="col-sm-5">Receipt Number</dt><dd class="col-sm-7">${d.receipt_number}</dd>
                            <dt class="col-sm-5">Worker Name</dt><dd class="col-sm-7">${d.name}</dd>
                            <dt class="col-sm-5">Email</dt><dd class="col-sm-7">${d.email}</dd>
                            <dt class="col-sm-5">Amount</dt><dd class="col-sm-7 fw-bold">₹${parseFloat(d.amount).toFixed(2)}</dd>
                            <dt class="col-sm-5">Method</dt><dd class="col-sm-7">ONLINE</dd>
                            <dt class="col-sm-5">Transaction ID</dt><dd class="col-sm-7">${d.transaction_id || "N/A"}</dd>
                            <dt class="col-sm-5">Status</dt><dd class="col-sm-7">${d.payment_status.toUpperCase()}</dd>
                            <dt class="col-sm-5">Date</dt><dd class="col-sm-7">${d.paid_at || "N/A"}</dd>
                        </dl>

                    </div>
                `;

                new bootstrap.Modal(document.getElementById("receiptModal")).show();

            });

        });
    });

});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>