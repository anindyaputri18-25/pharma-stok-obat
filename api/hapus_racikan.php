<?php
session_start();
include 'koneksi.php';

$id = $_GET['id'];

// Menghapus racikan otomatis menghapus detail karena kita pakai 'ON DELETE CASCADE' di SQL
$query = mysqli_query($koneksi, "DELETE FROM racikan WHERE id_racikan = '$id'");

if ($query) {
    header("Location: racikan_obat.php?pesan=hapus");
} else {
    echo "Gagal menghapus.";
}
?>