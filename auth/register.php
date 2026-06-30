<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$error = '';

/* =========================
   ALREADY LOGGED IN
========================= */
if (isLoggedIn()) {
    header('Location: ' . url($_SESSION['role'] . '/dashboard.php'));
    exit;
}

/* =========================
   REGISTER PROCESS
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $role = sanitize($_POST['role'] ?? 'customer');

    if (!in_array($role, ['customer', 'worker', 'admin'])) {
        $error = 'Invalid role selected.';
    }
    elseif (empty($name) || empty($email) || empty($password) || empty($confirmPassword)) {
        $error = 'All fields are required.';
    }
    elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    }
    elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    }
    else {

        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $error = 'Email already registered.';
        } else {

            $hashed = password_hash($password, PASSWORD_DEFAULT);

            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare("
                    INSERT INTO users (name, email, password, role, email_verified, payment_status, status)
                    VALUES (?, ?, ?, ?, 0, 'pending', 'active')
                ");
                $stmt->execute([$name, $email, $hashed, $role]);

                $userId = $pdo->lastInsertId();

                // Create worker profile if worker
                if ($role === 'worker') {
                    $stmt = $pdo->prepare("
                        INSERT INTO worker_profiles (user_id, bio, hourly_rate, experience_years)
                        VALUES (?, '', 0.00, 0)
                    ");
                    $stmt->execute([$userId]);
                }

                $pdo->commit();

                // Redirect logic
                if ($role === 'worker') {
                    header('Location: ' . url('payment.php?user_id=' . $userId));
                    exit;
                } else {
                    header('Location: ' . url('auth/login.php'));
                    exit;
                }

            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}

$showNavbar = false;
require_once __DIR__ . '/../includes/header.php';
?>

<!-- ================= REGISTER UI ================= -->
<div class="auth-split min-vh-100 d-flex">

    <!-- LEFT SIDE -->
    <div class="auth-brand d-none d-lg-flex flex-column justify-content-between p-5 text-white">
        <div>
            <h2 class="fw-bold">
                <i class="bi bi-calendar-check"></i> WorkerBook
            </h2>
            <p class="lead mt-3">
                Join our network of trusted professionals.
            </p>
        </div>
        <div>
            <p class="small opacity-75">
                Get started in minutes<br>
                Start booking or earning today
            </p>
        </div>
    </div>

    <!-- RIGHT SIDE -->
    <div class="auth-form flex-grow-1 d-flex align-items-center justify-content-center p-4 bg-light">

        <div class="card shadow-sm border-0" style="max-width: 420px; width: 100%;">

            <div class="card-body p-4 p-md-5">

                <div class="text-center mb-4">
                    <h3 class="fw-bold">Create account</h3>
                    <p class="text-muted">Choose your role and get started</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>

                <form method="POST">

                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-control"
                               value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control"
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required minlength="6">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">I want to</label>
                        <select name="role" class="form-select" required>
                            <option value="customer" <?= (($_POST['role'] ?? '') === 'customer') ? 'selected' : '' ?>>
                                Book services (Customer)
                            </option>
                            <option value="worker" <?= (($_POST['role'] ?? '') === 'worker') ? 'selected' : '' ?>>
                                Offer services (Worker)
                            </option>
                            <option value="admin" <?= (($_POST['role'] ?? '') === 'admin') ? 'selected' : '' ?>>
                                Admin Panel
                            </option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2">
                        Register
                    </button>

                </form>

                <p class="text-center text-muted mt-4 mb-0">
                    Already have an account?
                    <a href="<?= url('auth/login.php') ?>">Login</a>
                </p>

            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>