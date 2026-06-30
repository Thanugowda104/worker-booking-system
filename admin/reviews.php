<?php
// admin/reviews.php
require_once __DIR__ . '/../includes/auth.php';
requireAuth('admin');
require_once __DIR__ . '/../includes/functions.php';
$user = getUser();
$showNavbar = true;
require_once __DIR__ . '/../includes/header.php';

$stmt = $pdo->query("SELECT r.*, cu.name as customer, wu.name as worker FROM reviews r JOIN users cu ON r.customer_id = cu.id JOIN users wu ON r.worker_id = wu.id ORDER BY r.created_at DESC");
$reviews = $stmt->fetchAll();
?>
<div class="container-fluid py-4">
    <h2 class="mb-4">Reviews</h2>
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light"><tr><th>Customer</th><th>Worker</th><th>Rating</th><th>Comment</th><th>Date</th></tr></thead>
                    <tbody>
                        <?php foreach ($reviews as $r): ?>
                        <tr>
                            <td><?= sanitize($r['customer']) ?></td>
                            <td><?= sanitize($r['worker']) ?></td>
                            <td>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="bi bi-star<?= $i <= $r['rating'] ? '-fill text-warning' : ' text-muted' ?>"></i>
                                <?php endfor; ?>
                            </td>
                            <td><small><?= sanitize($r['comment']) ?></small></td>
                            <td><?= formatDate($r['created_at']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if (empty($reviews)): ?><div class="text-center py-5 text-muted">No reviews yet.</div><?php endif; ?>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>