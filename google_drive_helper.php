<?php
require_once __DIR__ . '/google_oauth_config.php';

function google_drive_access_token(mysqli $conn, int $guruId): ?string {
    $result = mysqli_query($conn, "SELECT google_refresh_token FROM guru WHERE id=$guruId LIMIT 1");
    $row = $result ? mysqli_fetch_assoc($result) : null;
    $refreshToken = (string)($row['google_refresh_token'] ?? '');
    $clientSecret = google_oauth_client_secret();
    if ($refreshToken === '' || $clientSecret === '') {
        return null;
    }

    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'client_id' => google_oauth_client_id(),
            'client_secret' => $clientSecret,
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
    ]);
    $response = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $token = json_decode((string)$response, true);
    return $status === 200 && !empty($token['access_token']) ? (string)$token['access_token'] : null;
}

function google_drive_request(string $method, string $url, string $accessToken, ?string $body = null, array $headers = []): array {
    $ch = curl_init($url);
    $defaultHeaders = ['Authorization: Bearer ' . $accessToken, 'Accept: application/json'];
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => array_merge($defaultHeaders, $headers),
    ]);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }
    $response = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['status' => $status, 'data' => json_decode((string)$response, true) ?: []];
}

function google_drive_folder_id(mysqli $conn, int $guruId, string $accessToken): ?string {
    $result = mysqli_query($conn, "SELECT google_drive_folder_id, nama_guru FROM guru WHERE id=$guruId LIMIT 1");
    $row = $result ? mysqli_fetch_assoc($result) : null;
    $existing = (string)($row['google_drive_folder_id'] ?? '');
    if ($existing !== '') return $existing;

    $folderName = 'SK Keaktifan - ' . trim((string)($row['nama_guru'] ?? 'Guru'));
    $query = http_build_query([
        'q' => "name='" . str_replace("'", "\\'", $folderName) . "' and mimeType='application/vnd.google-apps.folder' and trashed=false",
        'fields' => 'files(id,name)',
    ]);
    $found = google_drive_request('GET', 'https://www.googleapis.com/drive/v3/files?' . $query, $accessToken);
    if (($found['status'] ?? 0) === 200 && !empty($found['data']['files'][0]['id'])) {
        $folderId = (string)$found['data']['files'][0]['id'];
    } else {
        $created = google_drive_request('POST', 'https://www.googleapis.com/drive/v3/files?fields=id', $accessToken,
            json_encode(['name' => $folderName, 'mimeType' => 'application/vnd.google-apps.folder'], JSON_UNESCAPED_UNICODE),
            ['Content-Type: application/json']);
        $folderId = (string)($created['data']['id'] ?? '');
    }
    if ($folderId === '') return null;

    $folderIdEsc = mysqli_real_escape_string($conn, $folderId);
    mysqli_query($conn, "UPDATE guru SET google_drive_folder_id='$folderIdEsc' WHERE id=$guruId");
    return $folderId;
}

function google_drive_upload(mysqli $conn, int $guruId, string $localPath, string $originalName): ?array {
    $accessToken = google_drive_access_token($conn, $guruId);
    if ($accessToken === null) return null;
    $folderId = google_drive_folder_id($conn, $guruId, $accessToken);
    if ($folderId === null) return null;

    $absolutePath = (strncasecmp(PHP_OS_FAMILY, 'Windows', 7) === 0)
        ? str_replace('/', DIRECTORY_SEPARATOR, $localPath)
        : $localPath;
    if (!is_file($absolutePath)) {
        $absolutePath = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $localPath);
    }
    if (!is_file($absolutePath)) return null;

    $mime = mime_content_type($absolutePath) ?: 'application/octet-stream';
    $metadata = json_encode(['name' => $originalName, 'parents' => [$folderId]], JSON_UNESCAPED_UNICODE);
    $boundary = 'bukuinduk_' . bin2hex(random_bytes(12));
    $payload = "--$boundary\r\n";
    $payload .= "Content-Type: application/json; charset=UTF-8\r\n\r\n" . $metadata . "\r\n";
    $payload .= "--$boundary\r\n";
    $payload .= 'Content-Type: ' . $mime . "\r\n\r\n";
    $payload .= file_get_contents($absolutePath);
    $payload .= "\r\n--$boundary--\r\n";

    $uploaded = google_drive_request('POST', 'https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart&fields=id,webViewLink', $accessToken,
        $payload, ['Content-Type: multipart/related; boundary=' . $boundary]);
    if (($uploaded['status'] ?? 0) < 200 || ($uploaded['status'] ?? 0) >= 300 || empty($uploaded['data']['id'])) {
        return null;
    }
    return ['id' => (string)$uploaded['data']['id'], 'link' => (string)($uploaded['data']['webViewLink'] ?? '')];
}
