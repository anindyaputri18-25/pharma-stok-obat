<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['initiated'])) {
    session_regenerate_id(true);
    $_SESSION['initiated'] = true;
}

$current_file = basename($_SERVER['PHP_SELF']);

$public_pages = ['login.php', 'register.php', 'index.php', 'index.html'];

if (!isset($_SESSION['users'])) {
    // Belum login, redirect ke login kecuali sedang di halaman publik
    if (!in_array($current_file, $public_pages)) {
        header("Location: login.php");
        exit();
    }
} else {
    $role_saat_ini = $_SESSION['role'] ?? null;
    $users         = $_SESSION['users'] ?? null;

    // Sudah login tapi coba akses login/register lagi → redirect sesuai role
    if (in_array($current_file, ['login.php', 'register.php'])) {
        if ($role_saat_ini === 'Pending') {
            header("Location: pending.php");
        } elseif ($role_saat_ini === 'Kasir') {
            header("Location: kasir_dashboard.php");
        } else {
            header("Location: dashboard.php");
        }
        exit();
    }
}
?>