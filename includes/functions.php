<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';

/* =========================
   URL HELPER
========================= */
function url($path = '') {
    return BASE_URL . ltrim($path, '/');
}

/* =========================
   SANITIZE
========================= */
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/* =========================
   REDIRECT (SAFE)
========================= */
function redirect($path) {
    if (!str_starts_with($path, 'http')) {
        $path = BASE_URL . ltrim($path, '/');
    }
    header('Location: ' . $path);
    exit;
}

/* =========================
   FLASH
========================= */
function flashMessage($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

function getFlash() {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

/* =========================
   FORMATTERS
========================= */
function formatCurrency($amount) {
    return '₹' . number_format($amount, 2);
}

function formatDate($date) {
    return date('d M Y', strtotime($date));
}

function timeAgo($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    if ($diff->days > 0) return $diff->days . ' days ago';
    if ($diff->h > 0) return $diff->h . ' hours ago';
    return $diff->i . ' minutes ago';
}

/* =========================
   STATUS BADGE
========================= */
function statusBadge($status) {
    $map = [
        'pending' => 'warning',
        'accepted' => 'info',
        'rejected' => 'danger',
        'completed' => 'success',
        'cancelled' => 'secondary',
        'active' => 'success',
        'inactive' => 'secondary',
        'blocked' => 'danger'
    ];

    return '<span class="badge bg-' . ($map[$status] ?? 'secondary') . '">' . ucfirst($status) . '</span>';
}

/* =========================
   TOKEN
========================= */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}