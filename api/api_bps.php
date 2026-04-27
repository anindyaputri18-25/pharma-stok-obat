<?php
include 'koneksi.php'; // Pastikan pakai .php
include 'autentikasi.php';

header('Content-Type: application/json');

// Cek apakah cookie user ada. Jika ada, berarti user sudah login.
if (!isset($_COOKIE['users'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Akses ditolak. Silakan login terlebih dahulu."
    ]);
    exit();
}

// Cek apakah user berstatus Pending
if ($role_saat_ini == 'Pending') {
    echo json_encode([
        "status" => "error",
        "message" => "Akun Anda masih menunggu verifikasi."
    ]);
    exit();
}

$apiKey = "4f09e29b052cee2e8ed7436cefb94c4c"; 
$url = "https://webapi.bps.go.id/v1/api/view/domain/0000/model/statictable/lang/ind/id/1619/key/" . $apiKey;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo json_encode(["status" => "error", "message" => "Gagal mengambil data dari server BPS."]);
    exit();
}

echo $response;
?>