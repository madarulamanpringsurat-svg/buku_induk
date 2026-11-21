<?php
// Mencegah akses langsung tanpa melalui file index.php
if (!isset($_SESSION['logged_in'])) {
    header("Location: login.php");
    exit;
}

// SOLUSI: Pastikan file koneksi database dimuat jika variabel $conn belum terdefinisi
// Sesuaikan 'koneksi.php' dengan nama file konfigurasi database Anda jika berbeda
if (!isset($conn)) {
    include_once 'koneksi.php'; 
}

$pesan_sukses = '';
$pesan_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $nama_lengkap = mysqli_real_escape_string($conn, trim($_POST['nama_lengkap']));
    $password_baru = trim($_POST['password_baru']);
    $konfirmasi_password = trim($_POST['konfirmasi_password']);

    if (!empty($username) && !empty($nama_lengkap) && !empty($password_baru)) {
        // 1. Validasi apakah username sudah digunakan
        $cek_username = mysqli_query($conn, "SELECT username FROM users WHERE username = '$username' LIMIT 1");
        
        if ($cek_username && mysqli_num_rows($cek_username) > 0) {
            $pesan_error = 'Username "' . htmlspecialchars($username) . '" sudah terdaftar di sistem. Gunakan nama lain.';
        } else {
            // 2. Validasi kecocokan password
            if ($password_baru === $konfirmasi_password) {
                if (strlen($password_baru) >= 4) {
                    
                    // LANGSUNG SIMPAN MODEL BIASA (Teks biasa tanpa hash sesuai instruksi Anda)
                    $password_escape = mysqli_real_escape_string($conn, $password_baru);
                    $query_insert = "INSERT INTO users (username, password, nama_lengkap) VALUES ('$username', '$password_escape', '$nama_lengkap')";
                    
                    if (mysqli_query($conn, $query_insert)) {
                        $pesan_sukses = 'Pengguna baru bernama <strong>' . htmlspecialchars($nama_lengkap) . '</strong> berhasil didaftarkan!';
                    } else {
                        // Menampilkan error asli dari MySQL untuk mempermudah diagnosa
                        $pesan_error = 'Gagal menyimpan pengguna baru. Error Database: ' . mysqli_error($conn);
                    }
                } else {
                    $pesan_error = 'Password terlalu pendek! Minimal harus 4 karakter.';
                }
            } else {
                $pesan_error = 'Konfirmasi password tidak cocok. Silakan ulangi.';
            }
        }
    } else {
        $pesan_error = 'Semua kolom formulir wajib diisi.';
    }
}
?>

<div class="row justify-content-start">
    <div class="col-12 col-md-8 col-lg-6">
        
        <?php if (!empty($pesan_sukses)): ?>
            <div class="alert alert-success alert-dismissible fade show small" role="alert">
                <i class="fa-solid fa-circle-check me-2"></i> <?php echo $pesan_sukses; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($pesan_error)): ?>
            <div class="alert alert-danger alert-dismissible fade show small" role="alert">
                <i class="fa-solid fa-circle-exclamation me-2"></i> <?php echo $pesan_error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm border-0">
            <div class="card-header">
                <i class="fa-solid fa-user-plus text-success me-2"></i> Registrasi Pengguna Aplikasi Baru
            </div>
            <div class="card-body p-4">
                <form action="" method="POST">
                    
                    <div class="mb-3">
                        <label for="nama_lengkap" class="form-label small fw-medium">Nama Lengkap Anggota / TU</label>
                        <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" placeholder="Contoh: Siti Khotimah, S.Pd." required autofocus>
                    </div>

                    <div class="mb-3">
                        <label for="username" class="form-label small fw-medium">Username Login</label>
                        <input type="text" class="form-control" id="username" name="username" placeholder="Contoh: sititu" required>
                        <div class="form-text text-muted" style="font-size:0.75rem;">Gunakan huruf kecil atau angka tanpa spasi.</div>
                    </div>

                    <hr class="my-4 text-muted opacity-25">

                    <div class="mb-3">
                        <label for="password_baru" class="form-label small fw-medium">Kata Sandi (Password)</label>
                        <input type="password" class="form-control" id="password_baru" name="password_baru" placeholder="Buat kata sandi akun" required>
                    </div>

                    <div class="mb-4">
                        <label for="konfirmasi_password" class="form-label small fw-medium">Ulangi Kata Sandi (Konfirmasi)</label>
                        <input type="password" class="form-control" id="konfirmasi_password" name="konfirmasi_password" placeholder="Masukkan kembali kata sandi" required>
                    </div>

                    <div class="d-flex gap-2 justify-content-end">
                        <button type="reset" class="btn btn-light px-4 small">Reset Formulir</button>
                        <button type="submit" class="btn btn-success px-4 small"><i class="fa-solid fa-user-plus me-2"></i> Daftarkan User</button>
                    </div>

                </form>
            </div>
        </div>

    </div>
</div>