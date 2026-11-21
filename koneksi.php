<?php

$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASSWORD');
$db   = getenv('DB_NAME') ?: 'madarula_buku_induk_ma';
$port = getenv('DB_PORT');
$pass = $pass === false ? '' : $pass;
$port = $port === false || $port === '' ? 3306 : (int)$port;

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $koneksi = new mysqli($host, $user, $pass, $db, $port);
    $koneksi->set_charset('utf8mb4');
} catch (mysqli_sql_exception $error) {
    error_log('Koneksi database gagal: ' . $error->getMessage());
    http_response_code(500);
    exit('Koneksi database gagal. Periksa konfigurasi DB_HOST, DB_USER, DB_PASSWORD, dan DB_NAME pada hosting.');
}

$conn = $koneksi;
?>