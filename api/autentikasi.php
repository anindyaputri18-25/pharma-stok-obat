<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['initiated'])) {
    session_regenerate_id(true);
    $_SESSION['initiated'] = true;
}

// Mengambil URL saat ini untuk pengecekan halaman
$current_url = $_SERVER['REQUEST_URI'];

if (!isset($_SESSION['users'])) {
    // Jika tidak ada session, dan tidak sedang di halaman login/register/index
    if (strpos($current_url, 'login') === false && 
        strpos($current_url, 'register') === false && 
        $current_url !== '/' && 
        $current_url !== '/index.html') {
        
        header("Location: /login");
        exit();
    }
} else {
    $role_saat_ini = $_SESSION['role'] ?? null;
    $users = $_SESSION['users'] ?? null;

    // Cegah user yang sudah login mengakses halaman login/register kembali
    if (strpos($current_url, 'login') !== false || strpos($current_url, 'register') !== false) {
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