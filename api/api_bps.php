<?php
// api_bps.php
header('Content-Type: application/json');

// Ganti [API_KEY_KAMU] dengan API Key asli dari web BPS
$apiKey = "4f09e29b052cee2e8ed7436cefb94c4c"; 
$url = "https://webapi.bps.go.id/v1/api/view/domain/0000/model/statictable/lang/ind/id/1619/key/" . $apiKey;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Penting kalau di localhost
$response = curl_exec($ch);
curl_close($ch);

// Kirim apa adanya ke JavaScript
echo $response;
?>