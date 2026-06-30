<?php
// admin/bookings.php - all bookings across platform
require_once __DIR__ . '/../includes/auth.php';
requireAuth('admin');
require_once __DIR__ . '/../includes/functions.php';

$user = getUser();
$showNavbar = true;
require_once __DIR__ . '/../includes/header.php';

$status = $_GET['status'] ?? 'all';
$validStatus = ['all','pending','accepted','rejected','completed','cancelled'];

if (!in_array($status, $validStatus)) {
    $status = 'all';
}

$sql = "SELECT b.*, cu.name as customer, wu.name as worker, sc.name as service 
        FROM bookings b 
        JOIN users cu ON b.customer_id = cu.id 
        JOIN users wu ON b.worker_id = wu.id 
        LEFT JOIN service_categories sc ON b.category_id = sc.id";

$params = [];

if ($status !== 'all') {
    $sql .= " WHERE b.status = ?";
    $params[] = $status;
}

$sql .= " ORDER BY b.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();
?>

<div class="container-fluid py-4">
    <h2 class="mb-4">All Bookings</h2>

    <ul class="nav nav-pills mb-4" id="bookingTabs">
        <li class="nav-item"><a class="nav-link <?= $status === 'all' ? 'active' : '' ?>" href="?status=all">All</a></li>
        <li class="nav-item"><a class="nav-link <?= $status === 'pending' ? 'active' : '' ?>" href="?status=pending">Pending</a></li>
        <li class="nav-item"><a class="nav-link <?= $status === 'accepted' ? 'active' : '' ?>" href="?status=accepted">Accepted</a></li>
        <li class="nav-item"><a class="nav-link <?= $status === 'completed' ? 'active' : '' ?>" href="?status=completed">Completed</a></li>
        <li class="nav-item"><a class="nav-link <?= $status === 'cancelled' ? 'active' : '' ?>" href="?status=cancelled">Cancelled</a></li>
    </ul>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive mobile-table">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Code</th>
                            <th>Customer</th>
                            <th>Worker</th>
                            <th>Service</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($bookings as $b): ?>
                        <tr>
                            <td class="fw-semibold"><?= $b['booking_code'] ?></td>
                            <td><?= sanitize($b['customer']) ?></td>
                            <td><?= sanitize($b['worker']) ?></td>
                            <td><?= sanitize($b['service'] ?? '-') ?></td>
                            <td><?= statusBadge($b['status']) ?></td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary view-detail"
                                    data-booking='<?= htmlspecialchars(json_encode($b), ENT_QUOTES) ?>'>
                                    View
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if (empty($bookings)): ?>
                <div class="text-center py-5 text-muted">No bookings found.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Booking Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body" id="detailBody"></div>

        </div>
    </div>
</div>

<!-- ✅ FIXED JAVASCRIPT -->
<script>
document.addEventListener("DOMContentLoaded", function () {

    document.querySelectorAll(".view-detail").forEach(btn => {
        btn.addEventListener("click", function () {

            const d = JSON.parse(this.dataset.booking);

            document.getElementById("detailBody").innerHTML = `
                <dl class="row">
                    <dt class="col-sm-4">Code</dt><dd class="col-sm-8">${d.booking_code}</dd>
                    <dt class="col-sm-4">Customer</dt><dd class="col-sm-8">${d.customer}</dd>
                    <dt class="col-sm-4">Worker</dt><dd class="col-sm-8">${d.worker}</dd>
                    <dt class="col-sm-4">Service</dt><dd class="col-sm-8">${d.service || "-"}</dd>
                    <dt class="col-sm-4">Type</dt><dd class="col-sm-8">${(d.booking_type || "").toUpperCase()}</dd>
                    <dt class="col-sm-4">Status</dt><dd class="col-sm-8">
                        <span class="badge bg-${
                            {pending:"warning",accepted:"info",rejected:"danger",completed:"success",cancelled:"secondary"}[d.status]
                        }">${d.status}</span>
                    </dd>
                    <dt class="col-sm-4">Date/Time</dt>
                    <dd class="col-sm-8">
                        ${d.scheduled_date ? d.scheduled_date+" "+d.scheduled_start+"-"+d.scheduled_end : "Request"}
                    </dd>
                    <dt class="col-sm-4">Amount</dt>
                    <dd class="col-sm-8">₹${parseFloat(d.total_amount || 0).toFixed(2)}</dd>
                    <dt class="col-sm-4">Notes</dt>
                    <dd class="col-sm-8">${d.notes || "-"}</dd>
                    <dt class="col-sm-4">Created</dt>
                    <dd class="col-sm-8">${d.created_at}</dd>
                </dl>
            `;

            new bootstrap.Modal(document.getElementById("detailModal")).show();
        });
    });

});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>