<?php
// Ganti $koneksi menjadi nama variabel yang ada di koneksi.php (contoh: $conn)
global $conn; 
$koneksi = $conn; 

require 'vendor/autoload.php';
// ... sisa kode ke bawah tetap sama ...

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

$pesan = $_SESSION['upload_error_siswa'] ?? '';
$tipe_pesan = $pesan === '' ? '' : 'danger';
unset($_SESSION['upload_error_siswa']);

if (isset($_POST['import'])) {
    if (!isset($_FILES['file_excel']) || $_FILES['file_excel']['error'] !== UPLOAD_ERR_OK) {
        $kode_upload = $_FILES['file_excel']['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($kode_upload === UPLOAD_ERR_INI_SIZE || $kode_upload === UPLOAD_ERR_FORM_SIZE) {
            $pesan = 'Ukuran berkas melampaui batas unggah server. Tingkatkan upload_max_filesize dan post_max_size pada hosting.';
            $tipe_pesan = 'danger';
        } elseif ($kode_upload === UPLOAD_ERR_NO_FILE) {
            $pesan = 'Silakan pilih berkas Excel terlebih dahulu.';
            $tipe_pesan = 'warning';
        } else {
            $pesan = 'Server gagal menerima berkas Excel (kode unggah ' . (int)$kode_upload . '). Silakan unggah ulang atau periksa konfigurasi hosting.';
            $tipe_pesan = 'danger';
        }
    } else {
        
        $extension = strtolower(pathinfo($_FILES['file_excel']['name'], PATHINFO_EXTENSION));

        // Validasi ekstensi berkas
        $allowed_extension = array('xls', 'xlsx');
        
        if (in_array($extension, $allowed_extension)) {
            $file_path = $_FILES['file_excel']['tmp_name'];

            try {
                // Sembunyikan pesan warning dari internal DOMDocument HTML parsing jika file tidak murni
                libxml_use_internal_errors(true);

                // Tentukan Reader secara spesifik sesuai ekstensi agar tidak salah tebak sebagai HTML
                if ($extension == 'xlsx') {
                    $reader = IOFactory::createReader('Xlsx');
                } else {
                    $reader = IOFactory::createReader('Xls');
                }
                
                // Set agar reader hanya membaca data saja (mempercepat proses & menghindari struktur aneh)
                $reader->setReadDataOnly(true);
                
                // Membaca file excel
                $spreadsheet = $reader->load($file_path);
                $worksheet = $spreadsheet->getActiveSheet();
                $rows = $worksheet->toArray();

                // Bersihkan cache error libxml setelah selesai meload file
                libxml_clear_errors();

                $header = array_shift($rows);
                $header_template = [
                    'tahun_ajaran', 'nomor_induk', 'nis_lokal', 'nisn', 'nik', 'nama_siswa', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin', 'agama',
                    'jumlah_saudara', 'alamat', 'desa_kelurahan', 'kecamatan', 'kabupaten', 'provinsi', 'kodepos', 'jarak_rumah', 'nama_sekolah_asal', 'npsn_sekolah',
                    'no_blangko_skhu_smp', 'no_seri_ijazah_smp', 'total_nilai_un', 'tanggal_kelulusan_smp', 'no_peserta_un_smp', 'tanggal_masuk', 'diterima_di_kelas', 'nomor_kk',
                    'nama_ayah', 'tanggal_lahir_ayah', 'nik_ayah', 'pendidikan_ayah', 'pekerjaan_ayah', 'penghasilan_ayah', 'status_ayah',
                    'nama_ibu', 'tanggal_lahir_ibu', 'nik_ibu', 'pendidikan_ibu', 'pekerjaan_ibu', 'penghasilan_ibu', 'status_ibu',
                    'nomor_kip', 'nomor_kks', 'nomor_pkh', 'nama_wali', 'tanggal_lahir_wali', 'nik_wali', 'pendidikan_wali', 'pekerjaan_wali', 'penghasilan_wali'
                ];
                $header = array_map(static function ($nilai) {
                    return strtolower(trim((string)$nilai));
                }, $header ?? []);
                if (array_slice($header, 0, count($header_template)) !== $header_template) {
                    throw new RuntimeException('Format kolom tidak sesuai. Unduh dan gunakan template siswa terbaru.');
                }

                $sukses = 0;
                $gagal = 0;

                // Pastikan variabel $koneksi tersedia sebelum transaksi database dimulai
                if ($koneksi) {
                    $transaksi_aktif = false;
                    // Mulai Transaksi Database agar aman
                    $koneksi->begin_transaction();
                    $transaksi_aktif = true;

                    foreach ($rows as $row) {
                        // Jika baris kosong (misal nama siswa pada kolom indeks ke-5 kosong), lewati
                        if (!isset($row[5]) || empty(trim($row[5]))) { 
                            continue;
                        }

                        // Mapping data sesuai urutan kolom pada berkas template Anda
                        $tahun_ajaran          = $koneksi->real_escape_string($row[0] ?? '');
                        $nomor_induk           = $koneksi->real_escape_string($row[1] ?? '');
                        $nis_lokal             = $koneksi->real_escape_string($row[2] ?? '');
                        $nisn                  = $koneksi->real_escape_string($row[3] ?? '');
                        $nik                   = $koneksi->real_escape_string($row[4] ?? '');
                        $nama_siswa            = $koneksi->real_escape_string($row[5] ?? '');
                        $tempat_lahir          = $koneksi->real_escape_string($row[6] ?? '');
                        
                        // Format tanggal lahir YYYY-MM-DD
                        $tanggal_lahir         = !empty($row[7]) ? date('Y-m-d', strtotime($row[7])) : NULL;
                        
                        $jenis_kelamin         = $koneksi->real_escape_string($row[8] ?? '');
                        $agama                 = $koneksi->real_escape_string($row[9] ?? '');
                        $jumlah_saudara        = !empty($row[10]) ? (int)$row[10] : 0;
                        $alamat                = $koneksi->real_escape_string($row[11] ?? '');
                        $desa_kelurahan        = $koneksi->real_escape_string($row[12] ?? '');
                        $kecamatan             = $koneksi->real_escape_string($row[13] ?? '');
                        $kabupaten             = $koneksi->real_escape_string($row[14] ?? '');
                        $provinsi              = $koneksi->real_escape_string($row[15] ?? '');
                        $kodepos               = $koneksi->real_escape_string($row[16] ?? '');
                        $jarak_rumah           = $koneksi->real_escape_string($row[17] ?? '');
                        $nama_sekolah_asal     = $koneksi->real_escape_string($row[18] ?? '');
                        $npsn_sekolah          = $koneksi->real_escape_string($row[19] ?? '');
                        $no_blangko_skhu_smp   = $koneksi->real_escape_string($row[20] ?? '');
                        $no_seri_ijazah_smp    = $koneksi->real_escape_string($row[21] ?? '');
                        $total_nilai_un        = !empty($row[22]) ? (float)$row[22] : 0;
                        $tanggal_kelulusan_smp = !empty($row[23]) ? date('Y-m-d', strtotime($row[23])) : NULL;
                        $no_peserta_un_smp     = $koneksi->real_escape_string($row[24] ?? '');
                        $tanggal_masuk         = !empty($row[25]) ? date('Y-m-d', strtotime($row[25])) : NULL;
                        $diterima_di_kelas     = $koneksi->real_escape_string($row[26] ?? '');
                        $nomor_kk              = $koneksi->real_escape_string($row[27] ?? '');
                        
                        // Data Ayah
                        $nama_ayah             = $koneksi->real_escape_string($row[28] ?? '');
                        $tanggal_lahir_ayah    = !empty($row[29]) ? date('Y-m-d', strtotime($row[29])) : NULL;
                        $nik_ayah              = $koneksi->real_escape_string($row[30] ?? '');
                        $pendidikan_ayah       = $koneksi->real_escape_string($row[31] ?? '');
                        $pekerjaan_ayah        = $koneksi->real_escape_string($row[32] ?? '');
                        $penghasilan_ayah      = $koneksi->real_escape_string($row[33] ?? '');
                        $status_ayah           = $koneksi->real_escape_string($row[34] ?? '');

                        // Data Ibu
                        $nama_ibu              = $koneksi->real_escape_string($row[35] ?? '');
                        $tanggal_lahir_ibu     = !empty($row[36]) ? date('Y-m-d', strtotime($row[36])) : NULL;
                        $nik_ibu               = $koneksi->real_escape_string($row[37] ?? '');
                        $pendidikan_ibu        = $koneksi->real_escape_string($row[38] ?? '');
                        $pekerjaan_ibu         = $koneksi->real_escape_string($row[39] ?? '');
                        $penghasilan_ibu       = $koneksi->real_escape_string($row[40] ?? '');
                        $status_ibu            = $koneksi->real_escape_string($row[41] ?? '');

                        // Data Bantuan / Wali
                        $nomor_kip             = $koneksi->real_escape_string($row[42] ?? '');
                        $nomor_kks             = $koneksi->real_escape_string($row[43] ?? '');
                        $nomor_pkh             = $koneksi->real_escape_string($row[44] ?? '');
                        $nama_wali             = $koneksi->real_escape_string($row[45] ?? '');
                        $tanggal_lahir_wali    = !empty($row[46]) ? date('Y-m-d', strtotime($row[46])) : NULL;
                        $nik_wali              = $koneksi->real_escape_string($row[47] ?? '');
                        $pendidikan_wali       = $koneksi->real_escape_string($row[48] ?? '');
                        $pekerjaan_wali        = $koneksi->real_escape_string($row[49] ?? '');
                        $penghasilan_wali      = $koneksi->real_escape_string($row[50] ?? '');

                        // Query Simpan Data ke Tabel Siswa
                        $query = "INSERT INTO siswa (
                            tahun_ajaran, nomor_induk, nis_lokal, nisn, nik, nama_siswa, tempat_lahir, tanggal_lahir, jenis_kelamin, agama, 
                            jumlah_saudara, alamat, desa_kelurahan, kecamatan, kabupaten, provinsi, kodepos, jarak_rumah, nama_sekolah_asal, npsn_sekolah, 
                            no_blangko_skhu_smp, no_seri_ijazah_smp, total_nilai_un, tanggal_kelulusan_smp, no_peserta_un_smp, tanggal_masuk, diterima_di_kelas, nomor_kk, 
                            nama_ayah, tanggal_lahir_ayah, nik_ayah, pendidikan_ayah, pekerjaan_ayah, penghasilan_ayah, status_ayah, 
                            nama_ibu, tanggal_lahir_ibu, nik_ibu, pendidikan_ibu, pekerjaan_ibu, penghasilan_ibu, status_ibu, 
                            nomor_kip, nomor_kks, nomor_pkh, nama_wali, tanggal_lahir_wali, nik_wali, pendidikan_wali, pekerjaan_wali, penghasilan_wali
                        ) VALUES (
                            '$tahun_ajaran', '$nomor_induk', '$nis_lokal', '$nisn', '$nik', '$nama_siswa', '$tempat_lahir', " . ($tanggal_lahir ? "'$tanggal_lahir'" : "NULL") . ", '$jenis_kelamin', '$agama',
                            $jumlah_saudara, '$alamat', '$desa_kelurahan', '$kecamatan', '$kabupaten', '$provinsi', '$kodepos', '$jarak_rumah', '$nama_sekolah_asal', '$npsn_sekolah',
                            '$no_blangko_skhu_smp', '$no_seri_ijazah_smp', $total_nilai_un, " . ($tanggal_kelulusan_smp ? "'$tanggal_kelulusan_smp'" : "NULL") . ", '$no_peserta_un_smp', " . ($tanggal_masuk ? "'$tanggal_masuk'" : "NULL") . ", '$diterima_di_kelas', '$nomor_kk',
                            '$nama_ayah', " . ($tanggal_lahir_ayah ? "'$tanggal_lahir_ayah'" : "NULL") . ", '$nik_ayah', '$pendidikan_ayah', '$pekerjaan_ayah', '$penghasilan_ayah', '$status_ayah',
                            '$nama_ibu', " . ($tanggal_lahir_ibu ? "'$tanggal_lahir_ibu'" : "NULL") . ", '$nik_ibu', '$pendidikan_ibu', '$pekerjaan_ibu', '$penghasilan_ibu', '$status_ibu',
                            '$nomor_kip', '$nomor_kks', '$nomor_pkh', '$nama_wali', " . ($tanggal_lahir_wali ? "'$tanggal_lahir_wali'" : "NULL") . ", '$nik_wali', '$pendidikan_wali', '$pekerjaan_wali', '$penghasilan_wali'
                        )";

                        if ($koneksi->query($query)) {
                            $sukses++;
                        } else {
                            $gagal++;
                        }
                    }

                    // Commit transaksi database
                    $koneksi->commit();
                    $transaksi_aktif = false;
                    $pesan = "Proses impor selesai. <strong>$sukses data berhasil disimpan</strong>, dan <strong>$gagal data gagal</strong>.";
                    $tipe_pesan = "success";
                } else {
                    $pesan = "Koneksi database terputus atau tidak terdefinisi.";
                    $tipe_pesan = "danger";
                }

            } catch (Throwable $e) {
                if (!empty($transaksi_aktif)) {
                    $koneksi->rollback();
                }
                $pesan = "Terjadi kesalahan saat membaca berkas: " . $e->getMessage();
                $tipe_pesan = "danger";
            }
        } else {
            $pesan = "Format berkas tidak valid. Hanya berkas .xls dan .xlsx yang diperbolehkan.";
            $tipe_pesan = "danger";
        }
    }
}
?>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 text-primary"><i class="fa-solid fa-file-excel me-2"></i>Unggah Data Siswa Baru</h5>
    </div>
    <div class="card-body py-4">
        
        <?php if ($pesan != ""): ?>
            <div class="alert alert-<?php echo $tipe_pesan; ?> alert-dismissible fade show" role="alert">
                <?php echo $pesan; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row bg-light p-3 rounded mb-4 mx-1">
            <div class="col-md-8">
                <h6 class="fw-bold">Petunjuk Pengisian:</h6>
                <ul class="small text-muted mb-0 ps-3">
                    <li>Gunakan tombol di sebelah kanan untuk mengunduh template format resmi asli.</li>
                    <li>Jangan mengubah susunan atau menghapus judul kolom pada baris pertama.</li>
                    <li>Format penulisan tanggal wajib menggunakan susunan **YYYY-MM-DD** (Contoh: 2009-03-22).</li>
                    <li>Kolom **tahun_ajaran** diisi sesuai format aktif di sistem (Contoh: `2026/2027`).</li>
                </ul>
            </div>
            <div class="col-md-4 d-flex align-items-center justify-content-md-end mt-3 mt-md-0">
                <a href="index.php?page=upload_siswa&download_template=1" class="btn btn-outline-success">
                    <i class="fa-solid fa-download me-2"></i>Unduh Template Excel
                </a>
            </div>
        </div>

        <form action="" method="POST" enctype="multipart/form-data" class="col-md-6">
            <div class="mb-3">
                <label for="file_excel" class="form-label fw-bold">Pilih Berkas Excel (.xlsx / .xls)</label>
                <input class="form-control" type="file" id="file_excel" name="file_excel" accept=".xlsx, .xls" required>
            </div>
            <button type="submit" name="import" class="btn btn-primary">
                <i class="fa-solid fa-cloud-arrow-up me-2"></i>Mulai Unggah & Impor
            </button>
        </form>

    </div>
</div>