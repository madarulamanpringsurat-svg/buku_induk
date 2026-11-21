<?php

$googleOAuthLocalConfig = __DIR__ . '/google_oauth_local.php';
if (is_file($googleOAuthLocalConfig)) {
    require_once $googleOAuthLocalConfig;
}

function google_oauth_client_id(): string {
    $clientId = getenv('GOOGLE_CLIENT_ID') ?: (defined('GOOGLE_OAUTH_LOCAL_CLIENT_ID') ? GOOGLE_OAUTH_LOCAL_CLIENT_ID : '');
    if ($clientId === '' && in_array(strtolower((string)($_SERVER['HTTP_HOST'] ?? '')), ['localhost', '127.0.0.1'], true)) {
        $clientId = '549102445101-jaias4v1m7orh74ik92s4hpc1esllnbc.apps.googleusercontent.com';
    }
    return trim($clientId);
}

function google_oauth_redirect_uri(): string {
    $redirectUri = getenv('GOOGLE_REDIRECT_URI') ?: (defined('GOOGLE_OAUTH_LOCAL_REDIRECT_URI') ? GOOGLE_OAUTH_LOCAL_REDIRECT_URI : '');
    if ($redirectUri !== '') {
        return trim($redirectUri);
    }

    $https = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off')
        || (strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https');
    $scheme = $https ? 'https' : 'http';
    $host = preg_replace('/[^A-Za-z0-9.:-]/', '', (string)($_SERVER['HTTP_HOST'] ?? 'localhost'));
    $scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '/login_google.php'));
    $directory = rtrim(str_replace('login_google.php', '', $scriptName), '/');
    return $scheme . '://' . $host . $directory . '/login_google.php';
}

function google_oauth_client_secret(): string {
    $clientSecret = getenv('GOOGLE_CLIENT_SECRET') ?: (defined('GOOGLE_OAUTH_LOCAL_CLIENT_SECRET') ? GOOGLE_OAUTH_LOCAL_CLIENT_SECRET : '');
    return trim($clientSecret);
}
