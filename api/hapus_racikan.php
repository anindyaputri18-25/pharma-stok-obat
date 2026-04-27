<?php
include 'koneksi.php';
include 'autentikasi.php';

// Hanya Admin dan Apoteker yang boleh menghapus racikan
if (!in_array($role_saat_ini, ['Admin', 'Apoteker'])) {
    header("Location: dashboard.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: racikan_obat.php");
    exit();
}

$id = (int)$_GET['id'];

// Cek apakah racikan ada di database
$cek = mysqli_query($koneksi, "SELECT id_racikan FROM racikan WHERE id_racikan = '$id'");
if (mysqli_num_rows($cek) == 0) {
    header("Location: racikan_obat.php?pesan=notfound");
    exit();
}

// Hapus detail racikan terlebih dahulu untuk menjaga integritas data
mysqli_query($koneksi, "DELETE FROM racikan_detail WHERE id_racikan = '$id'");

// Hapus data utama di tabel racikan
$query = mysqli_query($koneksi, "DELETE FROM racikan WHERE id_racikan = '$id'");

if ($query) {
    header("Location: racikan_obat.php?pesan=hapus");
} else {
    header("Location: racikan_obat.php?pesan=error");
}
exit();
?>