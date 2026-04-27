<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Regenerasi ID untuk keamanan jika baru mulai
if (!isset($_SESSION['initiated'])) {
    session_regenerate_id(true);
    $_SESSION['initiated'] = true;
}

$current_page = basename($_SERVER['PHP_SELF']);

// Jika belum login (session users kosong)
if (!isset($_SESSION['users'])) {
    if ($current_page !== 'login.php' && $current_page !== 'register.php' && $current_page !== 'index.html') {
        header("Location: login.php");
        exit();
    }
} else {
    // Pastikan variabel ini tersedia untuk semua file yang menyertakan autentikasi.php
    $role_saat_ini = $_SESSION['role'] ?? null;
    $users = $_SESSION['users'] ?? null;

    // Jika user mencoba akses login padahal sudah login, arahkan ke dashboard yang sesuai
    if ($current_page === 'login.php') {
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