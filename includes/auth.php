<?php
// includes/auth.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

/* =========================
   LOGIN CHECK
========================= */
function isLoggedIn() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    return !empty($_SESSION['user_id']) && !empty($_SESSION['role']);
}

/* =========================
   ROLE CHECK
========================= */
function requireAuth($role = null) {

    if (!isLoggedIn()) {
        header('Location: http://localhost/WBS/auth/login.php');
        exit;
    }

    if ($role && $_SESSION['role'] !== $role) {
        header('Location: http://localhost/WBS/' . $_SESSION['role'] . '/dashboard.php');
        exit;
    }
}

/* =========================
   GET USER DATA
========================= */
function getUser() {

    if (!isLoggedIn()) return null;

    global $pdo;

    $stmt = $pdo->prepare("
        SELECT u.*, 
               wp.bio,
               wp.hourly_rate,
               wp.experience_years,
               wp.avg_rating,
               wp.is_verified,
               u.payment_status
        FROM users u
        LEFT JOIN worker_profiles wp ON u.id = wp.user_id
        WHERE u.id = ?
    ");

    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

/* =========================
   BOOKING CODE GENERATOR
========================= */
function generateBookingCode($pdo) {

    $code = 'WB-' . strtoupper(uniqid());

    $stmt = $pdo->prepare("SELECT id FROM bookings WHERE booking_code = ?");
    $stmt->execute([$code]);

    if ($stmt->fetch()) {
        return generateBookingCode($pdo);
    }

    return $code;
}

/* =========================
   RECEIPT FETCH
========================= */
function getReceipt($pdo, $receiptNumber) {

    $stmt = $pdo->prepare("
        SELECT p.*, u.name, u.email
        FROM payments p
        JOIN users u ON p.user_id = u.id
        WHERE p.receipt_number = ?
    ");

    $stmt->execute([$receiptNumber]);
    return $stmt->fetch();
}