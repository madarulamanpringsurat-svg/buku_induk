<?php
if (!isset($_SESSION['logged_in'])) {
    header("Location: login.php");
    exit;
}

$pesan_sukses = '';
$pesan_error = '';

// =========================================================================
// MENANGKAP STATUS REDIRECT HASIL DARI INTERSEPTOR INDEX.PHP (JIKA ADA)
// =========================================================================
if (isset($_GET['status'])) {
    switch ($_GET['status']) {
        case 'success_add':
            $nama = isset($_GET['nama']) ? htmlspecialchars($_GET['nama']) : 'User';
            $pesan_sukses = 'User baru "' . $nama . '" berhasil didaftarkan ke database.';
            break;
        case 'err_exist':
            $user = isset($_GET['user']) ? htmlspecialchars($_GET['user']) : '';
            $pesan_error = 'Username "' . $user . '" sudah terdaftar. Gunakan nama lain.';
            break;
        case 'err_len':
            $pesan_error = 'Password baru minimal harus 4 karakter.';
            break;
        case 'err_empty':
            $pesan_error = 'Semua kolom form tambah user wajib diisi.';
            break;
        case 'err_db':
            $pesan_error = 'Gagal mendaftarkan user baru ke dalam database.';
            break;
    }
}

// ==========================================
// 1. PROSES ACTION GET (UNTUK HAPUS USER)
// ==========================================
if (isset($_GET['action']) && $_GET['action'] === 'hapus_user' && isset($_GET['id'])) {
    $id_hapus = mysqli_real_escape_string($conn, $_GET['id']);
    
    if ($id_hapus == $_SESSION['user_id']) {
        $pesan_error = 'Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif.';
    } else {
        $query_hapus = mysqli_query($conn, "DELETE FROM users WHERE id = '$id_hapus'");
        if ($query_hapus) {
            $pesan_sukses = 'Pengguna berhasil dihapus dari sistem.';
        } else {
            $pesan_error = 'Gagal menghapus pengguna. Error: ' . mysqli_error($conn);
        }
    }
}

// ==========================================
// 2. PROSES SUBMIT POST (PROFIL, TAMBAH, & EDIT USER)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // --- ACTION A: TAMBAH USER BARU ---
    if (isset($_POST['action_type']) && $_POST['action_type'] === 'tambah_user_baru') {
        $username_baru = mysqli_real_escape_string($conn, trim($_POST['username']));
        $nama_baru     = mysqli_real_escape_string($conn, trim($_POST['nama_lengkap']));
        $pass_baru     = trim($_POST['password_baru']);

        if (!empty($username_baru) && !empty($nama_baru) && !empty($pass_baru)) {
            if (strlen($pass_baru) >= 4) {
                // Cek apakah username sudah ada di database
                $cek_username = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username_baru' LIMIT 1");
                if (mysqli_num_rows($cek_username) > 0) {
                    $pesan_error = 'Username "' . htmlspecialchars($username_baru) . '" sudah terdaftar. Gunakan nama lain.';
                } else {
                    // Menyimpan user baru ke database
                    $query_tambah = mysqli_query($conn, "INSERT INTO users (username, nama_lengkap, password) VALUES ('$username_baru', '$nama_baru', '$pass_baru')");
                    if ($query_tambah) {
                        $pesan_sukses = 'User baru "' . htmlspecialchars($nama_baru) . '" berhasil didaftarkan ke database.';
                    } else {
                        $pesan_error = 'Gagal mendaftarkan user baru ke dalam database: ' . mysqli_error($conn);
                    }
                }
            } else {
                $pesan_error = 'Password akun baru minimal harus 4 karakter.';
            }
        } else {
            $pesan_error = 'Semua kolom form tambah user wajib diisi.';
        }
    }
    
    // --- ACTION B: EDIT USER LAIN ---
    elseif (isset($_POST['action_type']) && $_POST['action_type'] === 'edit_user_lain') {
        $id_edit = mysqli_real_escape_string($conn, $_POST['id']);
        $username_edit = mysqli_real_escape_string($conn, trim($_POST['username']));
        $nama_edit = mysqli_real_escape_string($conn, trim($_POST['nama_lengkap']));
        $pass_user_baru = trim($_POST['password_baru_user']);

        if (!empty($id_edit) && !empty($username_edit) && !empty($nama_edit)) {
            $cek_username_lain = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username_edit' AND id != '$id_edit' LIMIT 1");
            if (mysqli_num_rows($cek_username_lain) > 0) {
                $pesan_error = 'Username sudah digunakan oleh pengguna lain.';
            } else {
                $sql_update = "UPDATE users SET username = '$username_edit', nama_lengkap = '$nama_edit'";
                if (!empty($pass_user_baru)) {
                    if (strlen($pass_user_baru) >= 4) {
                        $sql_update .= ", password = '$pass_user_baru'";
                    } else {
                        $pesan_error = 'Password baru minimal 4 karakter.';
                    }
                }
                $sql_update .= " WHERE id = '$id_edit'";
                
                if (empty($pesan_error)) {
                    if (mysqli_query($conn, $sql_update)) {
                        $pesan_sukses = 'Data pengguna berhasil diperbarui.';
                        if ($id_edit == $_SESSION['user_id']) {
                            $_SESSION['nama_lengkap'] = $nama_edit;
                        }
                    } else {
                        $pesan_error = 'Gagal memperbarui data pengguna: ' . mysqli_error($conn);
                    }
                }
            }
        } else {
            $pesan_error = 'Kolom ID, Username, dan Nama Lengkap tidak boleh kosong.';
        }
    }
    
    // --- ACTION C: UBAH PROFIL & PASSWORD SENDIRI ---
    elseif (isset($_POST['action_type']) && $_POST['action_type'] === 'update_profil_sendiri') {
        $user_id = $_SESSION['user_id'];
        $nama_lengkap = mysqli_real_escape_string($conn, trim($_POST['nama_lengkap']));
        $password_lama = trim($_POST['password_lama']);
        $password_baru = trim($_POST['password_baru']);
        $konfirmasi_password = trim($_POST['konfirmasi_password']);

        if (!empty($nama_lengkap)) {
            $update_nama = mysqli_query($conn, "UPDATE users SET nama_lengkap = '$nama_lengkap' WHERE id = '$user_id'");
            if ($update_nama) {
                $_SESSION['nama_lengkap'] = $nama_lengkap;
                $pesan_sukses = 'Profil Anda berhasil diperbarui.';
            }
        }

        if (!empty($password_lama)) {
            $query = mysqli_query($conn, "SELECT password FROM users WHERE id = '$user_id' LIMIT 1");
            $user = mysqli_fetch_assoc($query);

            if ($password_lama === $user['password']) {
                if (!empty($password_baru)) {
                    if ($password_baru === $konfirmasi_password) {
                        if (strlen($password_baru) >= 4) {
                            $update_pass = mysqli_query($conn, "UPDATE users SET password = '$password_baru' WHERE id = '$user_id'");
                            if ($update_pass) {
                                $pesan_sukses = 'Profil dan Password Anda berhasil diperbarui.';
                            }
                        } else {
                            $pesan_error = 'Password baru minimal harus 4 karakter.';
                        }
                    } else {
                        $pesan_error = 'Konfirmasi password baru tidak cocok.';
                    }
                } else {
                    $pesan_error = 'Masukkan password baru jika ingin mengubahnya.';
                }
            } else {
                $pesan_error = 'Password lama yang Anda masukkan salah.';
            }
        }
    }
}
$madrasah_sukses = $_SESSION['pesan_madrasah_sukses'] ?? '';
$madrasah_error = $_SESSION['pesan_madrasah_error'] ?? '';
unset($_SESSION['pesan_madrasah_sukses'], $_SESSION['pesan_madrasah_error']);
$hasil_identitas = mysqli_query($conn, "SELECT * FROM identitas_madrasah WHERE id=1");
if (!$hasil_identitas) {
    die('Identitas madrasah gagal dimuat: ' . htmlspecialchars(mysqli_error($conn)));
}
$identitas_madrasah = mysqli_fetch_assoc($hasil_identitas);
?>

<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 text-dark fw-bold">Pengaturan Sistem</h4>
            <p class="text-muted small mb-0">Kelola profil Anda dan manajemen data pengguna aplikasi.</p>
        </div>
    </div>

    <?php if (!empty($pesan_sukses)): ?>
        <div class="alert alert-success alert-dismissible fade show small shadow-sm" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i> <?php echo $pesan_sukses; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($pesan_error)): ?>
        <div class="alert alert-danger alert-dismissible fade show small shadow-sm" role="alert">
            <i class="fa-solid fa-circle-exclamation me-2"></i> <?php echo $pesan_error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($madrasah_sukses !== ''): ?>
    <div class="alert alert-success alert-dismissible fade show small shadow-sm" role="alert">
        <i class="fa-solid fa-circle-check me-2"></i><?php echo htmlspecialchars($madrasah_sukses); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
    <?php endif; ?>
    <?php if ($madrasah_error !== ''): ?>
    <div class="alert alert-danger alert-dismissible fade show small shadow-sm" role="alert">
        <i class="fa-solid fa-circle-exclamation me-2"></i><?php echo htmlspecialchars($madrasah_error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="card-title mb-0 fw-bold"><i class="fa-solid fa-school text-success me-2"></i>Identitas Madrasah</h5>
            <small class="text-muted">Informasi ini digunakan pada Buku Induk Guru dan halaman ringkasan madrasah.</small>
        </div>
        <div class="card-body p-4">
            <form action="index?page=pengaturan" method="POST">
                <input type="hidden" name="simpan_identitas_madrasah" value="1">
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label">Nama Madrasah *</label><input class="form-control" name="nama_madrasah" value="<?php echo htmlspecialchars($identitas_madrasah['nama_madrasah'] ?? 'MA Ma’arif Darul Aman Pringsurat'); ?>" required></div>
                    <div class="col-md-3"><label class="form-label">NSM</label><input class="form-control" name="nsm" value="<?php echo htmlspecialchars($identitas_madrasah['nsm'] ?? ''); ?>"></div>
                    <div class="col-md-3"><label class="form-label">NPSN</label><input class="form-control" name="npsn" value="<?php echo htmlspecialchars($identitas_madrasah['npsn'] ?? ''); ?>"></div>
                    <div class="col-12"><label class="form-label">Alamat Madrasah</label><textarea class="form-control" name="alamat" rows="2"><?php echo htmlspecialchars($identitas_madrasah['alamat'] ?? ''); ?></textarea></div>
                    <div class="col-md-4"><label class="form-label">Telepon</label><input class="form-control" name="telepon" value="<?php echo htmlspecialchars($identitas_madrasah['telepon'] ?? ''); ?>"></div>
                    <div class="col-md-4"><label class="form-label">Email</label><input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($identitas_madrasah['email'] ?? ''); ?>"></div>
                    <div class="col-md-4"><label class="form-label">Website</label><input type="url" class="form-control" name="website" value="<?php echo htmlspecialchars($identitas_madrasah['website'] ?? ''); ?>" placeholder="https://"></div>
                    <div class="col-12 text-end"><button class="btn btn-success px-4" type="submit"><i class="fa-solid fa-floppy-disk me-1"></i>Simpan Identitas Madrasah</button></div>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-4 col-lg-5">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                            <i class="fa-solid fa-user-gear fa-2xl text-success"></i>
                        </div>
                        <h5 class="mb-1 fw-bold text-dark"><?php echo htmlspecialchars($_SESSION['nama_lengkap'] ?? ''); ?></h5>
                        <p class="text-muted small">ID Akun Anda: #<?php echo $_SESSION['user_id'] ?? ''; ?></p>
                    </div>

                    <form action="index?page=pengaturan" method="POST">
                        <input type="hidden" name="action_type" value="update_profil_sendiri">
                        
                        <h6 class="text-uppercase tracking-wider text-xs text-muted mb-3 fw-bold" style="font-size: 0.75rem; letter-spacing: 0.3px;">Informasi Profil</h6>
                        
                        <div class="mb-4">
                            <label for="nama_lengkap" class="form-label small fw-medium">Nama Lengkap Anda</label>
                            <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" value="<?php echo htmlspecialchars($_SESSION['nama_lengkap'] ?? ''); ?>" required>
                        </div>

                        <hr class="my-4 text-muted opacity-25">
                        <h6 class="text-uppercase tracking-wider text-xs text-muted mb-3 fw-bold" style="font-size: 0.75rem; letter-spacing: 0.3px;">Ubah Keamanan Password</h6>

                        <div class="mb-3">
                            <label for="password_lama" class="form-label small fw-medium">Password Saat Ini (Lama)</label>
                            <input type="password" class="form-control" id="password_lama" name="password_lama" placeholder="Kosongkan jika tidak ingin mengganti">
                        </div>

                        <div class="mb-3">
                            <label for="password_baru" class="form-label small fw-medium">Password Baru</label>
                            <input type="password" class="form-control" id="password_baru" name="password_baru" placeholder="Masukkan password baru">
                        </div>

                        <div class="mb-4">
                            <label for="konfirmasi_password" class="form-label small fw-medium">Konfirmasi Password Baru</label>
                            <input type="password" class="form-control" id="konfirmasi_password" name="konfirmasi_password" placeholder="Ulangi password baru">
                        </div>

                        <div class="d-flex gap-2 justify-content-end">
                            <button type="submit" class="btn btn-success px-4 small w-100"><i class="fa-solid fa-floppy-disk me-2"></i> Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-8 col-lg-7">
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0 fw-bold text-dark"><i class="fa-solid fa-users text-muted me-2"></i> Daftar Pengguna Sistem</h5>
                    <button class="btn btn-success btn-sm small px-3" data-bs-toggle="modal" data-bs-target="#modalTambahUser">
                        <i class="fa-solid fa-user-plus me-1"></i> Tambah User
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-muted small text-uppercase">
                                <tr>
                                    <th class="ps-4" style="width: 80px;">ID</th>
                                    <th>Username</th>
                                    <th>Nama Lengkap</th>
                                    <th class="text-center" style="width: 150px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="small">
                                <?php
                                $query_users = mysqli_query($conn, "SELECT id, username, nama_lengkap FROM users ORDER BY id ASC");
                                if ($query_users && mysqli_num_rows($query_users) > 0) {
                                    while ($row = mysqli_fetch_assoc($query_users)) {
                                        echo "<tr>";
                                        echo "<td class='ps-4 fw-bold text-muted'>#" . $row['id'] . "</td>";
                                        echo "<td><code class='bg-light px-2 py-1 rounded text-danger'>" . htmlspecialchars($row['username']) . "</code></td>";
                                        echo "<td class='fw-medium text-dark'>" . htmlspecialchars($row['nama_lengkap']) . "</td>";
                                        echo "<td class='text-center pe-4'>";
                                        
                                        echo "<button class='btn btn-light btn-sm text-primary me-1' data-bs-toggle='modal' data-bs-target='#modalEditUser' data-id='" . $row['id'] . "' data-username='" . htmlspecialchars($row['username']) . "' data-nama='" . htmlspecialchars($row['nama_lengkap']) . "' title='Edit User'><i class='fa-solid fa-pen-to-square'></i></button>";
                                        
                                        if ($row['id'] == $_SESSION['user_id']) {
                                            echo "<button class='btn btn-light btn-sm text-muted' disabled title='Akun sedang digunakan'><i class='fa-solid fa-trash'></i></button>";
                                        } else {
                                            echo "<a href='index.php?page=pengaturan&action=hapus_user&id=" . $row['id'] . "' class='btn btn-light btn-sm text-danger' onclick='return confirm(\"Apakah Anda yakin ingin menghapus user ini?\")' title='Hapus User'><i class='fa-solid fa-trash'></i></a>";
                                        }
                                        
                                        echo "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='4' class='text-center text-muted py-4'>Tidak ada data pengguna lain.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTambahUser" data-bs-backdrop="static" tabindex="-1" aria-labelledby="modalTambahUserLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title fw-bold small" id="modalTambahUserLabel"><i class="fa-solid fa-user-plus me-2"></i> Daftarkan Pengguna Baru</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="index?page=pengaturan" method="POST">
        <input type="hidden" name="action_type" value="tambah_user_baru">
        <div class="modal-body small">
            <div class="mb-3">
                <label for="username" class="form-label fw-medium">Username</label>
                <input type="text" class="form-control" id="username" name="username" placeholder="Contoh: sititu" required>
                <div class="form-text text-muted" style="font-size:0.7rem;">Gunakan huruf kecil/angka tanpa spasi.</div>
            </div>
            <div class="mb-3">
                <label for="nama_lengkap_baru" class="form-label fw-medium">Nama Lengkap</label>
                <input type="text" class="form-control" id="nama_lengkap_baru" name="nama_lengkap" placeholder="Nama lengkap staf" required>
            </div>
            <div class="mb-3">
                <label for="password_baru_tambah" class="form-label fw-medium">Kata Sandi (Password)</label>
                <input type="password" class="form-control" id="password_baru_tambah" name="password_baru" placeholder="Buat kata sandi akun" required>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-light btn-sm small" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-success btn-sm small"><i class="fa-solid fa-user-plus me-1"></i> Daftarkan User</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modalEditUser" data-bs-backdrop="static" tabindex="-1" aria-labelledby="modalEditUserLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title fw-bold small" id="modalEditUserLabel"><i class="fa-solid fa-user-pen me-2"></i> Perbarui Data Pengguna</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="index?page=pengaturan" method="POST">
        <input type="hidden" name="action_type" value="edit_user_lain">
        <input type="hidden" id="edit_id" name="id">
        
        <div class="modal-body small">
            <div class="mb-3">
                <label for="edit_username" class="form-label fw-medium">Username</label>
                <input type="text" class="form-control" id="edit_username" name="username" required>
            </div>
            <div class="mb-3">
                <label for="edit_nama" class="form-label fw-medium">Nama Lengkap</label>
                <input type="text" class="form-control" id="edit_nama" name="nama_lengkap" required>
            </div>
            <div class="mb-3">
                <label for="password_baru_user" class="form-label fw-medium">Password Baru (Opsional)</label>
                <input type="password" class="form-control" id="password_baru_user" name="password_baru_user" placeholder="Kosongkan jika tidak ingin mengubah password">
                <div class="form-text text-muted" style="font-size: 0.7rem;">Minimal 4 karakter jika diubah.</div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-light btn-sm small" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-success btn-sm small"><i class="fa-solid fa-floppy-disk me-1"></i> Simpan Perubahan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalEditUser = document.getElementById('modalEditUser');
    if (modalEditUser) {
        modalEditUser.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            
            const id = button.getAttribute('data-id');
            const username = button.getAttribute('data-username');
            const nama = button.getAttribute('data-nama');

            modalEditUser.querySelector('#edit_id').value = id;
            modalEditUser.querySelector('#edit_username').value = username;
            modalEditUser.querySelector('#edit_nama').value = nama;
            modalEditUser.querySelector('#password_baru_user').value = "";
        });
    }
});
</script>