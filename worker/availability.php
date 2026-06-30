<?php
// worker/availability.php
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

$slotError = '';
$slotSuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_slot') {
    $date = sanitize($_POST['available_date'] ?? '');
    $start = sanitize($_POST['start_time'] ?? '');
    $end = sanitize($_POST['end_time'] ?? '');
    if (empty($date) || empty($start) || empty($end)) {
        $slotError = 'All fields are required.';
    } elseif ($start >= $end) {
        $slotError = 'End time must be after start time.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO availability (worker_id, available_date, start_time, end_time) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user['id'], $date, $start, $end]);
            $slotSuccess = 'Slot added successfully.';
        } catch (Exception $e) {
            $slotError = 'Failed to add slot. Possibly a duplicate.';
        }
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM availability WHERE id = ? AND worker_id = ? AND is_booked = 0");
    $stmt->execute([$id, $user['id']]);
    flashMessage('danger', $stmt->rowCount() ? 'Slot deleted.' : 'Cannot delete booked slot.');
    redirect('http://localhost/WBS/worker/availability.php');
}

$stmt = $pdo->prepare("SELECT * FROM availability WHERE worker_id = ? ORDER BY available_date DESC, start_time DESC");
$stmt->execute([$user['id']]);
$slots = $stmt->fetchAll();
?>
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-5">
            <h2 class="mb-4">Add Availability</h2>
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <?php if ($slotError): ?><div class="alert alert-danger"><?= $slotError ?></div><?php endif; ?>
                    <?php if ($slotSuccess): ?><div class="alert alert-success"><?= $slotSuccess ?></div><?php endif; ?>
                    <form method="POST">
                        <input type="hidden" name="action" value="add_slot">
                        <div class="mb-3">
                            <label class="form-label">Date</label>
                            <input type="date" name="available_date" class="form-control" required min="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="row g-2">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Start Time</label>
                                <input type="time" name="start_time" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">End Time</label>
                                <input type="time" name="end_time" class="form-control" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-plus-circle"></i> Add Slot</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <h2 class="mb-4">My Slots</h2>
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light"><tr><th>Date</th><th>Time</th><th>Status</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php foreach ($slots as $slot): ?>
                            <tr>
                                <td><?= formatDate($slot['available_date']) ?></td>
                                <td><?= $slot['start_time'] ?> - <?= $slot['end_time'] ?></td>
                                <td><?= $slot['is_booked'] ? '<span class="badge bg-success">Booked</span>' : '<span class="badge bg-secondary">Open</span>' ?></td>
                                <td>
                                    <?php if (!$slot['is_booked']): ?>
                                        <a href="http://localhost/WBS/worker/availability.php?action=delete&id=<?= $slot['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this open slot?')"><i class="bi bi-trash"></i></a>
                                    <?php else: ?>
                                        <span class="text-muted small">Locked</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if (empty($slots)): ?>
                        <div class="text-center py-5 text-muted">No slots created yet.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>