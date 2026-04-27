<?php
// Data dari TiDB Cloud
$host = 'gateway01.ap-southeast-1.prod.alicloud.tidbcloud.com';
$user = '2GjP4Lr35m6Tk9h.root';
$pass = 'dzRgf8oVK3jkjYNx';
$db   = 'stok_obat_db';
$port = 4000;

// Inisialisasi mysqli
$koneksi = mysqli_init();

// Menambahkan pengaturan SSL (Wajib untuk TiDB Serverless)
// Format: mysqli_ssl_set($link, key, cert, ca, capath, cipher)
mysqli_ssl_set($koneksi, NULL, NULL, NULL, NULL, NULL);

// Melakukan koneksi
$real_connect = mysqli_real_connect(
    $koneksi,   // koneksi
    $host,      // host
    $user,      // user
    $pass,      // password
    $db,        // database
    $port,      // port
    NULL,       // socket
    MYSQLI_CLIENT_SSL // flag SSL
);

if (!$real_connect) {
    die("Koneksi ke TiDB Cloud gagal: " . mysqli_connect_error());
}
?>