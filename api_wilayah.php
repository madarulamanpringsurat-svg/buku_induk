<?php
// Pastikan TIDAK ada spasi, enter, atau karakter apa pun sebelum tag <?php di atas

// Sembunyikan error mentah HTML agar tidak merusak format JSON jika server memblokir url
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if (!isset($_GET['get_wilayah'])) {
    echo json_encode([]);
    exit;
}

$type = $_GET['get_wilayah'];
$id = isset($_GET['id']) ? preg_replace('/[^0-9]/', '', $_GET['id']) : '';

$url = 'https://goforward.github.io/api-wilayah-indonesia/api/provinces.json';
if ($type == 'kab' && $id) {
    $url = "https://goforward.github.io/api-wilayah-indonesia/api/regencies/{$id}.json";
} elseif ($type == 'kec' && $id) {
    $url = "https://goforward.github.io/api-wilayah-indonesia/api/districts/{$id}.json";
} elseif ($type == 'desa' && $id) {
    $url = "https://goforward.github.io/api-wilayah-indonesia/api/villages/{$id}.json";
}

$response = false;

// 1. Opsi Utama: Menggunakan cURL dengan konfigurasi bypass SSL Hosting yang ketat
if (function_exists('curl_init')) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    // Menyamar menjadi Google Chrome asli agar lolos Cloudflare/Firewall GitHub
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
    curl_setopt($ch, CURLOPT_TIMEOUT, 7); 
    
    $response = curl_exec($ch);
    curl_close($ch);
}

// 2. Opsi Cadangan: Jika cURL diblokir total oleh hosting, paksa pakai stream kontekstual
if (!$response && ini_get('allow_url_fopen')) {
    $opts = [
        "http" => [
            "method" => "GET",
            "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36\r\n" .
                        "Accept: application/json\r\n",
            "timeout" => 7
        ],
        "ssl" => [
            "verify_peer" => false,
            "verify_peer_name" => false,
        ]
    ];
    $context = stream_context_create($opts);
    $response = @file_get_contents($url, false, $context);
}

// Kembalikan data ke JavaScript
if (!$response) {
    // Jika hosting memblokir semua akses keluar, kembalikan array kosong agar JavaScript tidak stuck 'Mohon Tunggu'
    echo json_encode([]);
} else {
    echo $response;
}
exit;