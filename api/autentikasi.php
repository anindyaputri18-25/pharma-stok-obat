<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['initiated'])) {
    session_regenerate_id(true);
    $_SESSION['initiated'] = true;
}

// CARA PALING AMAN UNTUK VERCEL:
// Kita cek apakah URL saat ini mengandung kata 'login' atau 'register'
$current_url = $_SERVER['REQUEST_URI'];

if (!isset($_SESSION['users'])) {
    // Jika tidak ada session, dan TIDAK sedang di halaman login/register/index
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

    // Jika SUDAH login tapi malah buka halaman login/register
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