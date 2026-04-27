<?php
include 'koneksi';
include 'autentikasi.php';
// api_bps.php
header('Content-Type: application/json');

if (!isset($users)) {
    echo json_encode([
        "status" => "error",
        "message" => "Akses ditolak. Silakan login terlebih dahulu."
    ]);
    exit();
}

// Ganti [API_KEY_KAMU] dengan API Key asli dari web BPS
$apiKey = "4f09e29b052cee2e8ed7436cefb94c4c"; 
$url = "https://webapi.bps.go.id/v1/api/view/domain/0000/model/statictable/lang/ind/id/1619/key/" . $apiKey;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Tambahkan baris ini
curl_close($ch);

if ($httpCode !== 200) {
    echo json_encode(["status" => "error", "message" => "Gagal ambil data BPS"]);
    exit();
}
echo $response;
?>