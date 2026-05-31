<?php
include 'koneksi.php';
include 'autentikasi.php';
include 'log_aktivitas.php';

if (!in_array($role_saat_ini, ['Admin','Apoteker'])) {
    header("Location: dashboard.php"); exit();
}
if (!isset($_GET['id'])) {
    header("Location: racikan_obat.php"); exit();
}

$id        = (int)$_GET['id'];
$id_apotek = get_id_apotek_user($koneksi);

$cek = mysqli_query($koneksi,"SELECT nama_racikan FROM racikan WHERE id_racikan='$id'");
if (mysqli_num_rows($cek) === 0) {
    header("Location: racikan_obat.php?pesan=notfound"); exit();
}
$nama_r = mysqli_fetch_assoc($cek)['nama_racikan'] ?? '-';

mysqli_query($koneksi,"DELETE FROM racikan_detail WHERE id_racikan='$id'");
$query = mysqli_query($koneksi,"DELETE FROM racikan WHERE id_racikan='$id'");

if ($query) {
    catat_log($koneksi,'Hapus Racikan',"Nama:$nama_r, ID:$id",$id_apotek);
    header("Location: racikan_obat.php?pesan=hapus");
} else {
    header("Location: racikan_obat.php?pesan=error");
}
exit();