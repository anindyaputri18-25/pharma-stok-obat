<?php
session_start();
include 'koneksi.php';
include 'autentikasi.php';

// Hanya Admin dan Apoteker
if (!in_array($role_saat_ini, ['Admin', 'Apoteker'])) {
    header("Location: dashboard.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: racikan_obat.php");
    exit();
}

$id = (int)$_GET['id'];

// Cek apakah racikan ada
$cek = mysqli_query($koneksi, "SELECT id_racikan FROM racikan WHERE id_racikan = '$id'");
if (mysqli_num_rows($cek) == 0) {
    header("Location: racikan_obat.php?pesan=notfound");
    exit();
}

// Hapus detail dulu (jika tidak ada ON DELETE CASCADE)
mysqli_query($koneksi, "DELETE FROM racikan_detail WHERE id_racikan = '$id'");

// Hapus racikan utama
$query = mysqli_query($koneksi, "DELETE FROM racikan WHERE id_racikan = '$id'");

if ($query) {
    header("Location: racikan_obat.php?pesan=hapus");
} else {
    header("Location: racikan_obat.php?pesan=error");
}
exit();
?>