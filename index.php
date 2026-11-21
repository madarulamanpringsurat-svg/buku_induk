<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load autoloader Composer
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

include 'koneksi.php';
require_once __DIR__ . '/google_drive_helper.php';

// Pastikan variabel koneksi fleksibel
if (!isset($conn) && isset($koneksi)) { $conn = $koneksi; }
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
if (!isset($koneksi) && isset($conn)) { $koneksi = $conn; }
$permintaan_status_sertifikasi = $_SERVER['REQUEST_METHOD'] === 'POST'
    && (isset($_POST['simpan_status_sertifikasi']) || isset($_POST['guru_id_status_sertifikasi']));
if ($permintaan_status_sertifikasi) {
    $page = 'buku_induk_guru';
}

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0
    && empty($_POST)
    && empty($_FILES)
    && in_array($page, ['buku_induk_guru', 'upload_siswa'], true)
) {
    $pesan_ukuran = 'Ukuran unggahan melampaui post_max_size pada hosting. Tingkatkan post_max_size lalu unggah kembali.';
    if ($page === 'buku_induk_guru') {
        $_SESSION['pesan_buku_guru'] = $pesan_ukuran;
        $_SESSION['status_buku_guru'] = 'gagal';
        header('Location: index?page=buku_induk_guru&bagian=pegawai');
    } else {
        $_SESSION['upload_error_siswa'] = $pesan_ukuran;
        header('Location: index.php?page=upload_siswa');
    }
    exit;
}

// --- PROTEKSI HALAMAN ---
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    if (!(isset($_GET['page']) && $_GET['page'] === 'upload_siswa' && isset($_GET['download_template']))) {
        header("Location: login.php");
        exit;
    }
}

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    if (!isset($_SESSION['role'])) {
        $_SESSION['role'] = 'admin';
    }
}

if (($_SESSION['role'] ?? '') === 'guru') {
    $page = ($_GET['page'] ?? '') === 'guru_portal' ? 'guru_portal' : 'guru_portal';
    $guruIdSesi = (int)($_SESSION['guru_id'] ?? 0);
    $_GET['page'] = 'guru_portal';
    $_GET['guru'] = $guruIdSesi;
    $_GET['edit'] = $guruIdSesi;
    if (!in_array($_GET['bagian'] ?? '', ['pegawai', 'keaktifan', 'pendidikan', 'keluarga', 'jabatan', 'penghargaan', 'sertifikasi'], true)) {
        $_GET['bagian'] = 'pegawai';
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        foreach (['guru_id', 'guru_id_status_sertifikasi', 'guru_id_riwayat'] as $fieldGuru) {
            if (array_key_exists($fieldGuru, $_POST)) {
                $_POST[$fieldGuru] = $guruIdSesi;
            }
        }
        $kategoriGuru = trim((string)($_POST['kategori'] ?? ''));
        if ($kategoriGuru === 'mutasi') {
            http_response_code(403);
            exit('Aksi mutasi tidak diizinkan untuk akun guru.');
        }
        $aksiGuruDilarang = ['import_guru', 'hapus_profil_guru', 'hapus_guru', 'simpan_identitas_madrasah'];
        foreach ($aksiGuruDilarang as $aksiDilarang) {
            if (isset($_POST[$aksiDilarang])) {
                http_response_code(403);
                exit('Aksi tidak diizinkan untuk akun guru.');
            }
        }
    }
}

// =========================================================================
// INTERCEPTOR POST: PROSES TAMBAH DATA GURU
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['page']) && $_GET['page'] === 'data_guru') {
    if (isset($_POST['action_simpan']) || isset($_POST['simpan'])) {
        $nip              = trim($_POST['nip'] ?? '-');
        $nama_guru        = trim($_POST['nama_guru'] ?? '');
        $jabatan          = trim($_POST['jabatan'] ?? '-');
        $jabatan_tambahan = trim($_POST['jabatan_tambahan'] ?? '-');
        $mata_pelajaran   = trim($_POST['mata_pelajaran'] ?? '-');

        if (!empty($nama_guru)) {
            $nip_esc              = mysqli_real_escape_string($conn, $nip);
            $nama_guru_esc        = mysqli_real_escape_string($conn, $nama_guru);
            $jabatan_esc          = mysqli_real_escape_string($conn, $jabatan);
            $jabatan_tambahan_esc = mysqli_real_escape_string($conn, $jabatan_tambahan);
            $mata_pelajaran_esc   = mysqli_real_escape_string($conn, $mata_pelajaran);
            $google_email_esc     = strtolower(trim(mysqli_real_escape_string($conn, $_POST['google_email'] ?? '')));
            $google_login_enabled = isset($_POST['google_login_enabled']) ? 1 : 0;

            $sql = "INSERT INTO guru (nama_guru, nip, jabatan, jabatan_tambahan, mata_pelajaran, google_email, google_login_enabled)
                    VALUES ('$nama_guru_esc', '$nip_esc', '$jabatan_esc', '$jabatan_tambahan_esc', '$mata_pelajaran_esc', '$google_email_esc', $google_login_enabled)";

            $query = mysqli_query($conn, $sql);

            if ($query) {
                header("Location: index.php?page=data_guru&pesan=tambah_berhasil");
                exit();
            } else {
                $_SESSION['db_error'] = "MySQL Error: " . mysqli_error($conn);
                header("Location: index.php?page=data_guru&pesan=gagal");
                exit();
            }
        } else {
            $_SESSION['db_error'] = "Nama guru tidak boleh kosong!";
            header("Location: index.php?page=data_guru&pesan=gagal");
            exit();
        }
    }
}

// =========================================================================
// INTERCEPTOR POST: PROSES TAMBAH DATA GURU
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['page']) && $_GET['page'] === 'data_guru') {
    if (isset($_POST['simpan'])) {
        $nip              = trim($_POST['nip'] ?? '-');
        $nama_guru        = trim($_POST['nama_guru'] ?? '');
        $jabatan          = trim($_POST['jabatan'] ?? '-');
        $jabatan_tambahan = trim($_POST['jabatan_tambahan'] ?? '-');
        $mata_pelajaran   = trim($_POST['mata_pelajaran'] ?? '-');

        if (!empty($nama_guru)) {
            // Gunakan query INSERT biasa jika Prepared Statement terkendala koneksi
            $nip_esc              = mysqli_real_escape_string($conn, $nip);
            $nama_guru_esc        = mysqli_real_escape_string($conn, $nama_guru);
            $jabatan_esc          = mysqli_real_escape_string($conn, $jabatan);
            $jabatan_tambahan_esc = mysqli_real_escape_string($conn, $jabatan_tambahan);
            $mata_pelajaran_esc   = mysqli_real_escape_string($conn, $mata_pelajaran);
            $google_email_esc     = strtolower(trim(mysqli_real_escape_string($conn, $_POST['google_email'] ?? '')));
            $google_login_enabled = isset($_POST['google_login_enabled']) ? 1 : 0;

            $sql = "INSERT INTO guru (nama_guru, nip, jabatan, jabatan_tambahan, mata_pelajaran, google_email, google_login_enabled)
                    VALUES ('$nama_guru_esc', '$nip_esc', '$jabatan_esc', '$jabatan_tambahan_esc', '$mata_pelajaran_esc', '$google_email_esc', $google_login_enabled)";

            $query = mysqli_query($conn, $sql);

            if ($query) {
                header("Location: index.php?page=data_guru&pesan=tambah_berhasil");
                exit();
            } else {
                $_SESSION['db_error'] = "MySQL Error: " . mysqli_error($conn);
                header("Location: index.php?page=data_guru&pesan=gagal");
                exit();
            }
        } else {
            $_SESSION['db_error'] = "Nama guru tidak boleh kosong!";
            header("Location: index.php?page=data_guru&pesan=gagal");
            exit();
        }
    }
}

// 2. Logika Intersepsi Download Template Excel
if (isset($_GET['page']) && $_GET['page'] === 'upload_siswa' && isset($_GET['download_template'])) {
    require 'vendor/autoload.php';

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Template Siswa');

    $headers = [
        'tahun_ajaran', 'nomor_induk', 'nis_lokal', 'nisn', 'nik', 'nama_siswa', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin', 'agama',
        'jumlah_saudara', 'alamat', 'desa_kelurahan', 'kecamatan', 'kabupaten', 'provinsi', 'kodepos', 'jarak_rumah', 'nama_sekolah_asal', 'npsn_sekolah',
        'no_blangko_skhu_smp', 'no_seri_ijazah_smp', 'total_nilai_un', 'tanggal_kelulusan_smp', 'no_peserta_un_smp', 'tanggal_masuk', 'diterima_di_kelas', 'nomor_kk',
        'nama_ayah', 'tanggal_lahir_ayah', 'nik_ayah', 'pendidikan_ayah', 'pekerjaan_ayah', 'penghasilan_ayah', 'status_ayah',
        'nama_ibu', 'tanggal_lahir_ibu', 'nik_ibu', 'pendidikan_ibu', 'pekerjaan_ibu', 'penghasilan_ibu', 'status_ibu',
        'nomor_kip', 'nomor_kks', 'nomor_pkh', 'nama_wali', 'tanggal_lahir_wali', 'nik_wali', 'pendidikan_wali', 'pekerjaan_wali', 'penghasilan_wali'
    ];

    foreach ($headers as $index => $header) {
        $cellCoordinate = Coordinate::stringFromColumnIndex($index + 1) . '1';
        $sheet->setCellValue($cellCoordinate, $header);
    }

    if (ob_get_length()) ob_end_clean();

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="template_siswa.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
    $writer->save('php://output');
    exit;
}

if (in_array($page, ['dashboard', 'buku_induk_guru', 'pengaturan'], true)) {
    require_once __DIR__ . '/guru_induk_helper.php';
    pastikan_tabel_buku_induk($conn);
}

if ($page === 'buku_induk_guru' && ($_GET['export_guru'] ?? '') === 'excel') {
    if (!class_exists('ZipArchive')) {
        http_response_code(500);
        exit('Ekstensi ZIP PHP belum aktif. Aktifkan extension=zip pada php.ini, lalu restart Apache.');
    }

    $hasil_export_guru = mysqli_query($conn, "
        SELECT g.nama_guru, g.nip, g.jabatan, g.jabatan_tambahan, g.mata_pelajaran,
               bg.tempat_lahir, bg.tanggal_lahir, bg.nuptk, bg.nik, bg.npk, bg.status_keaktifan, bg.status_kepegawaian, bg.no_hp, bg.email,
               (SELECT rb.nrg FROM riwayat_buku_guru rb WHERE rb.guru_id=g.id AND rb.kategori='sertifikasi' ORDER BY rb.id DESC LIMIT 1) AS nrg
        FROM guru g
        LEFT JOIN buku_induk_guru bg ON bg.guru_id=g.id
        ORDER BY g.nama_guru ASC
    ");
    if (!$hasil_export_guru) {
        error_log('Ekspor daftar guru gagal dimuat: ' . mysqli_error($conn));
        http_response_code(500);
        exit('Daftar guru gagal disiapkan untuk ekspor Excel.');
    }

    $daftar_export_guru = ['Aktif' => [], 'Nonaktif' => []];
    while ($guru_export = mysqli_fetch_assoc($hasil_export_guru)) {
        $status_keaktifan_export = $guru_export['status_keaktifan'] ?? 'Aktif';
        $daftar_export_guru[$status_keaktifan_export === 'Aktif' ? 'Aktif' : 'Nonaktif'][] = $guru_export;
    }

    $hasil_export_sertifikasi = mysqli_query($conn, "
        SELECT g.nama_guru, g.nip, bg.nik, bg.npk, bg.status_sertifikasi,
               sert.no_piagam, sert.nrg, sert.mapel_sertifikasi, sert.badan_sertifikasi,
               sert.jalur_sertifikasi, sert.tanggal_piagam
        FROM guru g
        LEFT JOIN buku_induk_guru bg ON bg.guru_id=g.id
        LEFT JOIN (
            SELECT rb.guru_id, rb.judul AS no_piagam, rb.nrg, rb.mapel_sertifikasi,
                   rb.instansi AS badan_sertifikasi, rb.jalur_sertifikasi, rb.tanggal AS tanggal_piagam
            FROM riwayat_buku_guru rb
            INNER JOIN (
                SELECT guru_id, MAX(id) AS riwayat_id
                FROM riwayat_buku_guru
                WHERE kategori='sertifikasi'
                GROUP BY guru_id
            ) terbaru ON terbaru.riwayat_id=rb.id
        ) sert ON sert.guru_id=g.id
        WHERE bg.status_sertifikasi='Sudah' OR sert.no_piagam IS NOT NULL
        ORDER BY g.nama_guru ASC
    ");
    if (!$hasil_export_sertifikasi) {
        error_log('Ekspor data sertifikasi gagal dimuat: ' . mysqli_error($conn));
        http_response_code(500);
        exit('Data sertifikasi gagal disiapkan untuk ekspor Excel.');
    }
    $daftar_export_sertifikasi = [];
    while ($guru_export = mysqli_fetch_assoc($hasil_export_sertifikasi)) {
        $daftar_export_sertifikasi[] = $guru_export;
    }

    $hasil_export_inpassing = mysqli_query($conn, "
        SELECT g.nama_guru, g.nip, bg.nik, bg.npk, bg.status_inpassing,
               bg.pangkat_golongan, bg.tmt_pangkat, bg.nomor_sk_inpassing, bg.keterangan_inpassing
        FROM guru g
        INNER JOIN buku_induk_guru bg ON bg.guru_id=g.id
        WHERE bg.status_inpassing='Sudah'
        ORDER BY g.nama_guru ASC
    ");
    if (!$hasil_export_inpassing) {
        error_log('Ekspor data inpassing gagal dimuat: ' . mysqli_error($conn));
        http_response_code(500);
        exit('Data inpassing gagal disiapkan untuk ekspor Excel.');
    }
    $daftar_export_inpassing = [];
    while ($guru_export = mysqli_fetch_assoc($hasil_export_inpassing)) {
        $daftar_export_inpassing[] = $guru_export;
    }

    $spreadsheet = new Spreadsheet();
    $judul_kolom_export = [
        'No.',
        'Nama Guru',
        'Tempat Lahir',
        'Tanggal Lahir',
        'NIP / No. Induk',
        'NUPTK',
        'NIK',
        'NPK',
        'NRG',
        'Jabatan Utama',
        'Jabatan Tambahan',
        'Mata Pelajaran',
        'Status Kepegawaian',
        'Nomor HP',
        'Email'
    ];
    $konfigurasi_sheet_export = [
        'Aktif' => [
            'data' => $daftar_export_guru['Aktif'],
            'headers' => $judul_kolom_export,
            'widths' => [8, 30, 20, 18, 20, 18, 18, 18, 24, 28, 24, 22, 20, 30, 30]
        ],
        'Nonaktif' => [
            'data' => $daftar_export_guru['Nonaktif'],
            'headers' => $judul_kolom_export,
            'widths' => [8, 30, 20, 18, 20, 18, 18, 18, 24, 28, 24, 22, 20, 30, 30]
        ],
        'Sertifikasi' => [
            'data' => $daftar_export_sertifikasi,
            'headers' => ['No.', 'Nama Guru', 'NIP / No. Induk', 'NIK', 'NPK', 'Status Sertifikasi', 'No. Piagam', 'NRG', 'Mata Pelajaran', 'Badan Sertifikasi', 'Jalur Sertifikasi', 'Tanggal Piagam'],
            'widths' => [8, 30, 20, 18, 18, 20, 25, 20, 25, 30, 25, 18]
        ],
        'Inpassing' => [
            'data' => $daftar_export_inpassing,
            'headers' => ['No.', 'Nama Guru', 'NIP / No. Induk', 'NIK', 'NPK', 'Status Inpassing', 'Pangkat / Golongan', 'TMT', 'Nomor SK', 'Keterangan'],
            'widths' => [8, 30, 20, 18, 18, 20, 25, 18, 25, 40]
        ]
    ];
    foreach ($konfigurasi_sheet_export as $judul_sheet => $konfigurasi) {
        $sheet = $judul_sheet === 'Aktif' ? $spreadsheet->getActiveSheet() : $spreadsheet->createSheet();
        $sheet->setTitle($judul_sheet);
        foreach ($konfigurasi['headers'] as $index_kolom => $judul_kolom) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($index_kolom + 1) . '1', $judul_kolom);
        }
        foreach ($konfigurasi['data'] as $index_guru => $guru_export) {
            if ($judul_sheet === 'Sertifikasi') {
                $nilai_baris_export = [
                    (string)($index_guru + 1),
                    (string)($guru_export['nama_guru'] ?? ''),
                    (string)($guru_export['nip'] ?? ''),
                    (string)($guru_export['nik'] ?? ''),
                    (string)($guru_export['npk'] ?? ''),
                    (string)(($guru_export['status_sertifikasi'] ?? '') === 'Sudah' || !empty($guru_export['no_piagam']) ? 'Sudah' : ''),
                    (string)($guru_export['no_piagam'] ?? ''),
                    (string)($guru_export['nrg'] ?? ''),
                    (string)($guru_export['mapel_sertifikasi'] ?? ''),
                    (string)($guru_export['badan_sertifikasi'] ?? ''),
                    (string)($guru_export['jalur_sertifikasi'] ?? ''),
                    !empty($guru_export['tanggal_piagam']) ? date('d-m-Y', strtotime($guru_export['tanggal_piagam'])) : ''
                ];
            } elseif ($judul_sheet === 'Inpassing') {
                $nilai_baris_export = [
                    (string)($index_guru + 1),
                    (string)($guru_export['nama_guru'] ?? ''),
                    (string)($guru_export['nip'] ?? ''),
                    (string)($guru_export['nik'] ?? ''),
                    (string)($guru_export['npk'] ?? ''),
                    (string)($guru_export['status_inpassing'] ?? ''),
                    (string)($guru_export['pangkat_golongan'] ?? ''),
                    !empty($guru_export['tmt_pangkat']) && $guru_export['tmt_pangkat'] !== '0000-00-00' ? date('d-m-Y', strtotime($guru_export['tmt_pangkat'])) : '',
                    (string)($guru_export['nomor_sk_inpassing'] ?? ''),
                    (string)($guru_export['keterangan_inpassing'] ?? '')
                ];
            } else {
                $nilai_baris_export = [
                    (string)($index_guru + 1),
                    (string)($guru_export['nama_guru'] ?? ''),
                    (string)($guru_export['tempat_lahir'] ?? ''),
                    !empty($guru_export['tanggal_lahir']) && $guru_export['tanggal_lahir'] !== '0000-00-00' ? date('d-m-Y', strtotime($guru_export['tanggal_lahir'])) : '',
                    (string)($guru_export['nip'] ?? ''),
                    (string)($guru_export['nuptk'] ?? ''),
                    (string)($guru_export['nik'] ?? ''),
                    (string)($guru_export['npk'] ?? ''),
                    (string)($guru_export['nrg'] ?? ''),
                    (string)($guru_export['jabatan'] ?? ''),
                    (string)($guru_export['jabatan_tambahan'] ?? ''),
                    (string)($guru_export['mata_pelajaran'] ?? ''),
                    (string)($guru_export['status_kepegawaian'] ?? ''),
                    (string)($guru_export['no_hp'] ?? ''),
                    (string)($guru_export['email'] ?? '')
                ];
            }
            foreach ($nilai_baris_export as $index_kolom => $nilai_export) {
                $sheet->setCellValueExplicit(
                    Coordinate::stringFromColumnIndex($index_kolom + 1) . ($index_guru + 2),
                    $nilai_export,
                    \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
                );
            }
        }
        $kolom_akhir_export = Coordinate::stringFromColumnIndex(count($konfigurasi['headers']));
        $sheet->getStyle('A1:' . $kolom_akhir_export . '1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F5132']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER, 'wrapText' => true]
        ]);
        foreach ($konfigurasi['widths'] as $index_kolom => $lebar_kolom) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($index_kolom + 1))->setWidth($lebar_kolom);
        }
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:' . $kolom_akhir_export . max(1, count($konfigurasi['data']) + 1));
        $sheet->getPageSetup()
            ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_FOLIO)
            ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
            ->setFitToWidth(1)
            ->setFitToHeight(0)
            ->setFitToPage(true);
        $sheet->getPageMargins()
            ->setLeft(10 / 25.4)
            ->setRight(10 / 25.4)
            ->setTop(10 / 25.4)
            ->setBottom(10 / 25.4);
    }

    if (ob_get_length()) {
        ob_end_clean();
    }
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="daftar_guru.xlsx"');
    header('Cache-Control: max-age=0');
    IOFactory::createWriter($spreadsheet, 'Xlsx')->save('php://output');
    exit;
}

if ($page === 'buku_induk_guru' && isset($_GET['download_template'])) {
    if (!class_exists('ZipArchive')) {
        http_response_code(500);
        exit('Ekstensi ZIP PHP belum aktif. Aktifkan extension=zip pada php.ini, lalu restart Apache.');
    }
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Data Guru');
    $headers = [
        'nip' => 'NIP / No. Induk',
        'nama_guru' => 'Nama Lengkap dan Gelar',
        'nik' => 'NIK',
        'nuptk' => 'NUPTK',
        'npk' => 'NPK',
        'peg_id' => 'PegID',
        'jenis_kelamin' => 'Jenis Kelamin',
        'tempat_lahir' => 'Tempat Lahir',
        'tanggal_lahir' => 'Tanggal Lahir',
        'agama' => 'Agama',
        'status_perkawinan' => 'Status Perkawinan',
        'nama_ibu_kandung' => 'Nama Ibu Kandung',
        'no_hp' => 'Nomor HP',
        'email' => 'Email',
        'alamat' => 'Alamat'
    ];
    foreach (array_values($headers) as $index => $header) {
        $sheet->setCellValue(Coordinate::stringFromColumnIndex($index + 1) . '1', $header);
    }
    $opsi_identitas = [
        'jenis_kelamin' => ['Laki-laki', 'Perempuan'],
        'agama' => ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu'],
        'status_perkawinan' => ['Belum Kawin', 'Kawin', 'Cerai Hidup', 'Cerai Mati']
    ];
    foreach ($opsi_identitas as $field => $opsi) {
        $kolom = Coordinate::stringFromColumnIndex(array_search($field, array_keys($headers), true) + 1);
        $validasi = new \PhpOffice\PhpSpreadsheet\Cell\DataValidation();
        $validasi->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
        $validasi->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_STOP);
        $validasi->setShowDropDown(true);
        $validasi->setAllowBlank(true);
        $validasi->setShowInputMessage(true);
        $validasi->setShowErrorMessage(true);
        $validasi->setPromptTitle('Pilih ' . $headers[$field]);
        $validasi->setPrompt('Pilih salah satu opsi dari daftar.');
        $validasi->setErrorTitle('Pilihan tidak valid');
        $validasi->setError('Pilih nilai dari daftar yang tersedia.');
        $validasi->setFormula1('"' . implode(',', $opsi) . '"');
        $sheet->setDataValidation($kolom . '2:' . $kolom . '1000', $validasi);
    }
    $kolom_akhir = Coordinate::stringFromColumnIndex(count($headers));
    $sheet->getStyle('A1:' . $kolom_akhir . '1')->applyFromArray([
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
        'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F5132']],
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER, 'wrapText' => true],
        'borders' => ['bottom' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => '0C3924']]]
    ]);
    $sheet->getRowDimension(1)->setRowHeight(32);
    $lebar_kolom = [18, 30, 20, 20, 18, 16, 18, 22, 18, 16, 22, 26, 18, 28, 40];
    foreach ($lebar_kolom as $index => $lebar) {
        $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($index + 1))->setWidth($lebar);
    }
    $sheet->freezePane('A2');
    $sheet->setAutoFilter('A1:' . $kolom_akhir . '1');
    if (ob_get_length()) {
        ob_end_clean();
    }
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="template_buku_induk_guru.xlsx"');
    header('Cache-Control: max-age=0');
    IOFactory::createWriter($spreadsheet, 'Xlsx')->save('php://output');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $page === 'pengaturan' && isset($_POST['simpan_identitas_madrasah'])) {
    $fields = ['nama_madrasah', 'nsm', 'npsn', 'alamat', 'telepon', 'email', 'website'];
    $values = [];
    foreach ($fields as $field) {
        $values[$field] = mysqli_real_escape_string($conn, trim($_POST[$field] ?? ''));
    }

    if ($values['nama_madrasah'] === '') {
        $_SESSION['pesan_madrasah_error'] = 'Nama madrasah wajib diisi.';
    } else {
        $sql = "UPDATE identitas_madrasah SET
            nama_madrasah='{$values['nama_madrasah']}', nsm='{$values['nsm']}',
            npsn='{$values['npsn']}', alamat='{$values['alamat']}',
            telepon='{$values['telepon']}', email='{$values['email']}',
            website='{$values['website']}' WHERE id=1";
        if (mysqli_query($conn, $sql)) {
            $_SESSION['pesan_madrasah_sukses'] = 'Identitas madrasah berhasil diperbarui.';
        } else {
            $_SESSION['pesan_madrasah_error'] = 'Identitas madrasah gagal disimpan: ' . mysqli_error($conn);
        }
    }
    header('Location: index.php?page=pengaturan');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $page === 'buku_induk_guru') {
    $url_kembali = 'index?page=buku_induk_guru';
    $pesan_status = 'gagal';
    $pesan = '';

    if (isset($_POST['import_guru'])) {
        $url_kembali .= '&bagian=pegawai';
        if (!isset($_FILES['file_excel']) || $_FILES['file_excel']['error'] !== UPLOAD_ERR_OK) {
            $kode_upload = $_FILES['file_excel']['error'] ?? UPLOAD_ERR_NO_FILE;
            if ($kode_upload === UPLOAD_ERR_INI_SIZE || $kode_upload === UPLOAD_ERR_FORM_SIZE) {
                $pesan = 'Ukuran berkas melampaui batas unggah server. Tingkatkan upload_max_filesize dan post_max_size pada hosting.';
            } elseif ($kode_upload === UPLOAD_ERR_PARTIAL) {
                $pesan = 'Berkas hanya terunggah sebagian. Silakan unggah ulang.';
            } elseif ($kode_upload === UPLOAD_ERR_NO_FILE) {
                $pesan = 'Pilih berkas XLSX yang akan diunggah.';
            } else {
                $pesan = 'Server gagal menerima berkas XLSX (kode unggah ' . (int)$kode_upload . '). Periksa batas unggah dan ruang penyimpanan sementara hosting.';
            }
        } elseif ($_FILES['file_excel']['size'] > 5 * 1024 * 1024 || strtolower(pathinfo($_FILES['file_excel']['name'], PATHINFO_EXTENSION)) !== 'xlsx') {
            $pesan = 'Pilih berkas Excel berformat .xlsx dengan ukuran maksimal 5 MB.';
        } else {
            $kolom_import = [
                'nip', 'nama_guru', 'nik', 'nuptk', 'npk', 'peg_id', 'jenis_kelamin',
                'tempat_lahir', 'tanggal_lahir', 'agama', 'status_perkawinan',
                'nama_ibu_kandung', 'no_hp', 'email', 'alamat'
            ];
            $header_template = [
                'nip / no. induk', 'nama lengkap dan gelar', 'nik', 'nuptk', 'npk', 'pegid',
                'jenis kelamin', 'tempat lahir', 'tanggal lahir', 'agama', 'status perkawinan',
                'nama ibu kandung', 'nomor hp', 'email', 'alamat'
            ];
            $jumlah_diimpor = 0;
            $jumlah_dilewati = 0;
            $jumlah_duplikat_dilewati = 0;
            $transaksi_import = false;
            try {
                if (!class_exists('ZipArchive')) {
                    throw new RuntimeException('Ekstensi ZIP PHP belum aktif. Aktifkan extension=zip pada php.ini, lalu restart Apache.');
                }
                $reader = IOFactory::createReader('Xlsx');
                $reader->setReadDataOnly(true);
                $worksheet = $reader->load($_FILES['file_excel']['tmp_name'])->getActiveSheet();
                $baris = $worksheet->toArray(null, true, true, false);
                $header = array_shift($baris);
                $header = array_map(static function ($nilai) {
                    return strtolower(trim((string)$nilai));
                }, $header ?? []);
                if (array_slice($header, 0, count($header_template)) !== $header_template) {
                    throw new RuntimeException('Format kolom tidak sesuai. Gunakan template identitas Buku Induk Guru terbaru.');
                }

                mysqli_begin_transaction($conn);
                $transaksi_import = true;
                foreach ($baris as $nomor_baris => $row) {
                    $data = [];
                    foreach ($kolom_import as $index => $kolom) {
                        $data[$kolom] = trim((string)($row[$index] ?? ''));
                    }
                    if (!array_filter($data, static function ($nilai) {
                        return $nilai !== '';
                    })) {
                        continue;
                    }
                    if ($data['nama_guru'] === '') {
                        $jumlah_dilewati++;
                        continue;
                    }

                    foreach (['tanggal_lahir'] as $kolom_tanggal) {
                        if ($data[$kolom_tanggal] === '') {
                            continue;
                        }
                        if (is_numeric($data[$kolom_tanggal])) {
                            $data[$kolom_tanggal] = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$data[$kolom_tanggal])->format('Y-m-d');
                        } else {
                            $tanggal = \DateTime::createFromFormat('!Y-m-d', $data[$kolom_tanggal]);
                            if (!$tanggal || $tanggal->format('Y-m-d') !== $data[$kolom_tanggal]) {
                                throw new RuntimeException('Format tanggal pada baris ' . ($nomor_baris + 2) . ' harus YYYY-MM-DD.');
                            }
                        }
                    }

                    $data['nama_guru'] = mysqli_real_escape_string($conn, $data['nama_guru']);
                    $data['nip'] = mysqli_real_escape_string($conn, $data['nip']);
                    $guru_ada = null;
                    if ($data['nip'] !== '' && $data['nip'] !== '-') {
                        $hasil_cari = mysqli_query($conn, "SELECT id FROM guru WHERE TRIM(COALESCE(nip, ''))='{$data['nip']}' ORDER BY id ASC LIMIT 1");
                        if (!$hasil_cari) {
                            throw new RuntimeException('Pencarian NIP gagal: ' . mysqli_error($conn));
                        }
                        $guru_ada = mysqli_fetch_assoc($hasil_cari) ?: null;
                    }

                    if (!$guru_ada && $data['nik'] !== '') {
                        $nik_cari = mysqli_real_escape_string($conn, $data['nik']);
                        $hasil_cari = mysqli_query($conn, "SELECT g.id FROM guru g JOIN buku_induk_guru bg ON bg.guru_id=g.id WHERE TRIM(COALESCE(bg.nik, ''))='$nik_cari' ORDER BY g.id ASC LIMIT 1");
                        if (!$hasil_cari) {
                            throw new RuntimeException('Pencarian NIK gagal: ' . mysqli_error($conn));
                        }
                        $guru_ada = mysqli_fetch_assoc($hasil_cari) ?: null;
                    }

                    if (!$guru_ada && ($data['nip'] === '' || $data['nip'] === '-') && $data['nik'] === '') {
                        $kondisi_identitas = ["TRIM(g.nama_guru)='{$data['nama_guru']}'"];
                        foreach ([
                            'nuptk', 'npk', 'peg_id', 'jenis_kelamin', 'tempat_lahir',
                            'tanggal_lahir', 'agama', 'status_perkawinan', 'nama_ibu_kandung',
                            'no_hp', 'email', 'alamat'
                        ] as $kolom_identitas) {
                            $nilai_identitas = trim($data[$kolom_identitas] ?? '');
                            if ($nilai_identitas !== '') {
                                $nilai_identitas_sql = mysqli_real_escape_string($conn, $nilai_identitas);
                                $kondisi_identitas[] = "TRIM(COALESCE(bg.$kolom_identitas, ''))='$nilai_identitas_sql'";
                            }
                        }
                        $sql_cari_identitas = "SELECT g.id FROM guru g
                            LEFT JOIN buku_induk_guru bg ON bg.guru_id=g.id
                            WHERE " . implode(' AND ', $kondisi_identitas) . "
                            ORDER BY g.id ASC LIMIT 1";
                        $hasil_cari = mysqli_query($conn, $sql_cari_identitas);
                        if (!$hasil_cari) {
                            throw new RuntimeException('Pencarian identitas guru gagal: ' . mysqli_error($conn));
                        }
                        $guru_ada = mysqli_fetch_assoc($hasil_cari) ?: null;
                    }

                    if ($guru_ada) {
                        $jumlah_duplikat_dilewati++;
                        continue;
                    }

                    $simpan_guru = mysqli_query($conn, "INSERT INTO guru (nip, nama_guru, jabatan, jabatan_tambahan, mata_pelajaran) VALUES ('{$data['nip']}', '{$data['nama_guru']}', '-', '-', '-')");
                    if (!$simpan_guru) {
                        throw new RuntimeException('Penyimpanan data guru baris ' . ($nomor_baris + 2) . ' gagal: ' . mysqli_error($conn));
                    }
                    $guru_id = (int)mysqli_insert_id($conn);
                    $jumlah_diimpor++;

                    $field_profil_import = [
                        'nik', 'nuptk', 'npk', 'peg_id', 'jenis_kelamin', 'tempat_lahir',
                        'tanggal_lahir', 'agama', 'status_perkawinan', 'nama_ibu_kandung',
                        'no_hp', 'email', 'alamat'
                    ];
                    $nilai_profil_import = [];
                    foreach ($field_profil_import as $kolom) {
                        $nilai = trim($data[$kolom] ?? '');
                        if ($nilai === '') {
                            $nilai_profil_import[$kolom] = 'NULL';
                        } elseif ($kolom === 'jumlah_anak') {
                            if (!ctype_digit($nilai)) {
                                throw new RuntimeException('Jumlah anak pada baris ' . ($nomor_baris + 2) . ' harus berupa bilangan bulat non-negatif.');
                            }
                            $nilai_profil_import[$kolom] = (string)(int)$nilai;
                        } else {
                            $nilai_profil_import[$kolom] = "'" . mysqli_real_escape_string($conn, $nilai) . "'";
                        }
                    }
                    $kolom_sql = implode(', ', $field_profil_import);
                    $nilai_sql = implode(', ', array_map(static function ($kolom) use ($nilai_profil_import) {
                        return $nilai_profil_import[$kolom];
                    }, $field_profil_import));
                    $pembaruan_sql = implode(', ', array_map(static function ($kolom) use ($nilai_profil_import) {
                        return $nilai_profil_import[$kolom] === 'NULL'
                            ? "$kolom=COALESCE(VALUES($kolom), $kolom)"
                            : "$kolom=VALUES($kolom)";
                    }, $field_profil_import));
                    if (!mysqli_query($conn, "INSERT INTO buku_induk_guru (guru_id, $kolom_sql) VALUES ($guru_id, $nilai_sql) ON DUPLICATE KEY UPDATE $pembaruan_sql")) {
                        throw new RuntimeException('Penyimpanan profil pada baris ' . ($nomor_baris + 2) . ' gagal: ' . mysqli_error($conn));
                    }
                }
                mysqli_commit($conn);
                $transaksi_import = false;
                $pesan_status = 'tersimpan';
                $pesan = "Impor selesai: $jumlah_diimpor guru baru, $jumlah_duplikat_dilewati data duplikat dilewati, $jumlah_dilewati baris tanpa nama dilewati.";
            } catch (Throwable $error) {
                if ($transaksi_import) {
                    mysqli_rollback($conn);
                }
                $pesan = 'Impor dibatalkan: ' . $error->getMessage();
            }
        }
    } elseif (isset($_POST['simpan_profil_guru'])) {
        $guru_id = (int)($_POST['guru_id'] ?? 0);
        $url_kembali .= '&bagian=pegawai' . ($guru_id > 0 ? '&edit=' . $guru_id : '&tambah=1');
        $nama_guru = trim($_POST['nama_guru'] ?? '');
        if ($nama_guru === '') {
            $pesan = 'Nama guru wajib diisi.';
        } else {
            $field_guru = ['nip', 'nama_guru', 'jabatan', 'jabatan_tambahan', 'mata_pelajaran'];
            $nilai_guru = [];
            foreach ($field_guru as $field) {
                $nilai_guru[$field] = mysqli_real_escape_string($conn, trim($_POST[$field] ?? ''));
            }

            $foto_lama = '';
            if ($guru_id > 0) {
                $cek_foto = mysqli_query($conn, "SELECT foto FROM buku_induk_guru WHERE guru_id = $guru_id");
                if ($cek_foto && $data_foto = mysqli_fetch_assoc($cek_foto)) {
                    $foto_lama = $data_foto['foto'] ?? '';
                }
            }
            $foto = $foto_lama;
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
                if ($_FILES['foto']['error'] !== UPLOAD_ERR_OK || $_FILES['foto']['size'] > 3 * 1024 * 1024) {
                    $pesan = 'Foto gagal diunggah atau melebihi batas 3 MB.';
                } else {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = $finfo ? finfo_file($finfo, $_FILES['foto']['tmp_name']) : '';
                    if ($finfo) {
                        finfo_close($finfo);
                    }
                    $ekstensi_foto = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
                    if (!isset($ekstensi_foto[$mime])) {
                        $pesan = 'Format foto harus JPG, PNG, atau WebP.';
                    } else {
                        $folder_foto = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'guru';
                        if (!is_dir($folder_foto) && !mkdir($folder_foto, 0755, true) && !is_dir($folder_foto)) {
                            $pesan = 'Folder penyimpanan foto tidak dapat dibuat.';
                        } else {
                            $nama_foto = bin2hex(random_bytes(16)) . '.' . $ekstensi_foto[$mime];
                            if (move_uploaded_file($_FILES['foto']['tmp_name'], $folder_foto . DIRECTORY_SEPARATOR . $nama_foto)) {
                                $foto = 'uploads/guru/' . $nama_foto;
                            } else {
                                $pesan = 'Foto gagal disimpan ke server.';
                            }
                        }
                    }
                }
            }

            if ($pesan === '') {
                $sql_guru = $guru_id > 0
                    ? "UPDATE guru SET nip='{$nilai_guru['nip']}', nama_guru='{$nilai_guru['nama_guru']}', jabatan='{$nilai_guru['jabatan']}', jabatan_tambahan='{$nilai_guru['jabatan_tambahan']}', mata_pelajaran='{$nilai_guru['mata_pelajaran']}' WHERE id=$guru_id"
                    : "INSERT INTO guru (nip, nama_guru, jabatan, jabatan_tambahan, mata_pelajaran) VALUES ('{$nilai_guru['nip']}', '{$nilai_guru['nama_guru']}', '{$nilai_guru['jabatan']}', '{$nilai_guru['jabatan_tambahan']}', '{$nilai_guru['mata_pelajaran']}')";

                mysqli_begin_transaction($conn);
                if (mysqli_query($conn, $sql_guru)) {
                    if ($guru_id === 0) {
                        $guru_id = (int)mysqli_insert_id($conn);
                    }
                    $field_profil = [
                        'nik', 'nuptk', 'npk', 'peg_id', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir', 'agama', 'mata_pelajaran_lainnya',
                        'status_perkawinan', 'nama_ibu_kandung', 'alamat', 'no_hp', 'email', 'tanggal_masuk', 'tanggal_mulai_jabatan', 'tanggal_mulai_jabatan_tambahan',
                        'status_kepegawaian', 'status_keaktifan', 'pendidikan_terakhir', 'program_studi',
                        'perguruan_tinggi', 'tahun_lulus', 'nama_pasangan', 'jumlah_anak',
                        'pangkat_golongan', 'tmt_pangkat'
                    ];
                    $nilai_profil = [];
                    foreach ($field_profil as $field) {
                        $nilai = trim($_POST[$field] ?? '');
                        $nilai_profil[$field] = $nilai === '' ? 'NULL' : "'" . mysqli_real_escape_string($conn, $nilai) . "'";
                    }
                    $foto_sql = "'" . mysqli_real_escape_string($conn, $foto) . "'";
                    $sql_profil = "INSERT INTO buku_induk_guru
                        (guru_id, nik, nuptk, npk, peg_id, jenis_kelamin, tempat_lahir, tanggal_lahir, agama, status_perkawinan, nama_ibu_kandung,
                         alamat, no_hp, email, mata_pelajaran_lainnya, tanggal_masuk, tanggal_mulai_jabatan, tanggal_mulai_jabatan_tambahan, status_kepegawaian, status_keaktifan, pendidikan_terakhir, program_studi,
                         perguruan_tinggi, tahun_lulus, nama_pasangan, jumlah_anak, pangkat_golongan, tmt_pangkat, foto)
                        VALUES ($guru_id, {$nilai_profil['nik']}, {$nilai_profil['nuptk']}, {$nilai_profil['npk']}, {$nilai_profil['peg_id']}, {$nilai_profil['jenis_kelamin']},
                         {$nilai_profil['tempat_lahir']}, {$nilai_profil['tanggal_lahir']}, {$nilai_profil['agama']},
                         {$nilai_profil['status_perkawinan']}, {$nilai_profil['nama_ibu_kandung']}, {$nilai_profil['alamat']}, {$nilai_profil['no_hp']},
                         {$nilai_profil['email']}, {$nilai_profil['mata_pelajaran_lainnya']}, {$nilai_profil['tanggal_masuk']}, {$nilai_profil['tanggal_mulai_jabatan']}, {$nilai_profil['tanggal_mulai_jabatan_tambahan']}, {$nilai_profil['status_kepegawaian']}, {$nilai_profil['status_keaktifan']},
                         {$nilai_profil['pendidikan_terakhir']}, {$nilai_profil['program_studi']}, {$nilai_profil['perguruan_tinggi']},
                         {$nilai_profil['tahun_lulus']}, {$nilai_profil['nama_pasangan']}, {$nilai_profil['jumlah_anak']},
                         {$nilai_profil['pangkat_golongan']}, {$nilai_profil['tmt_pangkat']}, $foto_sql)
                        ON DUPLICATE KEY UPDATE
                         nik=VALUES(nik), nuptk=VALUES(nuptk), npk=VALUES(npk), peg_id=VALUES(peg_id), jenis_kelamin=VALUES(jenis_kelamin),
                         tempat_lahir=VALUES(tempat_lahir), tanggal_lahir=VALUES(tanggal_lahir), agama=VALUES(agama),
                         status_perkawinan=VALUES(status_perkawinan), nama_ibu_kandung=VALUES(nama_ibu_kandung),
                         alamat=VALUES(alamat), no_hp=VALUES(no_hp),
                         email=VALUES(email), mata_pelajaran_lainnya=VALUES(mata_pelajaran_lainnya), tanggal_masuk=VALUES(tanggal_masuk), tanggal_mulai_jabatan=VALUES(tanggal_mulai_jabatan), tanggal_mulai_jabatan_tambahan=VALUES(tanggal_mulai_jabatan_tambahan), status_kepegawaian=VALUES(status_kepegawaian), status_keaktifan=VALUES(status_keaktifan),
                         pendidikan_terakhir=VALUES(pendidikan_terakhir), program_studi=VALUES(program_studi),
                         perguruan_tinggi=VALUES(perguruan_tinggi), tahun_lulus=VALUES(tahun_lulus),
                         nama_pasangan=VALUES(nama_pasangan), jumlah_anak=VALUES(jumlah_anak),
                         pangkat_golongan=VALUES(pangkat_golongan), tmt_pangkat=VALUES(tmt_pangkat), foto=VALUES(foto)";
                    $transaksi_profil_aktif = false;
                    try {
                        mysqli_begin_transaction($conn);
                        $transaksi_profil_aktif = true;

                        if (!mysqli_query($conn, $sql_guru)) {
                            throw new RuntimeException('Data guru gagal disimpan: ' . mysqli_error($conn));
                        }
                        if ($guru_id === 0) {
                            $guru_id = (int)mysqli_insert_id($conn);
                        }
                        if (!mysqli_query($conn, $sql_profil)) {
                            throw new RuntimeException('Profil kepegawaian gagal disimpan: ' . mysqli_error($conn));
                        }

                        mysqli_commit($conn);
                        $transaksi_profil_aktif = false;
                        $pesan_status = 'tersimpan';
                        $pesan = 'Data buku induk guru berhasil disimpan.';
                        $url_kembali = 'index?page=buku_induk_guru&bagian=pegawai';
                    } catch (mysqli_sql_exception | RuntimeException $error) {
                        if ($transaksi_profil_aktif) {
                            mysqli_rollback($conn);
                        }
                        error_log('Gagal menyimpan profil Buku Induk Guru: ' . $error->getMessage());
                        $pesan = 'Profil guru gagal disimpan karena terjadi masalah pada database. Silakan coba lagi atau hubungi administrator.';
                    }
                }
            }
        }
    } elseif ($permintaan_status_sertifikasi) {
        $guru_id = (int)($_POST['guru_id_status_sertifikasi'] ?? 0);
        $status_sertifikasi = trim($_POST['status_sertifikasi'] ?? '');
        $status_inpassing = trim($_POST['status_inpassing'] ?? '');
        $pangkat_golongan = trim($_POST['pangkat_golongan'] ?? '');
        $tmt_pangkat = trim($_POST['tmt_pangkat'] ?? '');
        $nomor_sk_inpassing = trim($_POST['nomor_sk_inpassing'] ?? '');
        $keterangan_inpassing = trim($_POST['keterangan_inpassing'] ?? '');
        $url_kembali .= '&bagian=sertifikasi&guru=' . $guru_id;
        try {
            $guru_terdaftar = $guru_id > 0 ? mysqli_query($conn, "SELECT id FROM guru WHERE id=$guru_id LIMIT 1") : false;
            if (!$guru_terdaftar || mysqli_num_rows($guru_terdaftar) !== 1) {
                $pesan = 'Pegawai yang dipilih tidak ditemukan.';
            } elseif (!in_array($status_sertifikasi, ['Sudah', 'Belum'], true)) {
                $pesan = 'Pilih status sertifikasi yang valid.';
            } else {
                $hasil_jumlah_sertifikasi = mysqli_query($conn, "SELECT COUNT(*) AS jumlah FROM riwayat_buku_guru WHERE guru_id=$guru_id AND kategori='sertifikasi'");
                if (!$hasil_jumlah_sertifikasi) {
                    throw new RuntimeException('Data sertifikasi gagal diperiksa: ' . mysqli_error($conn));
                }

                $jumlah_sertifikasi = (int)(mysqli_fetch_assoc($hasil_jumlah_sertifikasi)['jumlah'] ?? 0);
                if ($status_sertifikasi === 'Belum' && $jumlah_sertifikasi > 0) {
                    $pesan = 'Status tidak dapat diubah menjadi Belum karena data sertifikasi guru masih tersimpan.';
                } elseif ($status_sertifikasi === 'Sudah' && $jumlah_sertifikasi > 0 && !in_array($status_inpassing, ['Sudah', 'Belum'], true)) {
                    $pesan = 'Pilih status inpassing yang valid.';
                } elseif ($status_sertifikasi === 'Sudah' && $jumlah_sertifikasi > 0 && $status_inpassing === 'Sudah' && ($pangkat_golongan === '' || $nomor_sk_inpassing === '' || $tmt_pangkat === '')) {
                    $pesan = 'Pangkat/golongan, TMT, dan nomor SK wajib diisi untuk status inpassing Sudah.';
                } else {
                    $tmt_valid = $tmt_pangkat === '' ? null : DateTime::createFromFormat('!Y-m-d', $tmt_pangkat);
                    if ($status_sertifikasi === 'Sudah' && $jumlah_sertifikasi > 0 && $status_inpassing === 'Sudah' && (!$tmt_valid || $tmt_valid->format('Y-m-d') !== $tmt_pangkat)) {
                        $pesan = 'Tanggal TMT inpassing tidak valid.';
                    } else {
                        $status_sertifikasi_sql = mysqli_real_escape_string($conn, $status_sertifikasi);
                        $status_inpassing_sql = $status_sertifikasi === 'Sudah' && $jumlah_sertifikasi > 0
                            ? "'" . mysqli_real_escape_string($conn, $status_inpassing) . "'"
                            : 'NULL';
                        $set_detail_inpassing = $status_inpassing === 'Sudah' && $jumlah_sertifikasi > 0;
                        $golongan_sql = $set_detail_inpassing ? "'" . mysqli_real_escape_string($conn, $pangkat_golongan) . "'" : 'NULL';
                        $tmt_sql = $set_detail_inpassing ? "'" . mysqli_real_escape_string($conn, $tmt_pangkat) . "'" : 'NULL';
                        $nomor_sk_sql = $set_detail_inpassing ? "'" . mysqli_real_escape_string($conn, $nomor_sk_inpassing) . "'" : 'NULL';
                        $keterangan_sql = $set_detail_inpassing ? "'" . mysqli_real_escape_string($conn, $keterangan_inpassing) . "'" : 'NULL';
                        $pembaruan_detail_inpassing = $set_detail_inpassing
                            ? ", pangkat_golongan=$golongan_sql, tmt_pangkat=$tmt_sql, nomor_sk_inpassing=$nomor_sk_sql, keterangan_inpassing=$keterangan_sql"
                            : ", pangkat_golongan=NULL, tmt_pangkat=NULL, nomor_sk_inpassing=NULL, keterangan_inpassing=NULL";
                        $hasil_profil = mysqli_query($conn, "SELECT id FROM buku_induk_guru WHERE guru_id=$guru_id LIMIT 1");
                        if (!$hasil_profil) {
                            throw new RuntimeException('Profil guru gagal diperiksa: ' . mysqli_error($conn));
                        }
                        if (mysqli_num_rows($hasil_profil) > 0) {
                            $sql_status = "UPDATE buku_induk_guru SET
                                status_sertifikasi='$status_sertifikasi_sql',
                                status_inpassing=$status_inpassing_sql$pembaruan_detail_inpassing
                                WHERE guru_id=$guru_id";
                        } else {
                            $sql_status = "INSERT INTO buku_induk_guru
                                (guru_id, status_sertifikasi, status_inpassing, pangkat_golongan, tmt_pangkat, nomor_sk_inpassing, keterangan_inpassing)
                                VALUES ($guru_id, '$status_sertifikasi_sql', $status_inpassing_sql, $golongan_sql, $tmt_sql, $nomor_sk_sql, $keterangan_sql)";
                        }
                        mysqli_begin_transaction($conn);
                        try {
                            if (!mysqli_query($conn, $sql_status)) {
                                throw new RuntimeException('Status sertifikasi gagal disimpan: ' . mysqli_error($conn));
                            }
                            if ($set_detail_inpassing) {
                                $hasil_riwayat_inpassing = mysqli_query($conn, "SELECT id FROM riwayat_buku_guru WHERE guru_id=$guru_id AND kategori='inpassing' ORDER BY id DESC LIMIT 1 FOR UPDATE");
                                if (!$hasil_riwayat_inpassing) {
                                    throw new RuntimeException('Daftar inpassing gagal diperiksa: ' . mysqli_error($conn));
                                }
                                $riwayat_inpassing_tersimpan = mysqli_fetch_assoc($hasil_riwayat_inpassing);
                                $golongan_riwayat_sql = mysqli_real_escape_string($conn, $pangkat_golongan);
                                $nomor_sk_riwayat_sql = mysqli_real_escape_string($conn, $nomor_sk_inpassing);
                                $keterangan_riwayat_sql = mysqli_real_escape_string($conn, $keterangan_inpassing);
                                if ($riwayat_inpassing_tersimpan) {
                                    $id_riwayat_inpassing = (int)$riwayat_inpassing_tersimpan['id'];
                                    $sql_riwayat_inpassing = "UPDATE riwayat_buku_guru SET
                                        tanggal=$tmt_sql,
                                        judul='$golongan_riwayat_sql',
                                        instansi='$nomor_sk_riwayat_sql',
                                        detail=NULLIF('$keterangan_riwayat_sql', '')
                                        WHERE id=$id_riwayat_inpassing";
                                } else {
                                    $sql_riwayat_inpassing = "INSERT INTO riwayat_buku_guru
                                        (guru_id, kategori, tanggal, judul, instansi, detail)
                                        VALUES ($guru_id, 'inpassing', $tmt_sql, '$golongan_riwayat_sql',
                                        '$nomor_sk_riwayat_sql', NULLIF('$keterangan_riwayat_sql', ''))";
                                }
                                if (!mysqli_query($conn, $sql_riwayat_inpassing)) {
                                    throw new RuntimeException('Daftar inpassing gagal disimpan: ' . mysqli_error($conn));
                                }
                            } elseif (!mysqli_query($conn, "DELETE FROM riwayat_buku_guru WHERE guru_id=$guru_id AND kategori='inpassing'")) {
                                throw new RuntimeException('Daftar inpassing gagal diperbarui: ' . mysqli_error($conn));
                            }
                            if (!mysqli_commit($conn)) {
                                throw new RuntimeException('Transaksi status sertifikasi gagal disimpan: ' . mysqli_error($conn));
                            }
                        } catch (Throwable $transaction_error) {
                            mysqli_rollback($conn);
                            throw $transaction_error;
                        }
                        $pesan_status = 'tersimpan';
                        $pesan = $set_detail_inpassing
                            ? 'Status sertifikasi dan daftar inpassing berhasil disimpan.'
                            : 'Status sertifikasi dan inpassing berhasil disimpan.';
                    }
                }
            }
        } catch (Throwable $error) {
            error_log('Gagal menyimpan status sertifikasi guru: ' . $error->getMessage());
            $pesan = 'Status sertifikasi gagal disimpan (kode database ' . (int)$error->getCode() . '). Periksa error log hosting atau sampaikan kode ini kepada administrator.';
        }
    } elseif (isset($_POST['edit_riwayat_guru'])) {
        $riwayat_id = (int)($_POST['riwayat_id'] ?? 0);
        $guru_id = (int)($_POST['guru_id_riwayat'] ?? 0);
        $kategori = trim($_POST['kategori'] ?? '');
        $kategori_edit_valid = ['keaktifan', 'pendidikan', 'keluarga', 'penghargaan', 'sertifikasi'];
        $kategori_sql = mysqli_real_escape_string($conn, $kategori);
        $hasil_edit_riwayat = $riwayat_id > 0 && $guru_id > 0 && in_array($kategori, $kategori_edit_valid, true)
            ? mysqli_query($conn, "SELECT id FROM riwayat_buku_guru WHERE id=$riwayat_id AND guru_id=$guru_id AND kategori='$kategori_sql' LIMIT 1")
            : false;
        if (!$hasil_edit_riwayat || mysqli_num_rows($hasil_edit_riwayat) !== 1) {
            $pesan = 'Riwayat yang akan diedit tidak ditemukan atau tidak valid.';
        } else {
            $tanggal = trim($_POST['tanggal'] ?? '');
            $tanggal_valid = $tanggal === '' ? null : DateTime::createFromFormat('!Y-m-d', $tanggal);
            $judul = trim($_POST['judul'] ?? '');
            $instansi = trim($_POST['instansi'] ?? '');
            $detail = trim($_POST['detail'] ?? '');
            if ($kategori === 'keaktifan') {
                $instansi_sk = trim($_POST['instansi_sk'] ?? '');
                $nomor_sk = trim($_POST['nomor_sk'] ?? '');
                $tanggal_sk = trim($_POST['tanggal_sk'] ?? '');
                $tanggal_sk_valid = $tanggal_sk === '' ? null : DateTime::createFromFormat('!Y-m-d', $tanggal_sk);
                $opsi_instansi_sk = ['Kementerian Agama', 'Pemerintah Daerah', 'Kementerian Lainnya', 'Yayasan Penyelenggara Pendidikan', 'Satuan Pendidikan Sekolah', 'Lainnya'];
                if (!$tanggal_valid || $tanggal_valid->format('Y-m-d') !== $tanggal || !in_array($judul, ['Aktif', 'Tidak Aktif'], true)) {
                    $pesan = 'Tanggal efektif dan status keaktifan wajib diisi dengan benar.';
                } elseif ($instansi_sk !== '' && !in_array($instansi_sk, $opsi_instansi_sk, true)) {
                    $pesan = 'Pilih instansi penerbit SK yang valid.';
                } elseif ($tanggal_sk !== '' && (!$tanggal_sk_valid || $tanggal_sk_valid->format('Y-m-d') !== $tanggal_sk)) {
                    $pesan = 'Tanggal SK tidak valid.';
                } else {
                    $tanggal_sql = "'" . mysqli_real_escape_string($conn, $tanggal) . "'";
                    $tanggal_sk_sql = $tanggal_sk === '' ? 'NULL' : "'" . mysqli_real_escape_string($conn, $tanggal_sk) . "'";
                    $judul_sql = mysqli_real_escape_string($conn, $judul);
                    $instansi_sql = mysqli_real_escape_string($conn, $instansi);
                    $instansi_sk_sql = mysqli_real_escape_string($conn, $instansi_sk);
                    $nomor_sk_sql = mysqli_real_escape_string($conn, $nomor_sk);
                    mysqli_begin_transaction($conn);
                    $sql_edit = "UPDATE riwayat_buku_guru SET tanggal=$tanggal_sql, tahun_riwayat='" . substr($tanggal, 0, 4) . "',
                        judul='$judul_sql', instansi=NULLIF('$instansi_sql', ''), instansi_sk=NULLIF('$instansi_sk_sql', ''),
                        nomor_sk=NULLIF('$nomor_sk_sql', ''), tanggal_sk=$tanggal_sk_sql
                        WHERE id=$riwayat_id AND guru_id=$guru_id AND kategori='keaktifan' AND COALESCE(sumber_mutasi_id, 0)=0";
                    $edit_keaktifan_berhasil = mysqli_query($conn, $sql_edit);
                    $hasil_keaktifan_terakhir = $edit_keaktifan_berhasil
                        ? mysqli_query($conn, "SELECT id FROM riwayat_buku_guru WHERE guru_id=$guru_id AND kategori='keaktifan' ORDER BY id DESC LIMIT 1")
                        : false;
                    $status_keaktifan_berhasil = $hasil_keaktifan_terakhir !== false;
                    if ($hasil_keaktifan_terakhir) {
                        $keaktifan_terakhir = mysqli_fetch_assoc($hasil_keaktifan_terakhir);
                        if ((int)($keaktifan_terakhir['id'] ?? 0) === $riwayat_id) {
                            $status_keaktifan_berhasil = mysqli_query($conn, "INSERT INTO buku_induk_guru (guru_id, status_keaktifan)
                                VALUES ($guru_id, '$judul_sql')
                                ON DUPLICATE KEY UPDATE status_keaktifan=VALUES(status_keaktifan)");
                        }
                    }
                    if ($edit_keaktifan_berhasil && $status_keaktifan_berhasil && mysqli_commit($conn)) {
                        $pesan_status = 'tersimpan';
                        $pesan = 'Riwayat keaktifan berhasil diperbarui.';
                    } else {
                        $kesalahan_edit = mysqli_error($conn);
                        mysqli_rollback($conn);
                        $pesan = 'Riwayat keaktifan gagal diperbarui: ' . $kesalahan_edit;
                    }
                }
            } elseif ($kategori === 'pendidikan') {
                $jenjang = trim($_POST['jenjang'] ?? '');
                $fakultas = trim($_POST['fakultas'] ?? '');
                $jurusan = trim($_POST['jurusan'] ?? '');
                $tahun_lulus = trim($_POST['tahun_lulus'] ?? '');
                $lptk = trim($_POST['lptk'] ?? '');
                $jenjang_sekolah = ['SD', 'MI', 'SMP', 'MTs', 'SMA', 'MA', 'SMK'];
                if ($jenjang === '' || $instansi === '' || (!in_array($jenjang, $jenjang_sekolah, true) && !in_array($lptk, ['Ya', 'Tidak'], true))) {
                    $pesan = 'Jenjang, perguruan tinggi/sekolah, dan status LPTK wajib diisi.';
                } elseif ($tahun_lulus !== '' && (!ctype_digit($tahun_lulus) || strlen($tahun_lulus) !== 4 || (int)$tahun_lulus < 1950 || (int)$tahun_lulus > 2100)) {
                    $pesan = 'Tahun lulus tidak valid.';
                } else {
                    if (in_array($jenjang, $jenjang_sekolah, true)) {
                        $fakultas = '';
                        $lptk = 'Tidak';
                    }
                    $jenjang_sql = mysqli_real_escape_string($conn, $jenjang);
                    $instansi_sql = mysqli_real_escape_string($conn, $instansi);
                    $fakultas_sql = mysqli_real_escape_string($conn, $fakultas);
                    $jurusan_sql = mysqli_real_escape_string($conn, $jurusan);
                    $tahun_sql = mysqli_real_escape_string($conn, $tahun_lulus);
                    $lptk_sql = mysqli_real_escape_string($conn, $lptk);
                    $tanggal_sql = $tahun_lulus === '' ? 'NULL' : "'" . $tahun_lulus . "-01-01'";
                    $detail_sql = mysqli_real_escape_string($conn, 'Fakultas: ' . ($fakultas ?: '-') . '; Jurusan: ' . ($jurusan ?: '-') . '; LPTK: ' . $lptk);
                    $sql_edit = "UPDATE riwayat_buku_guru SET jenjang='$jenjang_sql', judul='$jenjang_sql', instansi='$instansi_sql',
                        fakultas='$fakultas_sql', jurusan='$jurusan_sql', tahun_lulus=NULLIF('$tahun_sql', ''),
                        lptk='$lptk_sql', tanggal=$tanggal_sql, detail='$detail_sql'
                        WHERE id=$riwayat_id AND guru_id=$guru_id AND kategori='pendidikan'";
                    if (mysqli_query($conn, $sql_edit)) {
                        $pesan_status = 'tersimpan';
                        $pesan = 'Riwayat pendidikan berhasil diperbarui.';
                    } else {
                        $pesan = 'Riwayat pendidikan gagal diperbarui: ' . mysqli_error($conn);
                    }
                }
            } elseif ($kategori === 'keluarga') {
                $hubungan = trim($_POST['hubungan'] ?? '');
                $jenis_kelamin = trim($_POST['jenis_kelamin_keluarga'] ?? '');
                $tempat_lahir = trim($_POST['tempat_lahir_keluarga'] ?? '');
                $pendidikan = trim($_POST['pendidikan_anggota'] ?? '');
                $opsi_pendidikan = ['Tidak Sekolah', 'Belum Sekolah', 'SD', 'MI', 'SMP', 'MTs', 'SMA', 'MA', 'SMK', 'D1', 'D2', 'D3', 'D4', 'S1', 'S2', 'S3'];
                if ($judul === '' || $hubungan === '') {
                    $pesan = 'Nama anggota dan hubungan keluarga wajib diisi.';
                } elseif ($tanggal !== '' && (!$tanggal_valid || $tanggal_valid->format('Y-m-d') !== $tanggal)) {
                    $pesan = 'Tanggal lahir tidak valid.';
                } elseif (strtolower($hubungan) === 'anak' && !in_array($jenis_kelamin, ['Laki-laki', 'Perempuan'], true)) {
                    $pesan = 'Pilih jenis kelamin anak.';
                } elseif ($pendidikan !== '' && !in_array($pendidikan, $opsi_pendidikan, true)) {
                    $pesan = 'Pendidikan keluarga tidak valid.';
                } else {
                    $tanggal_sql = $tanggal === '' ? 'NULL' : "'" . mysqli_real_escape_string($conn, $tanggal) . "'";
                    $judul_sql = mysqli_real_escape_string($conn, $judul);
                    $hubungan_sql = mysqli_real_escape_string($conn, $hubungan);
                    $detail_sql = mysqli_real_escape_string($conn, $detail);
                    $jenis_kelamin_sql = $jenis_kelamin === '' ? 'NULL' : "'" . mysqli_real_escape_string($conn, $jenis_kelamin) . "'";
                    $tempat_lahir_sql = $tempat_lahir === '' ? 'NULL' : "'" . mysqli_real_escape_string($conn, $tempat_lahir) . "'";
                    $pendidikan_sql = $pendidikan === '' ? 'NULL' : "'" . mysqli_real_escape_string($conn, $pendidikan) . "'";
                    $is_anak = strtolower($hubungan) === 'anak';
                    $is_pasangan = in_array(strtolower($hubungan), ['pasangan', 'suami', 'istri', 'suami/istri'], true);
                    $hubungan_status = $is_anak ? 'Anak Kandung' : ($is_pasangan ? 'Suami/Istri' : 'Lainnya');
                    $hubungan_status_sql = mysqli_real_escape_string($conn, $hubungan_status);
                    $sql_edit = "UPDATE riwayat_buku_guru SET tanggal=$tanggal_sql, judul='$judul_sql', instansi='$hubungan_sql',
                        detail='$detail_sql', jenis_kelamin_keluarga=$jenis_kelamin_sql, tempat_lahir_keluarga=$tempat_lahir_sql,
                        pendidikan_anak=" . ($is_anak ? $pendidikan_sql : 'NULL') . ",
                        pendidikan_keluarga=" . ($is_pasangan ? $pendidikan_sql : 'NULL') . ",
                        status_hubungan_keluarga='$hubungan_status_sql'
                        WHERE id=$riwayat_id AND guru_id=$guru_id AND kategori='keluarga'";
                    if (mysqli_query($conn, $sql_edit)) {
                        $pesan_status = 'tersimpan';
                        $pesan = 'Data keluarga berhasil diperbarui.';
                    } else {
                        $pesan = 'Data keluarga gagal diperbarui: ' . mysqli_error($conn);
                    }
                }
            } elseif ($kategori === 'penghargaan') {
                if ($judul === '') {
                    $pesan = 'Nama penghargaan wajib diisi.';
                } elseif ($tanggal !== '' && (!$tanggal_valid || $tanggal_valid->format('Y-m-d') !== $tanggal)) {
                    $pesan = 'Tanggal penghargaan tidak valid.';
                } else {
                    $tanggal_sql = $tanggal === '' ? 'NULL' : "'" . mysqli_real_escape_string($conn, $tanggal) . "'";
                    $judul_sql = mysqli_real_escape_string($conn, $judul);
                    $instansi_sql = mysqli_real_escape_string($conn, $instansi);
                    $detail_sql = mysqli_real_escape_string($conn, $detail);
                    $sql_edit = "UPDATE riwayat_buku_guru SET tanggal=$tanggal_sql, judul='$judul_sql',
                        instansi='$instansi_sql', detail='$detail_sql'
                        WHERE id=$riwayat_id AND guru_id=$guru_id AND kategori='penghargaan'";
                    if (mysqli_query($conn, $sql_edit)) {
                        $pesan_status = 'tersimpan';
                        $pesan = 'Data penghargaan berhasil diperbarui.';
                    } else {
                        $pesan = 'Data penghargaan gagal diperbarui: ' . mysqli_error($conn);
                    }
                }
            } else {
                $nrg = trim($_POST['nrg'] ?? '');
                $mapel = trim($_POST['mapel_sertifikasi'] ?? '');
                $jalur = trim($_POST['jalur_sertifikasi'] ?? '');
                if ($judul === '' || $instansi === '' || $mapel === '' || $jalur === '' || !$tanggal_valid || $tanggal_valid->format('Y-m-d') !== $tanggal) {
                    $pesan = 'Nomor piagam, tanggal, mapel, badan, dan jalur sertifikasi wajib diisi.';
                } else {
                    $tanggal_sql = "'" . mysqli_real_escape_string($conn, $tanggal) . "'";
                    $judul_sql = mysqli_real_escape_string($conn, $judul);
                    $instansi_sql = mysqli_real_escape_string($conn, $instansi);
                    $nrg_sql = mysqli_real_escape_string($conn, $nrg);
                    $mapel_sql = mysqli_real_escape_string($conn, $mapel);
                    $jalur_sql = mysqli_real_escape_string($conn, $jalur);
                    $detail_sql = mysqli_real_escape_string($conn, 'Mapel sertifikasi: ' . $mapel . '; Jalur: ' . $jalur);
                    $sql_edit = "UPDATE riwayat_buku_guru SET tanggal=$tanggal_sql, judul='$judul_sql',
                        instansi='$instansi_sql', nrg='$nrg_sql', mapel_sertifikasi='$mapel_sql',
                        jalur_sertifikasi='$jalur_sql', detail='$detail_sql'
                        WHERE id=$riwayat_id AND guru_id=$guru_id AND kategori='sertifikasi'";
                    if (mysqli_query($conn, $sql_edit)) {
                        $pesan_status = 'tersimpan';
                        $pesan = 'Data sertifikasi berhasil diperbarui.';
                    } else {
                        $pesan = 'Data sertifikasi gagal diperbarui: ' . mysqli_error($conn);
                    }
                }
            }
        }
        if (in_array(trim($_POST['kategori'] ?? ''), ['keaktifan', 'pendidikan', 'keluarga', 'penghargaan', 'sertifikasi'], true) && (int)($_POST['guru_id_riwayat'] ?? 0) > 0) {
            $url_kembali .= '&bagian=' . urlencode(trim($_POST['kategori'])) . '&guru=' . (int)$_POST['guru_id_riwayat'];
        }
    } elseif (isset($_POST['simpan_riwayat_guru'])) {
        $kategori_valid = ['keaktifan', 'pendidikan', 'keluarga', 'pangkat', 'jabatan', 'penghargaan', 'mutasi', 'sertifikasi'];
        $guru_id = (int)($_POST['guru_id_riwayat'] ?? 0);
        $kategori = $_POST['kategori'] ?? '';
        $judul = trim($_POST['judul'] ?? '');
        if (in_array($kategori, $kategori_valid, true) && $guru_id > 0) {
            $url_kembali .= '&bagian=' . urlencode($kategori) . '&guru=' . $guru_id;
        }
        if ($guru_id < 1 || !in_array($kategori, $kategori_valid, true) || (!in_array($kategori, ['keaktifan', 'pendidikan', 'keluarga', 'jabatan', 'mutasi', 'sertifikasi'], true) && $judul === '')) {
            $pesan = 'Pilih pegawai dan isi judul atau nama riwayat dengan benar.';
        } else {
            $guru_terdaftar = mysqli_query($conn, "SELECT id FROM guru WHERE id=$guru_id LIMIT 1");
            if (!$guru_terdaftar || mysqli_num_rows($guru_terdaftar) !== 1) {
                $pesan = 'Pegawai yang dipilih tidak ditemukan.';
            } elseif ($kategori === 'keaktifan') {
                $tanggal_efektif = trim($_POST['tanggal_efektif'] ?? '');
                $lembaga_induk = trim($_POST['lembaga_induk'] ?? '');
                $status_keaktifan_tahunan = trim($_POST['status_keaktifan_tahunan'] ?? '');
                $instansi_sk = trim($_POST['instansi_sk'] ?? '');
                $nomor_sk = trim($_POST['nomor_sk'] ?? '');
                $tanggal_sk = trim($_POST['tanggal_sk'] ?? '');
                $tanggal_efektif_valid = DateTime::createFromFormat('!Y-m-d', $tanggal_efektif);
                $tanggal_sk_valid = $tanggal_sk === '' ? null : DateTime::createFromFormat('!Y-m-d', $tanggal_sk);
                $opsi_instansi_sk = ['Kementerian Agama', 'Pemerintah Daerah', 'Kementerian Lainnya', 'Yayasan Penyelenggara Pendidikan', 'Satuan Pendidikan Sekolah', 'Lainnya'];
                if (!$tanggal_efektif_valid || $tanggal_efektif_valid->format('Y-m-d') !== $tanggal_efektif) {
                    $pesan = 'Tanggal efektif wajib diisi dengan tanggal yang valid.';
                } elseif (!in_array($status_keaktifan_tahunan, ['Aktif', 'Tidak Aktif'], true)) {
                    $pesan = 'Pilih status keaktifan yang valid.';
                } elseif ($instansi_sk !== '' && !in_array($instansi_sk, $opsi_instansi_sk, true)) {
                    $pesan = 'Pilih instansi penerbit SK dari daftar yang tersedia.';
                } elseif ($tanggal_sk !== '' && (!$tanggal_sk_valid || $tanggal_sk_valid->format('Y-m-d') !== $tanggal_sk)) {
                    $pesan = 'Tanggal SK tidak valid.';
                } else {
                    $berkas_sk = '';
                    $nama_berkas_sk = '';
                    if (isset($_FILES['berkas_sk']) && $_FILES['berkas_sk']['error'] !== UPLOAD_ERR_NO_FILE) {
                        if ($_FILES['berkas_sk']['error'] !== UPLOAD_ERR_OK) {
                            $pesan = 'Berkas SK gagal diunggah.';
                        } elseif ($_FILES['berkas_sk']['size'] > 5 * 1024 * 1024) {
                            $pesan = 'Berkas SK maksimal 5 MB.';
                        } else {
                            $hasil_drive = mysqli_query($conn, "SELECT google_drive_connected, google_refresh_token FROM guru WHERE id=$guru_id LIMIT 1");
                            $data_drive = $hasil_drive ? mysqli_fetch_assoc($hasil_drive) : null;
                            if ((int)($data_drive['google_drive_connected'] ?? 0) !== 1 || trim((string)($data_drive['google_refresh_token'] ?? '')) === '') {
                                $pesan = 'Hubungkan Google Drive pada menu akun terlebih dahulu sebelum mengunggah SK.';
                            } else {
                                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                                $mime = $finfo ? finfo_file($finfo, $_FILES['berkas_sk']['tmp_name']) : '';
                                if ($finfo) finfo_close($finfo);
                                $ekstensi = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
                                if (!isset($ekstensi[$mime])) {
                                    $pesan = 'Berkas SK harus PDF, JPG, PNG, atau WebP.';
                                } else {
                                    $nama_asli = basename((string)$_FILES['berkas_sk']['name']);
                                    $nama_sementara = 'sk_' . bin2hex(random_bytes(16)) . '.' . $ekstensi[$mime];
                                    $file_sementara = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $nama_sementara;
                                    if (!move_uploaded_file($_FILES['berkas_sk']['tmp_name'], $file_sementara)) {
                                        $pesan = 'Berkas SK gagal diproses untuk dikirim ke Google Drive.';
                                    } else {
                                        $berkas_sk = $file_sementara;
                                        $nama_berkas_sk = $nama_asli;
                                    }
                                }
                            }
                        }
                    }
                    if ($pesan === '') {
                    $tanggal_efektif_sql = mysqli_real_escape_string($conn, $tanggal_efektif);
                    $tahun_keaktifan_sql = substr($tanggal_efektif, 0, 4);
                    $status_keaktifan_sql = mysqli_real_escape_string($conn, $status_keaktifan_tahunan);
                    $lembaga_induk_sql = mysqli_real_escape_string($conn, $lembaga_induk);
                    $instansi_sk_sql = mysqli_real_escape_string($conn, $instansi_sk);
                    $nomor_sk_sql = mysqli_real_escape_string($conn, $nomor_sk);
                    $tanggal_sk_sql = $tanggal_sk === '' ? 'NULL' : "'" . mysqli_real_escape_string($conn, $tanggal_sk) . "'";
                    $berkas_sk_sql = $berkas_sk === '' ? 'NULL' : "'" . mysqli_real_escape_string($conn, $berkas_sk) . "'";
                    $nama_berkas_sk_sql = $nama_berkas_sk === '' ? 'NULL' : "'" . mysqli_real_escape_string($conn, $nama_berkas_sk) . "'";
                    $sql_keaktifan = "INSERT INTO riwayat_buku_guru
                            (guru_id, kategori, tanggal, judul, instansi, tahun_riwayat, instansi_sk, nomor_sk, tanggal_sk, berkas_sk, nama_berkas_sk)
                            VALUES ($guru_id, 'keaktifan', '$tanggal_efektif_sql', '$status_keaktifan_sql',
                            NULLIF('$lembaga_induk_sql', ''), '$tahun_keaktifan_sql', NULLIF('$instansi_sk_sql', ''),
                            NULLIF('$nomor_sk_sql', ''), $tanggal_sk_sql, $berkas_sk_sql, $nama_berkas_sk_sql)";
                    $sql_status_keaktifan = "INSERT INTO buku_induk_guru (guru_id, status_keaktifan)
                            VALUES ($guru_id, '$status_keaktifan_sql')
                            ON DUPLICATE KEY UPDATE status_keaktifan=VALUES(status_keaktifan)";
                    mysqli_begin_transaction($conn);
                    if (mysqli_query($conn, $sql_keaktifan)) {
                        $riwayatIdSk = (int)mysqli_insert_id($conn);
                        if (!mysqli_query($conn, $sql_status_keaktifan)) {
                            mysqli_rollback($conn);
                            if ($berkas_sk !== '') @unlink($berkas_sk);
                            $pesan_status = 'gagal';
                            $pesan = 'Status keaktifan gagal diperbarui: ' . mysqli_error($conn);
                        } else {
                            $driveGagal = false;
                            if ($berkas_sk !== '' && $nama_berkas_sk !== '') {
                                $driveUpload = google_drive_upload($conn, $guru_id, $berkas_sk, $nama_berkas_sk);
                                if (is_array($driveUpload)) {
                                    $driveIdEsc = mysqli_real_escape_string($conn, $driveUpload['id']);
                                    $driveLinkEsc = mysqli_real_escape_string($conn, $driveUpload['link']);
                                    mysqli_query($conn, "UPDATE riwayat_buku_guru SET berkas_sk=NULL, nama_berkas_sk='$nama_berkas_sk', google_drive_file_id='$driveIdEsc', google_drive_link='$driveLinkEsc' WHERE id=$riwayatIdSk AND guru_id=$guru_id");
                                } else {
                                    $driveGagal = true;
                                }
                                @unlink($berkas_sk);
                            }

                            if ($driveGagal) {
                                mysqli_rollback($conn);
                                $pesan_status = 'gagal';
                                $pesan = 'SK gagal dikirim ke Google Drive. Riwayat keaktifan tidak disimpan. Periksa koneksi Google Drive.';
                            } else {
                                mysqli_commit($conn);
                                $pesan_status = 'tersimpan';
                                $pesan = $berkas_sk !== ''
                                    ? 'Riwayat status keaktifan dan SK berhasil disimpan ke Google Drive.'
                                    : 'Riwayat status keaktifan berhasil disimpan.';
                            }
                        }
                    } else {
                        $kesalahan_keaktifan = mysqli_error($conn);
                        mysqli_rollback($conn);
                        if ($berkas_sk !== '') {
                            @unlink($berkas_sk);
                        }
                        $pesan_status = 'gagal';
                        $pesan = 'Riwayat status keaktifan gagal disimpan: ' . $kesalahan_keaktifan;
                    }
                }
            }
            } elseif ($kategori === 'pendidikan') {
                $jenjang = trim($_POST['jenjang'] ?? '');
                $perguruan_tinggi = trim($_POST['perguruan_tinggi'] ?? '');
                $fakultas = trim($_POST['fakultas'] ?? '');
                $jurusan = trim($_POST['jurusan'] ?? '');
                $tahun_lulus = trim($_POST['tahun_lulus'] ?? '');
                $lptk = trim($_POST['lptk'] ?? '');
                $jenjang_sekolah = ['SD', 'MI', 'SMP', 'MTs', 'SMA', 'MA', 'SMK'];
                $jenjang_tidak_perguruan_tinggi = in_array($jenjang, $jenjang_sekolah, true);
                if ($jenjang === '' || $perguruan_tinggi === '' || (!$jenjang_tidak_perguruan_tinggi && !in_array($lptk, ['Ya', 'Tidak'], true))) {
                    $pesan = 'Jenjang, perguruan tinggi, dan status LPTK wajib diisi.';
                } elseif ($tahun_lulus !== '' && (!ctype_digit($tahun_lulus) || strlen($tahun_lulus) !== 4 || (int)$tahun_lulus < 1950 || (int)$tahun_lulus > 2100)) {
                    $pesan = 'Tahun lulus harus berupa tahun yang valid.';
                } else {
                    if ($jenjang_tidak_perguruan_tinggi) {
                        $fakultas = '';
                        $lptk = 'Tidak';
                    }
                    $kolom_pendidikan = [
                        'jenjang' => $jenjang,
                        'instansi' => $perguruan_tinggi,
                        'fakultas' => $fakultas,
                        'jurusan' => $jurusan,
                        'tahun_lulus' => $tahun_lulus,
                        'lptk' => $lptk
                    ];
                    $nilai_pendidikan = [];
                    foreach ($kolom_pendidikan as $kolom => $nilai) {
                        $nilai_pendidikan[$kolom] = "'" . mysqli_real_escape_string($conn, $nilai) . "'";
                    }
                    $judul_sql = mysqli_real_escape_string($conn, $jenjang);
                    $instansi_sql = mysqli_real_escape_string($conn, $perguruan_tinggi);
                    $detail_sql = mysqli_real_escape_string($conn, 'Fakultas: ' . ($fakultas !== '' ? $fakultas : '-') . '; Jurusan: ' . ($jurusan !== '' ? $jurusan : '-') . '; LPTK: ' . $lptk);
                    $tanggal_sql = $tahun_lulus === '' ? 'NULL' : "'" . mysqli_real_escape_string($conn, $tahun_lulus . '-01-01') . "'";
                    $sql = "INSERT INTO riwayat_buku_guru
                        (guru_id, kategori, jenjang, tanggal, judul, instansi, fakultas, jurusan, tahun_lulus, lptk, detail)
                        VALUES ($guru_id, 'pendidikan', {$nilai_pendidikan['jenjang']}, $tanggal_sql, '$judul_sql',
                        '$instansi_sql', {$nilai_pendidikan['fakultas']}, {$nilai_pendidikan['jurusan']},
                        {$nilai_pendidikan['tahun_lulus']}, {$nilai_pendidikan['lptk']}, '$detail_sql')";
                    if (mysqli_query($conn, $sql)) {
                        $pesan_status = 'tersimpan';
                        $pesan = 'Riwayat pendidikan pegawai berhasil ditambahkan.';
                    } else {
                        $pesan = 'Riwayat pendidikan pegawai gagal disimpan: ' . mysqli_error($conn);
                    }
                }
            } elseif ($kategori === 'jabatan') {
                $tahun_jabatan = trim($_POST['tahun_riwayat'] ?? '');
                $nama_jabatan = trim($_POST['jabatan_tahunan'] ?? '');
                $jabatan_tambahan = trim($_POST['jabatan_tambahan_tahunan'] ?? '');
                $mapel_jabatan = trim($_POST['mapel_jabatan'] ?? '');
                $mapel_jabatan_lainnya = trim($_POST['mapel_jabatan_lainnya'] ?? '');
                $opsi_jabatan_utama = ['Guru Mata Pelajaran', 'Kepala Madrasah', 'Kepala Tata Usaha', 'Tenaga Kebersihan', 'Tenaga Keamanan', 'Kepala Perpustakaan', 'Kepala Laboratorium / Bengkel / Produksi', 'Staff Tata Usaha', 'Bendahara'];
                $opsi_jabatan_tambahan = ['Wakil Kepala Madrasah Bidang Kurikulum', 'Wakil Kepala Madrasah Bidang Kesiswaan', 'Wakil Kepala Madrasah Bidang Sarana dan Prasarana', 'Wakil Kepala Madrasah Bidang Humas', 'Wali Kelas', 'Kepala Perpustakaan', 'Kepala Laboratorium', 'Kepala Tata Usaha', 'Staff Tata Usaha', 'Bendahara', 'Operator Madrasah', 'Staff Perpustakaan', 'Pembina Ekstrakurikuler', 'Koordinator'];
                if (!ctype_digit($tahun_jabatan) || strlen($tahun_jabatan) !== 4 || (int)$tahun_jabatan < 1950 || (int)$tahun_jabatan > 2100 || !in_array($nama_jabatan, $opsi_jabatan_utama, true) || ($jabatan_tambahan !== '' && !in_array($jabatan_tambahan, $opsi_jabatan_tambahan, true))) {
                    $pesan = 'Tahun dan nama jabatan wajib diisi dengan benar.';
                } else {
                    $tahun_sql = mysqli_real_escape_string($conn, $tahun_jabatan);
                    $judul_jabatan_sql = mysqli_real_escape_string($conn, $nama_jabatan);
                    $tambahan_jabatan_sql = mysqli_real_escape_string($conn, $jabatan_tambahan);
                    $mapel_jabatan_sql = mysqli_real_escape_string($conn, $mapel_jabatan);
                    $mapel_jabatan_lainnya_sql = mysqli_real_escape_string($conn, $mapel_jabatan_lainnya);
                    $sql = "INSERT INTO riwayat_buku_guru (guru_id, kategori, tahun_riwayat, tanggal, judul, instansi, detail, mata_pelajaran_lainnya)
                            VALUES ($guru_id, 'jabatan', '$tahun_sql', '$tahun_sql-01-01', '$judul_jabatan_sql', NULLIF('$tambahan_jabatan_sql', ''), '$mapel_jabatan_sql', NULLIF('$mapel_jabatan_lainnya_sql', ''))";
                    if (mysqli_query($conn, $sql)) {
                        $pesan_status = 'tersimpan';
                        $pesan = 'Riwayat jabatan pegawai berhasil ditambahkan.';
                    } else {
                        $pesan = 'Riwayat jabatan pegawai gagal disimpan: ' . mysqli_error($conn);
                    }
                }
            } elseif ($kategori === 'mutasi') {
                $jenis_mutasi = trim($_POST['mutasi_jenis'] ?? '');
                $tanggal_mutasi = trim($_POST['tanggal'] ?? '');
                $asal_mutasi = trim($_POST['mutasi_asal'] ?? '');
                $tujuan_mutasi = trim($_POST['mutasi_tujuan'] ?? '');
                $alasan_mutasi = trim($_POST['mutasi_alasan'] ?? '');
                $tanggal_valid = DateTime::createFromFormat('!Y-m-d', $tanggal_mutasi);
                if (!in_array($jenis_mutasi, ['masuk', 'keluar'], true)) {
                    $pesan = 'Pilih jenis mutasi masuk atau mutasi keluar.';
                } elseif (!$tanggal_valid || $tanggal_valid->format('Y-m-d') !== $tanggal_mutasi) {
                    $pesan = 'Tanggal mutasi wajib diisi dengan tanggal yang valid.';
                } elseif ($jenis_mutasi === 'masuk' && $asal_mutasi === '') {
                    $pesan = 'Asal sekolah sebelumnya wajib diisi untuk mutasi masuk.';
                } elseif ($jenis_mutasi === 'keluar' && ($tujuan_mutasi === '' || $alasan_mutasi === '')) {
                    $pesan = 'Tujuan dan alasan wajib diisi untuk mutasi keluar.';
                } else {
                    $judul_mutasi = $jenis_mutasi === 'masuk' ? 'Mutasi Masuk' : 'Mutasi Keluar';
                    $instansi_mutasi = $jenis_mutasi === 'masuk' ? $asal_mutasi : $tujuan_mutasi;
                    $nilai_mutasi = [
                        'judul' => $judul_mutasi,
                        'instansi' => $instansi_mutasi,
                        'tanggal' => $tanggal_mutasi,
                        'jenis' => $jenis_mutasi,
                        'asal' => $asal_mutasi,
                        'tujuan' => $tujuan_mutasi,
                        'alasan' => $alasan_mutasi
                    ];
                    foreach ($nilai_mutasi as $kolom => $nilai) {
                        $nilai_mutasi[$kolom] = mysqli_real_escape_string($conn, $nilai);
                    }
                    $status_sebelum_mutasi = 'Aktif';
                    $status_sebelum_mutasi_sql = '';
                    $tahun_mutasi_sql = substr($tanggal_mutasi, 0, 4);
                    $detail_keaktifan_sql = mysqli_real_escape_string($conn, 'Otomatis dari mutasi keluar. Alasan: ' . $alasan_mutasi);
                    mysqli_begin_transaction($conn);
                    $status_sebelum_berhasil = true;
                    if ($jenis_mutasi === 'keluar') {
                        $hasil_status_sebelum = mysqli_query($conn, "SELECT status_keaktifan FROM buku_induk_guru WHERE guru_id=$guru_id LIMIT 1 FOR UPDATE");
                        if (!$hasil_status_sebelum) {
                            $status_sebelum_berhasil = false;
                            $pesan = 'Status keaktifan sebelumnya gagal diperiksa: ' . mysqli_error($conn);
                        } else {
                            $profil_status_sebelum = mysqli_fetch_assoc($hasil_status_sebelum);
                            if (!empty($profil_status_sebelum['status_keaktifan'])) {
                                $status_sebelum_mutasi = $profil_status_sebelum['status_keaktifan'];
                            }
                            $status_sebelum_mutasi_sql = mysqli_real_escape_string($conn, $status_sebelum_mutasi);
                        }
                    }
                    $sql = "INSERT INTO riwayat_buku_guru
                        (guru_id, kategori, tanggal, judul, instansi, detail, mutasi_jenis, mutasi_asal, mutasi_tujuan, mutasi_alasan, status_sebelum_mutasi)
                        VALUES ($guru_id, 'mutasi', '{$nilai_mutasi['tanggal']}', '{$nilai_mutasi['judul']}',
                        '{$nilai_mutasi['instansi']}', '{$nilai_mutasi['alasan']}', '{$nilai_mutasi['jenis']}',
                        NULLIF('{$nilai_mutasi['asal']}', ''), NULLIF('{$nilai_mutasi['tujuan']}', ''),
                        NULLIF('{$nilai_mutasi['alasan']}', ''), NULLIF('$status_sebelum_mutasi_sql', ''))";
                    if ($status_sebelum_berhasil && mysqli_query($conn, $sql)) {
                        $mutasi_id = (int)mysqli_insert_id($conn);
                        $status_mutasi_berhasil = true;
                        if ($jenis_mutasi === 'keluar') {
                            $sql_riwayat_keaktifan = "INSERT INTO riwayat_buku_guru
                                (guru_id, kategori, tanggal, judul, instansi, detail, tahun_riwayat, sumber_mutasi_id)
                                VALUES ($guru_id, 'keaktifan', '{$nilai_mutasi['tanggal']}', 'Tidak Aktif',
                                '{$nilai_mutasi['tujuan']}', '$detail_keaktifan_sql', '$tahun_mutasi_sql', $mutasi_id)";
                            $status_mutasi_berhasil = mysqli_query($conn, $sql_riwayat_keaktifan)
                                && mysqli_query($conn, "INSERT INTO buku_induk_guru (guru_id, status_keaktifan)
                                    VALUES ($guru_id, 'Tidak Aktif')
                                    ON DUPLICATE KEY UPDATE status_keaktifan='Tidak Aktif'");
                        }
                        if ($status_mutasi_berhasil && mysqli_commit($conn)) {
                            $pesan_status = 'tersimpan';
                            $pesan = $jenis_mutasi === 'keluar'
                                ? 'Mutasi Keluar tersimpan. Status pegawai langsung menjadi Tidak Aktif dan riwayat Keaktifan otomatis ditambahkan pada tanggal mutasi.'
                                : 'Riwayat mutasi pegawai berhasil ditambahkan.';
                        } else {
                            $kesalahan_mutasi = mysqli_error($conn);
                            mysqli_rollback($conn);
                            $pesan = 'Mutasi pegawai atau pembaruan status keaktifan gagal disimpan: ' . $kesalahan_mutasi;
                        }
                    } elseif ($status_sebelum_berhasil) {
                        $kesalahan_mutasi = mysqli_error($conn);
                        mysqli_rollback($conn);
                        $pesan = 'Riwayat mutasi pegawai gagal disimpan: ' . $kesalahan_mutasi;
                    } else {
                        mysqli_rollback($conn);
                    }
                }
            } elseif ($kategori === 'sertifikasi') {
                $hasil_status_sertifikasi = mysqli_query($conn, "SELECT status_sertifikasi FROM buku_induk_guru WHERE guru_id=$guru_id LIMIT 1");
                $data_status_sertifikasi = $hasil_status_sertifikasi ? mysqli_fetch_assoc($hasil_status_sertifikasi) : null;
                $no_piagam = trim($_POST['no_piagam'] ?? '');
                $nrg = trim($_POST['nrg'] ?? '');
                $mapel_sertifikasi = trim($_POST['mapel_sertifikasi'] ?? '');
                $badan_sertifikasi = trim($_POST['badan_sertifikasi'] ?? '');
                $jalur_sertifikasi = trim($_POST['jalur_sertifikasi'] ?? '');
                $tanggal_piagam = trim($_POST['tanggal'] ?? '');
                $tanggal_valid = DateTime::createFromFormat('!Y-m-d', $tanggal_piagam);
                if (!$hasil_status_sertifikasi) {
                    $pesan = 'Status sertifikasi gagal diperiksa: ' . mysqli_error($conn);
                } elseif (($data_status_sertifikasi['status_sertifikasi'] ?? '') !== 'Sudah') {
                    $pesan = 'Atur status sertifikasi menjadi Sudah sebelum menambahkan data piagam.';
                } elseif ($no_piagam === '' || $mapel_sertifikasi === '' || $badan_sertifikasi === '' || $jalur_sertifikasi === '' || !$tanggal_valid || $tanggal_valid->format('Y-m-d') !== $tanggal_piagam) {
                    $pesan = 'Semua data sertifikasi wajib diisi.';
                } else {
                    $nilai_sertifikasi = [
                        'judul' => $no_piagam,
                        'nrg' => $nrg,
                        'instansi' => $badan_sertifikasi,
                        'mapel_sertifikasi' => $mapel_sertifikasi,
                        'jalur_sertifikasi' => $jalur_sertifikasi
                    ];
                    foreach ($nilai_sertifikasi as $kolom => $nilai) {
                        $nilai_sertifikasi[$kolom] = mysqli_real_escape_string($conn, $nilai);
                    }
                    $tanggal_sql = "'" . mysqli_real_escape_string($conn, $tanggal_piagam) . "'";
                    $detail_sql = mysqli_real_escape_string($conn, 'Mapel sertifikasi: ' . $mapel_sertifikasi . '; Jalur: ' . $jalur_sertifikasi);
                    $sql = "INSERT INTO riwayat_buku_guru
                        (guru_id, kategori, tanggal, judul, instansi, detail, mapel_sertifikasi, nrg, jalur_sertifikasi)
                        VALUES ($guru_id, 'sertifikasi', $tanggal_sql, '{$nilai_sertifikasi['judul']}',
                        '{$nilai_sertifikasi['instansi']}', '$detail_sql',
                        '{$nilai_sertifikasi['mapel_sertifikasi']}', '{$nilai_sertifikasi['nrg']}', '{$nilai_sertifikasi['jalur_sertifikasi']}')";
                    if (mysqli_query($conn, $sql)) {
                        $pesan_status = 'tersimpan';
                        $pesan = 'Sertifikasi guru berhasil ditambahkan.';
                    } else {
                        $pesan = 'Sertifikasi guru gagal disimpan: ' . mysqli_error($conn);
                    }
                }
            } elseif ($kategori === 'keluarga') {
                $jenis_keluarga = $_POST['simpan_keluarga_tipe'] ?? '';
                $anggota = $_POST['keluarga'][$jenis_keluarga] ?? [];
                $nama_anggota = trim($anggota['nama'] ?? '');
                $tanggal_lahir = trim($anggota['tanggal'] ?? '');
                $keterangan = trim($anggota['detail'] ?? '');
                $jenis_kelamin_keluarga = trim($anggota['jenis_kelamin'] ?? '');
                $tempat_lahir_keluarga = trim($anggota['tempat_lahir'] ?? '');
                $pendidikan_anak = trim($anggota['pendidikan'] ?? '');
                $opsi_pendidikan_keluarga = ['Tidak Sekolah', 'Belum Sekolah', 'SD', 'MI', 'SMP', 'MTs', 'SMA', 'MA', 'SMK', 'D1', 'D2', 'D3', 'D4', 'S1', 'S2', 'S3'];
                $tanggal_valid = $tanggal_lahir === '' ? null : DateTime::createFromFormat('!Y-m-d', $tanggal_lahir);
                if (!in_array($jenis_keluarga, ['pasangan', 'anak'], true) || $nama_anggota === '') {
                    $pesan = 'Pilih jenis anggota keluarga dan isi namanya.';
                } elseif ($tanggal_lahir !== '' && (!$tanggal_valid || $tanggal_valid->format('Y-m-d') !== $tanggal_lahir)) {
                    $pesan = 'Tanggal lahir anggota keluarga tidak valid.';
                } elseif ($jenis_keluarga === 'anak' && !in_array($jenis_kelamin_keluarga, ['Laki-laki', 'Perempuan'], true)) {
                    $pesan = 'Pilih jenis kelamin anak kandung.';
                } elseif ($pendidikan_anak !== '' && !in_array($pendidikan_anak, $opsi_pendidikan_keluarga, true)) {
                    $pesan = 'Pilih pendidikan dari daftar yang tersedia.';
                } else {
                    $hubungan = $jenis_keluarga === 'anak' ? 'Anak' : 'Pasangan';
                    $tanggal_sql = $tanggal_lahir === '' ? 'NULL' : "'" . mysqli_real_escape_string($conn, $tanggal_lahir) . "'";
                    $nama_sql = mysqli_real_escape_string($conn, $nama_anggota);
                    $hubungan_sql = mysqli_real_escape_string($conn, $hubungan);
                    $keterangan_sql = mysqli_real_escape_string($conn, $keterangan);
                    $jenis_kelamin_sql = $jenis_kelamin_keluarga === '' ? 'NULL' : "'" . mysqli_real_escape_string($conn, $jenis_kelamin_keluarga) . "'";
                    $tempat_lahir_sql = $tempat_lahir_keluarga === '' ? 'NULL' : "'" . mysqli_real_escape_string($conn, $tempat_lahir_keluarga) . "'";
                    $pendidikan_anak_sql = $pendidikan_anak === '' ? 'NULL' : "'" . mysqli_real_escape_string($conn, $pendidikan_anak) . "'";
                    $pendidikan_keluarga_sql = $jenis_keluarga === 'pasangan' && $pendidikan_anak !== '' ? "'" . mysqli_real_escape_string($conn, $pendidikan_anak) . "'" : 'NULL';
                    $status_hubungan_sql = $jenis_keluarga === 'anak' ? "'Anak Kandung'" : "'Suami/Istri'";
                    $sql = "INSERT INTO riwayat_buku_guru
                            (guru_id, kategori, tanggal, judul, instansi, detail, jenis_kelamin_keluarga,
                             tempat_lahir_keluarga, pendidikan_anak, status_hubungan_keluarga, pendidikan_keluarga)
                            VALUES ($guru_id, 'keluarga', $tanggal_sql, '$nama_sql', '$hubungan_sql', '$keterangan_sql',
                             $jenis_kelamin_sql, $tempat_lahir_sql, $pendidikan_anak_sql, $status_hubungan_sql, $pendidikan_keluarga_sql)";
                    if (mysqli_query($conn, $sql)) {
                        $pesan_status = 'tersimpan';
                        $pesan = 'Data keluarga pegawai berhasil ditambahkan.';
                    } else {
                        $pesan = 'Data keluarga pegawai gagal disimpan: ' . mysqli_error($conn);
                    }
                }
            } else {
                $tanggal = trim($_POST['tanggal'] ?? '');
                $tanggal_sql = $tanggal === '' ? 'NULL' : "'" . mysqli_real_escape_string($conn, $tanggal) . "'";
                $judul_sql = mysqli_real_escape_string($conn, $judul);
                $instansi_sql = mysqli_real_escape_string($conn, trim($_POST['instansi'] ?? ''));
                $detail_sql = mysqli_real_escape_string($conn, trim($_POST['detail'] ?? ''));
                $kategori_sql = mysqli_real_escape_string($conn, $kategori);
                $sql = "INSERT INTO riwayat_buku_guru (guru_id, kategori, tanggal, judul, instansi, detail)
                        VALUES ($guru_id, '$kategori_sql', $tanggal_sql, '$judul_sql', '$instansi_sql', '$detail_sql')";
                if (mysqli_query($conn, $sql)) {
                    $pesan_status = 'tersimpan';
                    $pesan = 'Riwayat pegawai berhasil ditambahkan.';
                } else {
                    $pesan = 'Riwayat pegawai gagal disimpan: ' . mysqli_error($conn);
                }
            }
        }
    } elseif (isset($_POST['hapus_riwayat_guru'])) {
        $riwayat_id = (int)($_POST['riwayat_id'] ?? 0);
        if ($riwayat_id < 1) {
            $pesan = 'ID riwayat tidak valid.';
        } else {
            $hasil_riwayat_hapus = mysqli_query($conn, "SELECT id, guru_id, kategori, mutasi_jenis, sumber_mutasi_id, status_sebelum_mutasi FROM riwayat_buku_guru WHERE id=$riwayat_id LIMIT 1");
            if (!$hasil_riwayat_hapus) {
                $pesan = 'Riwayat pegawai gagal diperiksa: ' . mysqli_error($conn);
            } elseif (!$data_riwayat_hapus = mysqli_fetch_assoc($hasil_riwayat_hapus)) {
                $pesan = 'Riwayat pegawai tidak ditemukan.';
            } else {
                $status_pulih = null;
                $hapus_berhasil = true;
                $pesan_gagal_hapus = '';
                mysqli_begin_transaction($conn);
                if ($data_riwayat_hapus['kategori'] === 'mutasi' && $data_riwayat_hapus['mutasi_jenis'] === 'keluar') {
                    $hasil_keaktifan_mutasi = mysqli_query($conn, "SELECT id FROM riwayat_buku_guru WHERE sumber_mutasi_id=$riwayat_id AND guru_id=" . (int)$data_riwayat_hapus['guru_id'] . " AND kategori='keaktifan' LIMIT 1");
                    if (!$hasil_keaktifan_mutasi) {
                        $hapus_berhasil = false;
                        $pesan_gagal_hapus = 'Riwayat keaktifan dari mutasi gagal diperiksa: ' . mysqli_error($conn);
                    } elseif ($data_keaktifan_mutasi = mysqli_fetch_assoc($hasil_keaktifan_mutasi)) {
                        $riwayat_keaktifan_id = (int)$data_keaktifan_mutasi['id'];
                        $hasil_keaktifan_terakhir = mysqli_query($conn, "SELECT id FROM riwayat_buku_guru WHERE guru_id=" . (int)$data_riwayat_hapus['guru_id'] . " AND kategori='keaktifan' ORDER BY id DESC LIMIT 1");
                        $hasil_status_sekarang = mysqli_query($conn, "SELECT status_keaktifan FROM buku_induk_guru WHERE guru_id=" . (int)$data_riwayat_hapus['guru_id'] . " LIMIT 1");
                        if (!$hasil_keaktifan_terakhir || !$hasil_status_sekarang) {
                            $hapus_berhasil = false;
                            $pesan_gagal_hapus = 'Status keaktifan saat ini gagal diperiksa: ' . mysqli_error($conn);
                        } else {
                            $keaktifan_terakhir = mysqli_fetch_assoc($hasil_keaktifan_terakhir);
                            $status_sekarang = mysqli_fetch_assoc($hasil_status_sekarang);
                            if ((int)($keaktifan_terakhir['id'] ?? 0) === $riwayat_keaktifan_id
                                && ($status_sekarang['status_keaktifan'] ?? '') === 'Tidak Aktif') {
                                $status_pulih = $data_riwayat_hapus['status_sebelum_mutasi'] ?: 'Aktif';
                            }
                            if (!mysqli_query($conn, "DELETE FROM riwayat_buku_guru WHERE id=$riwayat_keaktifan_id")) {
                                $hapus_berhasil = false;
                                $pesan_gagal_hapus = 'Riwayat keaktifan otomatis gagal dihapus: ' . mysqli_error($conn);
                            }
                        }
                    }
                }
                if ($hapus_berhasil && !mysqli_query($conn, "DELETE FROM riwayat_buku_guru WHERE id=$riwayat_id")) {
                    $hapus_berhasil = false;
                    $pesan_gagal_hapus = 'Riwayat pegawai gagal dihapus: ' . mysqli_error($conn);
                }
                if ($hapus_berhasil && $data_riwayat_hapus['kategori'] === 'keaktifan' && empty($data_riwayat_hapus['sumber_mutasi_id'])) {
                    $guru_id_keaktifan = (int)$data_riwayat_hapus['guru_id'];
                    $hasil_keaktifan_terakhir = mysqli_query($conn, "SELECT judul FROM riwayat_buku_guru WHERE guru_id=$guru_id_keaktifan AND kategori='keaktifan' ORDER BY id DESC LIMIT 1");
                    if (!$hasil_keaktifan_terakhir) {
                        $hapus_berhasil = false;
                        $pesan_gagal_hapus = 'Status keaktifan terbaru gagal diperiksa: ' . mysqli_error($conn);
                    } else {
                        $keaktifan_terakhir = mysqli_fetch_assoc($hasil_keaktifan_terakhir);
                        $status_keaktifan_pulih = mysqli_real_escape_string($conn, $keaktifan_terakhir['judul'] ?? 'Aktif');
                        if (!mysqli_query($conn, "INSERT INTO buku_induk_guru (guru_id, status_keaktifan)
                            VALUES ($guru_id_keaktifan, '$status_keaktifan_pulih')
                            ON DUPLICATE KEY UPDATE status_keaktifan=VALUES(status_keaktifan)")) {
                            $hapus_berhasil = false;
                            $pesan_gagal_hapus = 'Status keaktifan gagal disinkronkan: ' . mysqli_error($conn);
                        }
                    }
                }
                if ($hapus_berhasil && $status_pulih !== null) {
                    $status_pulih_sql = mysqli_real_escape_string($conn, $status_pulih);
                    if (!mysqli_query($conn, "UPDATE buku_induk_guru SET status_keaktifan='$status_pulih_sql' WHERE guru_id=" . (int)$data_riwayat_hapus['guru_id'])) {
                        $hapus_berhasil = false;
                        $pesan_gagal_hapus = 'Status keaktifan sebelumnya gagal dipulihkan: ' . mysqli_error($conn);
                    }
                }
                if ($hapus_berhasil && mysqli_commit($conn)) {
                    $pesan_status = 'tersimpan';
                    $pesan = 'Riwayat pegawai berhasil dihapus.';
                    if ($status_pulih !== null) {
                        $pesan .= ' Status keaktifan sebelumnya (' . $status_pulih . ') juga dipulihkan.';
                    }
                } else {
                    mysqli_rollback($conn);
                    $pesan = $pesan_gagal_hapus !== '' ? $pesan_gagal_hapus : 'Riwayat pegawai gagal dihapus: ' . mysqli_error($conn);
                }
            }
        }
        $kategori = $_GET['bagian'] ?? 'pegawai';
        if (in_array($kategori, ['pendidikan', 'keluarga', 'pangkat', 'jabatan', 'penghargaan', 'mutasi', 'sertifikasi'], true)) {
            $url_kembali .= '&bagian=' . urlencode($kategori) . '&guru=' . (int)($_GET['guru'] ?? 0);
        }
    } elseif (isset($_POST['hapus_profil_guru'])) {
        $guru_id = (int)($_POST['guru_id'] ?? 0);
        if ($guru_id < 1) {
            $pesan = 'ID pegawai tidak valid.';
        } else {
            try {
                if (hapus_guru_dan_data_terkait($conn, $guru_id)) {
                    $pesan_status = 'tersimpan';
                    $pesan = 'Data buku induk pegawai berhasil dihapus.';
                } else {
                    $pesan = 'Data pegawai tidak ditemukan.';
                }
            } catch (Throwable $error) {
                $pesan = 'Data pegawai gagal dihapus: ' . $error->getMessage();
            }
        }
    } else {
        $pesan = 'Permintaan buku induk guru tidak dikenali.';
    }

    $_SESSION['pesan_buku_guru'] = $pesan;
    $_SESSION['status_buku_guru'] = $pesan_status;
    $url_kembali .= (strpos($url_kembali, '?') === false ? '?' : '&') . 'status=' . urlencode($pesan_status);
    header('Location: ' . $url_kembali);
    exit();
}

// Helper untuk menandai menu aktif di navigasi
function is_active($current_page, $target_page) {
    return $current_page === $target_page ? 'active' : '';
}

// Map judul halaman berdasarkan parameter page
$page_titles = [
    'dashboard' => 'Ringkasan Statistik',
    'tahun_ajaran' => 'Pengaturan Tahun Ajaran',
    'data_guru' => 'Manajemen Data Guru & Staff',
    'buku_induk_guru' => 'Buku Induk Guru & Tenaga Kependidikan',
    'tambah_siswa' => 'Formulir Siswa Baru',
    'upload_siswa' => 'Unggah Berkas Excel',
    'data_siswa' => 'Buku Induk Master Siswa',
    'tambah_user' => 'Registrasi Pengguna Baru',
    'pengaturan' => 'Pengaturan Profil Akun',
    'guru_portal' => 'Portal Guru - Buku Induk'
];
$current_title = isset($page_titles[$page]) ? $page_titles[$page] : 'Aplikasi Buku Induk';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $current_title; ?> - Buku Induk Digital MA Ma'arif Darul Aman Pringsurat</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        :root {
            --si-primary: #0f5132;      
            --si-primary-light: #198754;
            --si-bg: #f3f4f6;           
            --si-sidebar-w: 260px;      
            --si-transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body { 
            background-color: var(--si-bg); 
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #1f2937;
            overflow-x: hidden;
        }

        .sidebar { 
            width: var(--si-sidebar-w);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background-color: #0c3924; 
            color: #f3f4f6;
            z-index: 1030;
            transition: var(--si-transition);
            box-shadow: 4px 0 10px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
        }

        .brand-wrapper {
            padding: 1.5rem 1.25rem;
            background: rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .brand-logo-icon {
            width: 42px;
            height: 42px;
            border-radius: 8px;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            padding: 2px;
        }

        .brand-logo-img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .brand-text h1 {
            font-size: 0.95rem;
            font-weight: 700;
            margin: 0;
            letter-spacing: 0.5px;
            color: #ffffff;
        }

        .brand-text p {
            font-size: 0.7rem;
            margin: 0;
            color: #a3e635; 
            font-weight: 500;
        }

        .sidebar-menu-wrapper {
            padding: 1.25rem 0.75rem;
            overflow-y: auto;
            flex-grow: 1;
        }

        .menu-label {
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(255, 255, 255, 0.4);
            padding: 0.75rem 1rem 0.25rem 1rem;
            font-weight: 700;
        }

        .sidebar .nav-link { 
            color: rgba(255, 255, 255, 0.75); 
            font-size: 0.875rem;
            font-weight: 500;
            padding: 0.75rem 1rem;
            margin-bottom: 0.25rem;
            border-radius: 8px;
            display: flex;
            align-items: center;
            transition: var(--si-transition);
        }

        .sidebar .nav-link i {
            width: 1.5rem;
            font-size: 1.1rem;
            transition: var(--si-transition);
        }

        .sidebar .nav-link:hover { 
            color: #ffffff; 
            background-color: rgba(255, 255, 255, 0.08); 
        }

        .sidebar .nav-link.active { 
            color: #ffffff; 
            background-color: var(--si-primary-light); 
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(25, 135, 84, 0.3);
        }
        
        .sidebar .nav-link.active i {
            color: #a3e635;
        }

        .main-wrapper {
            margin-left: var(--si-sidebar-w);
            min-height: 100vh;
            transition: var(--si-transition);
            display: flex;
            flex-direction: column;
        }

        .top-navbar {
            background-color: #ffffff;
            height: 70px;
            padding: 0 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            position: sticky;
            top: 0;
            z-index: 1020;
        }

        .topbar-title {
            margin: 0;
            font-size: 1.15rem;
            font-weight: 700;
            color: #111827;
        }

        .content-body {
            padding: 2rem;
            flex-grow: 1;
        }

        @media (max-width: 991.98px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.show { transform: translateX(0); }
            .main-wrapper { margin-left: 0; }
            .top-navbar { padding: 0 1.25rem; }
            .content-body { padding: 1.25rem; }
        }
        
        @media print {
            .sidebar, .top-navbar { display: none !important; }
            .main-wrapper { margin-left: 0 !important; padding: 0 !important; }
            .content-body { padding: 0 !important; }
        }
    </style>
</head>
<body>

    <aside class="sidebar" id="sidebarMenu">
        <div class="brand-wrapper">
            <div class="brand-logo-icon">
                <img src="icon-512--.png" alt="Logo MA Ma'arif Darul Amin" class="brand-logo-img">
            </div>
            <div class="brand-text">
                <h1>BUKU INDUK</h1>
                <p>MA Ma'arif Darul Aman</p>
            </div>
        </div>
        
        <div class="sidebar-menu-wrapper">
            <ul class="nav flex-column">
                <?php if (($_SESSION['role'] ?? '') === 'guru'): ?>
                <li class="menu-label">Portal Guru</li>
                <li class="nav-item"><a class="nav-link <?php echo ($_GET['bagian'] ?? 'pegawai') === 'pegawai' ? 'active' : ''; ?>" href="index.php?page=guru_portal&amp;bagian=pegawai"><i class="fa-solid fa-user me-2"></i> Profil Saya</a></li>
                <li class="nav-item"><a class="nav-link <?php echo ($_GET['bagian'] ?? '') === 'pendidikan' ? 'active' : ''; ?>" href="index.php?page=guru_portal&amp;bagian=pendidikan"><i class="fa-solid fa-graduation-cap me-2"></i> Pendidikan</a></li>
                <li class="nav-item"><a class="nav-link <?php echo ($_GET['bagian'] ?? '') === 'keluarga' ? 'active' : ''; ?>" href="index.php?page=guru_portal&amp;bagian=keluarga"><i class="fa-solid fa-users me-2"></i> Keluarga</a></li>
                <li class="nav-item"><a class="nav-link <?php echo ($_GET['bagian'] ?? '') === 'keaktifan' ? 'active' : ''; ?>" href="index.php?page=guru_portal&amp;bagian=keaktifan"><i class="fa-solid fa-user-check me-2"></i> Keaktifan</a></li>
                <li class="nav-item"><a class="nav-link <?php echo ($_GET['bagian'] ?? '') === 'jabatan' ? 'active' : ''; ?>" href="index.php?page=guru_portal&amp;bagian=jabatan"><i class="fa-solid fa-briefcase me-2"></i> Jabatan</a></li>
                <li class="nav-item"><a class="nav-link <?php echo ($_GET['bagian'] ?? '') === 'penghargaan' ? 'active' : ''; ?>" href="index.php?page=guru_portal&amp;bagian=penghargaan"><i class="fa-solid fa-award me-2"></i> Penghargaan</a></li>
                <li class="nav-item"><a class="nav-link <?php echo ($_GET['bagian'] ?? '') === 'sertifikasi' ? 'active' : ''; ?>" href="index.php?page=guru_portal&amp;bagian=sertifikasi"><i class="fa-solid fa-certificate me-2"></i> Sertifikasi</a></li>
                <li class="nav-item"><hr class="text-white opacity-25 my-2"></li>
                <li class="nav-item"><a class="nav-link text-danger-emphasis" href="logout.php" onclick="return confirm('Apakah anda yakin ingin keluar dari sistem?')"><i class="fa-solid fa-right-from-bracket me-2 text-danger"></i> Keluar Akun</a></li>
                <?php else: ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo is_active($page, 'dashboard'); ?>" href="index.php?page=dashboard">
                        <i class="fa-solid fa-chart-pie me-2"></i> Dashboard
                    </a>
                </li>
                
                <li class="menu-label">Data Master</li>
                <li class="nav-item">
                    <a class="nav-link <?php echo is_active($page, 'tahun_ajaran'); ?>" href="index.php?page=tahun_ajaran">
                        <i class="fa-solid fa-calendar-days me-2"></i> Tahun Ajaran
                    </a>
                </li>
                <li class="menu-label">Buku Induk</li>
                <li class="nav-item">
                    <a class="nav-link <?php echo is_active($page, 'data_siswa'); ?>" href="index.php?page=data_siswa">
                        <i class="fa-solid fa-user-graduate me-2"></i> Buku Induk Siswa
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo is_active($page, 'buku_induk_guru'); ?>" href="index.php?page=buku_induk_guru">
                        <i class="fa-solid fa-address-card me-2"></i> Buku Induk Guru
                    </a>
                </li>

                <li class="menu-label">Manajemen Siswa</li>
                <li class="nav-item">
                    <a class="nav-link <?php echo is_active($page, 'tambah_siswa'); ?>" href="index.php?page=tambah_siswa">
                        <i class="fa-solid fa-user-plus me-2"></i> Tambah Siswa
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo is_active($page, 'upload_siswa'); ?>" href="index.php?page=upload_siswa">
                        <i class="fa-solid fa-file-excel me-2"></i> Upload Excel
                    </a>
                </li>
                
                <li class="nav-item">
                    <hr class="text-white opacity-25 my-2">
                </li>
                
                <li class="menu-label">Sistem</li>
                <li class="nav-item">
                    <a class="nav-link <?php echo is_active($page, 'tambah_user'); ?>" href="index.php?page=tambah_user">
                        <i class="fa-solid fa-user-gear me-2"></i> Tambah User
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo is_active($page, 'pengaturan'); ?>" href="index.php?page=pengaturan">
                        <i class="fa-solid fa-sliders me-2"></i> Pengaturan
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-danger-emphasis" href="logout.php" onclick="return confirm('Apakah anda yakin ingin keluar dari sistem?')">
                        <i class="fa-solid fa-right-from-bracket me-2 text-danger"></i> Keluar Akun
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </aside>

    <div class="main-wrapper">
        
        <header class="top-navbar">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-light d-lg-none shadow-sm" type="button" id="btnToggleSidebar" aria-label="Toggle Navigation">
                    <i class="fa-solid fa-bars-staggered"></i>
                </button>
                <h2 class="topbar-title d-none d-sm-block"><?php echo $current_title; ?></h2>
            </div>
            
            <div class="d-flex align-items-center gap-3">
                <div class="text-end d-none d-md-block">
                    <small class="text-muted d-block" style="font-size: 0.75rem;">Selamat Datang,</small>
                    <span class="fw-semibold text-dark small"><?php echo htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Staf TU'); ?></span>
                </div>
                <div class="dropdown">
                    <button class="btn p-0 bg-success-subtle text-success rounded-circle d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa-solid <?php echo (($_SESSION['role'] ?? '') === 'guru') ? 'fa-user' : 'fa-user-shield'; ?>"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                        <?php if (($_SESSION['role'] ?? '') === 'guru'): ?>
                        <li><a class="dropdown-item py-2 small" href="index.php?page=guru_portal&amp;bagian=pegawai&amp;foto=1"><i class="fa-solid fa-camera me-2 text-muted"></i> Edit Foto Profil</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <?php else: ?>
                        <li><a class="dropdown-item py-2 small" href="index.php?page=pengaturan"><i class="fa-solid fa-gear me-2 text-muted"></i> Pengaturan</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <?php endif; ?>
                        <li><a class="dropdown-item py-2 small text-danger" href="logout.php" onclick="return confirm('Apakah anda yakin ingin keluar?')"><i class="fa-solid fa-right-from-bracket me-2"></i> Keluar</a></li>
                    </ul>
                </div>
            </div>
        </header>

        <main class="content-body">
            <?php 
                switch ($page) {
                    case 'tahun_ajaran': include 'tahun_ajaran.php'; break;
                    case 'data_guru': include 'data_guru.php'; break;
                    case 'buku_induk_guru': include 'buku_induk_guru.php'; break;
                    case 'tambah_siswa': include 'tambah_siswa.php'; break;
                    case 'upload_siswa': include 'upload_siswa.php'; break;
                    case 'data_siswa': include 'data_siswa.php'; break;
                    case 'tambah_user': include 'tambah_user.php'; break;
                    case 'pengaturan': include 'pengaturan.php'; break;
                    case 'guru_portal': include 'guru_portal.php'; break;
                    default: include 'dashboard.php'; break;
                }
            ?>
        </main>
        
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const btnToggle = document.getElementById('btnToggleSidebar');
            const sidebar = document.getElementById('sidebarMenu');

            if (btnToggle && sidebar) {
                btnToggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    sidebar.classList.toggle('show');
                });

                document.addEventListener('click', function(e) {
                    if (!sidebar.contains(e.target) && !btnToggle.contains(e.target)) {
                        sidebar.classList.remove('show');
                    }
                });
            }
        });
    </script>
</body>
</html>