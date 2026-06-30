<?php
// includes/header.php
require_once __DIR__ . '/functions.php';

$flash = getFlash();
$user = getUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Worker Booking System</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Flatpickr -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <!-- Custom CSS -->
    <link href="http://localhost/WBS/assets/css/style.css" rel="stylesheet">
</head>

<body>

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show m-0 rounded-0" role="alert">
        <?= $flash['message'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($showNavbar) && $showNavbar && $user): ?>

<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm sticky-top">
    <div class="container-fluid px-3">

        <!-- Logo -->
        <a class="navbar-brand fw-bold text-primary"
           href="http://localhost/WBS/<?= $user['role'] ?>/dashboard.php">
            <i class="bi bi-calendar-check"></i> WorkerBook
        </a>

        <!-- Mobile Toggle -->
        <button class="navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#navMenu"
                aria-controls="navMenu"
                aria-expanded="false"
                aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navbar Menu -->
        <div class="collapse navbar-collapse" id="navMenu">

            <!-- Left Menu -->
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">

                <?php if ($user['role'] === 'customer'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="http://localhost/WBS/customer/dashboard.php">
                            Dashboard
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="http://localhost/WBS/customer/bookings.php">
                            My Bookings
                        </a>
                    </li>

                <?php elseif ($user['role'] === 'worker'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="http://localhost/WBS/worker/dashboard.php">
                            Dashboard
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="http://localhost/WBS/worker/bookings.php">
                            Bookings
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="http://localhost/WBS/worker/availability.php">
                            Availability
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="http://localhost/WBS/worker/profile.php">
                            Profile
                        </a>
                    </li>

                <?php elseif ($user['role'] === 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="http://localhost/WBS/admin/dashboard.php">
                            Dashboard
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="http://localhost/WBS/admin/bookings.php">
                            Bookings
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="http://localhost/WBS/admin/workers.php">
                            Workers
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="http://localhost/WBS/admin/categories.php">
                            Categories
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="http://localhost/WBS/admin/reviews.php">
                            Reviews
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="http://localhost/WBS/admin/payments.php">
                            Payments
                        </a>
                    </li>
                <?php endif; ?>

            </ul>

            <!-- Right Side -->
            <div class="d-lg-flex align-items-start align-items-lg-center gap-3 mt-3 mt-lg-0">

                <span class="text-muted small d-block">
                    <?= sanitize($user['name']) ?>
                    <small>(<?= ucfirst($user['role']) ?>)</small>
                </span>

                <a href="http://localhost/WBS/auth/logout.php"
                   class="btn btn-sm btn-outline-danger w-100 w-lg-auto">
                    Logout
                </a>

            </div>

        </div>
    </div>
</nav>

<?php endif; ?>

<main class="pb-4">