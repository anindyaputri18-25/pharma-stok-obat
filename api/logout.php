<?php
include 'koneksi.php';

// Catat log logout sebelum hapus cookie
if (isset($_COOKIE['users'], $_COOKIE['role'])) {
    $uname = mysqli_real_escape_string($koneksi, $_COOKIE['users']);
    $urole = mysqli_real_escape_string($koneksi, $_COOKIE['role']);
    $ip    = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $res   = mysqli_fetch_assoc(mysqli_query($koneksi,
        "SELECT id_apotek FROM users WHERE username='$uname' LIMIT 1"));
    $ap_id = ($res && $res['id_apotek']) ? "'{$res['id_apotek']}'" : "NULL";
    mysqli_query($koneksi,
        "INSERT INTO activity_log (id_apotek, username, role, aksi, detail, ip_address)
         VALUES ($ap_id, '$uname', '$urole', 'Logout', 'Logout dari sistem', '$ip')"
    );
}

// Hapus semua cookie
setcookie('users',     '', time() - 3600, "/");
setcookie('role',      '', time() - 3600, "/");
setcookie('sa_apotek', '', time() - 3600, "/");

header("Location: login.php?pesan=logout");
exit();