<?php 
// Memastikan koneksi database dimuat jika dipanggil independen
if (!isset($conn) && !isset($koneksi)) {
    include 'koneksi.php';
}
$conn = isset($conn) ? $conn : (isset($koneksi) ? $koneksi : null);
?>

<!-- Alert Notifikasi -->
<?php if(isset($_GET['pesan'])): ?>
    <?php if($_GET['pesan'] == 'tambah_berhasil'): ?>
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i> Data guru berhasil ditambahkan!
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php elseif($_GET['pesan'] == 'hapus_berhasil'): ?>
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="fa-solid fa-circle-xmark me-2"></i> Data guru berhasil dihapus!
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php elseif($_GET['pesan'] == 'update_berhasil'): ?>
        <div class="alert alert-primary alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="fa-solid fa-circle-info me-2"></i> Data guru berhasil diperbarui!
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php elseif($_GET['pesan'] == 'gagal'): ?>
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="fa-solid fa-circle-exclamation me-2"></i> Gagal menyimpan data: 
            <strong><?php echo htmlspecialchars($_SESSION['db_error'] ?? 'Terjadi kesalahan sistem.'); unset($_SESSION['db_error']); ?></strong>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
<?php endif; ?>

<div class="row g-4">
    <!-- Form Tambah Guru -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-success text-white py-3">
                <h6 class="card-title mb-0 fw-bold">
                    <i class="fa-solid fa-user-plus me-2"></i>Tambah Guru & Staff
                </h6>
            </div>
            <div class="card-body p-4">
                <form action="index?page=data_guru" method="POST">
                    <!-- Penanda Request POST -->
                    <input type="hidden" name="action_simpan" value="1">
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">NIP / No. Induk</label>
                        <input type="text" class="form-control" name="nip" value="-" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Nama Lengkap & Gelar</label>
                        <input type="text" class="form-control" name="nama_guru" placeholder="Contoh: Triyana Fajarwanti, S.Pd" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Jabatan Utama</label>
                        <input type="text" class="form-control" name="jabatan" value="Guru Mata Pelajaran" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Jabatan Tambahan (Opsional)</label>
                        <input type="text" class="form-control" name="jabatan_tambahan" value="-" placeholder="Misal: Waka Kurikulum / Wali Kelas">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Mata Pelajaran Diampu</label>
                        <input type="text" class="form-control" name="mata_pelajaran" value="-" placeholder="Contoh: Bahasa Inggris">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Email Gmail untuk Login Guru</label>
                        <input type="email" class="form-control" name="google_email" placeholder="nama@gmail.com">
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="google_login_enabled" id="google-login-enabled" value="1">
                        <label class="form-check-label small" for="google-login-enabled">Aktifkan login Google untuk guru ini</label>
                    </div>
                    <button type="submit" name="simpan" class="btn btn-success w-100 fw-semibold py-2">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Data
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Tabel Data Guru -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <h6 class="card-title mb-0 fw-bold text-dark">
                    <i class="fa-solid fa-list me-2 text-success"></i>Daftar Guru & Staff
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr class="small text-muted text-uppercase">
                                <th class="ps-3" style="width: 5%;">No</th>
                                <th>Nama Guru</th>
                                <th>NIP</th>
                                <th>Jabatan Utama</th>
                                <th>Jabatan Tambahan</th>
                                <th>Mapel</th>
                                <th class="text-center" style="width: 15%;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            <?php 
                            $no = 1;
                            $query_tampil = mysqli_query($conn, "SELECT * FROM guru ORDER BY nama_guru ASC");
                            if (!$query_tampil) {
                                echo "<tr><td colspan='7' class='text-center text-danger py-4'>Data guru gagal dimuat. Silakan periksa koneksi database.</td></tr>";
                            } elseif (mysqli_num_rows($query_tampil) == 0) {
                                echo "<tr><td colspan='7' class='text-center text-muted py-4'>Belum ada data guru.</td></tr>";
                            } else {
                                while ($row = mysqli_fetch_assoc($query_tampil)) {
                                    ?>
                                    <tr>
                                        <td class="ps-3 font-monospace"><?php echo $no++; ?></td>
                                        <td class="fw-bold text-dark"><?php echo htmlspecialchars($row['nama_guru']); ?></td>
                                        <td><code><?php echo htmlspecialchars($row['nip']); ?></code></td>
                                        <td><span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><?php echo htmlspecialchars($row['jabatan']); ?></span></td>
                                        <td class="text-muted fst-italic"><?php echo htmlspecialchars($row['jabatan_tambahan']); ?></td>
                                        <td><?php echo htmlspecialchars($row['mata_pelajaran']); ?></td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="edit_guru.php?id=<?php echo $row['id']; ?>" class="btn btn-warning text-dark" title="Edit Data">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                </a>
                                                <a href="hapus_guru.php?id=<?php echo $row['id']; ?>" class="btn btn-danger" onclick="return confirm('Yakin ingin menghapus data guru ini?')" title="Hapus Data">
                                                    <i class="fa-solid fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>