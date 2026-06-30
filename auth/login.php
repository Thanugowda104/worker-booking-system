<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$error = '';
$role = $_GET['role'] ?? 'customer';

/* IF ALREADY LOGGED IN */
if (isLoggedIn()) {
    header('Location: ' . url($_SESSION['role'] . '/dashboard.php'));
    exit;
}

/* LOGIN PROCESS */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'customer';

    if (empty($email) || empty($password)) {
        $error = 'Email and password are required.';
    } else {

        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = ?");
        $stmt->execute([$email, $role]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {

            if ($user['status'] === 'blocked') {
                $error = 'Your account has been blocked. Contact admin.';
            } else {

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['name'] = $user['name'];

                if ($user['role'] === 'worker' && $user['payment_status'] !== 'paid') {
                    header('Location: ' . url('payment.php?user_id=' . $user['id']));
                    exit;
                }

                header('Location: ' . url($user['role'] . '/dashboard.php'));
                exit;
            }

        } else {
            $error = 'Invalid email or password.';
        }
    }
}

$showNavbar = false;
require_once __DIR__ . '/../includes/header.php';
?>

<!-- ================= LOGIN UI ================= -->
<div class="auth-split">

    <!-- LEFT SIDE -->
    <div class="auth-brand d-none d-lg-flex flex-column justify-content-center text-white p-5">
        <h2 class="fw-bold">WorkerBook</h2>
        <p class="mt-3">
            Find trusted home service professionals instantly.
        </p>
    </div>

    <!-- RIGHT SIDE -->
    <div class="auth-form d-flex align-items-center justify-content-center p-4">

        <div class="card shadow border-0" style="max-width: 420px; width:100%;">

            <div class="card-body p-4">

                <h3 class="text-center mb-3">Login</h3>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <!-- ROLE SWITCH -->
                <div class="d-flex justify-content-center gap-2 mb-3">
                    <a href="?role=customer" class="btn btn-sm btn-outline-primary <?= $role=='customer'?'active':'' ?>">Customer</a>
                    <a href="?role=worker" class="btn btn-sm btn-outline-success <?= $role=='worker'?'active':'' ?>">Worker</a>
                    <a href="?role=admin" class="btn btn-sm btn-outline-dark <?= $role=='admin'?'active':'' ?>">Admin</a>
                </div>

                <form method="POST">

                    <input type="hidden" name="role" value="<?= htmlspecialchars($role) ?>">

                    <div class="mb-3">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>

                    <div class="mb-2">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>

                    <!-- 🔥 FORGOT PASSWORD -->
                    <div class="text-end mb-3">
                        <a href="<?= url('auth/forgot-password.php') ?>" class="small text-decoration-none">
                            Forgot password?
                        </a>
                    </div>

                    <button class="btn btn-primary w-100">
                        Login as <?= ucfirst($role) ?>
                    </button>

                </form>

                <!-- REGISTER -->
                <p class="text-center text-muted mt-4 mb-0">
                    Don't have an account?
                    <a href="<?= url('auth/register.php') ?>">Create account</a>
                </p>

            </div>
        </div>

    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>