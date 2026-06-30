<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = sanitize($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = ?");
    $stmt->execute([$email, $role]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {

        if ($user['status'] === 'blocked') {
            $error = "Account blocked";
        } else {

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['name'];

            header("Location: " . BASE_URL . $role . "/dashboard.php");
            exit;
        }

    } else {
        $error = "Invalid login details";
    }
}
?>

<div class="container d-flex justify-content-center align-items-center min-vh-100">

    <form method="POST" class="card p-4 shadow" style="width:350px;">

        <h4 class="text-center mb-3"><?= ucfirst($role) ?> Login</h4>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <input type="email" name="email" class="form-control mb-2" placeholder="Email" required>

        <input type="password" name="password" class="form-control mb-3" placeholder="Password" required>

        <button class="btn btn-primary w-100">Login</button>

    </form>

</div>