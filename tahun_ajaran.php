<?php
$alert_message = '';

// ==========================================
// PROSES AKSI: TAMBAH TAHUN AJARAN
// ==========================================
if (isset($_POST['simpan_ta'])) {
    $ta = mysqli_real_escape_string($conn, trim($_POST['tahun_ajaran']));
    $kepala = mysqli_real_escape_string($conn, trim($_POST['nama_kepala']));
    $nip = mysqli_real_escape_string($conn, trim($_POST['nip']));
    
    // Cek duplikasi data agar tahun ajaran tidak ganda
    $cek_ganda = mysqli_query($conn, "SELECT id FROM tahun_ajaran WHERE tahun_ajaran = '$ta'");
    if (mysqli_num_rows($cek_ganda) > 0) {
        $alert_message = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
                            <i class='fa-solid fa-triangle-exclamation me-2'></i> <strong>Gagal!</strong> Tahun Ajaran <b>$ta</b> sudah terdaftar sebelumnya.
                            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                          </div>";
    } else {
        $query = "INSERT INTO tahun_ajaran (tahun_ajaran, nama_kepala, nip) VALUES ('$ta', '$kepala', '$nip')";
        if (mysqli_query($conn, $query)) {
            $alert_message = "<div class='alert alert-success alert-dismissible fade show' role='alert'>
                                <i class='fa-solid fa-circle-check me-2'></i> Tahun Ajaran baru berhasil ditambahkan!
                                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                              </div>";
        }
    }
}

// ==========================================
// PROSES AKSI: EDIT TAHUN AJARAN
// ==========================================
if (isset($_POST['update_ta'])) {
    $id_ta = mysqli_real_escape_string($conn, $_POST['id_ta']);
    $ta = mysqli_real_escape_string($conn, trim($_POST['tahun_ajaran']));
    $kepala = mysqli_real_escape_string($conn, trim($_POST['nama_kepala']));
    $nip = mysqli_real_escape_string($conn, trim($_POST['nip']));
    
    $query_update = "UPDATE tahun_ajaran SET tahun_ajaran = '$ta', nama_kepala = '$kepala', nip = '$nip' WHERE id = '$id_ta'";
    if (mysqli_query($conn, $query_update)) {
        $alert_message = "<div class='alert alert-success alert-dismissible fade show' role='alert'>
                            <i class='fa-solid fa-circle-check me-2'></i> Data Tahun Ajaran berhasil diperbarui!
                            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                          </div>";
    } else {
        $alert_message = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
                            <i class='fa-solid fa-circle-xmark me-2'></i> Gagal memperbarui data ke database.
                            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                          </div>";
    }
}

// ==========================================
// PROSES AKSI: HAPUS TAHUN AJARAN & VALIDASI
// ==========================================
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id_hapus = mysqli_real_escape_string($conn, $_GET['id']);
    
    // 1. Ambil nama tahun ajaran terlebih dahulu berdasarkan ID untuk keperluan pengecekan relasi siswa
    $get_ta = mysqli_query($conn, "SELECT tahun_ajaran FROM tahun_ajaran WHERE id = '$id_hapus' LIMIT 1");
    if (mysqli_num_rows($get_ta) > 0) {
        $data_target = mysqli_fetch_assoc($get_ta);
        $nama_ta_target = $data_target['tahun_ajaran'];
        
        // 2. VALIDASI KRITIKAL: Cek apakah nama tahun ajaran ini sedang digunakan di tabel siswa
        $cek_siswa = mysqli_query($conn, "SELECT id FROM siswa WHERE tahun_ajaran = '$nama_ta_target' LIMIT 1");
        
        if (mysqli_num_rows($cek_siswa) > 0) {
            // JIKA ADA SISWA: Berikan proteksi penolakan penghapusan
            $alert_message = "<div class='alert alert-danger alert-dismissible fade show p-3' role='alert'>
                                <h5 class='fw-bold mb-1'><i class='fa-solid fa-ban me-2'></i>Tidak Dapat Menghapus!</h5>
                                <p class='small mb-0'>Tahun ajaran <strong>$nama_ta_target</strong> tidak dapat dihapus karena masih digunakan oleh data siswa yang terdaftar di sistem. Silakan hapus atau pindahkan data siswa terkait terlebih dahulu.</p>
                                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                              </div>";
        } else {
            // JIKA AMAN (KOSONG): Lakukan eksekusi hapus baris
            $query_hapus = "DELETE FROM tahun_ajaran WHERE id = '$id_hapus'";
            if (mysqli_query($conn, $query_hapus)) {
                $alert_message = "<div class='alert alert-success alert-dismissible fade show' role='alert'>
                                    <i class='fa-solid fa-trash-can me-2'></i> Tahun Ajaran berhasil dihapus secara permanen.
                                    <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                                  </div>";
            }
        }
    }
}

// Mengambil seluruh database tahun ajaran terbaru
$data_ta = mysqli_query($conn, "SELECT * FROM tahun_ajaran ORDER BY tahun_ajaran DESC");
?>

<?php if (!empty($alert_message)) echo $alert_message; ?>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white fw-bold py-3"><i class="fa-solid fa-plus me-2 text-success"></i> Tambah Tahun Ajaran</div>
    <div class="card-body">
        <form action="" method="POST" class="row g-3">
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Tahun Ajaran</label>
                <input type="text" name="tahun_ajaran" class="form-control" placeholder="Contoh: 2025/2026" required>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Nama Kepala Madrasah</label>
                <input type="text" name="nama_kepala" class="form-control" placeholder="Nama beserta gelar" required>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold">NIP Kepala Madrasah</label>
                <input type="text" name="nip" class="form-control" placeholder="Kosongkan jika tidak ada">
            </div>
            <div class="col-12 text-end">
                <button type="submit" name="simpan_ta" class="btn btn-success px-4 rounded-3"><i class="fa-solid fa-floppy-disk me-1"></i> Simpan Data</button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white fw-bold py-3"><i class="fa-solid fa-table-list me-2 text-success"></i> Daftar Tahun Ajaran</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle m-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 70px;" class="ps-4">No</th>
                        <th>Tahun Ajaran</th>
                        <th>Nama Kepala Madrasah</th>
                        <th>NIP</th>
                        <th class="text-center" style="width: 160px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no=1; while($row = mysqli_fetch_assoc($data_ta)): ?>
                    <tr>
                        <td class="ps-4"><?= $no++ ?></td>
                        <td><b><?= htmlspecialchars($row['tahun_ajaran']) ?></b></td>
                        <td><?= htmlspecialchars($row['nama_kepala']) ?></td>
                        <td><?= !empty($row['nip']) ? htmlspecialchars($row['nip']) : '-' ?></td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" 
                                        class="btn btn-outline-primary px-2.5" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editModal<?= $row['id'] ?>"
                                        title="Ubah Data">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <a href="index.php?page=tahun_ajaran&action=delete&id=<?= $row['id'] ?>" 
                                   class="btn btn-outline-danger px-2.5" 
                                   onclick="return confirm('Apakah Anda yakin ingin menghapus data Tahun Ajaran <?= $row['tahun_ajaran'] ?>?')"
                                   title="Hapus Data">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>

                    <div class="modal fade" id="editModal<?= $row['id'] ?>"  tabindex="-1" aria-labelledby="editModalLabel<?= $row['id'] ?>" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content rounded-4 border-0 shadow">
                                <div class="modal-header bg-light">
                                    <h5 class="modal-title fw-bold" id="editModalLabel<?= $row['id'] ?>">
                                        <i class="fa-solid fa-pen-to-square text-primary me-2"></i>Edit Tahun Ajaran
                                    </h5>
                                    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                                </div>
                                <form action="index?page=tahun_ajaran" method="POST">
                                    <div class="modal-body p-4">
                                        <input type="hidden" name="id_ta" value="<?= $row['id'] ?>">
                                        
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold">Tahun Ajaran</label>
                                            <input type="text" name="tahun_ajaran" class="form-control" value="<?= htmlspecialchars($row['tahun_ajaran']) ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold">Nama Kepala Madrasah</label>
                                            <input type="text" name="nama_kepala" class="form-control" value="<?= htmlspecialchars($row['nama_kepala']) ?>" required>
                                        </div>
                                        <div class="mb-0">
                                            <label class="form-label small fw-semibold">NIP Kepala Madrasah</label>
                                            <input type="text" name="nip" class="form-control" value="<?= htmlspecialchars($row['nip']) ?>" placeholder="Kosongkan jika tidak ada">
                                        </div>
                                    </div>
                                    <div class="modal-footer bg-light border-0">
                                        <button type="button" class="btn btn-secondary btn-sm px-3 rounded-2" data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" name="update_ta" class="btn btn-primary btn-sm px-3 rounded-2">
                                            <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Perubahan
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>