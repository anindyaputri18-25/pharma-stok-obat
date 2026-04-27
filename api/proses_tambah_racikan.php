<?php
session_start();
include 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Ambil data dari tabel utama 'racikan'
    $nama_racikan = mysqli_real_escape_string($koneksi, $_POST['nama_racikan']);
    $tipe_racikan = $_POST['tipe_racikan'];
    $stok_racikan = (int)$_POST['stok_racikan'];
    $keterangan   = mysqli_real_escape_string($koneksi, $_POST['keterangan']);
    
    // Buat kode unik otomatis (Misal: RAC-XXXXX)
    $kode_racikan = "RAC-" . strtoupper(substr(md5(time()), 0, 5));

    // Validasi apakah ada obat yang dipilih
    if (!isset($_POST['obat_dipilih']) || empty($_POST['obat_dipilih'])) {
        echo "<script>alert('Pilih minimal satu bahan obat!'); window.history.back();</script>";
        exit();
    }

    // 2. Insert ke tabel 'racikan'
    $sql_racikan = "INSERT INTO racikan (kode_racikan, nama_racikan, tipe_racikan, stok_racikan, keterangan, tanggal_buat) 
                    VALUES ('$kode_racikan', '$nama_racikan', '$tipe_racikan', '$stok_racikan', '$keterangan', NOW())";
    
    if (mysqli_query($koneksi, $sql_racikan)) {
        // Ambil ID racikan yang baru saja masuk
        $id_racikan_baru = mysqli_insert_id($koneksi);
        
        $obat_dipilih = $_POST['obat_dipilih']; // Array ID obat
        $semua_jumlah = $_POST['jumlah_pakai']; // Array jumlah pakai

        foreach ($obat_dipilih as $id_obat) {
            $jml_digunakan = (int)$semua_jumlah[$id_obat];

            if ($jml_digunakan > 0) {
                // 3. Masukkan ke tabel 'racikan_detail'
                $sql_detail = "INSERT INTO racikan_detail (id_racikan, id_obat, jumlah_digunakan) 
                               VALUES ('$id_racikan_baru', '$id_obat', '$jml_digunakan')";
                mysqli_query($koneksi, $sql_detail);

                // 4. Update/Kurangi stok di tabel 'medicines'
                $sql_update_stok = "UPDATE medicines SET jumlah = jumlah - $jml_digunakan WHERE id = '$id_obat'";
                mysqli_query($koneksi, $sql_update_stok);
            }
        }

        echo "<script>
                alert('Berhasil! Racikan tersimpan dan stok obat telah dikurangi.'); 
                window.location='racikan_obat.php';
              </script>";
    } else {
        echo "Error: " . mysqli_error($koneksi);
    }
}
?>