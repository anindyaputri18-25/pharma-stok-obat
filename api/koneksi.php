<?php
$host = 'gateway01.ap-southeast-1.prod.alicloud.tidbcloud.com';
$user = '2GjP4Lr35m6Tk9h.root';
$pass = 'dzRgf8oVK3jkjYNx';
$db   = 'stok_obat_db';
$port = 4000;

$koneksi = mysqli_init();

mysqli_ssl_set($koneksi, NULL, NULL, NULL, NULL, NULL);

$real_connect = mysqli_real_connect(
    $koneksi,
    $host,
    $user,
    $pass,
    $db,
    $port,
    NULL,
    MYSQLI_CLIENT_SSL
);

if (!$real_connect) {
    die("Koneksi ke TiDB Cloud gagal: " . mysqli_connect_error());
}

mysqli_set_charset($koneksi, 'utf8mb4');
?>