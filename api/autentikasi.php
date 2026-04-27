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
    // Tambahkan pengecekan agar tidak loop saat di login/register
    if ($current_page !== 'login.php' && $current_page !== 'register.php' && $current_page !== 'index.html') {
        // Gunakan /login agar ditangkap oleh route vercel.json menuju /api/login.php
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