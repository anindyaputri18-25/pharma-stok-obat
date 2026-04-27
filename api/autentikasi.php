<?php
$current_file = basename($_SERVER['PHP_SELF']);
$public_pages = ['login.php', 'register.php', 'index.php', 'index.html'];

if (!isset($_COOKIE['users'])) {
    if (!in_array($current_file, $public_pages)) {
        header("Location: login.php");
        exit();
    }
} else {
    $role_saat_ini = $_COOKIE['role'] ?? null;
    $users         = $_COOKIE['users'] ?? null;

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