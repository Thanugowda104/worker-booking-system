<?php
// customer/bookings.php
require_once __DIR__ . '/../includes/auth.php';
requireAuth('customer');
require_once __DIR__ . '/../includes/functions.php';

$user = getUser();
$showNavbar = true;

require_once __DIR__ . '/../includes/header.php';

$stmt = $pdo->prepare("
    SELECT 
        b.*,
        u.name AS worker_name,
        sc.name AS service_name
    FROM bookings b
    JOIN users u ON b.worker_id = u.id
    LEFT JOIN service_categories sc ON b.category_id = sc.id
    WHERE b.customer_id = ?
    ORDER BY b.created_at DESC
");

$stmt->execute([$user['id']]);
$bookings = $stmt->fetchAll();
?>

<div class="container-fluid py-4">
    <h2 class="mb-4">My Bookings</h2>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
           <div class="table-responsive mobile-table">

                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Code</th>
                            <th>Service</th>
                            <th>Worker</th>
                            <th>Type</th>
                            <th>Date/Time</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php foreach ($bookings as $b): ?>
                        <tr>

                            <td><?= htmlspecialchars($b['booking_code']) ?></td>
                            <td><?= htmlspecialchars($b['service_name'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($b['worker_name'] ?? '-') ?></td>

                            <td>
                                <span class="badge bg-secondary">
                                    <?= ucfirst($b['booking_type']) ?>
                                </span>
                            </td>

                            <td>
                                <?php if (!empty($b['scheduled_date'])): ?>
                                    <?= formatDate($b['scheduled_date']) ?><br>
                                    <small class="text-muted">
                                        <?= htmlspecialchars($b['scheduled_start']) ?> -
                                        <?= htmlspecialchars($b['scheduled_end']) ?>
                                    </small>
                                <?php else: ?>
                                    <small class="text-muted">Custom request</small>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?= statusBadge($b['status']) ?>
                            </td>

                            <td>

                                <?php if (in_array($b['status'], ['pending', 'accepted'])): ?>
                                    <button class="btn btn-sm btn-danger cancel-btn"
                                        data-id="<?= $b['id'] ?>">
                                        Cancel
                                    </button>
                                <?php endif; ?>

                                <?php if ($b['status'] === 'completed'): ?>

                                    <?php
                                    $stmt2 = $pdo->prepare("
                                        SELECT id FROM booking_payments WHERE booking_id = ?
                                    ");
                                    $stmt2->execute([$b['id']]);
                                    $hasPaid = (bool)$stmt2->fetch();
                                    ?>

                                    <?php if (!$hasPaid): ?>
                                        <a href="booking-payment.php?id=<?= $b['id'] ?>"
                                           class="btn btn-sm btn-warning">
                                            Pay Now
                                        </a>
                                    <?php else: ?>
                                        <span class="badge bg-success">Paid</span>
                                    <?php endif; ?>

                                    <button class="btn btn-sm btn-outline-warning review-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#reviewModal"
                                        data-id="<?= $b['id'] ?>"
                                        data-worker="<?= htmlspecialchars($b['worker_name']) ?>">
                                        Review
                                    </button>

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

<!-- REVIEW MODAL -->
<div class="modal fade" id="reviewModal" tabindex="-1">
<div class="modal-dialog">
<div class="modal-content">

    <div class="modal-header">
        <h5 class="modal-title">Leave Review</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
    </div>

    <div class="modal-body">

        <p id="workerNameText" class="fw-bold"></p>
        <input type="hidden" id="bookingId">

        <div class="mb-3">
            <label>Rating</label><br>

            <span class="star fs-3" data-value="1">★</span>
            <span class="star fs-3" data-value="2">★</span>
            <span class="star fs-3" data-value="3">★</span>
            <span class="star fs-3" data-value="4">★</span>
            <span class="star fs-3" data-value="5">★</span>

            <input type="hidden" id="rating" value="5">
        </div>

        <div class="mb-3">
            <label>Comment</label>
            <textarea id="comment" class="form-control"></textarea>
        </div>

    </div>

    <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button class="btn btn-primary" id="submitReview">Submit</button>
    </div>

</div>
</div>
</div>

<!-- CSS -->
<style>
.star {
    cursor: pointer;
    color: #ccc;
}
.star.active {
    color: gold;
}
</style>

<!-- JS -->
<script>
let selectedRating = 5;
let bookingId = 0;

document.querySelectorAll(".review-btn").forEach(btn => {
    btn.addEventListener("click", () => {
        bookingId = btn.dataset.id;
        selectedRating = 5;

        document.getElementById("bookingId").value = bookingId;
        document.getElementById("workerNameText").innerText =
            "Review for: " + btn.dataset.worker;

        updateStars();
    });
});

document.querySelectorAll(".star").forEach(star => {
    star.addEventListener("click", () => {
        selectedRating = star.dataset.value;
        document.getElementById("rating").value = selectedRating;
        updateStars();
    });
});

function updateStars() {
    document.querySelectorAll(".star").forEach(s => {
        s.classList.toggle("active", s.dataset.value <= selectedRating);
    });
}

document.getElementById("submitReview").addEventListener("click", () => {

    fetch("http://localhost/WBS/customer/actions.php", {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify({
            action: "submit_review",
            booking_id: bookingId,
            rating: selectedRating,
            comment: document.getElementById("comment").value
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert("Review submitted!");
            location.reload();
        } else {
            alert(data.message);
        }
    });

});

// CANCEL
document.querySelectorAll(".cancel-btn").forEach(btn => {
    btn.addEventListener("click", () => {
        if (!confirm("Cancel booking?")) return;

        fetch("http://localhost/WBS/customer/actions.php", {
            method: "POST",
            headers: {"Content-Type": "application/json"},
            body: JSON.stringify({
                action: "cancel_booking",
                booking_id: btn.dataset.id
            })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) location.reload();
            else alert(data.message);
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>