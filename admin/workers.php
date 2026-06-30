<?php
// admin/workers.php
require_once __DIR__ . '/../includes/auth.php';
requireAuth('admin');
require_once __DIR__ . '/../includes/functions.php';
$user = getUser();
$showNavbar = true;
require_once __DIR__ . '/../includes/header.php';

$stmt = $pdo->query("SELECT u.id, u.name, u.email, u.status, wp.bio, wp.hourly_rate, wp.experience_years, wp.avg_rating, wp.is_verified FROM users u JOIN worker_profiles wp ON u.id = wp.user_id WHERE u.role = 'worker' ORDER BY u.name");
$workers = $stmt->fetchAll();
?>
<div class="container-fluid py-4">
    <h2 class="mb-4">Manage Workers</h2>
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light"><tr><th>Name</th><th>Email</th><th>Rate</th><th>Exp</th><th>Rating</th><th>Verified</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($workers as $w): ?>
                        <tr>
                            <td class="fw-semibold"><?= sanitize($w['name']) ?></td>
                            <td><?= sanitize($w['email']) ?></td>
                            <td>₹<?= number_format($w['hourly_rate'], 2) ?></td>
                            <td><?= $w['experience_years'] ?></td>
                            <td><?= $w['avg_rating'] ? number_format($w['avg_rating'], 1) : 'New' ?></td>
                            <td><?= $w['is_verified'] ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-danger">No</span>' ?></td>
                            <td><?= statusBadge($w['status']) ?></td>
                            <td>
                                <button class="btn btn-sm toggle-verify" data-id="<?= $w['id'] ?>" data-verified="<?= $w['is_verified'] ?>">
                                    <?= $w['is_verified'] ? 'Unverify' : 'Verify' ?>
                                </button>
                                <button class="btn btn-sm toggle-block" data-id="<?= $w['id'] ?>" data-status="<?= $w['status'] ?>">
                                    <?= $w['status'] === 'blocked' ? 'Unblock' : 'Block' ?>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if (empty($workers)): ?><div class="text-center py-5 text-muted">No workers registered yet.</div><?php endif; ?>
        </div>
    </div>
</div>
<?php $extraJS = '
document.querySelectorAll(".toggle-verify").forEach(b => b.addEventListener("click", () => {
    fetch("http://localhost/WBS/admin/actions.php", {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify({action: "toggle_verify", user_id: parseInt(b.dataset.id), is_verified: parseInt(b.dataset.verified)})
    }).then(r => r.json()).then(data => { if (data.success) location.reload(); else alert(data.message); });
}));
document.querySelectorAll(".toggle-block").forEach(b => b.addEventListener("click", () => {
    fetch("http://localhost/WBS/admin/actions.php", {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify({action: "toggle_block", user_id: parseInt(b.dataset.id), status: b.dataset.status})
    }).then(r => r.json()).then(data => { if (data.success) location.reload(); else alert(data.message); });
}));
';
require_once __DIR__ . '/../includes/footer.php'; ?>