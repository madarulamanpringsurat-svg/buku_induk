<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/guru_induk_helper.php';

$url_kembali = 'index.php?page=data_guru';
$guru_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($guru_id === false || $guru_id === null || $guru_id < 1) {
    $_SESSION['db_error'] = 'ID guru tidak valid.';
    header('Location: ' . $url_kembali . '&pesan=gagal');
    exit();
}

try {
    pastikan_tabel_buku_induk($koneksi);
    if (hapus_guru_dan_data_terkait($koneksi, $guru_id)) {
        header('Location: ' . $url_kembali . '&pesan=hapus_berhasil');
    } else {
        $_SESSION['db_error'] = 'Data guru tidak ditemukan.';
        header('Location: ' . $url_kembali . '&pesan=gagal');
    }
} catch (Throwable $error) {
    error_log('Penghapusan guru gagal: ' . $error->getMessage());
    $_SESSION['db_error'] = 'Data guru gagal dihapus. Periksa log server untuk detailnya.';
    header('Location: ' . $url_kembali . '&pesan=gagal');
}
exit();
?>