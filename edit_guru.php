<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: login.php');
    exit();
}
include 'koneksi.php';
require_once 'guru_induk_helper.php';
pastikan_tabel_buku_induk($koneksi);

if (!isset($_GET['id'])) {
    header("Location: data_guru.php");
    exit();
}

$id = mysqli_real_escape_string($koneksi, $_GET['id']);
$query = mysqli_query($koneksi, "SELECT * FROM guru WHERE id = '$id'");
$data = mysqli_fetch_assoc($query);

if (!$data) {
    die("Data guru tidak ditemukan.");
}

if (isset($_POST['update'])) {
    $nip = mysqli_real_escape_string($koneksi, $_POST['nip']);
    $nama_guru = mysqli_real_escape_string($koneksi, $_POST['nama_guru']);
    $jabatan = mysqli_real_escape_string($koneksi, $_POST['jabatan']);
    $jabatan_tambahan = mysqli_real_escape_string($koneksi, $_POST['jabatan_tambahan']);
    $mata_pelajaran = mysqli_real_escape_string($koneksi, $_POST['mata_pelajaran']);
    $google_email = strtolower(trim(mysqli_real_escape_string($koneksi, $_POST['google_email'] ?? '')));
    $google_login_enabled = isset($_POST['google_login_enabled']) ? 1 : 0;

    $query_update = mysqli_query($koneksi, "UPDATE guru SET nip='$nip', nama_guru='$nama_guru', jabatan='$jabatan', jabatan_tambahan='$jabatan_tambahan', mata_pelajaran='$mata_pelajaran', google_email='$google_email', google_login_enabled=$google_login_enabled WHERE id='$id'");
    if ($query_update) {
        header("Location: data_guru.php?pesan=update_berhasil");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Data Guru</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background-color: #f4f7f6; }
        .container { max-width: 500px; background: white; padding: 30px; border-radius: 8px; box-shadow: 0px 0px 10px rgba(0,0,0,0.1); margin: 0 auto; }
        h2 { text-align: center; color: #333; }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: bold; margin-bottom: 5px; }
        input[type="text"] { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .btn-container { display: flex; justify-content: space-between; margin-top: 20px; }
        button { padding: 10px 20px; background-color: #007bff; color: white; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; width: 48%; }
        .btn-batal { display: inline-block; padding: 10px 20px; background-color: #6c757d; color: white; text-decoration: none; border-radius: 4px; font-weight: bold; text-align: center; width: 48%; box-sizing: border-box; }
    </style>
</head>
<body>

<div class="container">
    <h2>✏️ Edit Data Guru</h2>
    <form action="" method="POST">
        <div class="form-group">
            <label>NIP / No. Induk:</label>
            <input type="text" name="nip" value="<?php echo $data['nip']; ?>" required>
        </div>
        <div class="form-group">
            <label>Nama Lengkap & Gelar:</label>
            <input type="text" name="nama_guru" value="<?php echo $data['nama_guru']; ?>" required>
        </div>
        <div class="form-group">
            <label>Jabatan Utama:</label>
            <input type="text" name="jabatan" value="<?php echo $data['jabatan']; ?>" required>
        </div>
        <div class="form-group">
            <label>Jabatan Tambahan:</label>
            <input type="text" name="jabatan_tambahan" value="<?php echo $data['jabatan_tambahan']; ?>">
        </div>
        <div class="form-group">
            <label>Mata Pelajaran:</label>
            <input type="text" name="mata_pelajaran" value="<?php echo $data['mata_pelajaran']; ?>">
        </div>
        <div class="form-group">
            <label>Email Gmail untuk Login Guru:</label>
            <input type="email" name="google_email" value="<?php echo htmlspecialchars($data['google_email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="nama@gmail.com">
        </div>
        <label class="form-group">
            <input type="checkbox" name="google_login_enabled" value="1" <?php echo ((int)($data['google_login_enabled'] ?? 0) === 1) ? 'checked' : ''; ?>> Aktifkan login Google
        </label>
        <div class="btn-container">
            <a href="data_guru.php" class="btn-batal">Batal</a>
            <button type="submit" name="update">Simpan Perubahan</button>
        </div>
    </form>
</div>

</body>
</html>