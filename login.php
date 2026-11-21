<?php
session_start();

// Memuat berkas koneksi database
require_once 'koneksi.php';
require_once 'guru_induk_helper.php';
pastikan_tabel_buku_induk($conn);

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: index.php");
    exit;
}

$error = $_SESSION['login_google_error'] ?? '';
unset($_SESSION['login_google_error']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Memastikan variabel $conn dari koneksi.php benar-benar ada
    if (!isset($conn)) {
        die("Error: Variabel koneksi database (\$conn) tidak ditemukan. Periksa kembali file koneksi.php Anda.");
    }

    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        $query = "SELECT * FROM users WHERE username = '$username' LIMIT 1";
        $result = mysqli_query($conn, $query);

        if ($result && mysqli_num_rows($result) > 0) {
            $user = mysqli_fetch_assoc($result);
            
            // Perbandingan teks langsung model biasa (tanpa hash)
            if ($password === $user['password']) {
                $_SESSION['logged_in'] = true;
                $_SESSION['role'] = 'admin';
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                
                header("Location: index.php");
                exit;
            } else {
                $error = 'Password yang Anda masukkan salah.';
            }
        } else {
            $error = 'Username tidak terdaftar.';
        }
    } else {
        $error = 'Silakan isi username dan password.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk Aplikasi Buku Induk - MA Ma'arif Darul Aman</title>
    <!-- Google Fonts & Bootstrap 5 -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons untuk pemanis form -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-card {
            border: none;
            border-radius: 24px;
            box-shadow: 0 15px 35px rgba(12, 57, 36, 0.1);
            background: #ffffff;
            width: 100%;
            max-width: 440px;
            overflow: hidden;
            transition: transform 0.3s ease;
        }
        .login-header {
            background: linear-gradient(135deg, #0c3924 0%, #145334 100%);
            color: #ffffff;
            padding: 3rem 2rem 2.5rem 2rem;
            text-align: center;
            position: relative;
        }
        .brand-logo {
            width: 90px;
            height: 90px;
            object-fit: contain;
            background: #ffffff;
            padding: 8px;
            border-radius: 50%;
            box-shadow: 0 8px 16px rgba(0,0,0,0.15);
            margin-bottom: 1.25rem;
        }
        .form-control {
            padding: 0.75rem 1rem;
            border-radius: 12px;
            border: 1.5px solid #e0e0e0;
            background-color: #f8f9fa;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }
        .form-control:focus {
            background-color: #ffffff;
            border-color: #198754;
            box-shadow: 0 0 0 0.25rem rgba(25, 135, 84, 0.15);
        }
        .btn-maarif {
            background-color: #198754;
            color: white;
            font-weight: 600;
            padding: 0.75rem 1rem;
            border-radius: 12px;
            border: none;
            transition: all 0.2s ease;
        }
        .btn-maarif:hover {
            background-color: #0f5132;
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(25, 135, 84, 0.3);
        }
        .btn-maarif:active {
            transform: translateY(0);
        }
        .alert {
            border-radius: 12px;
            border: none;
        }
        .footer-text {
            font-size: 0.8rem;
            color: #a0aec0;
            text-align: center;
            margin-top: 1.5rem;
        }
    </style>
</head>
<body>

<div class="login-card">
    <!-- Header dengan Logo dan Judul -->
    <div class="login-header">
        <img src="icon-512--.png" alt="Logo MA Ma'arif" class="brand-logo">
        <h4 class="mb-1 fw-bold" style="font-size: 1.35rem; letter-spacing: 0.5px;">APLIKASI BUKU INDUK</h4>
        <p class="mb-0 text-white-50 small" style="font-weight: 500;">MA Ma'arif Darul Aman Pringsurat</p>
    </div>
    
    <!-- Form Login -->
    <div class="card-body p-4 sm-p-5">
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger d-flex align-items-center small py-2.5 mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <div><?php echo $error; ?></div>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="mb-3">
                <label for="username" class="form-label small fw-semibold text-dark mb-1">Username</label>
                <div class="input-group">
                    <input type="text" class="form-control" id="username" name="username" placeholder="Masukkan username" required autofocus>
                </div>
            </div>
            
            <div class="mb-4">
                <label for="password" class="form-label small fw-semibold text-dark mb-1">Password</label>
                <div class="input-group">
                    <input type="password" class="form-control" id="password" name="password" placeholder="Masukkan password" required>
                </div>
            </div>
            
            <button type="submit" class="btn btn-maarif w-100 d-flex align-items-center justify-content-center gap-2">
                <i class="bi bi-box-arrow-in-right"></i> Masuk Sebagai Admin
            </button>
        </form>

        <?php
        require_once __DIR__ . '/google_oauth_config.php';
        $googleClientId = google_oauth_client_id();
        ?>
        <?php if ($googleClientId !== ''): ?>
            <div class="text-center text-muted small my-3">atau</div>
            <a href="login_google.php?start=1" class="btn btn-light border w-100 d-flex align-items-center justify-content-center gap-2 py-2">
                <i class="bi bi-google text-danger"></i> Masuk dengan Google dan Drive
            </a>
        <?php endif; ?>
        
        <div class="footer-text">
            &copy; <?php echo date('Y'); ?> MA Ma'arif Darul Aman. All Rights Reserved.
        </div>
    </div>
</div>

</body>
</html>