<?php
// 1. Memulai atau mengaktifkan sesi yang sedang berjalan
session_start();

// 2. Menghapus semua variabel sesi yang terdaftar
$_SESSION = array();

// 3. Menghapus cookie sesi dari peramban (browser) jika ada untuk keamanan total
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), 
        '', 
        time() - 42000,
        $params["path"], 
        $params["domain"],
        $params["secure"], 
        $params["httponly"]
    );
}

// 4. Menghancurkan (destroy) sesi secara permanen di sisi server
session_destroy();

// 5. Mengalihkan pengguna kembali ke halaman login dengan aman
header("Location: login.php");
exit;
?>