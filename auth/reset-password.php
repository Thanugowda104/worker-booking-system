<?php
// auth/reset-password.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$error = '';
$success = '';

$token = $_GET['token'] ?? '';
$valid = false;

if (empty($token)) {
    $error = 'Invalid or expired reset token.';
} else {

    // STEP 1: only fetch token (NO TIME CHECK IN SQL)
    $stmt = $pdo->prepare("
        SELECT pr.*, u.email
        FROM password_resets pr
        JOIN users u ON pr.user_id = u.id
        WHERE pr.token = ?
    ");
    $stmt->execute([$token]);
    $reset = $stmt->fetch();

    // STEP 2: validate in PHP (IMPORTANT FIX)
    if ($reset) {

        if ($reset['used'] == 1) {
            $error = 'This reset link has already been used.';
        }
        elseif (strtotime($reset['expires_at']) < time()) {
            $error = 'This reset link has expired.';
        }
        else {
            $valid = true;

            // STEP 3: handle password update
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {

                $password = $_POST['password'] ?? '';
                $confirmPassword = $_POST['confirm_password'] ?? '';

                if (empty($password) || empty($confirmPassword)) {
                    $error = 'Both fields are required.';
                }
                elseif ($password !== $confirmPassword) {
                    $error = 'Passwords do not match.';
                }
                elseif (strlen($password) < 6) {
                    $error = 'Password must be at least 6 characters.';
                }
                else {

                    $hashed = password_hash($password, PASSWORD_DEFAULT);

                    try {
                        $pdo->beginTransaction();

                        // update password
                        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                        $stmt->execute([$hashed, $reset['user_id']]);

                        // mark token used
                        $stmt = $pdo->prepare("UPDATE password_resets SET used = 1 WHERE id = ?");
                        $stmt->execute([$reset['id']]);

                        $pdo->commit();

                        flashMessage('success', 'Password reset successful! Please log in.');

                        header('Location: ' . url('auth/login.php'));
                        exit;

                    } catch (Exception $e) {
                        $pdo->rollBack();
                        $error = 'Reset failed. Please try again.';
                    }
                }
            }
        }

    } else {
        $error = 'Invalid reset token.';
    }
}

$showNavbar = false;
require_once __DIR__ . '/../includes/header.php';
?>

<!-- ================= RESET PASSWORD UI ================= -->
<div class="auth-split min-vh-100 d-flex">

    <div class="auth-brand d-none d-lg-flex flex-column justify-content-between p-5 text-white">
        <div>
            <h2 class="fw-bold">
                <i class="bi bi-calendar-check"></i> WorkerBook
            </h2>
            <p class="lead mt-3">Set your new password securely</p>
        </div>
        <div>
            <p class="small opacity-75">Choose a strong password</p>
        </div>
    </div>

    <div class="auth-form flex-grow-1 d-flex align-items-center justify-content-center p-4 bg-light">

        <div class="card shadow-sm border-0" style="max-width: 420px; width: 100%;">

            <div class="card-body p-4 p-md-5">

                <div class="text-center mb-4">
                    <h3 class="fw-bold">Reset password</h3>
                    <p class="text-muted">Enter your new password</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <?php if ($valid): ?>
                    <form method="POST">

                        <div class="mb-3">
                            <label class="form-label">New password</label>
                            <input type="password" name="password" class="form-control" required minlength="6">
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Confirm password</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2">
                            Reset password
                        </button>

                    </form>
                <?php endif; ?>

                <p class="text-center text-muted mt-4 mb-0">
                    <a href="<?= url('auth/login.php') ?>" class="text-decoration-none">
                        Back to login
                    </a>
                </p>

            </div>
        </div>

    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>