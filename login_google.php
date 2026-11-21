<?php
session_start();
require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/guru_induk_helper.php';
require_once __DIR__ . '/google_oauth_config.php';
pastikan_tabel_buku_induk($conn);

$clientId = google_oauth_client_id();
$clientSecret = google_oauth_client_secret();
$redirectUri = google_oauth_redirect_uri();

if (isset($_GET['start'])) {
    if ($clientId === '' || $clientSecret === '') {
        $_SESSION['login_google_error'] = 'Client ID atau Client Secret Google belum dikonfigurasi.';
        header('Location: login.php');
        exit;
    }

    $_SESSION['google_login_state'] = bin2hex(random_bytes(16));
    $params = http_build_query([
        'client_id' => $clientId,
        'redirect_uri' => $redirectUri,
        'response_type' => 'code',
        'scope' => 'openid email profile https://www.googleapis.com/auth/drive.file',
        'access_type' => 'offline',
        'prompt' => 'consent',
        'include_granted_scopes' => 'true',
        'state' => $_SESSION['google_login_state'],
    ]);
    header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . $params);
    exit;
}

$code = trim($_GET['code'] ?? '');
$state = trim($_GET['state'] ?? '');
if ($code === '' || $state === '' || !hash_equals((string)($_SESSION['google_login_state'] ?? ''), $state)) {
    $_SESSION['login_google_error'] = 'Login Google dibatalkan atau state OAuth tidak valid.';
    header('Location: login.php');
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

if ($httpStatus !== 200 || empty($token['access_token'])) {
    $_SESSION['login_google_error'] = 'Login Google dan izin Drive gagal diproses.';
    header('Location: login.php');
    exit;
}

$ch = curl_init('https://openidconnect.googleapis.com/v1/userinfo');
curl_setopt_array($ch, [
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token['access_token']],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 15,
]);
$userInfoResponse = curl_exec($ch);
$userInfoStatus = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$userInfo = json_decode((string)$userInfoResponse, true);

$email = strtolower(trim($userInfo['email'] ?? ''));
$googleId = trim($userInfo['sub'] ?? '');
if ($userInfoStatus !== 200 || $email === '' || $googleId === '' || ($userInfo['email_verified'] ?? false) !== true) {
    $_SESSION['login_google_error'] = 'Informasi akun Google tidak lengkap atau email belum diverifikasi.';
    header('Location: login.php');
    exit;
}

$emailEsc = mysqli_real_escape_string($conn, $email);
$googleIdEsc = mysqli_real_escape_string($conn, $googleId);
$result = mysqli_query($conn, "
    SELECT g.id, g.nama_guru
    FROM guru g
    LEFT JOIN buku_induk_guru bg ON bg.guru_id=g.id
    WHERE g.google_login_enabled=1
      AND (
        g.google_id='$googleIdEsc'
        OR LOWER(TRIM(COALESCE(g.google_email, '')))='$emailEsc'
        OR LOWER(TRIM(COALESCE(bg.email, '')))='$emailEsc'
      )
    LIMIT 1
");

if (!$result || mysqli_num_rows($result) !== 1) {
    $_SESSION['login_google_error'] = 'Akun Gmail belum didaftarkan atau belum diaktifkan untuk login guru.';
    header('Location: login.php');
    exit;
}

$guru = mysqli_fetch_assoc($result);
$guruId = (int)$guru['id'];
$refreshTokenSql = '';
if (!empty($token['refresh_token'])) {
    $refreshTokenEsc = mysqli_real_escape_string($conn, $token['refresh_token']);
    $refreshTokenSql = ", google_refresh_token='$refreshTokenEsc', google_drive_connected=1";
}
mysqli_query($conn, "UPDATE guru SET google_id='$googleIdEsc', google_email='$emailEsc'$refreshTokenSql WHERE id=$guruId");

unset($_SESSION['google_login_state']);
$_SESSION['logged_in'] = true;
$_SESSION['role'] = 'guru';
$_SESSION['guru_id'] = $guruId;
$_SESSION['nama_lengkap'] = $guru['nama_guru'];
$_SESSION['username'] = $email;
$_SESSION['google_email'] = $email;

session_regenerate_id(true);
header('Location: index.php?page=guru_portal&bagian=pegawai');
exit;
