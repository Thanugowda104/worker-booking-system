<?php
// auth/forgot-password.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = sanitize($_POST['email'] ?? '');

    if (empty($email)) {
        $error = 'Please enter your email.';
    } else {

        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {

            $token = generateToken();
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $stmt = $pdo->prepare("
                INSERT INTO password_resets (user_id, token, expires_at)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$user['id'], $token, $expires]);

            // ✅ FIXED: use url() instead of hardcoded localhost
            $resetLink = url('auth/reset-password.php?token=' . $token);

            $message = 'Reset link generated! (Simulated email)<br>
                        <a href="' . $resetLink . '" class="alert-link">
                        Click here to reset your password</a>';

        } else {
            $error = 'No account found with that email.';
        }
    }
}

$showNavbar = false;
require_once __DIR__ . '/../includes/header.php';
?>

<!-- ================= FORGOT PASSWORD UI ================= -->
<div class="auth-split min-vh-100 d-flex">

    <!-- LEFT SIDE -->
    <div class="auth-brand d-none d-lg-flex flex-column justify-content-between p-5 text-white">
        <div>
            <h2 class="fw-bold">
                <i class="bi bi-calendar-check"></i> WorkerBook
            </h2>
            <p class="lead mt-3">
                Forgot your password?<br>
                No worries, we'll help you reset it.
            </p>
        </div>
        <div>
            <p class="small opacity-75">
                Enter your email and we'll generate a reset link
            </p>
        </div>
    </div>

    <!-- RIGHT SIDE -->
    <div class="auth-form flex-grow-1 d-flex align-items-center justify-content-center p-4 bg-light">

        <div class="card shadow-sm border-0" style="max-width: 420px; width: 100%;">

            <div class="card-body p-4 p-md-5">

                <div class="text-center mb-4">
                    <h3 class="fw-bold">Forgot password</h3>
                    <p class="text-muted">We'll help you regain access</p>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-success"><?= $message ?></div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>

                <form method="POST">

                    <div class="mb-4">
                        <label class="form-label">Email address</label>
                        <input type="email" name="email" class="form-control"
                               required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2">
                        Send reset link
                    </button>

                </form>

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