<?php
/**
 * api_bps.php — Proxy ke API BPS dengan error handling lengkap
 * Endpoint: webapi.bps.go.id/v1/api/view
 * Cara update key: Login ke https://webapi.bps.go.id → menu Key API → copy key
 */
include 'koneksi.php';
include 'autentikasi.php';

header('Content-Type: application/json; charset=utf-8');

// Cek login
if (!isset($_COOKIE['users'])) {
    echo json_encode(['status'=>'error','message'=>'Akses ditolak. Silakan login terlebih dahulu.']);
    exit();
}
if ($role_saat_ini === 'Pending') {
    echo json_encode(['status'=>'error','message'=>'Akun masih menunggu verifikasi.']);
    exit();
}

// =====================================================
// GANTI API KEY DI SINI
// Cara: Login ke https://webapi.bps.go.id → Key API
// =====================================================
$apiKey = "4f09e29b052cee2e8ed7436cefb94c4c";

// ID tabel BPS: 1619 = Persentase Penduduk Keluhan Kesehatan & Obat
$url = "https://webapi.bps.go.id/v1/api/view"
     . "/domain/0000"
     . "/model/statictable"
     . "/lang/ind"
     . "/id/1619"
     . "/key/" . $apiKey;

// Cek apakah cURL tersedia
if (!function_exists('curl_init')) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Ekstensi PHP cURL tidak aktif di server ini. Aktifkan di php.ini: extension=curl'
    ]);
    exit();
}

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_USERAGENT      => 'PharmaStock/2.0 PHP/' . PHP_VERSION,
    CURLOPT_HTTPHEADER     => ['Accept: application/json'],
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
$curlErrNo= curl_errno($ch);
curl_close($ch);

// Error koneksi
if ($curlErr) {
    $msg = match($curlErrNo) {
        6  => 'Tidak bisa resolve domain BPS (CURLE_COULDNT_RESOLVE_HOST). Cek koneksi internet server.',
        7  => 'Tidak bisa terhubung ke server BPS (CURLE_COULDNT_CONNECT). Port mungkin diblokir.',
        28 => 'Koneksi ke BPS timeout. Server BPS mungkin sedang sibuk, coba lagi.',
        35 => 'Error SSL saat menghubungi BPS. Coba nonaktifkan SSL verify.',
        default => "cURL Error #{$curlErrNo}: {$curlErr}",
    };
    echo json_encode(['status'=>'error','message'=>$msg,'curl_errno'=>$curlErrNo]);
    exit();
}

// HTTP error
if ($httpCode !== 200) {
    $msg = match($httpCode) {
        400 => 'Request tidak valid. Periksa parameter ID tabel BPS.',
        401 => 'API Key tidak valid atau sudah kadaluarsa. Perbarui di webapi.bps.go.id',
        403 => 'Akses ditolak BPS. Pastikan domain server sudah di-whitelist di akun BPS.',
        404 => 'ID tabel BPS tidak ditemukan (1619). Cek di https://webapi.bps.go.id',
        429 => 'Rate limit BPS terlampaui. Coba lagi beberapa menit kemudian.',
        500 => 'Server BPS sedang error. Coba lagi nanti.',
        503 => 'Server BPS sedang maintenance. Coba lagi nanti.',
        0   => 'Tidak ada respons dari server BPS (HTTP 0). Cek koneksi internet server.',
        default => "HTTP Error {$httpCode} dari server BPS.",
    };
    echo json_encode(['status'=>'error','message'=>$msg,'http_code'=>$httpCode]);
    exit();
}

// Validasi JSON
if (empty($response)) {
    echo json_encode(['status'=>'error','message'=>'Respons BPS kosong. Coba lagi nanti.']);
    exit();
}

$decoded = json_decode($response, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Respons BPS bukan JSON valid: ' . json_last_error_msg(),
        'raw'     => substr($response, 0, 200)
    ]);
    exit();
}

// Sukses — teruskan ke frontend
echo $response;