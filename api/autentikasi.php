<?php
/**
 * autentikasi.php (VERSI BARU - tambahkan role Super Admin)
 * GANTI file autentikasi.php yang lama dengan file ini
 */
$current_file  = basename($_SERVER['PHP_SELF']);
$public_pages  = ['login.php', 'register.php', 'index.php', 'index.html', 'landing.php'];

if (!isset($_COOKIE['users'])) {
    // Halaman publik boleh diakses tanpa login
    if (!in_array($current_file, $public_pages)) {
        header("Location: login.php");
        exit();
    }
} else {
    $role_saat_ini = $_COOKIE['role'] ?? null;
    $users         = $_COOKIE['users'] ?? null;

    // Jika sudah login dan mencoba akses login/register, redirect ke dashboard
    if (in_array($current_file, ['login.php', 'register.php'])) {
        if ($role_saat_ini === 'Pending') {
            header("Location: pending.php");
        } elseif ($role_saat_ini === 'Kasir') {
            header("Location: kasir_dashboard.php");
        } elseif ($role_saat_ini === 'Super Admin') {
            header("Location: super_admin_dashboard.php");
        } else {
            header("Location: dashboard.php");
        }
        exit();
    }

    // Redirect Super Admin ke dashboardnya jika mencoba akses dashboard biasa
    if ($current_file === 'dashboard.php' && $role_saat_ini === 'Super Admin') {
        header("Location: super_admin_dashboard.php");
        exit();
    }
}