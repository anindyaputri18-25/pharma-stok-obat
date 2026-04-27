<?php
include 'koneksi.php';
include 'autentikasi.php'; //

if (!in_array($role_saat_ini, ['Admin', 'Apoteker'])) { //
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: tambah_racikan.php");
    exit();
}

$nama_racikan = mysqli_real_escape_string($koneksi, $_POST['nama_racikan']);
$tipe_racikan = mysqli_real_escape_string($koneksi, $_POST['tipe_racikan']);
$stok_racikan = (int)$_POST['stok_racikan'];
$keterangan   = mysqli_real_escape_string($koneksi, $_POST['keterangan']);

// Validasi minimal 1 bahan
if (!isset($_POST['obat_dipilih']) || empty($_POST['obat_dipilih'])) {
    echo "<script>alert('Pilih minimal satu bahan obat dengan jumlah > 0!'); window.history.back();</script>";
    exit();
}

// Kode unik otomatis
$kode_racikan = "RAC-" . strtoupper(substr(md5(time() . rand()), 0, 5));

$obat_dipilih = $_POST['obat_dipilih'];
$semua_jumlah = $_POST['jumlah_pakai']; // Sesuai dengan name="jumlah_pakai[ID]" di form

foreach ($obat_dipilih as $id_obat) {
    $jml_digunakan = (int)$semua_jumlah[$id_obat];
    $cek_stok = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT nama_obat, jumlah FROM medicines WHERE id = '$id_obat'"));
    
    if ($cek_stok['jumlah'] < $jml_digunakan) {
        echo "<script>alert('Stok ".$cek_stok['nama_obat']." tidak cukup!'); window.history.back();</script>";
        exit();
    }
}

// Insert racikan
$sql_racikan = "INSERT INTO racikan (kode_racikan, nama_racikan, tipe_racikan, stok_racikan, keterangan, tanggal_buat)
                VALUES ('$kode_racikan', '$nama_racikan', '$tipe_racikan', '$stok_racikan', '$keterangan', NOW())";

if (!mysqli_query($koneksi, $sql_racikan)) {
    echo "<script>alert('Gagal menyimpan racikan: " . mysqli_error($koneksi) . "'); window.history.back();</script>";
    exit();
}

$id_racikan_baru = mysqli_insert_id($koneksi);

foreach ($obat_dipilih as $id_obat) {
    $id_obat       = (int)$id_obat;
    $jml_digunakan = isset($semua_jumlah[$id_obat]) ? (int)$semua_jumlah[$id_obat] : 0;
    if ($jml_digunakan <= 0) continue;

    // Baris 46 - Pastikan query insert detail lengkap
    $sql_detail = "INSERT INTO racikan_detail (id_racikan, id_obat, jumlah_digunakan) 
                VALUES ('$id_racikan_baru', '$id_obat', '$jml_digunakan')";

    if (!mysqli_query($koneksi, $sql_detail)) {
        echo "<script>alert('Gagal menyimpan detail bahan: " . mysqli_error($koneksi) . "');</script>";
    }

    // Update stok obat di tabel medicines setelah racikan dibuat
    mysqli_query($koneksi, "UPDATE medicines SET jumlah = jumlah - $jml_digunakan WHERE id = '$id_obat'");
}

echo "<script>
        alert('Berhasil! Racikan tersimpan dan stok obat telah dikurangi.');
        window.location='racikan_obat.php';
      </script>";
?>