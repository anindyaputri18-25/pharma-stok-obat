<?php
include 'koneksi.php';
include 'autentikasi.php';
include 'log_aktivitas.php';

if (!in_array($role_saat_ini, ['Admin','Apoteker'])) {
    header("Location: dashboard.php"); exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: tambah_racikan.php"); exit();
}

$id_apotek    = get_id_apotek_user($koneksi);
$nama_racikan = mysqli_real_escape_string($koneksi, $_POST['nama_racikan']);
$tipe_racikan = mysqli_real_escape_string($koneksi, $_POST['tipe_racikan']);
$stok_racikan = (int)$_POST['stok_racikan'];
$keterangan   = mysqli_real_escape_string($koneksi, $_POST['keterangan'] ?? '');

if (!isset($_POST['obat_dipilih']) || empty($_POST['obat_dipilih'])) {
    echo "<script>alert('Pilih minimal satu bahan obat dengan jumlah > 0!'); window.history.back();</script>"; exit();
}

$kode_racikan = 'RAC-'.strtoupper(substr(md5(time().rand()),0,5));
$obat_dipilih = $_POST['obat_dipilih'];
$semua_jumlah = $_POST['jumlah_pakai'];
$ap_val       = $id_apotek ? "'$id_apotek'" : "NULL";

// Cek stok semua bahan dulu
foreach ($obat_dipilih as $id_obat) {
    $jml = (int)($semua_jumlah[$id_obat] ?? 0);
    $cek = mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT nama_obat,jumlah FROM medicines WHERE id='$id_obat'"));
    if ($cek['jumlah'] < $jml) {
        echo "<script>alert('Stok ".addslashes($cek['nama_obat'])." tidak cukup! Stok: {$cek['jumlah']}, Butuh: $jml'); window.history.back();</script>"; exit();
    }
}

// Insert racikan (tambahkan id_apotek)
$sql_racikan = "INSERT INTO racikan (kode_racikan,nama_racikan,tipe_racikan,stok_racikan,keterangan,tanggal_buat,id_apotek)
                VALUES ('$kode_racikan','$nama_racikan','$tipe_racikan','$stok_racikan','$keterangan',NOW(),$ap_val)";
if (!mysqli_query($koneksi,$sql_racikan)) {
    echo "<script>alert('Gagal menyimpan racikan: ".mysqli_error($koneksi)."'); window.history.back();</script>"; exit();
}

$id_racikan_baru = mysqli_insert_id($koneksi);
$bahan_log = [];

foreach ($obat_dipilih as $id_obat) {
    $id_obat       = (int)$id_obat;
    $jml_digunakan = isset($semua_jumlah[$id_obat]) ? (int)$semua_jumlah[$id_obat] : 0;
    if ($jml_digunakan <= 0) continue;

    $sql_detail = "INSERT INTO racikan_detail (id_racikan,id_obat,jumlah_digunakan)
                   VALUES ('$id_racikan_baru','$id_obat','$jml_digunakan')";
    mysqli_query($koneksi,$sql_detail);
    mysqli_query($koneksi,"UPDATE medicines SET jumlah=jumlah-$jml_digunakan WHERE id='$id_obat'");

    $nm = mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT nama_obat FROM medicines WHERE id='$id_obat'"))['nama_obat']??'-';
    $bahan_log[] = "$nm($jml_digunakan)";
}

catat_log($koneksi,'Tambah Racikan',"Nama:$nama_racikan, Bahan:".implode(',',$bahan_log),$id_apotek);

echo "<script>alert('Berhasil! Racikan tersimpan dan stok obat telah dikurangi.'); window.location='racikan_obat.php';</script>";