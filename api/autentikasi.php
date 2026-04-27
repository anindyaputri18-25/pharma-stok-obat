<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Regenerasi ID untuk keamanan jika baru mulai
if (!isset($_SESSION['initiated'])) {
    session_regenerate_id(true);
    $_SESSION['initiated'] = true;
}
// Ganti bagian pengecekan ini di autentikasi.php
$current_page = basename($_SERVER['PHP_SELF']);

if (!isset($_SESSION['users'])) {
    // Pakai strpos agar lebih aman mendeteksi apakah kita di login atau register
    if (strpos($current_page, 'login') === false && strpos($current_page, 'register') === false && $current_page !== 'index.html') {
        header("Location: /login");
        exit();
    }
} else {
    // Variabel global untuk file lain
    $role_saat_ini = $_SESSION['role'] ?? null;
    $users = $_SESSION['users'] ?? null;

    // Jika user sudah login tapi maksa buka halaman login/register/index
    if ($current_page === 'login.php' || $current_page === 'register.php' || $current_page === 'index.html') {
        if ($role_saat_ini === 'Pending') {
            header("Location: /pending");
        } elseif ($role_saat_ini === 'Kasir') {
            header("Location: /kasir_dashboard");
        } else {
            header("Location: /dashboard");
        }
        exit();
    }
}
?>