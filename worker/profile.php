<?php
// worker/profile.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

$user = getUser();

if (!$user || $user['role'] !== 'worker' || $user['payment_status'] !== 'paid') {
    if (!$user || $user['role'] !== 'worker') {
        header('Location: http://localhost/WBS/auth/login.php');
        exit;
    }
    flashMessage('warning', 'Please complete your registration payment first.');
    redirect('http://localhost/WBS/payment.php?user_id=' . $user['id']);
}

// ✅ Load service categories
$stmt = $pdo->prepare("SELECT * FROM service_categories WHERE is_active = 1 ORDER BY name");
$stmt->execute();
$categories = $stmt->fetchAll();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bio = sanitize($_POST['bio'] ?? '');
    $rate = (float)($_POST['hourly_rate'] ?? 0);
    $exp = (int)($_POST['experience_years'] ?? 0);
    $category_id = (int)($_POST['category_id'] ?? 0);

    try {
        // NOTE: ensure your table has category_id column if you want to store it
        $stmt = $pdo->prepare("
            UPDATE worker_profiles 
            SET bio = ?, hourly_rate = ?, experience_years = ?, category_id = ?
            WHERE user_id = ?
        ");
        $stmt->execute([$bio, $rate, $exp, $category_id, $user['id']]);

        $message = 'Profile updated successfully.';
    } catch (Exception $e) {
        $message = 'Update failed.';
    }
}

$user = getUser();
$showNavbar = true;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4" style="max-width: 640px;">
    <h2 class="mb-4">Worker Profile</h2>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">

            <?php if ($message): ?>
                <div class="alert alert-success"><?= $message ?></div>
            <?php endif; ?>

            <form method="POST">

                <!-- Service Category Dropdown -->
                <div class="mb-3">
                    <label class="form-label">Select Your Service</label>
                    <select name="category_id" class="form-control" required>
                        <option value="">Choose Service</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"
                                <?= ($user['category_id'] ?? 0) == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Hourly Rate (₹)</label>
                    <input type="number" step="0.01" name="hourly_rate" class="form-control"
                        value="<?= htmlspecialchars($user['hourly_rate'] ?? '') ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Experience (Years)</label>
                    <input type="number" name="experience_years" class="form-control"
                        value="<?= htmlspecialchars($user['experience_years'] ?? '') ?>" required>
                </div>

                <div class="mb-4">
                    <label class="form-label">Bio</label>
                    <textarea name="bio" class="form-control" rows="5"
                        placeholder="Tell customers about yourself..."><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Save Changes</button>
            </form>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>