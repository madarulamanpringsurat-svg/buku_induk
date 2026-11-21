<?php
session_start();
require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/guru_induk_helper.php';
require_once __DIR__ . '/google_oauth_config.php';
pastikan_tabel_buku_induk($conn);

if (($_SESSION['role'] ?? '') !== 'guru') {
    header('Location: login.php');
    exit;
}

$clientId = google_oauth_client_id();
$clientSecret = google_oauth_client_secret();
$redirectUri = google_oauth_redirect_uri();
$guruId = (int)($_SESSION['guru_id'] ?? 0);

if (isset($_GET['start'])) {
    if ($clientId === '' || $clientSecret === '') {
        $_SESSION['pesan_buku_guru'] = 'Client Secret Google Drive belum dikonfigurasi.';
        $_SESSION['status_buku_guru'] = 'gagal';
        header('Location: index.php?page=guru_portal&bagian=pegawai');
        exit;
    }
    $_SESSION['google_drive_state'] = bin2hex(random_bytes(16));
    $params = http_build_query([
        'client_id' => $clientId,
        'redirect_uri' => $redirectUri,
        'response_type' => 'code',
        'scope' => 'openid email profile https://www.googleapis.com/auth/drive.file',
        'access_type' => 'offline',
        'prompt' => 'consent',
        'include_granted_scopes' => 'true',
        'state' => $_SESSION['google_drive_state'],
        'login_hint' => (string)($_SESSION['google_email'] ?? ''),
    ]);
    header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . $params);
    exit;
}

$code = trim($_GET['code'] ?? '');
$state = trim($_GET['state'] ?? '');
if ($code === '' || $state === '' || !hash_equals((string)($_SESSION['google_drive_state'] ?? ''), $state)) {
    $_SESSION['pesan_buku_guru'] = 'Koneksi Google Drive dibatalkan atau tidak valid.';
    $_SESSION['status_buku_guru'] = 'gagal';
    header('Location: index.php?page=guru_portal&bagian=pegawai');
    exit;
}

$ch = curl_init('https://oauth2.googleapis.com/token');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        'code' => $code,
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'redirect_uri' => $redirectUri,
        'grant_type' => 'authorization_code',
    ]),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 20,
]);
$response = curl_exec($ch);
$httpStatus = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$token = json_decode((string)$response, true);

if ($httpStatus !== 200 || empty($token['refresh_token'])) {
    $_SESSION['pesan_buku_guru'] = 'Google Drive gagal terhubung. Silakan coba lagi.';
    $_SESSION['status_buku_guru'] = 'gagal';
    header('Location: index.php?page=guru_portal&bagian=pegawai');
    exit;
}

$refreshTokenEsc = mysqli_real_escape_string($conn, $token['refresh_token']);
if (!mysqli_query($conn, "UPDATE guru SET google_refresh_token='$refreshTokenEsc', google_drive_connected=1 WHERE id=$guruId")) {
    $_SESSION['pesan_buku_guru'] = 'Token Google Drive gagal disimpan.';
    $_SESSION['status_buku_guru'] = 'gagal';
} else {
    unset($_SESSION['google_drive_state']);
    $_SESSION['pesan_buku_guru'] = 'Google Drive berhasil terhubung dengan akun guru.';
    $_SESSION['status_buku_guru'] = 'tersimpan';
}
header('Location: index.php?page=guru_portal&bagian=pegawai');
exit;
