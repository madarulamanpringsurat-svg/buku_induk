<?php
// Koneksi database placeholder (Silakan sesuaikan dengan konfigurasi database Anda)
// $conn = mysqli_connect("localhost", "root", "", "db_madrasah");

// Ambil filter tahun ajaran yang dipilih dari URL
$filter_ta = isset($_GET['tahun_ajaran']) ? $_GET['tahun_ajaran'] : '';

// Handle Aksi Hapus (Diselaraskan agar setelah hapus kembali ke index template)
if (isset($_GET['aksi']) && $_GET['aksi'] == 'hapus' && isset($_GET['id'])) {
    $id_hapus = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if ($id_hapus === false || $id_hapus < 1) {
        $pesan_hapus = 'ID siswa tidak valid.';
    } else {
        try {
            $query_hapus = mysqli_query($conn, "DELETE FROM siswa WHERE id = $id_hapus");
            $pesan_hapus = $query_hapus && mysqli_affected_rows($conn) === 1
                ? 'Data siswa berhasil dihapus!'
                : 'Data siswa tidak ditemukan atau gagal dihapus.';
        } catch (Throwable $error) {
            error_log('Penghapusan siswa gagal: ' . $error->getMessage());
            $pesan_hapus = 'Data siswa gagal dihapus. Periksa log server untuk detailnya.';
        }
    }
    echo '<script>alert(' . json_encode($pesan_hapus, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) . '); window.location="index.php?page=data_siswa";</script>';
    exit;
}

// ====================================================================
// FITUR DIUBAH: Proses Simpan / Update Link Raport Langsung Ke Tabel Siswa
// ====================================================================
if (isset($_POST['simpan_raport'])) {
    $id_siswa = mysqli_real_escape_string($conn, $_POST['id_siswa']);
    $link_drive = mysqli_real_escape_string($conn, $_POST['link_drive']);
    
    // Menyimpan data link langsung ke kolom link_drive yang baru ditambahkan di tabel siswa
    $query_raport = "UPDATE siswa SET link_drive = '$link_drive' WHERE id = '$id_siswa'";
    
    if (mysqli_query($conn, $query_raport)) {
        echo "<script>alert('Link Google Drive Raport & Berkas berhasil disimpan!'); window.location='index.php?page=data_siswa';</script>";
    } else {
        echo "<script>alert('Gagal menyimpan link raport: " . mysqli_error($conn) . "');</script>";
    }
}

// ==========================================
// HANDLE AKSI UPDATE / SIMPAN PERUBAHAN SISWA
// ==========================================
if (isset($_POST['update_siswa'])) {
    $id = mysqli_real_escape_string($conn, $_POST['id']);
    $tahun_ajaran = mysqli_real_escape_string($conn, $_POST['tahun_ajaran']);
    $nomor_induk = mysqli_real_escape_string($conn, $_POST['nomor_induk']);
    $nis_lokal = mysqli_real_escape_string($conn, $_POST['nis_lokal']);
    $nisn = mysqli_real_escape_string($conn, $_POST['nisn']);
    $nik = mysqli_real_escape_string($conn, $_POST['nik']);
    $nama_siswa = mysqli_real_escape_string($conn, $_POST['nama_siswa']);
    $tempat_lahir = mysqli_real_escape_string($conn, $_POST['tempat_lahir']);
    $tanggal_lahir = mysqli_real_escape_string($conn, $_POST['tanggal_lahir']);
    $jenis_kelamin = mysqli_real_escape_string($conn, $_POST['jenis_kelamin']);
    $agama = mysqli_real_escape_string($conn, $_POST['agama']);
    $jumlah_saudara = mysqli_real_escape_string($conn, $_POST['jumlah_saudara']);
    $diterima_di_kelas = mysqli_real_escape_string($conn, $_POST['diterima_di_kelas']);
    $tanggal_masuk = mysqli_real_escape_string($conn, $_POST['tanggal_masuk']);
    $jarak_rumah = mysqli_real_escape_string($conn, $_POST['jarak_rumah']);
    
    // Tab Alamat
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
    $desa_kelurahan = mysqli_real_escape_string($conn, $_POST['desa_kelurahan']);
    $kecamatan = mysqli_real_escape_string($conn, $_POST['kecamatan']);
    $kabupaten = mysqli_real_escape_string($conn, $_POST['kabupaten']);
    $provinsi = mysqli_real_escape_string($conn, $_POST['provinsi']);
    $kodepos = mysqli_real_escape_string($conn, $_POST['kodepos']);
    
    // Tab Riwayat SMP
    $nama_sekolah_asal = mysqli_real_escape_string($conn, $_POST['nama_sekolah_asal']);
    $npsn_sekolah = mysqli_real_escape_string($conn, $_POST['npsn_sekolah']);
    $no_seri_ijazah_smp = mysqli_real_escape_string($conn, $_POST['no_seri_ijazah_smp']);
    $no_blangko_skhu_smp = mysqli_real_escape_string($conn, $_POST['no_blangko_skhu_smp']);
    $no_peserta_un_smp = mysqli_real_escape_string($conn, $_POST['no_peserta_un_smp']);
    $total_nilai_un = mysqli_real_escape_string($conn, $_POST['total_nilai_un']);
    $tanggal_kelulusan_smp = mysqli_real_escape_string($conn, $_POST['tanggal_kelulusan_smp']);
    
    // Tab Orang Tua
    $nomor_kk = mysqli_real_escape_string($conn, $_POST['nomor_kk']);
    $nama_ayah = mysqli_real_escape_string($conn, $_POST['nama_ayah']);
    $nik_ayah = mysqli_real_escape_string($conn, $_POST['nik_ayah']);
    $tanggal_lahir_ayah = mysqli_real_escape_string($conn, $_POST['tanggal_lahir_ayah']);
    $pendidikan_ayah = mysqli_real_escape_string($conn, $_POST['pendidikan_ayah']);
    $pekerjaan_ayah = mysqli_real_escape_string($conn, $_POST['pekerjaan_ayah']);
    $penghasilan_ayah = mysqli_real_escape_string($conn, $_POST['penghasilan_ayah']);
    $status_ayah = mysqli_real_escape_string($conn, $_POST['status_ayah']);
    
    $nama_ibu = mysqli_real_escape_string($conn, $_POST['nama_ibu']);
    $nik_ibu = mysqli_real_escape_string($conn, $_POST['nik_ibu']);
    $tanggal_lahir_ibu = mysqli_real_escape_string($conn, $_POST['tanggal_lahir_ibu']);
    $pendidikan_ibu = mysqli_real_escape_string($conn, $_POST['pendidikan_ibu']);
    $pekerjaan_ibu = mysqli_real_escape_string($conn, $_POST['pekerjaan_ibu']);
    $penghasilan_ibu = mysqli_real_escape_string($conn, $_POST['penghasilan_ibu']);
    $status_ibu = mysqli_real_escape_string($conn, $_POST['status_ibu']);
    
    // Tab Wali & Bansos
    $nama_wali = mysqli_real_escape_string($conn, $_POST['nama_wali']);
    $nik_wali = mysqli_real_escape_string($conn, $_POST['nik_wali']);
    $tanggal_lahir_wali = mysqli_real_escape_string($conn, $_POST['tanggal_lahir_wali']);
    $pendidikan_wali = mysqli_real_escape_string($conn, $_POST['pendidikan_wali']);
    $pekerjaan_wali = mysqli_real_escape_string($conn, $_POST['pekerjaan_wali']);
    $penghasilan_wali = mysqli_real_escape_string($conn, $_POST['penghasilan_wali']);
    $nomor_kip = mysqli_real_escape_string($conn, $_POST['nomor_kip']);
    $nomor_kks = mysqli_real_escape_string($conn, $_POST['nomor_kks']);
    $nomor_pkh = mysqli_real_escape_string($conn, $_POST['nomor_pkh']);
    
    // Tab Status Keluar
    $alasan_putus_sekolah = mysqli_real_escape_string($conn, $_POST['alasan_putus_sekolah']);
    $tanggal_putus_sekolah = mysqli_real_escape_string($conn, $_POST['tanggal_putus_sekolah']);
    $alasan_mutasi = mysqli_real_escape_string($conn, $_POST['alasan_mutasi']);
    $tanggal_mutasi = mysqli_real_escape_string($conn, $_POST['tanggal_mutasi']);
    $tanggal_lulus_ma = mysqli_real_escape_string($conn, $_POST['tanggal_lulus_ma']);
    $no_ijazah_ma = mysqli_real_escape_string($conn, $_POST['no_ijazah_ma']);
    $no_skhu_ma = mysqli_real_escape_string($conn, $_POST['no_skhu_ma']);

    $sql_update = "UPDATE siswa SET 
        tahun_ajaran='$tahun_ajaran', nomor_induk='$nomor_induk', nis_lokal='$nis_lokal', nisn='$nisn', nik='$nik', nama_siswa='$nama_siswa', tempat_lahir='$tempat_lahir', tanggal_lahir='$tanggal_lahir', jenis_kelamin='$jenis_kelamin', agama='$agama', jumlah_saudara='$jumlah_saudara', diterima_di_kelas='$diterima_di_kelas', tanggal_masuk='$tanggal_masuk', jarak_rumah='$jarak_rumah',
        alamat='$alamat', desa_kelurahan='$desa_kelurahan', kecamatan='$kecamatan', kabupaten='$kabupaten', provinsi='$provinsi', kodepos='$kodepos',
        nama_sekolah_asal='$nama_sekolah_asal', npsn_sekolah='$npsn_sekolah', no_seri_ijazah_smp='$no_seri_ijazah_smp', no_blangko_skhu_smp='$no_blangko_skhu_smp', no_peserta_un_smp='$no_peserta_un_smp', total_nilai_un='$total_nilai_un', tanggal_kelulusan_smp='$tanggal_kelulusan_smp',
        nomor_kk='$nomor_kk', nama_ayah='$nama_ayah', nik_ayah='$nik_ayah', tanggal_lahir_ayah='$tanggal_lahir_ayah', pendidikan_ayah='$pendidikan_ayah', pekerjaan_ayah='$pekerjaan_ayah', penghasilan_ayah='$penghasilan_ayah', status_ayah='$status_ayah',
        nama_ibu='$nama_ibu', nik_ibu='$nik_ibu', tanggal_lahir_ibu='$tanggal_lahir_ibu', pendidikan_ibu='$pendidikan_ibu', pekerjaan_ibu='$pekerjaan_ibu', penghasilan_ibu='$penghasilan_ibu', status_ibu='$status_ibu',
        nama_wali='$nama_wali', nik_wali='$nik_wali', tanggal_lahir_wali='$tanggal_lahir_wali', pendidikan_wali='$pendidikan_wali', pekerjaan_wali='$pekerjaan_wali', penghasilan_wali='$penghasilan_wali',
        nomor_kip='$nomor_kip', nomor_kks='$nomor_kks', nomor_pkh='$nomor_pkh',
        alasan_putus_sekolah='$alasan_putus_sekolah', tanggal_putus_sekolah='$tanggal_putus_sekolah', alasan_mutasi='$alasan_mutasi', tanggal_mutasi='$tanggal_mutasi', tanggal_lulus_ma='$tanggal_lulus_ma', no_ijazah_ma='$no_ijazah_ma', no_skhu_ma='$no_skhu_ma'
        WHERE id='$id'";

    if (mysqli_query($conn, $sql_update)) {
        echo "<script>alert('Data siswa berhasil diperbarui!'); window.location='index.php?page=data_siswa';</script>";
    } else {
        echo "<script>alert('Gagal memperbarui data: " . mysqli_error($conn) . "');</script>";
    }
}
?>

<style>
    @media print {
        body * {
            visibility: hidden;
        }
        .no-print {
            display: none !important;
        }
        #areaCetakSiswa, #areaCetakSiswa * {
            visibility: visible;
        }
        #areaCetakSiswa {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }
        @page {
            size: 215mm 330mm; /* Standar Ukuran Kertas F4 / Folio Indonesia */
            margin: 15mm 15mm 15mm 15mm;
        }
        .page-break {
            page-break-before: always;
        }
    }
    .table-view-detail th {
        background-color: #f8f9fa;
        width: 30%;
        font-weight: 600;
    }
    .print-title {
        text-align: center;
        text-transform: uppercase;
        margin-bottom: 20px;
        border-bottom: 3px double #000;
        padding-bottom: 10px;
    }
    /* Style Kotak Foto */
    .kotak-foto-cetak {
        width: 3cm;
        height: 4cm;
        border: 2px dashed #666;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        font-size: 11px;
        color: #555;
        font-weight: bold;
        background-color: #fafafa;
        margin-bottom: 10px;
    }
</style>

<div class="container-fluid pt-4 px-4">
    <div class="row g-4 mb-4 no-print">
        <div class="col-12">
            <div class="bg-light rounded h-100 p-4 shadow-sm border-start border-primary border-3">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <h4 class="mb-1 text-dark fw-bold"><i class="fa-solid fa-users me-2 text-primary"></i>Database Profil & Raport Siswa</h4>
                        <p class="mb-0 text-muted small">Kelola data profil lengkap, dokumen digital, dan arsip raport per semester</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="index.php?page=buku_induk_guru" class="btn btn-outline-danger btn-sm px-3 fw-semibold"><i class="fa-solid fa-address-card me-1"></i>Buku Induk Guru</a>
                        <form method="GET" action="index" class="d-flex gap-2">
                            <input type="hidden" name="page" value="data_siswa">
                            <select name="tahun_ajaran" class="form-select form-select-sm" style="min-width: 160px;" onchange="this.form.submit()">
                                <option value="">-- Semua TA --</option>
                                <?php
                                $q_ta = mysqli_query($conn, "SELECT DISTINCT tahun_ajaran FROM siswa ORDER BY tahun_ajaran DESC");
                                while($ta = mysqli_fetch_assoc($q_ta)) {
                                    $selected = ($filter_ta == $ta['tahun_ajaran']) ? 'selected' : '';
                                    echo "<option value='".$ta['tahun_ajaran']."' $selected>".$ta['tahun_ajaran']."</option>";
                                }
                                ?>
                            </select>
                        </form>
                        <a href="index.php?page=tambah_siswa" class="btn btn-primary btn-sm px-3 fw-semibold"><i class="fa-solid fa-user-plus me-1"></i>Tambah Siswa Baru</a>
                        <a href="export_excel_siswa.php?tahun_ajaran=<?= urlencode($filter_ta) ?>" class="btn btn-success btn-sm px-3 fw-semibold" target="_blank"><i class="fa-solid fa-file-excel me-1"></i>Export Master Excel</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12">
            <div class="bg-light rounded h-100 p-4 shadow-sm border">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 no-print">
                    <h5 class="mb-0 fw-bold text-secondary"><i class="fa-solid fa-table-list me-2"></i>Daftar Siswa Terdaftar <?php echo !empty($filter_ta) ? "TA: <span class='text-primary'>$filter_ta</span>" : "(Semua Angkatan)"; ?></h5>
                    <span class="badge bg-secondary p-2 small">Total: 
                        <?php 
                        $where_clause = !empty($filter_ta) ? "WHERE tahun_ajaran = '$filter_ta'" : "";
                        $c_res = mysqli_query($conn, "SELECT COUNT(*) as total FROM siswa $where_clause");
                        $c_row = mysqli_fetch_assoc($c_res);
                        echo $c_row['total'];
                        ?> Siswa
                    </span>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover align-middle border" id="tabelMasterSiswa" style="font-size: 0.875rem;">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-center" width="5%">No</th>
                                <th width="10%">No. Induk / NISN</th>
                                <th width="22%">Nama Lengkap</th>
                                <th class="text-center" width="5%">JK</th>
                                <th width="18%">Tempat, Tgl Lahir</th>
                                <th width="15%">Nama Orang Tua</th>
                                <th class="text-center no-print" width="25%">Aksi Manajemen Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no = 1;
                            $sql_siswa = "SELECT * FROM siswa $where_clause ORDER BY nama_siswa ASC";
                            $res_siswa = mysqli_query($conn, $sql_siswa);
                            
                            while ($siswa = mysqli_fetch_assoc($res_siswa)) {
                                $json_siswa = htmlspecialchars(json_encode($siswa), ENT_QUOTES, 'UTF-8');
                            ?>
                            <tr>
                                <td class="text-center fw-bold text-muted"><?= $no++; ?></td>
                                <td>
                                    <span class="fw-semibold text-dark d-block"><?= !empty($siswa['nomor_induk']) ? $siswa['nomor_induk'] : '-'; ?></span>
                                    <small class="text-muted small">NISN: <?= !empty($siswa['nisn']) ? $siswa['nisn'] : '-'; ?></small>
                                </td>
                                <td>
                                    <span class="fw-bold text-primary text-uppercase d-block"><?= $siswa['nama_siswa']; ?></span>
                                    <small class="text-muted italic small">TA: <?= $siswa['tahun_ajaran']; ?> | Kelas: <?= $siswa['diterima_di_kelas'] ?></small>
                                </td>
                                <td class="text-center">
                                    <?php if ($siswa['jenis_kelamin'] == 'Laki-laki'): ?>
                                        <span class="badge bg-info text-dark fw-bold px-2">L</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger fw-bold px-2">P</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="d-block"><?= !empty($siswa['tempat_lahir']) ? $siswa['tempat_lahir'] : '-'; ?></span>
                                    <small class="text-secondary"><?= !empty($siswa['tanggal_lahir']) ? date('d-m-Y', strtotime($siswa['tanggal_lahir'])) : '-'; ?></small>
                                </td>
                                <td>
                                    <span class="d-block small"><i class="fa-solid fa-user-tie text-muted me-1"></i>A: <?= !empty($siswa['nama_ayah']) ? $siswa['nama_ayah'] : '-'; ?></span>
                                    <span class="d-block small"><i class="fa-solid fa-user text-muted me-1"></i>I:  <?= !empty($siswa['nama_ibu']) ? $siswa['nama_ibu'] : '-'; ?></span>
                                </td>
                                <td class="text-center no-print">
                                    <div class="d-flex justify-content-center gap-1">
                                        <button type="button" class="btn btn-info btn-sm text-dark fw-semibold" onclick="bukaModalRaport(<?php echo $json_siswa; ?>)" title="Arsip Raport & Berkas">
                                            <i class="fa-solid fa-folder-open me-1"></i>Raport
                                        </button>
                                        <button type="button" class="btn btn-light btn-sm border" onclick="bukaModalView(<?php echo $json_siswa; ?>)" title="Lihat Profil Lengkap">
                                            <i class="fa-solid fa-eye text-secondary"></i>
                                        </button>
                                        <button type="button" class="btn btn-warning btn-sm" onclick="bukaModalEdit(<?php echo $json_siswa; ?>)" title="Edit Siswa">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <a href="index.php?page=data_siswa&aksi=hapus&id=<?= $siswa['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus data siswa <?= addslashes($siswa['nama_siswa']); ?> secara permanen dari sistem?')" title="Hapus Data">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade no-print" id="modalRaportSiswa" tabindex="-1" aria-labelledby="modalRaportLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-dark">
                <h5 class="modal-title fw-bold" id="modalRaportLabel"><i class="fa-solid fa-folder-tree me-2"></i>Arsip Dokumen & Raport Digital</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-light border mb-3">
                    <table class="table table-sm table-borderless mb-0 m-0 p-0" style="font-size:0.9rem;">
                        <tr><td width="20%" class="fw-bold text-muted">Nama Siswa</td><td width="2%">:</td><td id="m_nama_siswa" class="fw-bold text-primary text-uppercase"></td></tr>
                        <tr><td class="fw-bold text-muted">ID Sistem</td><td>:</td><td id="m_id_siswa_text" class="font-monospace text-secondary"></td></tr>
                    </table>
                </div>

                <div class="card border-info mb-3">
                    <div class="card-header bg-info text-dark py-2 fw-semibold small">
                        <i class="fa-solid fa-link me-1"></i> Link Raport Aktif Saat Ini
                    </div>
                    <div class="card-body p-3">
                        <div id="status_link_raport">
                        </div>
                    </div>
                </div>

                <div class="card border-primary mb-3">
                    <div class="card-header bg-primary text-white py-2 fw-semibold small">
                        <i class="fa-solid fa-cloud-arrow-up me-1"></i> Form Input / Update Link Arsip Cloud (Google Drive)
                    </div>
                    <form method="POST" action="">
                        <input type="hidden" name="id_siswa" id="m_id_siswa_input">
                        <div class="card-body p-3 row g-3">
                            <div class="col-md-12">
                                <label class="form-label small fw-bold">URL Google Drive Raport / Berkas Dokumen Siswa Full</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light text-success"><i class="fa-brands fa-google-drive"></i></span>
                                    <input type="url" name="link_drive" id="m_link_drive_input" class="form-control" placeholder="https://drive.google.com/drive/folders/..." required>
                                    <button type="submit" name="simpan_raport" class="btn btn-success fw-bold"><i class="fa-solid fa-floppy-disk me-1"></i>Simpan</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade no-print" id="modalViewSiswa" tabindex="-1" aria-labelledby="modalViewLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold" id="modalViewLabel"><i class="fa-solid fa-user-check me-2"></i>Profil & Biodata Lengkap Siswa</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="d-flex justify-content-end mb-3">
                    <button type="button" class="btn btn-secondary px-4 me-2" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-primary px-4 fw-bold" onclick="window.print()"><i class="fa-solid fa-print me-2"></i>Cetak Kertas F4 (Folio)</button>
                </div>
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <h6 class="fw-bold text-primary border-bottom pb-1"><i class="fa-solid fa-id-card me-2"></i>1. Identitas Peserta Didik</h6>
                        <table class="table table-bordered table-sm table-view-detail mb-3" style="font-size:0.85rem;">
                            <tr><th>Tahun Ajaran</th><td id="v_tahun_ajaran"></td></tr>
                            <tr><th>No. Induk / Stb</th><td id="v_nomor_induk"></td></tr>
                            <tr><th>NIS Lokal</th><td id="v_nis_lokal"></td></tr>
                            <tr><th>NISN</th><td id="v_nisn"></td></tr>
                            <tr><th>NIK</th><td id="v_nik"></td></tr>
                            <tr><th>Nama Lengkap</th><td id="v_nama_siswa" class="fw-bold text-uppercase text-dark"></td></tr>
                            <tr><th>Tempat, Tgl Lahir</th><td id="v_ttl"></td></tr>
                            <tr><th>Jenis Kelamin</th><td id="v_jenis_kelamin"></td></tr>
                            <tr><th>Agama</th><td id="v_agama"></td></tr>
                            <tr><th>Jumlah Saudara</th><td id="v_jumlah_saudara"></td></tr>
                            <tr><th>Diterima di Kelas</th><td id="v_diterima_di_kelas"></td></tr>
                            <tr><th>Tanggal Masuk</th><td id="v_tanggal_masuk"></td></tr>
                            <tr><th>Jarak ke Sekolah</th><td id="v_jarak_rumah"></td></tr>
                        </table>
                        
                        <h6 class="fw-bold text-primary border-bottom pb-1"><i class="fa-solid fa-map-location-dot me-2"></i>2. Data Tempat Tinggal</h6>
                        <table class="table table-bordered table-sm table-view-detail mb-3" style="font-size:0.85rem;">
                            <tr><th>Alamat Lengkap</th><td id="v_alamat"></td></tr>
                            <tr><th>Desa / Kelurahan</th><td id="v_desa_kelurahan"></td></tr>
                            <tr><th>Kecamatan</th><td id="v_kecamatan"></td></tr>
                            <tr><th>Kabupaten / Kota</th><td id="v_kabupaten"></td></tr>
                            <tr><th>Provinsi</th><td id="v_provinsi"></td></tr>
                            <tr><th>Kode Pos</th><td id="v_kodepos"></td></tr>
                        </table>

                        <h6 class="fw-bold text-primary border-bottom pb-1"><i class="fa-solid fa-graduation-cap me-2"></i>3. Riwayat Pendidikan Asal (SMP/MTs)</h6>
                        <table class="table table-bordered table-sm table-view-detail mb-3" style="font-size:0.85rem;">
                            <tr><th>Nama Sekolah Asal</th><td id="v_nama_sekolah_asal"></td></tr>
                            <tr><th>NPSN Sekolah</th><td id="v_npsn_sekolah"></td></tr>
                            <tr><th>No Seri Ijazah</th><td id="v_no_seri_ijazah_smp"></td></tr>
                            <tr><th>No Blangko SKHU</th><td id="v_no_blangko_skhu_smp"></td></tr>
                            <tr><th>No Peserta UN</th><td id="v_no_peserta_un_smp"></td></tr>
                            <tr><th>Total Nilai UN</th><td id="v_total_nilai_un"></td></tr>
                            <tr><th>Tanggal Lulus SMP</th><td id="v_tanggal_kelulusan_smp"></td></tr>
                        </table>
                    </div>
                    
                    <div class="col-md-6">
                        <h6 class="fw-bold text-primary border-bottom pb-1"><i class="fa-solid fa-user-group me-2"></i>4. Data Orang Tua Kandung</h6>
                        <table class="table table-bordered table-sm table-view-detail mb-3" style="font-size:0.85rem;">
                            <tr><th>Nomor KK</th><td id="v_nomor_kk" class="fw-bold"></td></tr>
                            <tr class="table-light"><th colspan="2" class="text-center small py-0 fw-bold text-secondary">Data Ayah</th></tr>
                            <tr><th>Nama Ayah</th><td id="v_nama_ayah"></td></tr>
                            <tr><th>NIK Ayah</th><td id="v_nik_ayah"></td></tr>
                            <tr><th>Tgl Lahir Ayah</th><td id="v_tanggal_lahir_ayah"></td></tr>
                            <tr><th>Pendidikan Ayah</th><td id="v_pendidikan_ayah"></td></tr>
                            <tr><th>Pekerjaan Ayah</th><td id="v_pekerjaan_ayah"></td></tr>
                            <tr><th>Penghasilan Ayah</th><td id="v_penghasilan_ayah"></td></tr>
                            <tr><th>Status Ayah</th><td id="v_status_ayah"></td></tr>
                            <tr class="table-light"><th colspan="2" class="text-center small py-0 fw-bold text-secondary">Data Ibu</th></tr>
                            <tr><th>Nama Ibu</th><td id="v_nama_ibu"></td></tr>
                            <tr><th>NIK Ibu</th><td id="v_nik_ibu"></td></tr>
                            <tr><th>Tgl Lahir Ibu</th><td id="v_tanggal_lahir_ibu"></td></tr>
                            <tr><th>Pendidikan Ibu</th><td id="v_pendidikan_ibu"></td></tr>
                            <tr><th>Pekerjaan Ibu</th><td id="v_pekerjaan_ibu"></td></tr>
                            <tr><th>Penghasilan Ibu</th><td id="v_penghasilan_ibu"></td></tr>
                            <tr><th>Status Ibu</th><td id="v_status_ibu"></td></tr>
                        </table>

                        <h6 class="fw-bold text-primary border-bottom pb-1"><i class="fa-solid fa-hands-holding-child me-2"></i>5. Data Wali & Bantuan Sosial (Bansos)</h6>
                        <table class="table table-bordered table-sm table-view-detail mb-3" style="font-size:0.85rem;">
                            <tr><th>Nama Wali</th><td id="v_nama_wali"></td></tr>
                            <tr><th>NIK Wali</th><td id="v_nik_wali"></td></tr>
                            <tr><th>Pendidikan Wali</th><td id="v_pendidikan_wali"></td></tr>
                            <tr><th>Pekerjaan Wali</th><td id="v_pekerjaan_wali"></td></tr>
                            <tr><th>Penghasilan Wali</th><td id="v_penghasilan_wali"></td></tr>
                            <tr><th>Nomor KIP</th><td id="v_nomor_kip"></td></tr>
                            <tr><th>Nomor KKS</th><td id="v_nomor_kks"></td></tr>
                            <tr><th>Nomor PKH</th><td id="v_nomor_pkh"></td></tr>
                        </table>

                        <h6 class="fw-bold text-primary border-bottom pb-1"><i class="fa-solid fa-arrow-right-from-bracket me-2"></i>6. Status Keluar Khusus MA</h6>
                        <table class="table table-bordered table-sm table-view-detail mb-3" style="font-size:0.85rem;">
                            <tr><th>Alasan Putus Sekolah</th><td id="v_alasan_putus_sekolah"></td></tr>
                            <tr><th>Tanggal Putus Sekolah</th><td id="v_tanggal_putus_sekolah"></td></tr>
                            <tr><th>Alasan Mutasi (Pindah)</th><td id="v_alasan_mutasi"></td></tr>
                            <tr><th>Tanggal Mutasi</th><td id="v_tanggal_mutasi"></td></tr>
                            <tr><th>Tanggal Lulus MA</th><td id="v_tanggal_lulus_ma"></td></tr>
                            <tr><th>No. Seri Ijazah MA</th><td id="v_no_ijazah_ma"></td></tr>
                            <tr><th>No. SKHU MA</th><td id="v_no_skhu_ma"></td></tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade no-print" id="modalEditSiswa" tabindex="-1" aria-labelledby="modalEditLabel" aria-hidden="true" style="overflow-y: auto;">
function bukaModalEdit(siswa) {
    // TAMBAHKAN DUA BARIS INI DI PALING ATAS FUNGSI BUKAMODALEDIT
    if(document.getElementById('edit_sama_dengan_ayah')) document.getElementById('edit_sama_dengan_ayah').checked = false;
    if(document.getElementById('edit_sama_dengan_ibu')) document.getElementById('edit_sama_dengan_ibu').checked = false;
    
    // ... sisa kode fungsi bukaModalEdit Anda di bawahnya ...

    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title fw-bold" id="modalEditLabel"><i class="fa-solid fa-user-pen me-2"></i>Edit Data Lengkap Siswa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-4" style="max-height: calc(100vh - 200px); overflow-y: auto;">
                <form method="POST" action="">
                    <input type="hidden" name="id" id="edit_id_siswa">
                    
                    
                    <ul class="nav nav-tabs mb-3" id="editSiswaTab" role="tablist">
                        <li class="nav-item"><button class="nav-link active fw-semibold" id="edit-pribadi-tab" data-bs-toggle="tab" data-bs-target="#edit-pribadi" type="button">Data Pribadi</button></li>
                        <li class="nav-item"><button class="nav-link fw-semibold" id="edit-akademik-tab" data-bs-toggle="tab" data-bs-target="#edit-akademik" type="button">Asal Sekolah & Masuk</button></li>
                        <li class="nav-item"><button class="nav-link fw-semibold" id="edit-ortu-tab" data-bs-toggle="tab" data-bs-target="#edit-ortu" type="button">Orang Tua / Wali</button></li>
                        <li class="nav-item"><button class="nav-link fw-semibold" id="edit-status-tab" data-bs-toggle="tab" data-bs-target="#edit-status" type="button">Status Khusus</button></li>
                    </ul>

                    <div class="tab-content bg-white p-2" id="editSiswaTabContent">
                        <div class="tab-pane fade show active" id="edit-pribadi">
                            <div class="d-flex flex-column">
                                <div class="row form-group-row mb-3">
                                    <label class="col-sm-3 label-side">Tahun Ajaran *</label>
                                    <div class="col-sm-9">
                                        <select name="tahun_ajaran" id="edit_tahun_ajaran" class="form-select" required>
                                            <?php 
                                            $list_ta = mysqli_query($conn, "SELECT tahun_ajaran FROM tahun_ajaran ORDER BY tahun_ajaran DESC");
                                            while($t = mysqli_fetch_assoc($list_ta)) {
                                                echo "<option value='{$t['tahun_ajaran']}'>{$t['tahun_ajaran']}</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="row form-group-row mb-3">
                                    <label class="col-sm-3 label-side">Nomor Induk *</label>
                                    <div class="col-sm-9"><input type="text" name="nomor_induk" id="edit_nomor_induk" class="form-control" required></div>
                                </div>
                                <div class="row form-group-row mb-3">
                                    <label class="col-sm-3 label-side">NIS Lokal</label>
                                    <div class="col-sm-9"><input type="text" name="nis_lokal" id="edit_nis_lokal" class="form-control"></div>
                                </div>
                                <div class="row form-group-row mb-3">
                                    <label class="col-sm-3 label-side">NISN</label>
                                    <div class="col-sm-9"><input type="text" name="nisn" id="edit_nisn" class="form-control" maxlength="10"></div>
                                </div>
                                <div class="row form-group-row mb-3">
                                    <label class="col-sm-3 label-side">Nama Lengkap Siswa *</label>
                                    <div class="col-sm-9"><input type="text" name="nama_siswa" id="edit_nama_siswa" class="form-control" required></div>
                                </div>
                                <div class="row form-group-row mb-3">
                                    <label class="col-sm-3 label-side">NIK</label>
                                    <div class="col-sm-9"><input type="text" name="nik" id="edit_nik" class="form-control" maxlength="16"></div>
                                </div>
                                <div class="row form-group-row mb-3">
                                    <label class="col-sm-3 label-side">Jenis Kelamin</label>
                                    <div class="col-sm-9">
                                        <select name="jenis_kelamin" id="edit_jenis_kelamin" class="form-select">
                                            <option value="Laki-laki">Laki-laki</option>
                                            <option value="Perempuan">Perempuan</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row form-group-row mb-3">
                                    <label class="col-sm-3 label-side">Tempat Lahir</label>
                                    <div class="col-sm-9"><input type="text" name="tempat_lahir" id="edit_tempat_lahir" class="form-control"></div>
                                </div>
                                <div class="row form-group-row mb-3">
                                    <label class="col-sm-3 label-side">Tanggal Lahir</label>
                                    <div class="col-sm-9"><input type="date" name="tanggal_lahir" id="edit_tanggal_lahir" class="form-control"></div>
                                </div>
                                <div class="row form-group-row mb-3">
                                    <label class="col-sm-3 label-side">Agama</label>
                                    <div class="col-sm-9">
                                        <select name="agama" id="edit_agama" class="form-select">
                                            <option value="Islam">Islam</option>
                                            <option value="Kristen">Kristen</option>
                                            <option value="Katolik">Katolik</option>
                                            <option value="Budha">Budha</option>
                                            <option value="Hindu">Hindu</option>
                                            <option value="Konghuchu">Konghuchu</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row form-group-row mb-3">
                                    <label class="col-sm-3 label-side">Jumlah Saudara</label>
                                    <div class="col-sm-9"><input type="number" name="jumlah_saudara" id="edit_jumlah_saudara" class="form-control"></div>
                                </div>
                                <div class="row form-group-row mb-3">
                                    <label class="col-sm-3 label-side">Alamat Jalan</label>
                                    <div class="col-sm-9"><input type="text" name="alamat" id="edit_alamat" class="form-control"></div>
                                </div>
                                <div class="row form-group-row mb-3">
                                    <label class="col-sm-3 label-side">Desa/Kelurahan</label>
                                    <div class="col-sm-9"><input type="text" name="desa_kelurahan" id="edit_desa_kelurahan" class="form-control"></div>
                                </div>
                                <div class="row form-group-row mb-3">
                                    <label class="col-sm-3 label-side">Kecamatan</label>
                                    <div class="col-sm-9"><input type="text" name="kecamatan" id="edit_kecamatan" class="form-control"></div>
                                </div>
                                <div class="row form-group-row mb-3">
                                    <label class="col-sm-3 label-side">Kabupaten</label>
                                    <div class="col-sm-9"><input type="text" name="kabupaten" id="edit_kabupaten" class="form-control"></div>
                                </div>
                                <div class="row form-group-row mb-3">
                                    <label class="col-sm-3 label-side">Provinsi</label>
                                    <div class="col-sm-9"><input type="text" name="provinsi" id="edit_provinsi" class="form-control"></div>
                                </div>
                                <div class="row form-group-row mb-3">
                                    <label class="col-sm-3 label-side">Kode Pos</label>
                                    <div class="col-sm-9"><input type="text" name="kodepos" id="edit_kodepos" class="form-control"></div>
                                </div>
                                <div class="row form-group-row mb-3">
                                    <label class="col-sm-3 label-side">Jarak Rumah ke Madrasah</label>
                                    <div class="col-sm-9"><input type="text" name="jarak_rumah" id="edit_jarak_rumah" class="form-control"></div>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="edit-akademik">
                            <div class="d-flex flex-column">
                                <div class="row form-group-row mb-3">
                                    <label class="col-sm-3 label-side">Nama Sekolah Asal</label>
                                    <div class="col-sm-9"><input type="text" name="nama_sekolah_asal" id="edit_nama_sekolah_asal" class="form-control"></div>
                                </div>
                                <div class="row form-group-row mb-3">
                                    <label class="col-sm-3 label-side">NPSN Sekolah Asal</label>
                                    <div class="col-sm-9"><input type="text" name="npsn_sekolah" id="edit_npsn_sekolah" class="form-control"></div>
                                </div>
                                <div class="row form-group-row mb-3">
                                    <label class="col-sm-3 label-side">Total Nilai UN</label>
                                    <div class="col-sm-9"><input type="text" name="total_nilai_un" id="edit_total_nilai_un" class="form-control"></div>
                                </div>
                                <div class="row form-group-row mb-3">
                                    <label class="col-sm-3 label-side">Nomor Blangko SKHU</label>
                                    <div class="col-sm-9"><input type="text" name="no_blangko_skhu_smp" id="edit_no_blangko_skhu_smp" class="form-control"></div>
                                </div>
                                <div class="row form-group-row mb-3">
                                    <label class="col-sm-3 label-side">Nomor Seri Ijazah</label>
                                    <div class="col-sm-9"><input type="text" name="no_seri_ijazah_smp" id="edit_no_seri_ijazah_smp" class="form-control"></div>
                                </div>
                                <div class="row form-group-row mb-3">
                                    <label class="col-sm-3 label-side">No Peserta UN SMP</label>
                                    <div class="col-sm-9"><input type="text" name="no_peserta_un_smp" id="edit_no_peserta_un_smp" class="form-control"></div>
                                </div>
                                <div class="row form-group-row mb-3">
                                    <label class="col-sm-3 label-side">Tanggal Kelulusan SMP</label>
                                    <div class="col-sm-9"><input type="date" name="tanggal_kelulusan_smp" id="edit_tanggal_kelulusan_smp" class="form-control"></div>
                                </div>
                                <div class="row form-group-row mb-3">
                                    <label class="col-sm-3 label-side">Tanggal Masuk MA</label>
                                    <div class="col-sm-9"><input type="date" name="tanggal_masuk" id="edit_tanggal_masuk" class="form-control"></div>
                                </div>
                                <div class="row form-group-row mb-3">
                                    <label class="col-sm-3 label-side">Diterima di Kelas</label>
                                    <div class="col-sm-9"><input type="text" name="diterima_di_kelas" id="edit_diterima_di_kelas" class="form-control"></div>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="edit-ortu">
                            <div class="d-flex flex-column">
                                <div class="row form-group-row mb-3">
                                    <label class="col-sm-3 label-side">No Kartu Keluarga (KK)</label>
                                    <div class="col-sm-9"><input type="text" name="nomor_kk" id="edit_nomor_kk" class="form-control"></div>
                                </div>
                                
                                <div class="bg-light p-2 my-2 rounded-2 fw-bold text-success"><i class="fa-solid fa-user me-2"></i>Data Ayah Kandung</div>
                                <div class="row form-group-row mb-3">
                                    <label class="col-sm-3 label-side">Nama Ayah</label>
                                    <div class="col-sm-9"><input type="text" name="nama_ayah" id="edit_nama_ayah" class="form-control"></div>
                                </div>
                                <div class="row form-group-row mb-3">
                                    <label class="col-sm-3 label-side">NIK Ayah</label>
                                    <div class="col-sm-9"><input type="text" name="nik_ayah" id="edit_nik_ayah" class="form-control"></div>
                                </div>
                                <div class="row form-group-row mb-3">
                                    <label class="col-sm-3 label-side">Tanggal Lahir Ayah</label>
                                    <div class="col-sm-9"><input type="date" name="tanggal_lahir_ayah" id="edit_tanggal_lahir_ayah" class="form-control"></div>
                                </div>
                                <div class="row form-group-row mb-3">
                                    <label class="col-sm-3 label-side">Pendidikan Ayah</label>
                                    <div class="col-sm-9">
                                        <select name="pendidikan_ayah" id="edit_pendidikan_ayah" class="form-select">
                                            <option value="">-- Pilih Pendidikan --</option>
                                            <option value="Tidak Sekolah">Tidak Sekolah</option>
                                            <option value="SD">SD</option>
                                            <option value="SMP">SMP</option>
                                            <option value="SMA">SMA</option>
                                            <option value="D1">D1</option>
                                            <option value="D2">D2</option>
                                            <option value="D3">D3</option>
                                            <option value="D4">D4</option>
                                            <option value="S1">S1</option>
                                            <option value="S2">S2</option>
                                            <option value="S3">S3</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row form-group-row mb-3">
                                    <label class="col-sm-3 label-side">Pekerjaan Ayah</label>
                                    <div class="col-sm-9">
                                        <select name="pekerjaan_ayah" id="edit_pekerjaan_ayah" class="form-select">
                                            <option value="">-- Pilih Pekerjaan --</option>
                                            <option value="Tidak Bekerja / Mengurus Rumah Tangga">Tidak Bekerja / Mengurus Rumah Tangga</option>
                                            <option value="Pegawai Negeri Sipil (PNS) / ASN">Pegawai Negeri Sipil (PNS) / ASN</option>
                                            <option value="TNI / POLRI">TNI / POLRI</option>
                                            <option value="Karyawan Swasta">Karyawan Swasta</option>
                                            <option value="Pedagang / Wiraswasta">Pedagang / Wiraswasta</option>
                                            <option value="Petani / Nelayan">Petani / Nelayan</option>
                                            <option value="Buruh">Buruh</option>
                                            <option value="Pensiunan / Almarhum">Pensiunan / Almarhum</option>
                                            <option value="Guru / Dosen">Guru / Dosen</option>
                                            <option value="Lainnya / Pekerjaan Lain">Lainnya / Pekerjaan Lain</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row form-group-row mb-3">
                                    <label class="col-sm-3 label-side">Penghasilan Ayah</label>
                                    <div class="col-sm-9">
                                        <select name="penghasilan_ayah" id="edit_penghasilan_ayah" class="form-select">
                                            <option value="">-- Pilih Penghasilan --</option>
                                            <option value="Kurang dari 500.000">Kurang dari 500.000</option>
                                            <option value="500.000 - 1.000.000">500.000 - 1.000.000</option>
                                            <option value="1.000.001 - 2.000.000">1.000.001 - 2.000.000</option>
                                            <option value="2.000.001 - 3.000.000">2.000.001 - 3.000.000</option>
                                            <option value="3.000.001 - 5.000.000">3.000.001 - 5.000.000</option>
                                            <option value="Lebih dari 5.000.000">Lebih dari 5.000.000</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row form-group-row mb-3">
                                    <label class="col-sm-3 label-side">Status Ayah</label>
                                    <div class="col-sm-9">
                                        <select name="status_ayah" id="edit_status_ayah" class="form-select">
                                            <option value="Masih Hidup">Masih Hidup</option>
                                            <option value="Sudah Meninggal">Sudah Meninggal</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="bg-light p-2 my-2 rounded-2 fw-bold text-danger"><i class="fa-solid fa-user me-2"></i>Data Ibu Kandung</div>
                                <div class="row form-group-row mb-3">
                                    <label class="col-sm-3 label-side">Nama Ibu</label>
                                    <div class="col-sm-9"><input type="text" name="nama_ibu" id="edit_nama_ibu" class="form-control"></div>
                                </div>
                                <div class="row form-group-row mb-3">
                                    <label class="col-sm-3 label-side">NIK Ibu</label>
                                    <div class="col-sm-9"><input type="text" name="nik_ibu" id="edit_nik_ibu" class="form-control"></div>
                                </div>
                                <div class="row form-group-row mb-3">
                                    <label class="col-sm-3 label-side">Tanggal Lahir Ibu</label>
                                    <div class="col-sm-9"><input type="date" name="tanggal_lahir_ibu" id="edit_tanggal_lahir_ibu" class="form-control"></div>
                                </div>
                                <div class="row form-group-row mb-3">
                                    <label class="col-sm-3 label-side">Pendidikan Ibu</label>
                                    <div class="col-sm-9">
                                        <select name="pendidikan_ibu" id="edit_pendidikan_ibu" class="form-select">
                                            <option value="">-- Pilih Pendidikan --</option>
                                            <option value="Tidak Sekolah">Tidak Sekolah</option>
                                            <option value="SD">SD</option>
                                            <option value="SMP">SMP</option>
                                            <option value="SMA">SMA</option>
                                            <option value="D1">D1</option>
                                            <option value="D2">D2</option>
                                            <option value="D3">D3</option>
                                            <option value="D4">D4</option>
                                            <option value="S1">S1</option>
                                            <option value="S2">S2</option>
                                            <option value="S3">S3</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row form-group-row mb-3">
                                    <label class="col-sm-3 label-side">Pekerjaan Ibu</label>
                                    <div class="col-sm-9">
                                        <select name="pekerjaan_ibu" id="edit_pekerjaan_ibu" class="form-select">
                                            <option value="">-- Pilih Pekerjaan --</option>
                                            <option value="Tidak Bekerja / Mengurus Rumah Tangga">Tidak Bekerja / Mengurus Rumah Tangga</option>
                                            <option value="Pegawai Negeri Sipil (PNS) / ASN">Pegawai Negeri Sipil (PNS) / ASN</option>
                                            <option value="TNI / POLRI">TNI / POLRI</option>
                                            <option value="Karyawan Swasta">Karyawan Swasta</option>
                                            <option value="Pedagang / Wiraswasta">Pedagang / Wiraswasta</option>
                                            <option value="Petani / Nelayan">Petani / Nelayan</option>
                                            <option value="Buruh">Buruh</option>
                                            <option value="Pensiunan / Almarhum">Pensiunan / Almarhum</option>
                                            <option value="Guru / Dosen">Guru / Dosen</option>
                                            <option value="Lainnya / Pekerjaan Lain">Lainnya / Pekerjaan Lain</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row form-group-row mb-3">
                                    <label class="col-sm-3 label-side">Penghasilan Ibu</label>
                                    <div class="col-sm-9">
                                        <select name="penghasilan_ibu" id="edit_penghasilan_ibu" class="form-select">
                                            <option value="">-- Pilih Penghasilan --</option>
                                            <option value="Kurang dari 500.000">Kurang dari 500.000</option>
                                            <option value="500.000 - 1.000.000">500.000 - 1.000.000</option>
                                            <option value="1.000.001 - 2.000.000">1.000.001 - 2.000.000</option>
                                            <option value="2.000.001 - 3.000.000">2.000.001 - 3.000.000</option>
                                            <option value="3.000.001 - 5.000.000">3.000.001 - 5.000.000</option>
                                            <option value="Lebih dari 5.000.000">Lebih dari 5.000.000</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row form-group-row mb-3">
                                    <label class="col-sm-3 label-side">Status Ibu</label>
                                    <div class="col-sm-9">
                                        <select name="status_ibu" id="edit_status_ibu" class="form-select">
                                            <option value="Masih Hidup">Masih Hidup</option>
                                            <option value="Sudah Meninggal">Sudah Meninggal</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="bg-light p-2 my-2 rounded-2 d-flex flex-wrap align-items-center justify-content-between gap-2">
                                    <span class="fw-bold text-secondary"><i class="fa-solid fa-user-shield me-2"></i>Data Wali Siswa</span>
                                    <div class="d-flex gap-3">
                                        <div class="col-md-12 mb-2">
    <label class="form-label d-block fw-bold text-secondary small">Pilihan Cepat Data Wali :</label>
    <div class="form-check form-check-inline">
        <input class="form-check-input border-secondary" type="checkbox" id="edit_sama_dengan_ayah" onclick="salinDataWaliEdit('ayah')">
        <label class="form-check-label small fw-semibold" for="edit_sama_dengan_ayah">
            <i class="fa-solid fa-user-tie text-muted me-1"></i>Sama dengan Ayah
        </label>
    </div>
    <div class="form-check form-check-inline">
        <input class="form-check-input border-secondary" type="checkbox" id="edit_sama_dengan_ibu" onclick="salinDataWaliEdit('ibu')">
        <label class="form-check-label small fw-semibold" for="edit_sama_dengan_ibu">
            <i class="fa-solid fa-user text-muted me-1"></i>Sama dengan Ibu
        </label>
    </div>
</div>
                                    </div>
                                </div>
                                <div class="row form-group-row mb-3">
                                    <label class="col-sm-3 label-side">Nama Wali</label>
                                    <div class="col-sm-9"><input type="text" name="nama_wali" id="edit_nama_wali" class="form-control"></div>
                                </div>
                                <div class="row form-group-row mb-3">
                                    <label class="col-sm-3 label-side">NIK Wali</label>
                                    <div class="col-sm-9"><input type="text" name="nik_wali" id="edit_nik_wali" class="form-control"></div>
                                </div>
                                <div class="row form-group-row mb-3">
                                    <label class="col-sm-3 label-side">Tanggal Lahir Wali</label>
                                    <div class="col-sm-9"><input type="date" name="tanggal_lahir_wali" id="edit_tanggal_lahir_wali" class="form-control"></div>
                                </div>
                                <div class="row form-group-row mb-3">
                                    <label class="col-sm-3 label-side">Pendidikan Wali</label>
                                    <div class="col-sm-9">
                                        <select name="pendidikan_wali" id="edit_pendidikan_wali" class="form-select">
                                            <option value="">-- Pilih Pendidikan --</option>
                                            <option value="Tidak Sekolah">Tidak Sekolah</option>
                                            <option value="SD">SD</option>
                                            <option value="SMP">SMP</option>
                                            <option value="SMA">SMA</option>
                                            <option value="D1">D1</option>
                                            <option value="D2">D2</option>
                                            <option value="D3">D3</option>
                                            <option value="D4">D4</option>
                                            <option value="S1">S1</option>
                                            <option value="S2">S2</option>
                                            <option value="S3">S3</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row form-group-row mb-3">
                                    <label class="col-sm-3 label-side">Pekerjaan Wali</label>
                                    <div class="col-sm-9">
                                        <select name="pekerjaan_wali" id="edit_pekerjaan_wali" class="form-select">
                                            <option value="">-- Pilih Pekerjaan --</option>
                                            <option value="Tidak Bekerja / Mengurus Rumah Tangga">Tidak Bekerja / Mengurus Rumah Tangga</option>
                                            <option value="Pegawai Negeri Sipil (PNS) / ASN">Pegawai Negeri Sipil (PNS) / ASN</option>
                                            <option value="TNI / POLRI">TNI / POLRI</option>
                                            <option value="Karyawan Swasta">Karyawan Swasta</option>
                                            <option value="Pedagang / Wiraswasta">Pedagang / Wiraswasta</option>
                                            <option value="Petani / Nelayan">Petani / Nelayan</option>
                                            <option value="Buruh">Buruh</option>
                                            <option value="Pensiunan / Almarhum">Pensiunan / Almarhum</option>
                                            <option value="Guru / Dosen">Guru / Dosen</option>
                                            <option value="Lainnya / Pekerjaan Lain">Lainnya / Pekerjaan Lain</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row form-group-row mb-3">
                                    <label class="col-sm-3 label-side">Penghasilan Wali</label>
                                    <div class="col-sm-9">
                                        <select name="penghasilan_wali" id="edit_penghasilan_wali" class="form-select">
                                            <option value="">-- Pilih Penghasilan --</option>
                                            <option value="Kurang dari 500.000">Kurang dari 500.000</option>
                                            <option value="500.000 - 1.000.000">500.000 - 1.000.000</option>
                                            <option value="1.000.001 - 2.000.000">1.000.001 - 2.000.000</option>
                                            <option value="2.000.001 - 3.000.000">2.000.001 - 3.000.000</option>
                                            <option value="3.000.001 - 5.000.000">3.000.001 - 5.000.000</option>
                                            <option value="Lebih dari 5.000.000">Lebih dari 5.000.000</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="bg-light p-2 my-2 rounded-2 fw-bold text-dark"><i class="fa-solid fa-hand-holding-dollar me-2"></i>Jaminan Sosial / Bantuan Pemerintah</div>
                                <div class="row form-group-row mb-3">
                                    <label class="col-sm-3 label-side">Nomor KIP</label>
                                    <div class="col-sm-9"><input type="text" name="nomor_kip" id="edit_nomor_kip" class="form-control"></div>
                                </div>
                                <div class="row form-group-row mb-3">
                                    <label class="col-sm-3 label-side">Nomor KKS</label>
                                    <div class="col-sm-9"><input type="text" name="nomor_kks" id="edit_nomor_kks" class="form-control"></div>
                                </div>
                                <div class="row form-group-row mb-3">
                                    <label class="col-sm-3 label-side">Nomor PKH</label>
                                    <div class="col-sm-9"><input type="text" name="nomor_pkh" id="edit_nomor_pkh" class="form-control"></div>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="edit-status">
                            <div class="d-flex flex-column">
                                <div class="bg-light p-2 mb-2 rounded-2 fw-bold text-warning"><i class="fa-solid fa-user-slash me-2"></i>Siswa Putus Sekolah</div>
                                <div class="row form-group-row mb-3">
                                    <label class="col-sm-3 label-side">Alasan Putus Sekolah</label>
                                    <div class="col-sm-9"><input type="text" name="alasan_putus_sekolah" id="edit_alasan_putus_sekolah" class="form-control"></div>
                                </div>
                                <div class="row form-group-row mb-3">
                                    <label class="col-sm-3 label-side">Tanggal Putus Sekolah</label>
                                    <div class="col-sm-9"><input type="date" name="tanggal_putus_sekolah" id="edit_tanggal_putus_sekolah" class="form-control"></div>
                                </div>
                                
                                <div class="bg-light p-2 my-2 rounded-2 fw-bold text-info"><i class="fa-solid fa-arrow-right-arrows-left me-2"></i>Siswa Mutasi / Pindah</div>
                                <div class="row form-group-row mb-3">
                                    <label class="col-sm-3 label-side">Alasan Mutasi</label>
                                    <div class="col-sm-9"><input type="text" name="alasan_mutasi" id="edit_alasan_mutasi" class="form-control"></div>
                                </div>
                                <div class="row form-group-row mb-3">
                                    <label class="col-sm-3 label-side">Tujuan Sekolah Mutasi</label>
                                    <div class="col-sm-9"><input type="text" name="tujuan_mutasi" id="edit_tujuan_mutasi" class="form-control"></div>
                                </div>
                                <div class="row form-group-row mb-3">
                                    <label class="col-sm-3 label-side">Tanggal Mutasi</label>
                                    <div class="col-sm-9"><input type="date" name="tanggal_mutasi" id="edit_tanggal_mutasi" class="form-control"></div>
                                </div>

                                <div class="bg-light p-2 my-2 rounded-2 fw-bold text-success"><i class="fa-solid fa-graduation-cap me-2"></i>Siswa Lulus Sekolah</div>
                                <div class="row form-group-row mb-3">
                                    <label class="col-sm-3 label-side">Tanggal Lulus MA</label>
                                    <div class="col-sm-9"><input type="date" name="tanggal_lulus_ma" id="edit_tanggal_lulus_ma" class="form-control"></div>
                                </div>
                                <div class="row form-group-row mb-3">
                                    <label class="col-sm-3 label-side">No. Ijazah MA</label>
                                    <div class="col-sm-9"><input type="text" name="no_ijazah_ma" id="edit_no_ijazah_ma" class="form-control"></div>
                                </div>
                                <div class="row form-group-row mb-3">
                                    <label class="col-sm-3 label-side">No. SKHU MA</label>
                                    <div class="col-sm-9"><input type="text" name="no_skhu_ma" id="edit_no_skhu_ma" class="form-control"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="update_siswa" class="btn btn-primary px-4 fw-semibold"><i class="fa-solid fa-floppy-disk me-2"></i>Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function bukaModalEdit(siswa) {
    // 1. Reset checkbox wali jika ada
    if(document.getElementById('e_sama_dengan_ayah')) document.getElementById('e_sama_dengan_ayah').checked = false;
    if(document.getElementById('e_sama_dengan_ibu')) document.getElementById('e_sama_dengan_ibu').checked = false;

    // 2. Isi data input Modal Edit menggunakan ID berawalan 'e_' sesuai struktur file Anda
    document.getElementById('e_id').value = siswa.id;
    document.getElementById('e_tahun_ajaran').value = siswa.tahun_ajaran || '';
    document.getElementById('e_nomor_induk').value = siswa.nomor_induk || '';
    document.getElementById('e_nis_lokal').value = siswa.nis_lokal || '';
    document.getElementById('e_nisn').value = siswa.nisn || '';
    document.getElementById('e_nik').value = siswa.nik || '';
    document.getElementById('e_nama_siswa').value = siswa.nama_siswa || '';
    document.getElementById('e_tempat_lahir').value = siswa.tempat_lahir || '';
    document.getElementById('e_tanggal_lahir').value = siswa.tanggal_lahir || '';
    document.getElementById('e_jenis_kelamin').value = siswa.jenis_kelamin || 'Laki-laki';
    document.getElementById('e_agama').value = siswa.agama || 'Islam';
    document.getElementById('e_jumlah_saudara').value = siswa.jumlah_saudara || '0';
    document.getElementById('e_diterima_di_kelas').value = siswa.diterima_di_kelas || '';
    document.getElementById('e_tanggal_masuk').value = siswa.tanggal_masuk || '';
    document.getElementById('e_jarak_rumah').value = siswa.jarak_rumah || '';
    
    // Tab Alamat
    document.getElementById('e_alamat').value = siswa.alamat || '';
    document.getElementById('e_desa_kelurahan').value = siswa.desa_kelurahan || '';
    document.getElementById('e_kecamatan').value = siswa.kecamatan || '';
    document.getElementById('e_kabupaten').value = siswa.kabupaten || '';
    document.getElementById('e_provinsi').value = siswa.provinsi || '';
    document.getElementById('e_kodepos').value = siswa.kodepos || '';
    
    // Tab Sekolah Asal
    document.getElementById('e_nama_sekolah_asal').value = siswa.nama_sekolah_asal || '';
    document.getElementById('e_npsn_sekolah').value = siswa.npsn_sekolah || '';
    document.getElementById('e_total_nilai_un').value = siswa.total_nilai_un || '';
    document.getElementById('e_no_blangko_skhu_smp').value = siswa.no_blangko_skhu_smp || '';
    document.getElementById('e_no_seri_ijazah_smp').value = siswa.no_seri_ijazah_smp || '';
    document.getElementById('e_no_peserta_un_smp').value = siswa.no_peserta_un_smp || '';
    document.getElementById('e_tanggal_kelulusan_smp').value = siswa.tanggal_kelulusan_smp || '';
    
    // Tab Orang Tua
    document.getElementById('e_nomor_kk').value = siswa.nomor_kk || '';
    document.getElementById('e_nama_ayah').value = siswa.nama_ayah || '';
    document.getElementById('e_nik_ayah').value = siswa.nik_ayah || '';
    document.getElementById('e_tanggal_lahir_ayah').value = siswa.tanggal_lahir_ayah || '';
    document.getElementById('e_pendidikan_ayah').value = siswa.pendidikan_ayah || '';
    document.getElementById('e_pekerjaan_ayah').value = siswa.pekerjaan_ayah || '';
    document.getElementById('e_penghasilan_ayah').value = siswa.penghasilan_ayah || '';
    document.getElementById('e_status_ayah').value = siswa.status_ayah || 'Masih Hidup';
    
    document.getElementById('e_nama_ibu').value = siswa.nama_ibu || '';
    document.getElementById('e_nik_ibu').value = siswa.nik_ibu || '';
    document.getElementById('e_tanggal_lahir_ibu').value = siswa.tanggal_lahir_ibu || '';
    document.getElementById('e_pendidikan_ibu').value = siswa.pendidikan_ibu || '';
    document.getElementById('e_pekerjaan_ibu').value = siswa.pekerjaan_ibu || '';
    document.getElementById('e_penghasilan_ibu').value = siswa.penghasilan_ibu || '';
    document.getElementById('e_status_ibu').value = siswa.status_ibu || 'Masih Hidup';
    
    // Tab Wali & Bansos
    document.getElementById('e_nama_wali').value = siswa.nama_wali || '';
    document.getElementById('e_nik_wali').value = siswa.nik_wali || '';
    document.getElementById('e_tanggal_lahir_wali').value = siswa.tanggal_lahir_wali || '';
    document.getElementById('e_pendidikan_wali').value = siswa.pendidikan_wali || '';
    document.getElementById('e_pekerjaan_wali').value = siswa.pekerjaan_wali || '';
    document.getElementById('e_penghasilan_wali').value = siswa.penghasilan_wali || '';
    
    document.getElementById('e_nomor_kip').value = siswa.nomor_kip || '';
    document.getElementById('e_nomor_kks').value = siswa.nomor_kks || '';
    document.getElementById('e_nomor_pkh').value = siswa.nomor_pkh || '';

    // Tab Status Keluar
    document.getElementById('e_alasan_putus_sekolah').value = siswa.alasan_putus_sekolah || '';
    document.getElementById('e_tanggal_putus_sekolah').value = siswa.tanggal_putus_sekolah || '';
    document.getElementById('e_alasan_mutasi').value = siswa.alasan_mutasi || '';
    document.getElementById('e_tanggal_mutasi').value = siswa.tanggal_mutasi || '';
    document.getElementById('e_tanggal_lulus_ma').value = siswa.tanggal_lulus_ma || '';
    document.getElementById('e_no_ijazah_ma').value = siswa.no_ijazah_ma || '';
    document.getElementById('e_no_skhu_ma').value = siswa.no_skhu_ma || '';

    // 3. Panggil Modal Bootstrap untuk Muncul
    // Pastikan ID modal pembungkus Form Edit Anda adalah id="modalEditSiswa"
    var modalEdit = new bootstrap.Modal(document.getElementById('modalEditSiswa'));
    modalEdit.show();
}
// Catatan saat memetakan data di fungsi JavaScript bawaan bukaModalEdit(data):
// Pastikan baris kode di dalam fungsi bukaModalEdit(data) tetap berjalan normal untuk mengisi nilai elemen seperti berikut:
// document.getElementById('edit_tahun_ajaran').value = data.tahun_ajaran;
// document.getElementById('edit_agama').value = data.agama;
// document.getElementById('edit_pendidikan_ayah').value = data.pendidikan_ayah;
// ... dst.
// Serta pastikan sebelum membuka modal, kedua checkbox dikosongkan terlebih dahulu:
// document.getElementById('edit_sama_dengan_ayah').checked = false;
// document.getElementById('edit_sama_dengan_ibu').checked = false;
</script>
<div id="areaCetakSiswa" class="d-none d-print-block">
</div>

<script>
// ====================================================================
// FUNGSI KHUSUS UNTUK MENYALIN DATA AYAH/IBU KE WALI PADA MODAL EDIT
// ====================================================================
function salinDataWaliEdit(tipe) {
    var cbAyah = document.getElementById('edit_sama_dengan_ayah');
    var cbIbu = document.getElementById('edit_sama_dengan_ibu');
    
    if (tipe === 'ayah') {
        if (cbAyah.checked) {
            // Uncheck checkbox ibu agar tidak bentrok
            if (cbIbu) cbIbu.checked = false;
            
            // Salin data dari input Ayah ke input Wali
            document.getElementById('edit_nama_wali').value = document.getElementById('edit_nama_ayah').value;
            document.getElementById('edit_nik_wali').value = document.getElementById('edit_nik_ayah').value;
            document.getElementById('edit_tanggal_lahir_wali').value = document.getElementById('edit_tanggal_lahir_ayah').value;
            document.getElementById('edit_pendidikan_wali').value = document.getElementById('edit_pendidikan_ayah').value;
            document.getElementById('edit_pekerjaan_wali').value = document.getElementById('edit_pekerjaan_ayah').value;
            document.getElementById('edit_penghasilan_wali').value = document.getElementById('edit_penghasilan_ayah').value;
        } else {
            // Jika uncheck, kosongkan form wali
            kosongkanFormWaliEdit();
        }
    } else if (tipe === 'ibu') {
        if (cbIbu.checked) {
            // Uncheck checkbox ayah agar tidak bentrok
            if (cbAyah) cbAyah.checked = false;
            
            // Salin data dari input Ibu ke input Wali
            document.getElementById('edit_nama_wali').value = document.getElementById('edit_nama_ibu').value;
            document.getElementById('edit_nik_wali').value = document.getElementById('edit_nik_ibu').value;
            document.getElementById('edit_tanggal_lahir_wali').value = document.getElementById('edit_tanggal_lahir_ibu').value;
            document.getElementById('edit_pendidikan_wali').value = document.getElementById('edit_pendidikan_ibu').value;
            document.getElementById('edit_pekerjaan_wali').value = document.getElementById('edit_pekerjaan_ibu').value;
            document.getElementById('edit_penghasilan_wali').value = document.getElementById('edit_penghasilan_ibu').value;
        } else {
            // Jika uncheck, kosongkan form wali
            kosongkanFormWaliEdit();
        }
    }
}

// Fungsi bantu untuk mereset input wali jika checklist dicopot
function kosongkanFormWaliEdit() {
    document.getElementById('edit_nama_wali').value = '';
    document.getElementById('edit_nik_wali').value = '';
    document.getElementById('edit_tanggal_lahir_wali').value = '';
    document.getElementById('edit_pendidikan_wali').value = '';
    document.getElementById('edit_pekerjaan_wali').value = '';
    document.getElementById('edit_penghasilan_wali').value = '';
}
</script>

<script>
// FITUR MODAL ARSIP & RAPORT
function bukaModalRaport(siswa) {
    document.getElementById('m_id_siswa_input').value = siswa.id;
    document.getElementById('m_id_siswa_text').innerText = "#ID-" + siswa.id;
    document.getElementById('m_nama_siswa').innerText = siswa.nama_siswa;
    
    var linkAktif = siswa.link_drive || '';
    var statusContainer = document.getElementById('status_link_raport');
    var inputField = document.getElementById('m_link_drive_input');
    
    if (linkAktif.trim() !== '') {
        statusContainer.innerHTML = `
            <div class="p-2 bg-white border rounded d-flex justify-content-between align-items-center">
                <span class="text-truncate text-success fw-semibold small" style="max-width: 80%;">
                    <i class="fa-brands fa-google-drive me-1"></i> <a href="${linkAktif}" target="_blank" class="text-success text-decoration-underline">${linkAktif}</a>
                </span>
                <a href="${linkAktif}" target="_blank" class="btn btn-primary btn-sm fw-bold px-2 py-1">
                    <i class="fa-solid fa-arrow-up-right-from-square me-1"></i>Buka Link
                </a>
            </div>
        `;
        inputField.value = linkAktif;
    } else {
        statusContainer.innerHTML = `
            <div class="alert alert-warning py-2 mb-0 small border border-warning">
                <i class="fa-solid fa-triangle-exclamation me-1"></i> Belum ada arsip link rapot full yang di-upload untuk siswa ini.
            </div>
        `;
        inputField.value = '';
    }
    
    var myModal = new bootstrap.Modal(document.getElementById('modalRaportSiswa'));
    myModal.show();
}

// FITUR KEMBALI DIKONFIGURASI: Mengisi Data ke Tampilan View Modal & Mengisi Area Cetak
function bukaModalView(siswa) {
    document.getElementById('v_tahun_ajaran').innerText = siswa.tahun_ajaran || '-';
    document.getElementById('v_nomor_induk').innerText = siswa.nomor_induk || '-';
    document.getElementById('v_nis_lokal').innerText = siswa.nis_lokal || '-';
    document.getElementById('v_nisn').innerText = siswa.nisn || '-';
    document.getElementById('v_nik').innerText = siswa.nik || '-';
    document.getElementById('v_nama_siswa').innerText = siswa.nama_siswa || '-';
    document.getElementById('v_ttl').innerText = (siswa.tempat_lahir || '-') + ', ' + (siswa.tanggal_lahir || '-');
    document.getElementById('v_jenis_kelamin').innerText = siswa.jenis_kelamin || '-';
    document.getElementById('v_agama').innerText = siswa.agama || '-';
    document.getElementById('v_jumlah_saudara').innerText = siswa.jumlah_saudara || '-';
    document.getElementById('v_diterima_di_kelas').innerText = siswa.diterima_di_kelas || '-';
    document.getElementById('v_tanggal_masuk').innerText = siswa.tanggal_masuk || '-';
    document.getElementById('v_jarak_rumah').innerText = siswa.jarak_rumah || '-';
    
    document.getElementById('v_alamat').innerText = siswa.alamat || '-';
    document.getElementById('v_desa_kelurahan').innerText = siswa.desa_kelurahan || '-';
    document.getElementById('v_kecamatan').innerText = siswa.kecamatan || '-';
    document.getElementById('v_kabupaten').innerText = siswa.kabupaten || '-';
    document.getElementById('v_provinsi').innerText = siswa.provinsi || '-';
    document.getElementById('v_kodepos').innerText = siswa.kodepos || '-';
    
    document.getElementById('v_nama_sekolah_asal').innerText = siswa.nama_sekolah_asal || '-';
    document.getElementById('v_npsn_sekolah').innerText = siswa.npsn_sekolah || '-';
    document.getElementById('v_no_seri_ijazah_smp').innerText = siswa.no_seri_ijazah_smp || '-';
    document.getElementById('v_no_blangko_skhu_smp').innerText = siswa.no_blangko_skhu_smp || '-';
    document.getElementById('v_no_peserta_un_smp').innerText = siswa.no_peserta_un_smp || '-';
    document.getElementById('v_total_nilai_un').innerText = siswa.total_nilai_un || '-';
    document.getElementById('v_tanggal_kelulusan_smp').innerText = siswa.tanggal_kelulusan_smp || '-';
    
    document.getElementById('v_nomor_kk').innerText = siswa.nomor_kk || '-';
    document.getElementById('v_nama_ayah').innerText = siswa.nama_ayah || '-';
    document.getElementById('v_nik_ayah').innerText = siswa.nik_ayah || '-';
    document.getElementById('v_tanggal_lahir_ayah').innerText = siswa.tanggal_lahir_ayah || '-';
    document.getElementById('v_pendidikan_ayah').innerText = siswa.pendidikan_ayah || '-';
    document.getElementById('v_pekerjaan_ayah').innerText = siswa.pekerjaan_ayah || '-';
    document.getElementById('v_penghasilan_ayah').innerText = siswa.penghasilan_ayah || '-';
    document.getElementById('v_status_ayah').innerText = siswa.status_ayah || '-';
    
    document.getElementById('v_nama_ibu').innerText = siswa.nama_ibu || '-';
    document.getElementById('v_nik_ibu').innerText = siswa.nik_ibu || '-';
    document.getElementById('v_tanggal_lahir_ibu').innerText = siswa.tanggal_lahir_ibu || '-';
    document.getElementById('v_pendidikan_ibu').innerText = siswa.pendidikan_ibu || '-';
    document.getElementById('v_pekerjaan_ibu').innerText = siswa.pekerjaan_ibu || '-';
    document.getElementById('v_penghasilan_ibu').innerText = siswa.penghasilan_ibu || '-';
    document.getElementById('v_status_ibu').innerText = siswa.status_ibu || '-';
    
    document.getElementById('v_nama_wali').innerText = siswa.nama_wali || '-';
    document.getElementById('v_nik_wali').innerText = siswa.nik_wali || '-';
    document.getElementById('v_pendidikan_wali').innerText = siswa.pendidikan_wali || '-';
    document.getElementById('v_pekerjaan_wali').innerText = siswa.pekerjaan_wali || '-';
    document.getElementById('v_penghasilan_wali').innerText = siswa.penghasilan_wali || '-';
    document.getElementById('v_nomor_kip').innerText = siswa.nomor_kip || '-';
    document.getElementById('v_nomor_kks').innerText = siswa.nomor_kks || '-';
    document.getElementById('v_nomor_pkh').innerText = siswa.nomor_pkh || '-';
    
    document.getElementById('v_alasan_putus_sekolah').innerText = siswa.alasan_putus_sekolah || '-';
    document.getElementById('v_tanggal_putus_sekolah').innerText = siswa.tanggal_putus_sekolah || '-';
    document.getElementById('v_alasan_mutasi').innerText = siswa.alasan_mutasi || '-';
    document.getElementById('v_tanggal_mutasi').innerText = siswa.tanggal_mutasi || '-';
    document.getElementById('v_tanggal_lulus_ma').innerText = siswa.tanggal_lulus_ma || '-';
    document.getElementById('v_no_ijazah_ma').innerText = siswa.no_ijazah_ma || '-';
    document.getElementById('v_no_skhu_ma').innerText = siswa.no_skhu_ma || '-';
    
    // MENYALIN DATA KE AREA CETAK KERTAS AGAR TIDAK KOSONG SAAT DI-PRINT
    var areaCetak = document.getElementById('areaCetakSiswa');
    areaCetak.innerHTML = `
        <div class="print-title">
            <h4>Biodata & Profil Lengkap Siswa</h4>
            <h6>Tahun Ajaran: ${siswa.tahun_ajaran || '-'}</h6>
        </div>
        <div style="display: flex; gap: 15px;">
            <div style="flex: 1;">
                <!-- KOTAK FOTO DI ATAS SEBELAH PALING KIRI -->
                <div class="kotak-foto-cetak">
                    FOTO<br>3 x 4
                </div>
                <h6 style="border-bottom: 2px solid #000; padding-bottom:5px; margin-top:5px;">1. Identitas Peserta Didik</h6>
                <table class="table table-bordered table-sm" style="font-size:11px; width:100%;">
                    <tr><th width="40%">No. Induk / Stb</th><td>${siswa.nomor_induk || '-'}</td></tr>
                    <tr><th>NIS Lokal</th><td>${siswa.nis_lokal || '-'}</td></tr>
                    <tr><th>NISN</th><td>${siswa.nisn || '-'}</td></tr>
                    <tr><th>NIK</th><td>${siswa.nik || '-'}</td></tr>
                    <tr><th>Nama Lengkap</th><td><b>${(siswa.nama_siswa || '-').toUpperCase()}</b></td></tr>
                    <tr><th>Tempat, Tgl Lahir</th><td>${siswa.tempat_lahir || '-' }, ${siswa.tanggal_lahir || '-'}</td></tr>
                    <tr><th>Jenis Kelamin</th><td>${siswa.jenis_kelamin || '-'}</td></tr>
                    <tr><th>Agama</th><td>${siswa.agama || '-'}</td></tr>
                    <tr><th>Jumlah Saudara</th><td>${siswa.jumlah_saudara || '-'}</td></tr>
                    <tr><th>Diterima di Kelas</th><td>${siswa.diterima_di_kelas || '-'}</td></tr>
                    <tr><th>Tanggal Masuk</th><td>${siswa.tanggal_masuk || '-'}</td></tr>
                    <tr><th>Jarak ke Sekolah</th><td>${siswa.jarak_rumah || '-'}</td></tr>
                </table>
                
                <h6 style="border-bottom: 2px solid #000; padding-bottom:5px; margin-top:10px;">2. Data Tempat Tinggal</h6>
                <table class="table table-bordered table-sm" style="font-size:11px; width:100%;">
                    <tr><th width="40%">Alamat Lengkap</th><td>${siswa.alamat || '-'}</td></tr>
                    <tr><th>Desa / Kelurahan</th><td>${siswa.desa_kelurahan || '-'}</td></tr>
                    <tr><th>Kecamatan</th><td>${siswa.kecamatan || '-'}</td></tr>
                    <tr><th>Kabupaten / Kota</th><td>${siswa.kabupaten || '-'}</td></tr>
                    <tr><th>Provinsi</th><td>${siswa.provinsi || '-'}</td></tr>
                    <tr><th>Kode Pos</th><td>${siswa.kodepos || '-'}</td></tr>
                </table>

                <h6 style="border-bottom: 2px solid #000; padding-bottom:5px; margin-top:10px;">3. Riwayat Pendidikan Asal (SMP/MTs)</h6>
                <table class="table table-bordered table-sm" style="font-size:11px; width:100%;">
                    <tr><th width="40%">Nama Sekolah Asal</th><td>${siswa.nama_sekolah_asal || '-'}</td></tr>
                    <tr><th>NPSN Sekolah</th><td>${siswa.npsn_sekolah || '-'}</td></tr>
                    <tr><th>No Seri Ijazah</th><td>${siswa.no_seri_ijazah_smp || '-'}</td></tr>
                    <tr><th>No Blangko SKHU</th><td>${siswa.no_blangko_skhu_smp || '-'}</td></tr>
                    <tr><th>No Peserta UN</th><td>${siswa.no_peserta_un_smp || '-'}</td></tr>
                    <tr><th>Total Nilai UN</th><td>${siswa.total_nilai_un || '-'}</td></tr>
                    <tr><th>Tanggal Lulus SMP</th><td>${siswa.tanggal_kelulusan_smp || '-'}</td></tr>
                </table>
            </div>
            
            <div style="flex: 1;">
                <h6 style="border-bottom: 2px solid #000; padding-bottom:5px; margin-top:10px;">4. Data Orang Tua Kandung</h6>
                <table class="table table-bordered table-sm" style="font-size:11px; width:100%;">
                    <tr><th width="40%">Nomor KK</th><td><b>${siswa.nomor_kk || '-'}</b></td></tr>
                    <tr style="background-color:#eee;"><th colspan="2" style="text-align:center; font-size:11px;">Data Ayah</th></tr>
                    <tr><th>Nama Ayah</th><td>${siswa.nama_ayah || '-'}</td></tr>
                    <tr><th>NIK Ayah</th><td>${siswa.nik_ayah || '-'}</td></tr>
                    <tr><th>Tgl Lahir Ayah</th><td>${siswa.tanggal_lahir_ayah || '-'}</td></tr>
                    <tr><th>Pendidikan Ayah</th><td>${siswa.pendidikan_ayah || '-'}</td></tr>
                    <tr><th>Pekerjaan Ayah</th><td>${siswa.pekerjaan_ayah || '-'}</td></tr>
                    <tr><th>Penghasilan Ayah</th><td>${siswa.penghasilan_ayah || '-'}</td></tr>
                    <tr><th>Status Ayah</th><td>${siswa.status_ayah || '-'}</td></tr>
                    <tr style="background-color:#eee;"><th colspan="2" style="text-align:center; font-size:11px;">Data Ibu</th></tr>
                    <tr><th>Nama Ibu</th><td>${siswa.nama_ibu || '-'}</td></tr>
                    <tr><th>NIK Ibu</th><td>${siswa.nik_ibu || '-'}</td></tr>
                    <tr><th>Tgl Lahir Ibu</th><td>${siswa.tanggal_lahir_ibu || '-'}</td></tr>
                    <tr><th>Pendidikan Ibu</th><td>${siswa.pendidikan_ibu || '-'}</td></tr>
                    <tr><th>Pekerjaan Ibu</th><td>${siswa.pekerjaan_ibu || '-'}</td></tr>
                    <tr><th>Penghasilan Ibu</th><td>${siswa.penghasilan_ibu || '-'}</td></tr>
                    <tr><th>Status Ibu</th><td>${siswa.status_ibu || '-'}</td></tr>
                </table>

                <h6 style="border-bottom: 2px solid #000; padding-bottom:5px; margin-top:10px;">5. Data Wali & Bantuan Sosial</h6>
                <table class="table table-bordered table-sm" style="font-size:11px; width:100%;">
                    <tr><th width="40%">Nama Wali</th><td>${siswa.nama_wali || '-'}</td></tr>
                    <tr><th>NIK Wali</th><td>${siswa.nik_wali || '-'}</td></tr>
                    <tr><th>Pendidikan Wali</th><td>${siswa.pendidikan_wali || '-'}</td></tr>
                    <tr><th>Pekerjaan Wali</th><td>${siswa.pekerjaan_wali || '-'}</td></tr>
                    <tr><th>Penghasilan Wali</th><td>${siswa.penghasilan_wali || '-'}</td></tr>
                    <tr><th>Nomor KIP</th><td>${siswa.nomor_kip || '-'}</td></tr>
                    <tr><th>Nomor KKS</th><td>${siswa.nomor_kks || '-'}</td></tr>
                    <tr><th>Nomor PKH</th><td>${siswa.nomor_pkh || '-'}</td></tr>
                </table>

                <h6 style="border-bottom: 2px solid #000; padding-bottom:5px; margin-top:10px;">6. Status Keluar Khusus MA</h6>
                <table class="table table-bordered table-sm" style="font-size:11px; width:100%;">
                    <tr><th width="40%">Alasan Putus Sekolah</th><td>${siswa.alasan_putus_sekolah || '-'}</td></tr>
                    <tr><th>Tanggal Putus Sekolah</th><td>${siswa.tanggal_putus_sekolah || '-'}</td></tr>
                    <tr><th>Alasan Mutasi (Pindah)</th><td>${siswa.alasan_mutasi || '-'}</td></tr>
                    <tr><th>Tanggal Mutasi</th><td>${siswa.tanggal_mutasi || '-'}</td></tr>
                    <tr><th>Tanggal Lulus MA</th><td>${siswa.tanggal_lulus_ma || '-'}</td></tr>
                    <tr><th>No. Seri Ijazah MA</th><td>${siswa.no_ijazah_ma || '-'}</td></tr>
                    <tr><th>No. SKHU MA</th><td>${siswa.no_skhu_ma || '-'}</td></tr>
                </table>
            </div>
        </div>
    `;
    
    var myModal = new bootstrap.Modal(document.getElementById('modalViewSiswa'));
    myModal.show();
}

// ==========================================
// FUNGSI UNTUK MEMBUKA MODAL EDIT SISWA
// ==========================================
// ==========================================
// FUNGSI UNTUK MEMBUKA MODAL EDIT SISWA
// ==========================================
function bukaModalEdit(siswa) {
    // Menyetel ID Siswa pada input hidden
    document.getElementById('edit_id_siswa').value = siswa.id || '';
    
    // Tab Data Pribadi & Alamat
    document.getElementById('edit_tahun_ajaran').value = siswa.tahun_ajaran || '';
    if(document.getElementById('edit_nomor_induk')) document.getElementById('edit_nomor_induk').value = siswa.nomor_induk || '';
    if(document.getElementById('edit_nis_lokal')) document.getElementById('edit_nis_lokal').value = siswa.nis_lokal || '';
    if(document.getElementById('edit_nisn')) document.getElementById('edit_nisn').value = siswa.nisn || '';
    if(document.getElementById('edit_nik')) document.getElementById('edit_nik').value = siswa.nik || '';
    if(document.getElementById('edit_nama_siswa')) document.getElementById('edit_nama_siswa').value = siswa.nama_siswa || '';
    if(document.getElementById('edit_tempat_lahir')) document.getElementById('edit_tempat_lahir').value = siswa.tempat_lahir || '';
    if(document.getElementById('edit_tanggal_lahir')) document.getElementById('edit_tanggal_lahir').value = siswa.tanggal_lahir || '';
    if(document.getElementById('edit_jenis_kelamin')) document.getElementById('edit_jenis_kelamin').value = siswa.jenis_kelamin || 'Laki-laki';
    if(document.getElementById('edit_agama')) document.getElementById('edit_agama').value = siswa.agama || 'Islam';
    if(document.getElementById('edit_jumlah_saudara')) document.getElementById('edit_jumlah_saudara').value = siswa.jumlah_saudara || '0';
    if(document.getElementById('edit_diterima_di_kelas')) document.getElementById('edit_diterima_di_kelas').value = siswa.diterima_di_kelas || '';
    if(document.getElementById('edit_tanggal_masuk')) document.getElementById('edit_tanggal_masuk').value = siswa.tanggal_masuk || '';
    if(document.getElementById('edit_jarak_rumah')) document.getElementById('edit_jarak_rumah').value = siswa.jarak_rumah || '';
    if(document.getElementById('edit_alamat')) document.getElementById('edit_alamat').value = siswa.alamat || '';
    if(document.getElementById('edit_desa_kelurahan')) document.getElementById('edit_desa_kelurahan').value = siswa.desa_kelurahan || '';
    if(document.getElementById('edit_kecamatan')) document.getElementById('edit_kecamatan').value = siswa.kecamatan || '';
    if(document.getElementById('edit_kabupaten')) document.getElementById('edit_kabupaten').value = siswa.kabupaten || '';
    if(document.getElementById('edit_provinsi')) document.getElementById('edit_provinsi').value = siswa.provinsi || '';
    if(document.getElementById('edit_kodepos')) document.getElementById('edit_kodepos').value = siswa.kodepos || '';

    // Tab Sekolah Asal
    if(document.getElementById('edit_nama_sekolah_asal')) document.getElementById('edit_nama_sekolah_asal').value = siswa.nama_sekolah_asal || '';
    if(document.getElementById('edit_npsn_sekolah')) document.getElementById('edit_npsn_sekolah').value = siswa.npsn_sekolah || '';
    if(document.getElementById('edit_total_nilai_un')) document.getElementById('edit_total_nilai_un').value = siswa.total_nilai_un || '';
    if(document.getElementById('edit_no_blangko_skhu_smp')) document.getElementById('edit_no_blangko_skhu_smp').value = siswa.no_blangko_skhu_smp || '';
    if(document.getElementById('edit_no_seri_ijazah_smp')) document.getElementById('edit_no_seri_ijazah_smp').value = siswa.no_seri_ijazah_smp || '';
    if(document.getElementById('edit_no_peserta_un_smp')) document.getElementById('edit_no_peserta_un_smp').value = siswa.no_peserta_un_smp || '';
    if(document.getElementById('edit_tanggal_kelulusan_smp')) document.getElementById('edit_tanggal_kelulusan_smp').value = siswa.tanggal_kelulusan_smp || '';

    // Tab Orang Tua
    if(document.getElementById('edit_nomor_kk')) document.getElementById('edit_nomor_kk').value = siswa.nomor_kk || '';
    if(document.getElementById('edit_nama_ayah')) document.getElementById('edit_nama_ayah').value = siswa.nama_ayah || '';
    if(document.getElementById('edit_nik_ayah')) document.getElementById('edit_nik_ayah').value = siswa.nik_ayah || '';
    if(document.getElementById('edit_tanggal_lahir_ayah')) document.getElementById('edit_tanggal_lahir_ayah').value = siswa.tanggal_lahir_ayah || '';
    if(document.getElementById('edit_pendidikan_ayah')) document.getElementById('edit_pendidikan_ayah').value = siswa.pendidikan_ayah || '';
    if(document.getElementById('edit_pekerjaan_ayah')) document.getElementById('edit_pekerjaan_ayah').value = siswa.pekerjaan_ayah || '';
    if(document.getElementById('edit_penghasilan_ayah')) document.getElementById('edit_penghasilan_ayah').value = siswa.penghasilan_ayah || '';
    if(document.getElementById('edit_status_ayah')) document.getElementById('edit_status_ayah').value = siswa.status_ayah || 'Masih Hidup';
    
    if(document.getElementById('edit_nama_ibu')) document.getElementById('edit_nama_ibu').value = siswa.nama_ibu || '';
    if(document.getElementById('edit_nik_ibu')) document.getElementById('edit_nik_ibu').value = siswa.nik_ibu || '';
    if(document.getElementById('edit_tanggal_lahir_ibu')) document.getElementById('edit_tanggal_lahir_ibu').value = siswa.tanggal_lahir_ibu || '';
    if(document.getElementById('edit_pendidikan_ibu')) document.getElementById('edit_pendidikan_ibu').value = siswa.pendidikan_ibu || '';
    if(document.getElementById('edit_pekerjaan_ibu')) document.getElementById('edit_pekerjaan_ibu').value = siswa.pekerjaan_ibu || '';
    if(document.getElementById('edit_penghasilan_ibu')) document.getElementById('edit_penghasilan_ibu').value = siswa.penghasilan_ibu || '';
    if(document.getElementById('edit_status_ibu')) document.getElementById('edit_status_ibu').value = siswa.status_ibu || 'Masih Hidup';
    
    // Tab Wali & Bansos
    if(document.getElementById('edit_nama_wali')) document.getElementById('edit_nama_wali').value = siswa.nama_wali || '';
    if(document.getElementById('edit_nik_wali')) document.getElementById('edit_nik_wali').value = siswa.nik_wali || '';
    if(document.getElementById('edit_tanggal_lahir_wali')) document.getElementById('edit_tanggal_lahir_wali').value = siswa.tanggal_lahir_wali || '';
    if(document.getElementById('edit_pendidikan_wali')) document.getElementById('edit_pendidikan_wali').value = siswa.pendidikan_wali || '';
    if(document.getElementById('edit_pekerjaan_wali')) document.getElementById('edit_pekerjaan_wali').value = siswa.pekerjaan_wali || '';
    if(document.getElementById('edit_penghasilan_wali')) document.getElementById('edit_penghasilan_wali').value = siswa.penghasilan_wali || '';
    if(document.getElementById('edit_nomor_kip')) document.getElementById('edit_nomor_kip').value = siswa.nomor_kip || '';
    if(document.getElementById('edit_nomor_kks')) document.getElementById('edit_nomor_kks').value = siswa.nomor_kks || '';
    if(document.getElementById('edit_nomor_pkh')) document.getElementById('edit_nomor_pkh').value = siswa.nomor_pkh || '';

    // Tab Status Khusus
    if(document.getElementById('edit_alasan_putus_sekolah')) document.getElementById('edit_alasan_putus_sekolah').value = siswa.alasan_putus_sekolah || '';
    if(document.getElementById('edit_tanggal_putus_sekolah')) document.getElementById('edit_tanggal_putus_sekolah').value = siswa.tanggal_putus_sekolah || '';
    if(document.getElementById('edit_alasan_mutasi')) document.getElementById('edit_alasan_mutasi').value = siswa.alasan_mutasi || '';
    if(document.getElementById('edit_tujuan_mutasi')) document.getElementById('edit_tujuan_mutasi').value = siswa.tujuan_mutasi || '';
    if(document.getElementById('edit_tanggal_mutasi')) document.getElementById('edit_tanggal_mutasi').value = siswa.tanggal_mutasi || '';
    if(document.getElementById('edit_tanggal_lulus_ma')) document.getElementById('edit_tanggal_lulus_ma').value = siswa.tanggal_lulus_ma || '';
    if(document.getElementById('edit_no_ijazah_ma')) document.getElementById('edit_no_ijazah_ma').value = siswa.no_ijazah_ma || '';
    if(document.getElementById('edit_no_skhu_ma')) document.getElementById('edit_no_skhu_ma').value = siswa.no_skhu_ma || '';

    // Menampilkan Modal Edit menggunakan Bootstrap 5 (Sesuai id modal 'modalEditSiswa')
    var modalEdit = new bootstrap.Modal(document.getElementById('modalEditSiswa'));
    modalEdit.show();
}

// ====================================================================
// FUNGSI KHUSUS UNTUK MENYALIN DATA AYAH/IBU KE WALI PADA MODAL EDIT
// ====================================================================
function salinDataWaliEdit(tipe) {
    var cbAyah = document.getElementById('edit_sama_dengan_ayah');
    var cbIbu = document.getElementById('edit_sama_dengan_ibu');

    if (tipe === 'ayah') {
        if (cbAyah && cbAyah.checked) {
            // Batalkan ceklis Ibu jika Ayah dipilih
            if (cbIbu) cbIbu.checked = false; 
            
            // Salin data dari input Ayah ke input Wali
            document.getElementById('edit_nama_wali').value = document.getElementById('edit_nama_ayah').value || '';
            document.getElementById('edit_nik_wali').value = document.getElementById('edit_nik_ayah').value || '';
            document.getElementById('edit_tanggal_lahir_wali').value = document.getElementById('edit_tanggal_lahir_ayah').value || '';
            document.getElementById('edit_pendidikan_wali').value = document.getElementById('edit_pendidikan_ayah').value || '';
            document.getElementById('edit_pekerjaan_wali').value = document.getElementById('edit_pekerjaan_ayah').value || '';
            document.getElementById('edit_penghasilan_wali').value = document.getElementById('edit_penghasilan_ayah').value || '';
        } else {
            kosongkanFormWaliEdit();
        }
    } else if (tipe === 'ibu') {
        if (cbIbu && cbIbu.checked) {
            // Batalkan ceklis Ayah jika Ibu dipilih
            if (cbAyah) cbAyah.checked = false; 
            
            // Salin data dari input Ibu ke input Wali
            document.getElementById('edit_nama_wali').value = document.getElementById('edit_nama_ibu').value || '';
            document.getElementById('edit_nik_wali').value = document.getElementById('edit_nik_ibu').value || '';
            document.getElementById('edit_tanggal_lahir_wali').value = document.getElementById('edit_tanggal_lahir_ibu').value || '';
            document.getElementById('edit_pendidikan_wali').value = document.getElementById('edit_pendidikan_ibu').value || '';
            document.getElementById('edit_pekerjaan_wali').value = document.getElementById('edit_pekerjaan_ibu').value || '';
            document.getElementById('edit_penghasilan_wali').value = document.getElementById('edit_penghasilan_ibu').value || '';
        } else {
            kosongkanFormWaliEdit();
        }
    }
}

// Fungsi pembantu untuk mengosongkan kembali form wali jika ceklis dicopot
function kosongkanFormWaliEdit() {
    if(document.getElementById('edit_nama_wali')) document.getElementById('edit_nama_wali').value = '';
    if(document.getElementById('edit_nik_wali')) document.getElementById('edit_nik_wali').value = '';
    if(document.getElementById('edit_tanggal_lahir_wali')) document.getElementById('edit_tanggal_lahir_wali').value = '';
    if(document.getElementById('edit_pendidikan_wali')) document.getElementById('edit_pendidikan_wali').value = '';
    if(document.getElementById('edit_pekerjaan_wali')) document.getElementById('edit_pekerjaan_wali').value = '';
    if(document.getElementById('edit_penghasilan_wali')) document.getElementById('edit_penghasilan_wali').value = '';
}

</script>