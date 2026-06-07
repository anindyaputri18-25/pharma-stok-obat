<?php
/**
 * log_aktivitas.php
 * Helper: catat setiap aksi user ke tabel activity_log
 * Include file ini setelah koneksi.php & autentikasi.php
 */

function catat_log($koneksi, $aksi, $detail = '', $id_apotek = null) {
    $username   = $_COOKIE['users'] ?? 'Publik';
    $role       = $_COOKIE['role']  ?? 'Publik';
    $ip         = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    $username   = mysqli_real_escape_string($koneksi, $username);
    $role       = mysqli_real_escape_string($koneksi, $role);
    $aksi       = mysqli_real_escape_string($koneksi, $aksi);
    $detail     = mysqli_real_escape_string($koneksi, $detail);
    $ip         = mysqli_real_escape_string($koneksi, $ip);
    $id_apotek  = $id_apotek ? (int)$id_apotek : 'NULL';

    mysqli_query($koneksi,
        "INSERT INTO activity_log (id_apotek, username, role, aksi, detail, ip_address)
         VALUES ($id_apotek, '$username', '$role', '$aksi', '$detail', '$ip')"
    );
}

/**
 * Ambil id_apotek user yang sedang login dari tabel users
 */
function get_id_apotek_user($koneksi) {
    if (!isset($_COOKIE['users'])) return null;
    $username = mysqli_real_escape_string($koneksi, $_COOKIE['users']);
    $res = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT id_apotek FROM users WHERE username='$username' LIMIT 1"));
    return $res['id_apotek'] ?? null;
}

/**
 * Ambil data apotek berdasarkan id
 */
function get_apotek($koneksi, $id_apotek) {
    if (!$id_apotek) return null;
    $id = (int)$id_apotek;
    return mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM apotek WHERE id='$id' LIMIT 1"));
}