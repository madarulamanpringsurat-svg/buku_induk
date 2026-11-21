<?php
$bagian_valid = ['dashboard', 'pegawai', 'keaktifan', 'pendidikan', 'keluarga', 'jabatan', 'penghargaan', 'mutasi', 'sertifikasi', 'info_sertifikasi', 'foto', 'laporan'];
$bagian_diajukan = $_GET['bagian'] ?? 'dashboard';
$bagian = $bagian_diajukan === 'pangkat' ? 'jabatan' : (in_array($bagian_diajukan, $bagian_valid, true) ? $bagian_diajukan : 'dashboard');
$id_edit_guru = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$filter_guru = isset($_GET['guru']) ? (int)$_GET['guru'] : 0;
if (($_SESSION['role'] ?? '') === 'guru') {
    $guruIdSesi = (int)($_SESSION['guru_id'] ?? 0);
    $filter_guru = $guruIdSesi;
    $id_edit_guru = $bagian === 'pegawai' ? $guruIdSesi : 0;
}
$mode_cetak = $_GET['cetak'] ?? '';
$notifikasi = $_SESSION['pesan_buku_guru'] ?? '';
$status_notifikasi = $_SESSION['status_buku_guru'] ?? 'tersimpan';
unset($_SESSION['pesan_buku_guru'], $_SESSION['status_buku_guru']);
if ($notifikasi === '' && isset($_GET['status'])) {
    if ($_GET['status'] === 'tersimpan') {
        $notifikasi = 'Perubahan Buku Induk Guru berhasil diproses.';
        $status_notifikasi = 'tersimpan';
    } elseif ($_GET['status'] === 'gagal') {
        $notifikasi = 'Perubahan Buku Induk Guru gagal diproses. Periksa kembali isian atau log error server.';
        $status_notifikasi = 'gagal';
    }
}

$hasil_identitas = mysqli_query($conn, "SELECT * FROM identitas_madrasah WHERE id=1");
if (!$hasil_identitas) {
    die('Identitas madrasah gagal dimuat: ' . htmlspecialchars(mysqli_error($conn)));
}
$identitas_madrasah = mysqli_fetch_assoc($hasil_identitas);
$syarat_daftar_guru = '';
if (($_SESSION['role'] ?? '') === 'guru') {
    $syarat_daftar_guru = ' WHERE g.id=' . (int)($_SESSION['guru_id'] ?? 0);
}
$daftar_guru = mysqli_query($conn, "
    SELECT g.*, bg.nik, bg.nuptk, bg.npk, bg.peg_id,
           (SELECT rb.nrg FROM riwayat_buku_guru rb WHERE rb.guru_id=g.id AND rb.kategori='sertifikasi' ORDER BY rb.id DESC LIMIT 1) AS nrg,
           bg.jenis_kelamin, bg.tempat_lahir, bg.tanggal_lahir, bg.agama,
           bg.status_perkawinan, bg.nama_ibu_kandung, bg.alamat, bg.no_hp, bg.email, bg.mata_pelajaran_lainnya, bg.tanggal_masuk, bg.tanggal_mulai_jabatan, bg.tanggal_mulai_jabatan_tambahan, bg.status_kepegawaian,
           bg.status_keaktifan,
           bg.pendidikan_terakhir, bg.program_studi, bg.perguruan_tinggi, bg.tahun_lulus,
           bg.nama_pasangan, bg.jumlah_anak, bg.pangkat_golongan, bg.tmt_pangkat,
           bg.status_sertifikasi, bg.status_inpassing, bg.nomor_sk_inpassing, bg.keterangan_inpassing, bg.foto
    FROM guru g
    LEFT JOIN buku_induk_guru bg ON bg.guru_id = g.id
    $syarat_daftar_guru
    ORDER BY g.nama_guru ASC
");
if (!$daftar_guru) {
    die('Data buku induk guru gagal dimuat: ' . htmlspecialchars(mysqli_error($conn)));
}
$daftar_guru_aktif = [];
$daftar_guru_non_aktif = [];
mysqli_data_seek($daftar_guru, 0);
while ($pegawai_dashboard = mysqli_fetch_assoc($daftar_guru)) {
    if (($pegawai_dashboard['status_keaktifan'] ?? 'Aktif') === 'Aktif') {
        $daftar_guru_aktif[] = $pegawai_dashboard;
    } else {
        $daftar_guru_non_aktif[] = $pegawai_dashboard;
    }
}
mysqli_data_seek($daftar_guru, 0);

$daftar_info_sertifikasi = null;
if ($bagian === 'info_sertifikasi') {
    $daftar_info_sertifikasi = mysqli_query($conn, "
    SELECT g.id, g.nama_guru, g.nip, g.jabatan, bg.nik, bg.npk,
           bg.status_sertifikasi, bg.status_inpassing, bg.pangkat_golongan, bg.tmt_pangkat,
           bg.nomor_sk_inpassing, bg.keterangan_inpassing,
           sert.no_piagam, sert.nrg, sert.mapel_sertifikasi, sert.badan_sertifikasi, sert.jalur_sertifikasi, sert.tanggal_piagam
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
    WHERE bg.status_sertifikasi='Sudah' OR sert.no_piagam IS NOT NULL OR bg.status_inpassing='Sudah'
    ORDER BY g.nama_guru ASC
    ");
    if (!$daftar_info_sertifikasi) {
        die('Informasi sertifikasi dan inpassing gagal dimuat: ' . htmlspecialchars(mysqli_error($conn)));
    }
}

$guru_sudah_mutasi_keluar = [];
$hasil_mutasi_keluar = mysqli_query($conn, "SELECT DISTINCT guru_id FROM riwayat_buku_guru WHERE kategori='mutasi' AND (mutasi_jenis='keluar' OR judul LIKE '%Keluar%')");
if (!$hasil_mutasi_keluar) {
    die('Data mutasi guru gagal diperiksa: ' . htmlspecialchars(mysqli_error($conn)));
}
while ($baris_mutasi_keluar = mysqli_fetch_assoc($hasil_mutasi_keluar)) {
    $guru_sudah_mutasi_keluar[(int)$baris_mutasi_keluar['guru_id']] = true;
}

$profil_edit = null;
if ($id_edit_guru > 0) {
    $hasil_edit = mysqli_query($conn, "
        SELECT g.*, bg.nik, bg.nuptk, bg.npk, bg.peg_id, bg.jenis_kelamin, bg.tempat_lahir, bg.tanggal_lahir, bg.agama,
               bg.status_perkawinan, bg.nama_ibu_kandung, bg.alamat, bg.no_hp, bg.email, bg.mata_pelajaran_lainnya, bg.tanggal_masuk, bg.tanggal_mulai_jabatan, bg.tanggal_mulai_jabatan_tambahan, bg.status_kepegawaian,
               bg.status_keaktifan,
               bg.pendidikan_terakhir, bg.program_studi, bg.perguruan_tinggi, bg.tahun_lulus,
               bg.nama_pasangan, bg.jumlah_anak, bg.pangkat_golongan, bg.tmt_pangkat,
               bg.status_sertifikasi, bg.status_inpassing, bg.nomor_sk_inpassing, bg.keterangan_inpassing, bg.foto
        FROM guru g LEFT JOIN buku_induk_guru bg ON bg.guru_id=g.id WHERE g.id=$id_edit_guru LIMIT 1
    ");
    if (!$hasil_edit || mysqli_num_rows($hasil_edit) === 0) {
        die('Data pegawai yang akan diedit tidak ditemukan.');
    }
    $profil_edit = mysqli_fetch_assoc($hasil_edit);
    $bagian = 'pegawai';
}

function nilai_buku_guru($data, $field, $default = '') {
    return htmlspecialchars((string)($data[$field] ?? $default), ENT_QUOTES, 'UTF-8');
}

$jumlah_guru = mysqli_num_rows($daftar_guru);
$hasil_aktif = mysqli_query($conn, "SELECT COUNT(*) AS jumlah FROM guru g LEFT JOIN buku_induk_guru bg ON bg.guru_id=g.id WHERE COALESCE(bg.status_keaktifan, 'Aktif')='Aktif'");
$hasil_non_aktif = mysqli_query($conn, "SELECT COUNT(*) AS jumlah FROM guru g LEFT JOIN buku_induk_guru bg ON bg.guru_id=g.id WHERE COALESCE(bg.status_keaktifan, 'Aktif')='Tidak Aktif'");
$hasil_pendidik = mysqli_query($conn, "SELECT COUNT(*) AS jumlah FROM guru g LEFT JOIN buku_induk_guru bg ON bg.guru_id=g.id WHERE (TRIM(COALESCE(g.jabatan, ''))='Guru Mata Pelajaran' OR TRIM(COALESCE(g.jabatan_tambahan, ''))='Guru Mata Pelajaran') AND COALESCE(bg.status_keaktifan, 'Aktif')='Aktif'");
$hasil_kependidikan = mysqli_query($conn, "SELECT COUNT(*) AS jumlah FROM guru g LEFT JOIN buku_induk_guru bg ON bg.guru_id=g.id WHERE TRIM(COALESCE(g.jabatan, ''))<>'Guru Mata Pelajaran' AND TRIM(COALESCE(g.jabatan_tambahan, ''))<>'Guru Mata Pelajaran' AND COALESCE(bg.status_keaktifan, 'Aktif')='Aktif'");
$hasil_sudah_sertifikasi = mysqli_query($conn, "SELECT COUNT(*) AS jumlah FROM guru g LEFT JOIN buku_induk_guru bg ON bg.guru_id=g.id WHERE (TRIM(COALESCE(g.jabatan, ''))='Guru Mata Pelajaran' OR TRIM(COALESCE(g.jabatan_tambahan, ''))='Guru Mata Pelajaran') AND COALESCE(bg.status_keaktifan, 'Aktif')='Aktif' AND (bg.status_sertifikasi='Sudah' OR EXISTS (SELECT 1 FROM riwayat_buku_guru rb WHERE rb.guru_id=g.id AND rb.kategori='sertifikasi'))");
if (!$hasil_aktif || !$hasil_non_aktif || !$hasil_pendidik || !$hasil_kependidikan || !$hasil_sudah_sertifikasi) {
    die('Ringkasan buku induk guru gagal dimuat: ' . htmlspecialchars(mysqli_error($conn)));
}
$jumlah_aktif = (int)(mysqli_fetch_assoc($hasil_aktif)['jumlah'] ?? 0);
$jumlah_non_aktif = (int)(mysqli_fetch_assoc($hasil_non_aktif)['jumlah'] ?? 0);
$jumlah_pendidik = (int)(mysqli_fetch_assoc($hasil_pendidik)['jumlah'] ?? 0);
$jumlah_kependidikan = (int)(mysqli_fetch_assoc($hasil_kependidikan)['jumlah'] ?? 0);
$jumlah_sudah_sertifikasi = (int)(mysqli_fetch_assoc($hasil_sudah_sertifikasi)['jumlah'] ?? 0);
$label_bagian = [
    'dashboard' => 'Dashboard',
    'pegawai' => 'Data Pegawai',
    'keaktifan' => 'Keaktifan',
    'pendidikan' => 'Pendidikan',
    'keluarga' => 'Data Keluarga',
    'jabatan' => 'Jabatan',
    'penghargaan' => 'Penghargaan',
    'mutasi' => 'Mutasi',
    'sertifikasi' => 'Sertifikasi',
    'info_sertifikasi' => 'Info Sertifikasi/Inpassing',
    'foto' => 'Data Foto',
    'laporan' => 'Laporan'
];
$ikon_bagian = [
    'dashboard' => 'fa-chart-pie',
    'pegawai' => 'fa-address-book',
    'keaktifan' => 'fa-user-check',
    'pendidikan' => 'fa-graduation-cap',
    'keluarga' => 'fa-people-roof',
    'jabatan' => 'fa-briefcase',
    'penghargaan' => 'fa-award',
    'mutasi' => 'fa-right-left',
    'sertifikasi' => 'fa-certificate',
    'info_sertifikasi' => 'fa-address-card',
    'foto' => 'fa-camera',
    'laporan' => 'fa-file-lines'
];
$opsi_jabatan_utama = ['Guru Mata Pelajaran', 'Kepala Madrasah', 'Kepala Tata Usaha', 'Tenaga Kebersihan', 'Tenaga Keamanan', 'Kepala Perpustakaan', 'Kepala Laboratorium / Bengkel / Produksi', 'Staff Tata Usaha', 'Bendahara'];
$opsi_jabatan_tambahan = ['Wakil Kepala Madrasah Bidang Kurikulum', 'Wakil Kepala Madrasah Bidang Kesiswaan', 'Wakil Kepala Madrasah Bidang Sarana dan Prasarana', 'Wakil Kepala Madrasah Bidang Humas', 'Wali Kelas', 'Kepala Perpustakaan', 'Kepala Laboratorium', 'Kepala Tata Usaha', 'Staff Tata Usaha', 'Bendahara', 'Operator Madrasah', 'Staff Perpustakaan', 'Pembina Ekstrakurikuler', 'Koordinator'];
?>

<style>
    .staff-hero { background: linear-gradient(120deg, #0c3924, #198754); }
    .staff-nav { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: .35rem; overflow: visible; padding-bottom: .25rem; }
    .staff-nav .nav-link { min-width: 0; display: flex; align-items: center; justify-content: center; gap: .25rem; white-space: nowrap; text-align: center; padding: .55rem .35rem; line-height: 1.15; color: #495057; background: #f4f5f7; border-radius: .55rem; font-size: .72rem; }
    .staff-nav .nav-link i { flex: 0 0 auto; }
    .staff-nav .nav-link.active { background: #198754; color: #fff; }
    @media (max-width: 767.98px) { .staff-nav { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    .staff-stat { border-left: 4px solid #0f5132; }
    .container-fluid .btn-danger { --bs-btn-bg: #198754; --bs-btn-border-color: #198754; --bs-btn-hover-bg: #157347; --bs-btn-hover-border-color: #146c43; --bs-btn-active-bg: #146c43; --bs-btn-active-border-color: #13653f; }
    .container-fluid .btn-outline-danger { --bs-btn-color: #0f5132; --bs-btn-border-color: #198754; --bs-btn-hover-bg: #198754; --bs-btn-hover-border-color: #198754; --bs-btn-hover-color: #fff; --bs-btn-active-bg: #157347; --bs-btn-active-border-color: #157347; }
    .container-fluid .text-danger { color: #198754 !important; }
    .staff-table { min-width: 850px; }
    .staff-list-table { min-width: 1100px; }
    @media print { .no-print { display: none !important; } .staff-hero { color: #000 !important; background: #fff !important; } }
</style>
<?php if ($bagian === 'info_sertifikasi'): ?>
<style>
    @media print {
        @page { size: 330.2mm 215.9mm; margin: 10mm; }
        .certification-report { font-size: 8pt; }
        .certification-report table { font-size: 7pt; }
    }
</style>
<?php endif; ?>

<?php if ($bagian === 'laporan' && in_array($mode_cetak, ['profil', 'semua'], true)): ?>
<?php
$laporan_guru = [];
if ($mode_cetak === 'profil') {
    $hasil_laporan = mysqli_query($conn, "
        SELECT g.id, g.nama_guru, g.nip, g.jabatan, g.jabatan_tambahan, g.mata_pelajaran,
               bg.nik, bg.nuptk, bg.npk, bg.peg_id, bg.jenis_kelamin, bg.tempat_lahir, bg.tanggal_lahir, bg.agama,
               bg.status_perkawinan, bg.nama_ibu_kandung, bg.alamat, bg.no_hp, bg.email, bg.mata_pelajaran_lainnya, bg.tanggal_masuk, bg.tanggal_mulai_jabatan, bg.tanggal_mulai_jabatan_tambahan,
               bg.status_kepegawaian, bg.status_keaktifan, bg.pendidikan_terakhir, bg.program_studi, bg.perguruan_tinggi,
               bg.tahun_lulus, bg.nama_pasangan, bg.jumlah_anak, bg.pangkat_golongan, bg.tmt_pangkat,
               bg.status_sertifikasi, bg.status_inpassing, bg.nomor_sk_inpassing, bg.keterangan_inpassing, bg.foto
        FROM guru g LEFT JOIN buku_induk_guru bg ON bg.guru_id=g.id WHERE g.id=$filter_guru LIMIT 1
    ");
    if (!$hasil_laporan || mysqli_num_rows($hasil_laporan) !== 1) {
        die('Data guru untuk laporan tidak ditemukan: ' . htmlspecialchars(mysqli_error($conn)));
    }
    $laporan_guru[] = mysqli_fetch_assoc($hasil_laporan);
} else {
    mysqli_data_seek($daftar_guru, 0);
    while ($guru_laporan = mysqli_fetch_assoc($daftar_guru)) {
        $laporan_guru[] = $guru_laporan;
    }
}
$riwayat_laporan = [];
$laporan_guru_aktif = [];
$laporan_guru_non_aktif = [];
$laporan_sertifikasi = [];
$laporan_inpassing = [];
if ($mode_cetak === 'profil') {
    $hasil_riwayat_laporan = mysqli_query($conn, "SELECT kategori, tanggal, judul, instansi, detail, jenjang, fakultas, jurusan, tahun_lulus, lptk, mapel_sertifikasi, nrg, jalur_sertifikasi, jenis_kelamin_keluarga, tempat_lahir_keluarga, pendidikan_anak, status_hubungan_keluarga, pendidikan_keluarga, mutasi_jenis, mutasi_asal, mutasi_tujuan, mutasi_alasan, tahun_riwayat, mata_pelajaran_lainnya FROM riwayat_buku_guru WHERE guru_id=$filter_guru ORDER BY kategori, tanggal, id");
    if (!$hasil_riwayat_laporan) {
        die('Riwayat guru gagal dimuat: ' . htmlspecialchars(mysqli_error($conn)));
    }
    while ($riwayat_item = mysqli_fetch_assoc($hasil_riwayat_laporan)) {
        $riwayat_laporan[$riwayat_item['kategori']][] = $riwayat_item;
    }
    if (empty($laporan_guru[0]['status_sertifikasi']) && !empty($riwayat_laporan['sertifikasi'])) {
        $laporan_guru[0]['status_sertifikasi'] = 'Sudah';
    }
}
if ($mode_cetak === 'semua') {
    foreach ($laporan_guru as $guru_laporan) {
        if (($guru_laporan['status_keaktifan'] ?? 'Aktif') === 'Aktif') {
            $laporan_guru_aktif[] = $guru_laporan;
        } else {
            $laporan_guru_non_aktif[] = $guru_laporan;
        }
    }

    $hasil_laporan_sertifikasi = mysqli_query($conn, "
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
    if (!$hasil_laporan_sertifikasi) {
        die('Data sertifikasi untuk laporan gagal dimuat: ' . htmlspecialchars(mysqli_error($conn)));
    }
    while ($guru_sertifikasi = mysqli_fetch_assoc($hasil_laporan_sertifikasi)) {
        $laporan_sertifikasi[] = $guru_sertifikasi;
    }

    $hasil_laporan_inpassing = mysqli_query($conn, "
        SELECT g.nama_guru, g.nip, bg.nik, bg.npk, bg.status_inpassing,
               bg.pangkat_golongan, bg.tmt_pangkat, bg.nomor_sk_inpassing, bg.keterangan_inpassing
        FROM guru g
        INNER JOIN buku_induk_guru bg ON bg.guru_id=g.id
        WHERE bg.status_inpassing='Sudah'
        ORDER BY g.nama_guru ASC
    ");
    if (!$hasil_laporan_inpassing) {
        die('Data inpassing untuk laporan gagal dimuat: ' . htmlspecialchars(mysqli_error($conn)));
    }
    while ($guru_inpassing = mysqli_fetch_assoc($hasil_laporan_inpassing)) {
        $laporan_inpassing[] = $guru_inpassing;
    }
}
?>
<style>
    @page { size: <?php echo $mode_cetak === 'semua' ? '330.2mm 215.9mm' : '215.9mm 330.2mm'; ?>; margin: 10mm; }
    body { background: #fff !important; color: #111 !important; font: 10pt Arial, sans-serif !important; }
    .print-report { max-width: 100%; }
    .print-report h1 { font-size: 16pt; text-align: center; margin: 0 0 2mm; }
    .print-report h2 { font-size: 12pt; border-bottom: 1px solid #333; padding-bottom: 1mm; margin: 5mm 0 2mm; }
    .print-report h3 { font-size: 10pt; font-weight: 700; margin: 3mm 0 1mm; }
    .print-report table { width: 100%; border-collapse: collapse; }
    .print-report th, .print-report td { border: 1px solid #555; padding: 1.5mm; vertical-align: top; }
    .print-report .identity td { border: 0; padding: 1mm; }
    .print-report .profile-photo { max-width: 30mm; max-height: 40mm; object-fit: contain; }
    .print-report .page-break { break-after: page; page-break-after: always; }
    @media print {
        .no-print { display: none !important; }
        .sidebar, .top-navbar { display: none !important; }
        .main-wrapper { margin: 0 !important; }
        .content-body { padding: 0 !important; }
        .print-list { font-size: 8pt; }
        .print-list th, .print-list td { padding: 1.3mm; }
        .print-list-details { font-size: 7pt; }
        .print-list-details th, .print-list-details td { padding: 1mm; }
        .print-list thead { display: table-header-group; }
        .print-list tr { break-inside: avoid; page-break-inside: avoid; }
    }
</style>
<div class="container-fluid print-report">
    <div class="d-flex justify-content-end align-items-center gap-3 mb-3 no-print">
        <small>Pilih "Save as PDF" pada pilihan printer untuk menyimpan sebagai PDF.</small>
        <button class="btn btn-primary" onclick="window.print()"><i class="fa-solid fa-print me-1"></i>Cetak / Simpan PDF</button>
    </div>
    <?php if ($mode_cetak === 'semua'): ?>
    <h1><?php echo nilai_buku_guru($identitas_madrasah, 'nama_madrasah'); ?></h1>
    <div class="text-center mb-3">NSM: <?php echo nilai_buku_guru($identitas_madrasah, 'nsm'); ?> | NPSN: <?php echo nilai_buku_guru($identitas_madrasah, 'npsn'); ?><br><?php echo nilai_buku_guru($identitas_madrasah, 'alamat'); ?><br>Daftar Seluruh Guru dan Tenaga Kependidikan</div>
    <?php foreach (['Aktif' => $laporan_guru_aktif, 'Nonaktif' => $laporan_guru_non_aktif] as $judul_status => $daftar_status): ?>
    <section class="print-list-section">
        <h2><?php echo htmlspecialchars($judul_status); ?> (<?php echo count($daftar_status); ?>)</h2>
        <table class="print-list">
            <thead><tr><th>No.</th><th>Nama</th><th>Tempat Lahir</th><th>Tanggal Lahir</th><th>NIP / NUPTK</th><th>NIK</th><th>NPK</th><th>NRG</th><th>Jabatan</th><th>Mata Pelajaran</th><th>Kontak</th></tr></thead>
            <tbody><?php foreach ($daftar_status as $nomor => $guru_laporan): ?>
                <tr>
                    <td><?php echo $nomor + 1; ?></td><td><?php echo nilai_buku_guru($guru_laporan, 'nama_guru'); ?></td>
                    <td><?php echo nilai_buku_guru($guru_laporan, 'tempat_lahir', '-'); ?></td>
                    <td><?php echo !empty($guru_laporan['tanggal_lahir']) && $guru_laporan['tanggal_lahir'] !== '0000-00-00' ? date('d-m-Y', strtotime($guru_laporan['tanggal_lahir'])) : '-'; ?></td>
                    <td><?php echo nilai_buku_guru($guru_laporan, 'nip', '-'); ?><br><?php echo nilai_buku_guru($guru_laporan, 'nuptk'); ?></td>
                    <td><?php echo nilai_buku_guru($guru_laporan, 'nik', '-'); ?></td>
                    <td><?php echo nilai_buku_guru($guru_laporan, 'npk', '-'); ?></td>
                    <td><?php echo nilai_buku_guru($guru_laporan, 'nrg', '-'); ?></td>
                    <td><?php echo nilai_buku_guru($guru_laporan, 'jabatan'); ?><br><?php echo nilai_buku_guru($guru_laporan, 'jabatan_tambahan'); ?></td>
                    <td><?php echo nilai_buku_guru($guru_laporan, 'mata_pelajaran'); ?><?php if (!empty($guru_laporan['mata_pelajaran_lainnya'])): ?><br><?php echo nilai_buku_guru($guru_laporan, 'mata_pelajaran_lainnya'); ?><?php endif; ?></td>
                    <td><?php echo nilai_buku_guru($guru_laporan, 'no_hp'); ?><br><?php echo nilai_buku_guru($guru_laporan, 'email'); ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($daftar_status)): ?><tr><td colspan="11" class="text-center">Tidak ada guru pada daftar ini.</td></tr><?php endif; ?></tbody>
        </table>
    </section>
    <?php endforeach; ?>
    <section class="print-list-section page-break">
        <h2>Sertifikasi (<?php echo count($laporan_sertifikasi); ?>)</h2>
        <table class="print-list print-list-details">
            <thead><tr><th>No.</th><th>Nama Guru</th><th>NIP</th><th>NIK</th><th>NPK</th><th>Status</th><th>No. Piagam</th><th>NRG</th><th>Mata Pelajaran</th><th>Badan Sertifikasi</th><th>Jalur</th><th>Tanggal Piagam</th></tr></thead>
            <tbody><?php foreach ($laporan_sertifikasi as $nomor => $guru_sertifikasi): ?>
                <tr>
                    <td><?php echo $nomor + 1; ?></td>
                    <td><?php echo nilai_buku_guru($guru_sertifikasi, 'nama_guru'); ?></td>
                    <td><?php echo nilai_buku_guru($guru_sertifikasi, 'nip', '-'); ?></td>
                    <td><?php echo nilai_buku_guru($guru_sertifikasi, 'nik', '-'); ?></td>
                    <td><?php echo nilai_buku_guru($guru_sertifikasi, 'npk', '-'); ?></td>
                    <td><?php echo (($guru_sertifikasi['status_sertifikasi'] ?? '') === 'Sudah' || !empty($guru_sertifikasi['no_piagam'])) ? 'Sudah' : '-'; ?></td>
                    <td><?php echo nilai_buku_guru($guru_sertifikasi, 'no_piagam', '-'); ?></td>
                    <td><?php echo nilai_buku_guru($guru_sertifikasi, 'nrg', '-'); ?></td>
                    <td><?php echo nilai_buku_guru($guru_sertifikasi, 'mapel_sertifikasi', '-'); ?></td>
                    <td><?php echo nilai_buku_guru($guru_sertifikasi, 'badan_sertifikasi', '-'); ?></td>
                    <td><?php echo nilai_buku_guru($guru_sertifikasi, 'jalur_sertifikasi', '-'); ?></td>
                    <td><?php echo !empty($guru_sertifikasi['tanggal_piagam']) ? date('d-m-Y', strtotime($guru_sertifikasi['tanggal_piagam'])) : '-'; ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($laporan_sertifikasi)): ?><tr><td colspan="12" class="text-center">Belum ada data sertifikasi.</td></tr><?php endif; ?></tbody>
        </table>
    </section>
    <section>
        <h2>Inpassing (<?php echo count($laporan_inpassing); ?>)</h2>
        <table class="print-list print-list-details">
            <thead><tr><th>No.</th><th>Nama Guru</th><th>NIP</th><th>NIK</th><th>NPK</th><th>Status</th><th>Pangkat / Golongan</th><th>TMT</th><th>Nomor SK</th><th>Keterangan</th></tr></thead>
            <tbody><?php foreach ($laporan_inpassing as $nomor => $guru_inpassing): ?>
                <tr>
                    <td><?php echo $nomor + 1; ?></td>
                    <td><?php echo nilai_buku_guru($guru_inpassing, 'nama_guru'); ?></td>
                    <td><?php echo nilai_buku_guru($guru_inpassing, 'nip', '-'); ?></td>
                    <td><?php echo nilai_buku_guru($guru_inpassing, 'nik', '-'); ?></td>
                    <td><?php echo nilai_buku_guru($guru_inpassing, 'npk', '-'); ?></td>
                    <td><?php echo nilai_buku_guru($guru_inpassing, 'status_inpassing', '-'); ?></td>
                    <td><?php echo nilai_buku_guru($guru_inpassing, 'pangkat_golongan', '-'); ?></td>
                    <td><?php echo !empty($guru_inpassing['tmt_pangkat']) && $guru_inpassing['tmt_pangkat'] !== '0000-00-00' ? date('d-m-Y', strtotime($guru_inpassing['tmt_pangkat'])) : '-'; ?></td>
                    <td><?php echo nilai_buku_guru($guru_inpassing, 'nomor_sk_inpassing', '-'); ?></td>
                    <td><?php echo nilai_buku_guru($guru_inpassing, 'keterangan_inpassing', '-'); ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($laporan_inpassing)): ?><tr><td colspan="10" class="text-center">Belum ada data inpassing.</td></tr><?php endif; ?></tbody>
        </table>
    </section>
    <?php else: ?>
    <?php
    $label_profil = [
        'nip' => 'NIP / No. Induk', 'nik' => 'NIK', 'nuptk' => 'NUPTK', 'npk' => 'NPK', 'peg_id' => 'PegID', 'jenis_kelamin' => 'Jenis Kelamin',
        'tempat_lahir' => 'Tempat Lahir', 'tanggal_lahir' => 'Tanggal Lahir', 'agama' => 'Agama',
        'status_perkawinan' => 'Status Perkawinan', 'nama_ibu_kandung' => 'Nama Ibu Kandung', 'alamat' => 'Alamat', 'no_hp' => 'Nomor HP',
        'email' => 'Email', 'jabatan' => 'Jabatan Utama', 'tanggal_mulai_jabatan' => 'Tanggal Mulai Jabatan Utama',
        'jabatan_tambahan' => 'Jabatan Tambahan', 'tanggal_mulai_jabatan_tambahan' => 'Tanggal Mulai Jabatan Tambahan',
        'mata_pelajaran' => 'Mata Pelajaran Utama', 'mata_pelajaran_lainnya' => 'Mata Pelajaran Lainnya', 'status_kepegawaian' => 'Status Kepegawaian',
        'status_keaktifan' => 'Status Keaktifan',
        'tanggal_masuk' => 'Tanggal Mulai Bertugas'
    ];
    $kategori_laporan = ['pendidikan' => 'Riwayat Pendidikan', 'keluarga' => 'Data Keluarga', 'jabatan' => 'Riwayat Jabatan', 'penghargaan' => 'Penghargaan', 'mutasi' => 'Mutasi', 'sertifikasi' => 'Sertifikasi Guru'];
    ?>
    <?php foreach ($laporan_guru as $guru_laporan): ?>
    <section>
        <h1><?php echo nilai_buku_guru($identitas_madrasah, 'nama_madrasah'); ?></h1>
        <div class="text-center mb-3">NSM: <?php echo nilai_buku_guru($identitas_madrasah, 'nsm'); ?> | NPSN: <?php echo nilai_buku_guru($identitas_madrasah, 'npsn'); ?><br><?php echo nilai_buku_guru($identitas_madrasah, 'alamat'); ?><br>Buku Induk Guru dan Tenaga Kependidikan</div>
        <table class="identity"><tr><td><strong><?php echo nilai_buku_guru($guru_laporan, 'nama_guru'); ?></strong><br><?php echo nilai_buku_guru($guru_laporan, 'jabatan'); ?></td>
        <td class="text-end"><?php if (!empty($guru_laporan['foto'])): ?><img class="profile-photo" src="<?php echo nilai_buku_guru($guru_laporan, 'foto'); ?>" alt="Foto guru"><?php endif; ?></td></tr></table>
        <h2>Identitas dan Kepegawaian</h2>
        <table><tbody><?php foreach ($label_profil as $field => $label): ?><tr><th style="width:32%"><?php echo htmlspecialchars($label); ?></th><td><?php echo nl2br(nilai_buku_guru($guru_laporan, $field, '-')); ?></td></tr><?php endforeach; ?></tbody></table>
        <?php foreach ($kategori_laporan as $kategori => $judul_kategori): ?>
        <h2><?php echo htmlspecialchars($judul_kategori); ?></h2>
        <?php if ($kategori === 'pendidikan'): ?>
        <table><thead><tr><th>Jenjang</th><th>Perguruan Tinggi</th><th>Fakultas</th><th>Jurusan</th><th>Tahun Lulus</th><th>LPTK</th></tr></thead><tbody>
            <?php foreach (($riwayat_laporan[$kategori] ?? []) as $item): ?>
            <tr><td><?php echo nilai_buku_guru($item, 'jenjang', $item['judul'] ?? '-'); ?></td><td><?php echo nilai_buku_guru($item, 'instansi', '-'); ?></td><td><?php echo nilai_buku_guru($item, 'fakultas', '-'); ?></td><td><?php echo nilai_buku_guru($item, 'jurusan', '-'); ?></td><td><?php echo nilai_buku_guru($item, 'tahun_lulus', !empty($item['tanggal']) ? date('Y', strtotime($item['tanggal'])) : '-'); ?></td><td><?php echo nilai_buku_guru($item, 'lptk', '-'); ?></td></tr>
            <?php endforeach; ?>
            <?php if (empty($riwayat_laporan[$kategori])): ?><tr><td colspan="6">Belum ada data.</td></tr><?php endif; ?>
        </tbody></table>
        <?php elseif ($kategori === 'keluarga'): ?>
        <?php
        $laporan_pasangan = [];
        $laporan_anak = [];
        $laporan_keluarga_lain = [];
        foreach (($riwayat_laporan[$kategori] ?? []) as $item_keluarga) {
            $hubungan = strtolower(trim((string)($item_keluarga['instansi'] ?? '')));
            if (in_array($hubungan, ['suami', 'istri', 'pasangan', 'suami/istri'], true)) {
                $laporan_pasangan[] = $item_keluarga;
            } elseif ($hubungan === 'anak') {
                $laporan_anak[] = $item_keluarga;
            } else {
                $laporan_keluarga_lain[] = $item_keluarga;
            }
        }
        ?>
        <h3>Suami/Istri</h3>
        <table><thead><tr><th>Nama Suami/Istri</th><th>Tempat, Tanggal Lahir</th><th>Pendidikan</th><th>Pekerjaan</th></tr></thead><tbody>
            <?php foreach ($laporan_pasangan as $item): ?><tr><td><?php echo nilai_buku_guru($item, 'judul'); ?></td><td><?php echo nilai_buku_guru($item, 'tempat_lahir_keluarga', '-'); ?><?php if (!empty($item['tanggal'])): ?>, <?php echo nilai_buku_guru($item, 'tanggal'); ?><?php endif; ?></td><td><?php echo nilai_buku_guru($item, 'pendidikan_keluarga', '-'); ?></td><td><?php echo nilai_buku_guru($item, 'detail', '-'); ?></td></tr><?php endforeach; ?>
            <?php if (empty($laporan_pasangan)): ?><tr><td colspan="4">Belum ada data.</td></tr><?php endif; ?>
        </tbody></table>
        <h3>Anak Kandung</h3>
        <table><thead><tr><th>Nama</th><th>Jenis Kelamin</th><th>Tempat, Tanggal Lahir</th><th>Pendidikan</th><th>Pekerjaan</th></tr></thead><tbody>
            <?php foreach ($laporan_anak as $item): ?><tr><td><?php echo nilai_buku_guru($item, 'judul'); ?></td><td><?php echo nilai_buku_guru($item, 'jenis_kelamin_keluarga', '-'); ?></td><td><?php echo nilai_buku_guru($item, 'tempat_lahir_keluarga', '-'); ?><?php if (!empty($item['tanggal'])): ?>, <?php echo nilai_buku_guru($item, 'tanggal'); ?><?php endif; ?></td><td><?php echo nilai_buku_guru($item, 'pendidikan_anak', '-'); ?></td><td><?php echo nilai_buku_guru($item, 'detail', '-'); ?></td></tr><?php endforeach; ?>
            <?php if (empty($laporan_anak)): ?><tr><td colspan="5">Belum ada data.</td></tr><?php endif; ?>
        </tbody></table>
        <?php if (!empty($laporan_keluarga_lain)): ?>
        <h3>Data Keluarga Lainnya</h3>
        <table><thead><tr><th>Nama Anggota Keluarga</th><th>Tanggal</th><th>Hubungan</th><th>Keterangan</th></tr></thead><tbody>
            <?php foreach ($laporan_keluarga_lain as $item): ?><tr><td><?php echo nilai_buku_guru($item, 'judul'); ?></td><td><?php echo nilai_buku_guru($item, 'tanggal', '-'); ?></td><td><?php echo nilai_buku_guru($item, 'instansi', '-'); ?></td><td><?php echo nilai_buku_guru($item, 'detail', '-'); ?></td></tr><?php endforeach; ?>
        </tbody></table>
        <?php endif; ?>
        <?php elseif ($kategori === 'jabatan'): ?>
        <table><thead><tr><th>Tahun</th><th>Jabatan</th><th>Jabatan Tambahan</th><th>Mata Pelajaran</th></tr></thead><tbody>
            <?php foreach (($riwayat_laporan[$kategori] ?? []) as $item): ?>
            <tr><td><?php echo nilai_buku_guru($item, 'tahun_riwayat', !empty($item['tanggal']) ? date('Y', strtotime($item['tanggal'])) : '-'); ?></td><td><?php echo nilai_buku_guru($item, 'judul'); ?></td><td><?php echo nilai_buku_guru($item, 'instansi', '-'); ?></td><td><?php echo nilai_buku_guru($item, 'detail', '-'); ?><?php if (!empty($item['mata_pelajaran_lainnya'])): ?><br><?php echo nilai_buku_guru($item, 'mata_pelajaran_lainnya'); ?><?php endif; ?></td></tr>
            <?php endforeach; ?>
            <?php if (empty($riwayat_laporan[$kategori])): ?><tr><td colspan="4">Belum ada data.</td></tr><?php endif; ?>
        </tbody></table>
        <?php elseif ($kategori === 'mutasi'): ?>
        <?php
        $mutasi_masuk_laporan = [];
        $mutasi_keluar_laporan = [];
        foreach (($riwayat_laporan[$kategori] ?? []) as $item_mutasi) {
            $jenis_mutasi_laporan = strtolower(trim((string)($item_mutasi['mutasi_jenis'] ?? '')));
            if ($jenis_mutasi_laporan === 'keluar' || (strpos(strtolower((string)($item_mutasi['judul'] ?? '')), 'keluar') !== false)) {
                $mutasi_keluar_laporan[] = $item_mutasi;
            } else {
                $mutasi_masuk_laporan[] = $item_mutasi;
            }
        }
        ?>
        <h3>Mutasi Masuk</h3>
        <table><thead><tr><th>Tanggal Mutasi Masuk</th><th>Asal Sekolah Sebelumnya</th></tr></thead><tbody>
            <?php foreach ($mutasi_masuk_laporan as $item): ?><tr><td><?php echo !empty($item['tanggal']) ? date('d-m-Y', strtotime($item['tanggal'])) : '-'; ?></td><td><?php echo nilai_buku_guru($item, 'mutasi_asal', $item['instansi'] ?? '-'); ?></td></tr><?php endforeach; ?>
            <?php if (empty($mutasi_masuk_laporan)): ?><tr><td colspan="2">Belum ada data mutasi masuk.</td></tr><?php endif; ?>
        </tbody></table>
        <h3>Mutasi Keluar</h3>
        <table><thead><tr><th>Tanggal Mutasi Keluar</th><th>Tujuan</th><th>Alasan</th></tr></thead><tbody>
            <?php foreach ($mutasi_keluar_laporan as $item): ?><tr><td><?php echo !empty($item['tanggal']) ? date('d-m-Y', strtotime($item['tanggal'])) : ''; ?></td><td><?php echo nilai_buku_guru($item, 'mutasi_tujuan', $item['instansi'] ?? ''); ?></td><td><?php echo nl2br(nilai_buku_guru($item, 'mutasi_alasan', $item['detail'] ?? '')); ?></td></tr><?php endforeach; ?>
            <?php for ($baris_mutasi_kosong = 0; $baris_mutasi_kosong < 1; $baris_mutasi_kosong++): ?><tr><td style="height:9mm">&nbsp;</td><td></td><td></td></tr><?php endfor; ?>
        </tbody></table>
        <?php elseif ($kategori === 'sertifikasi'): ?>
        <h3>Status Sertifikasi dan Inpassing</h3>
        <table><tbody>
            <tr><th style="width:32%">Status Sertifikasi</th><td><?php echo nilai_buku_guru($guru_laporan, 'status_sertifikasi', '-'); ?></td></tr>
            <?php if (($guru_laporan['status_sertifikasi'] ?? '') === 'Sudah' && !empty($riwayat_laporan['sertifikasi'])): ?>
            <tr><th>Status Inpassing</th><td><?php echo nilai_buku_guru($guru_laporan, 'status_inpassing', '-'); ?></td></tr>
            <?php if (($guru_laporan['status_inpassing'] ?? '') === 'Sudah'): ?>
            <tr><th>Pangkat / Golongan</th><td><?php echo nilai_buku_guru($guru_laporan, 'pangkat_golongan', '-'); ?></td></tr>
            <tr><th>TMT Inpassing</th><td><?php echo nilai_buku_guru($guru_laporan, 'tmt_pangkat', '-'); ?></td></tr>
            <tr><th>Nomor SK Inpassing</th><td><?php echo nilai_buku_guru($guru_laporan, 'nomor_sk_inpassing', '-'); ?></td></tr>
            <tr><th>Keterangan</th><td><?php echo nl2br(nilai_buku_guru($guru_laporan, 'keterangan_inpassing', '-')); ?></td></tr>
            <?php endif; endif; ?>
        </tbody></table>
        <table><thead><tr><th>No. Piagam</th><th>NRG</th><th>Mapel Sertifikasi</th><th>Badan Sertifikasi</th><th>Jalur</th><th>Tanggal Piagam</th></tr></thead><tbody>
            <?php foreach (($riwayat_laporan[$kategori] ?? []) as $item): ?>
            <tr><td><?php echo nilai_buku_guru($item, 'judul'); ?></td><td><?php echo nilai_buku_guru($item, 'nrg', '-'); ?></td><td><?php echo nilai_buku_guru($item, 'mapel_sertifikasi', '-'); ?></td><td><?php echo nilai_buku_guru($item, 'instansi', '-'); ?></td><td><?php echo nilai_buku_guru($item, 'jalur_sertifikasi', '-'); ?></td><td><?php echo nilai_buku_guru($item, 'tanggal', '-'); ?></td></tr>
            <?php endforeach; ?>
            <?php if (empty($riwayat_laporan[$kategori])): ?><tr><td colspan="5">Belum ada data.</td></tr><?php endif; ?>
        </tbody></table>
        <?php if (!empty($riwayat_laporan['pangkat'])): ?>
        <h3>Riwayat Pangkat Sebelumnya</h3>
        <table><thead><tr><th>TMT</th><th>Pangkat / Golongan</th><th>Nomor SK / Instansi</th><th>Keterangan</th></tr></thead><tbody>
            <?php foreach ($riwayat_laporan['pangkat'] as $item): ?><tr><td><?php echo nilai_buku_guru($item, 'tanggal', '-'); ?></td><td><?php echo nilai_buku_guru($item, 'judul'); ?></td><td><?php echo nilai_buku_guru($item, 'instansi', '-'); ?></td><td><?php echo nilai_buku_guru($item, 'detail', '-'); ?></td></tr><?php endforeach; ?>
        </tbody></table>
        <?php endif; ?>
        <?php else: ?>
        <table><thead><tr><th>Tanggal</th><th>Nama / Judul</th><th>Instansi / Hubungan</th><th>Detail</th></tr></thead><tbody>
            <?php foreach (($riwayat_laporan[$kategori] ?? []) as $item): ?>
            <tr><td><?php echo nilai_buku_guru($item, 'tanggal', '-'); ?></td><td><?php echo nilai_buku_guru($item, 'judul'); ?></td><td><?php echo nilai_buku_guru($item, 'instansi', '-'); ?></td><td><?php echo nl2br(nilai_buku_guru($item, 'detail', '-')); ?></td></tr>
            <?php endforeach; ?>
            <?php if (empty($riwayat_laporan[$kategori])): ?><tr><td colspan="4">Belum ada data.</td></tr><?php endif; ?>
        </tbody></table>
        <?php endif; ?>
        <?php endforeach; ?>
    </section>
    <?php endforeach; ?>
    <?php endif; ?>
</div>
<script>window.addEventListener('load', function () { window.print(); });</script>
<?php else: ?>
<div class="container-fluid px-0">
    <div class="staff-hero text-white rounded-3 p-4 mb-4 shadow-sm">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <div class="small opacity-75 text-uppercase fw-semibold">Buku Induk Kepegawaian</div>
                <h2 class="h4 fw-bold mb-1">Buku Induk Guru &amp; Tenaga Kependidikan</h2>
                <div class="small"><?php echo nilai_buku_guru($identitas_madrasah, 'nama_madrasah', "MA Ma'arif Darul Aman Pringsurat"); ?></div>
                <div class="small opacity-75"><?php echo nilai_buku_guru($identitas_madrasah, 'alamat', 'Pringsurat, Temanggung'); ?></div>
            </div>
            <div class="d-flex gap-2 no-print">
                <a href="index.php?page=data_guru" class="btn btn-light btn-sm"><i class="fa-solid fa-list me-1"></i>Data Guru Lama</a>
                <a href="index.php?page=pengaturan" class="btn btn-outline-light btn-sm"><i class="fa-solid fa-gear me-1"></i>Identitas Madrasah</a>
            </div>
        </div>
    </div>

    <?php if ($notifikasi !== ''): ?>
    <div class="alert alert-<?php echo $status_notifikasi === 'tersimpan' ? 'success' : 'danger'; ?> alert-dismissible fade show shadow-sm" role="alert">
        <?php echo htmlspecialchars($notifikasi); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
    <?php endif; ?>

    <?php if (($_SESSION['role'] ?? '') !== 'guru'): ?>
    <div class="card border-0 shadow-sm mb-4 no-print">
        <div class="card-body p-3">
            <nav class="nav staff-nav">
                <?php foreach ($label_bagian as $key => $label): ?>
                <a class="nav-link <?php echo $bagian === $key ? 'active' : ''; ?>" href="index.php?page=buku_induk_guru&amp;bagian=<?php echo urlencode($key); ?>">
                    <i class="fa-solid <?php echo $ikon_bagian[$key]; ?> me-1"></i><?php echo htmlspecialchars($label); ?>
                </a>
                <?php endforeach; ?>
            </nav>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($bagian === 'dashboard'): ?>
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl"><div class="card staff-stat border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small">Jumlah Pegawai</div><div class="fs-2 fw-bold text-danger"><?php echo $jumlah_guru; ?></div></div></div></div>
        <div class="col-6 col-xl"><div class="card staff-stat border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small">Pegawai Aktif</div><div class="fs-2 fw-bold text-success"><?php echo $jumlah_aktif; ?></div></div></div></div>
        <div class="col-6 col-xl"><div class="card staff-stat border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small">Pegawai Nonaktif</div><div class="fs-2 fw-bold text-secondary"><?php echo $jumlah_non_aktif; ?></div></div></div></div>
        <div class="col-6 col-xl"><div class="card staff-stat border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small">Guru Sudah Sertifikasi</div><div class="fs-2 fw-bold text-primary"><?php echo $jumlah_sudah_sertifikasi; ?></div></div></div></div>
        <div class="col-6 col-xl"><div class="card staff-stat border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small">Jumlah Tenaga Pendidik</div><div class="fs-2 fw-bold text-info"><?php echo $jumlah_pendidik; ?></div></div></div></div>
        <div class="col-6 col-xl"><div class="card staff-stat border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small">Jumlah Tenaga Kependidikan</div><div class="fs-2 fw-bold text-warning"><?php echo $jumlah_kependidikan; ?></div></div></div></div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-bold"><i class="fa-solid fa-school text-danger me-2"></i>Identitas Madrasah</div>
                <div class="card-body">
                    <dl class="row small mb-0">
                        <dt class="col-sm-4">Nama</dt><dd class="col-sm-8"><?php echo nilai_buku_guru($identitas_madrasah, 'nama_madrasah'); ?></dd>
                        <dt class="col-sm-4">NSM / NPSN</dt><dd class="col-sm-8"><?php echo nilai_buku_guru($identitas_madrasah, 'nsm'); ?> / <?php echo nilai_buku_guru($identitas_madrasah, 'npsn'); ?></dd>
                        <dt class="col-sm-4">Alamat</dt><dd class="col-sm-8"><?php echo nilai_buku_guru($identitas_madrasah, 'alamat'); ?></dd>
                        <dt class="col-sm-4">Kontak</dt><dd class="col-sm-8"><?php echo nilai_buku_guru($identitas_madrasah, 'telepon'); ?><br><?php echo nilai_buku_guru($identitas_madrasah, 'email'); ?></dd>
                    </dl>
                    <a href="index.php?page=pengaturan" class="btn btn-sm btn-outline-danger mt-3 no-print">Ubah Identitas Madrasah</a>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-bold"><i class="fa-solid fa-compass text-danger me-2"></i>Bagian Buku Induk</div>
                <div class="card-body">
                    <div class="row g-3">
                        <?php foreach (['pegawai', 'keaktifan', 'pendidikan', 'keluarga', 'jabatan', 'sertifikasi', 'penghargaan', 'mutasi', 'foto', 'laporan'] as $key): ?>
                        <div class="col-6 col-md-4">
                            <a class="btn btn-outline-secondary w-100 text-start" href="index.php?page=buku_induk_guru&amp;bagian=<?php echo urlencode($key); ?>">
                                <i class="fa-solid <?php echo $ikon_bagian[$key]; ?> text-danger me-2"></i><?php echo htmlspecialchars($label_bagian[$key]); ?>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($bagian === 'pegawai'): ?>
        <?php if ($id_edit_guru > 0 || isset($_GET['tambah'])): ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h3 class="h6 fw-bold mb-0"><?php echo $profil_edit ? 'Edit Profil Pegawai' : 'Tambah Pegawai'; ?></h3>
                <a href="index.php?page=buku_induk_guru&amp;bagian=pegawai" class="btn-close" aria-label="Tutup"></a>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="simpan_profil_guru" value="1">
                    <input type="hidden" name="guru_id" value="<?php echo (int)($profil_edit['id'] ?? 0); ?>">
                    <ul class="nav nav-tabs mb-3" role="tablist">
                        <li class="nav-item"><span class="nav-link active">Identitas</span></li>
                    </ul>
                    <section id="profil-identitas">
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label">Nama lengkap dan gelar *</label><input class="form-control" name="nama_guru" value="<?php echo nilai_buku_guru($profil_edit, 'nama_guru'); ?>" required></div>
                            <div class="col-md-3"><label class="form-label">NIP / No. Induk</label><input class="form-control" name="nip" value="<?php echo nilai_buku_guru($profil_edit, 'nip', '-'); ?>"></div>
                            <div class="col-md-3"><label class="form-label">NIK</label><input class="form-control" name="nik" maxlength="20" value="<?php echo nilai_buku_guru($profil_edit, 'nik'); ?>"></div>
                            <div class="col-md-4"><label class="form-label">NUPTK</label><input class="form-control" name="nuptk" value="<?php echo nilai_buku_guru($profil_edit, 'nuptk'); ?>"></div>
                            <div class="col-md-4"><label class="form-label">NPK</label><input class="form-control" name="npk" value="<?php echo nilai_buku_guru($profil_edit, 'npk'); ?>"></div>
                            <div class="col-md-4"><label class="form-label">PegID</label><input class="form-control" name="peg_id" value="<?php echo nilai_buku_guru($profil_edit, 'peg_id'); ?>"></div>
                            <div class="col-md-4"><label class="form-label">Jenis kelamin</label><select class="form-select" name="jenis_kelamin"><option value="">Pilih</option><?php foreach (['Laki-laki', 'Perempuan'] as $jk): ?><option value="<?php echo $jk; ?>" <?php echo (($profil_edit['jenis_kelamin'] ?? '') === $jk) ? 'selected' : ''; ?>><?php echo $jk; ?></option><?php endforeach; ?></select></div>
                            <div class="col-md-4"><label class="form-label">Agama</label><select class="form-select" name="agama"><option value="">Pilih agama</option><?php $opsi_agama = ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu']; $agama_sekarang = (string)($profil_edit['agama'] ?? ''); if ($agama_sekarang !== '' && !in_array($agama_sekarang, $opsi_agama, true)): ?><option value="<?php echo htmlspecialchars($agama_sekarang); ?>" selected><?php echo htmlspecialchars($agama_sekarang); ?></option><?php endif; foreach ($opsi_agama as $agama): ?><option value="<?php echo htmlspecialchars($agama); ?>" <?php echo $agama_sekarang === $agama ? 'selected' : ''; ?>><?php echo htmlspecialchars($agama); ?></option><?php endforeach; ?></select></div>
                            <div class="col-md-4"><label class="form-label">Tempat lahir</label><input class="form-control" name="tempat_lahir" value="<?php echo nilai_buku_guru($profil_edit, 'tempat_lahir'); ?>"></div>
                            <div class="col-md-4"><label class="form-label">Tanggal lahir</label><input type="date" class="form-control" name="tanggal_lahir" value="<?php echo nilai_buku_guru($profil_edit, 'tanggal_lahir'); ?>"></div>
                            <div class="col-md-4"><label class="form-label">Status perkawinan</label><select class="form-select" name="status_perkawinan"><option value="">Pilih status perkawinan</option><?php $opsi_perkawinan = ['Belum Kawin', 'Kawin', 'Cerai Hidup', 'Cerai Mati']; $status_perkawinan_sekarang = (string)($profil_edit['status_perkawinan'] ?? ''); if ($status_perkawinan_sekarang !== '' && !in_array($status_perkawinan_sekarang, $opsi_perkawinan, true)): ?><option value="<?php echo htmlspecialchars($status_perkawinan_sekarang); ?>" selected><?php echo htmlspecialchars($status_perkawinan_sekarang); ?></option><?php endif; foreach ($opsi_perkawinan as $status_perkawinan): ?><option value="<?php echo htmlspecialchars($status_perkawinan); ?>" <?php echo $status_perkawinan_sekarang === $status_perkawinan ? 'selected' : ''; ?>><?php echo htmlspecialchars($status_perkawinan); ?></option><?php endforeach; ?></select></div>
                            <div class="col-md-4"><label class="form-label">Nama Ibu Kandung</label><input class="form-control" name="nama_ibu_kandung" value="<?php echo nilai_buku_guru($profil_edit, 'nama_ibu_kandung'); ?>"></div>
                            <div class="col-md-6"><label class="form-label">Nomor HP</label><input class="form-control" name="no_hp" value="<?php echo nilai_buku_guru($profil_edit, 'no_hp'); ?>"></div>
                            <div class="col-md-6"><label class="form-label">Email</label><input type="email" class="form-control" name="email" value="<?php echo nilai_buku_guru($profil_edit, 'email'); ?>"></div>
                            <div class="col-12"><label class="form-label">Alamat</label><textarea class="form-control" name="alamat" rows="2"><?php echo nilai_buku_guru($profil_edit, 'alamat'); ?></textarea></div>
                        </div>
                    </section>
                    <section class="border-top mt-4 pt-3">
                        <h4 class="h6 fw-bold mb-3">Informasi Kepegawaian</h4>
                        <div class="row g-3">
                            <?php
                            $jabatan_utama_sekarang = (string)($profil_edit['jabatan'] ?? 'Guru Mata Pelajaran');
                            $jabatan_tambahan_sekarang = (string)($profil_edit['jabatan_tambahan'] ?? '-');
                            ?>
                            <div class="col-md-4"><label class="form-label">Jabatan utama</label><select class="form-select" name="jabatan" required><?php if ($jabatan_utama_sekarang === 'Tenaga Kependidikan' || $jabatan_utama_sekarang === ''): ?><option value="" selected>Pilih jabatan utama</option><?php endif; ?><?php if ($jabatan_utama_sekarang !== '' && $jabatan_utama_sekarang !== 'Tenaga Kependidikan' && !in_array($jabatan_utama_sekarang, $opsi_jabatan_utama, true)): ?><option value="<?php echo htmlspecialchars($jabatan_utama_sekarang); ?>" selected><?php echo htmlspecialchars($jabatan_utama_sekarang); ?></option><?php endif; ?><?php foreach ($opsi_jabatan_utama as $opsi_jabatan): ?><option value="<?php echo htmlspecialchars($opsi_jabatan); ?>" <?php echo $jabatan_utama_sekarang === $opsi_jabatan ? 'selected' : ''; ?>><?php echo htmlspecialchars($opsi_jabatan); ?></option><?php endforeach; ?></select></div>
                            <div class="col-md-4"><label class="form-label">Tanggal mulai jabatan utama</label><input type="date" class="form-control" name="tanggal_mulai_jabatan" value="<?php echo nilai_buku_guru($profil_edit, 'tanggal_mulai_jabatan'); ?>"></div>
                            <div class="col-md-4"><label class="form-label">Jabatan tambahan</label><select class="form-select" name="jabatan_tambahan"><option value="-">Tidak ada</option><?php if ($jabatan_tambahan_sekarang !== '' && $jabatan_tambahan_sekarang !== '-' && !in_array($jabatan_tambahan_sekarang, $opsi_jabatan_tambahan, true)): ?><option value="<?php echo htmlspecialchars($jabatan_tambahan_sekarang); ?>" selected><?php echo htmlspecialchars($jabatan_tambahan_sekarang); ?></option><?php endif; ?><?php foreach ($opsi_jabatan_tambahan as $opsi_jabatan): ?><option value="<?php echo htmlspecialchars($opsi_jabatan); ?>" <?php echo $jabatan_tambahan_sekarang === $opsi_jabatan ? 'selected' : ''; ?>><?php echo htmlspecialchars($opsi_jabatan); ?></option><?php endforeach; ?></select></div>
                            <div class="col-md-4"><label class="form-label">Tanggal mulai jabatan tambahan</label><input type="date" class="form-control" name="tanggal_mulai_jabatan_tambahan" value="<?php echo nilai_buku_guru($profil_edit, 'tanggal_mulai_jabatan_tambahan'); ?>"></div>
                            <div class="col-md-4"><label class="form-label">Mata pelajaran utama</label><input class="form-control" name="mata_pelajaran" value="<?php echo nilai_buku_guru($profil_edit, 'mata_pelajaran', '-'); ?>"></div>
                            <div class="col-md-8"><label class="form-label">Mata pelajaran lainnya</label><textarea class="form-control" name="mata_pelajaran_lainnya" rows="2" placeholder="Pisahkan dengan koma jika lebih dari satu"><?php echo nilai_buku_guru($profil_edit, 'mata_pelajaran_lainnya'); ?></textarea><div class="form-text">Isi jika guru juga mengampu mata pelajaran lain; pisahkan dengan koma.</div></div>
                            <?php $status_kepegawaian_sekarang = (string)($profil_edit['status_kepegawaian'] ?? ''); $opsi_status_kepegawaian = ['GTT', 'GTY', 'PTT', 'PTY']; ?>
                            <div class="col-md-4"><label class="form-label">Status kepegawaian</label><select class="form-select" name="status_kepegawaian"><option value="">Pilih status</option><?php if ($status_kepegawaian_sekarang !== '' && !in_array($status_kepegawaian_sekarang, $opsi_status_kepegawaian, true)): ?><option value="<?php echo htmlspecialchars($status_kepegawaian_sekarang); ?>" selected><?php echo htmlspecialchars($status_kepegawaian_sekarang); ?></option><?php endif; ?><?php foreach ($opsi_status_kepegawaian as $status): ?><option value="<?php echo $status; ?>" <?php echo $status_kepegawaian_sekarang === $status ? 'selected' : ''; ?>><?php echo $status; ?></option><?php endforeach; ?></select></div>
                            <?php $status_keaktifan_sekarang = (string)($profil_edit['status_keaktifan'] ?? 'Aktif'); ?>
                            <div class="col-md-4"><label class="form-label">Status keaktifan</label><select class="form-select" name="status_keaktifan"><?php if ($status_keaktifan_sekarang === 'Pensiun'): ?><option value="Tidak Aktif" selected>Tidak Aktif</option><?php endif; ?><?php foreach (['Aktif', 'Tidak Aktif'] as $status_aktif): ?><option value="<?php echo $status_aktif; ?>" <?php echo $status_keaktifan_sekarang === $status_aktif ? 'selected' : ''; ?>><?php echo $status_aktif; ?></option><?php endforeach; ?></select></div>
                            <div class="col-md-4"><label class="form-label">Tanggal mulai bertugas</label><input type="date" class="form-control" name="tanggal_masuk" value="<?php echo nilai_buku_guru($profil_edit, 'tanggal_masuk'); ?>"></div>
                        </div>
                    </section>
                    <section id="profil-foto" class="border-top mt-4 pt-3">
                        <h4 class="h6 fw-bold mb-3">Foto Pegawai</h4>
                        <div class="row g-3 align-items-center">
                            <div class="col-md-3"><?php if (!empty($profil_edit['foto'])): ?><img src="<?php echo nilai_buku_guru($profil_edit, 'foto'); ?>" alt="Foto <?php echo nilai_buku_guru($profil_edit, 'nama_guru'); ?>" class="img-thumbnail" style="max-height: 150px"><?php else: ?><div class="bg-light rounded p-5 text-center text-muted"><i class="fa-solid fa-user fa-2x"></i></div><?php endif; ?></div>
                            <div class="col-md-9"><label class="form-label">Foto pegawai (JPG, PNG, WebP; maksimal 3 MB)</label><input type="file" class="form-control" name="foto" accept="image/jpeg,image/png,image/webp"></div>
                        </div>
                    </section>
                    <?php foreach (['pendidikan_terakhir', 'program_studi', 'perguruan_tinggi', 'tahun_lulus', 'nama_pasangan', 'jumlah_anak', 'pangkat_golongan', 'tmt_pangkat'] as $field_profil_lama): ?>
                    <input type="hidden" name="<?php echo $field_profil_lama; ?>" value="<?php echo nilai_buku_guru($profil_edit, $field_profil_lama); ?>">
                    <?php endforeach; ?>
                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="index.php?page=buku_induk_guru&amp;bagian=pegawai" class="btn btn-outline-secondary">Batal</a>
                        <button class="btn btn-danger" type="submit"><i class="fa-solid fa-floppy-disk me-1"></i>Simpan Profil</button>
                    </div>
                </form>
            </div>
        </div>
        <?php else: ?>
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div><h3 class="h5 fw-bold mb-1">Daftar Pegawai</h3><div class="text-muted small">Data identitas dasar dan profil kepegawaian madrasah.</div></div>
            <div class="d-flex flex-wrap gap-2">
                <a href="index.php?page=buku_induk_guru&amp;download_template=1" class="btn btn-outline-success"><i class="fa-solid fa-download me-1"></i>Template XLSX</a>
                <a href="index.php?page=buku_induk_guru&amp;bagian=pegawai&amp;tambah=1" class="btn btn-danger"><i class="fa-solid fa-user-plus me-1"></i>Tambah Pegawai</a>
            </div>
        </div>
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <form action="index?page=buku_induk_guru&amp;bagian=pegawai" method="POST" enctype="multipart/form-data" class="row g-2 align-items-end">
                    <input type="hidden" name="import_guru" value="1">
                    <div class="col-md-8"><label class="form-label fw-semibold" for="file_excel_guru">Impor identitas guru dari XLSX</label><input class="form-control" type="file" id="file_excel_guru" name="file_excel" accept=".xlsx" required><div class="form-text">Maksimal 5 MB. Template hanya untuk data identitas; lengkapi jabatan, kepegawaian, pendidikan, keluarga, dan informasi lainnya melalui aplikasi. Tanggal lahir gunakan format YYYY-MM-DD. Data yang cocok dengan NIP atau NIK terdaftar akan dilewati, bukan dibuat ulang atau diperbarui. Jika NIP dan NIK kosong, nama serta seluruh data identitas yang diisi harus sama persis agar dikenali sebagai duplikat.</div></div>
                    <div class="col-md-4"><button class="btn btn-success w-100" type="submit"><i class="fa-solid fa-file-import me-1"></i>Unggah dan Impor XLSX</button></div>
                </form>
            </div>
        </div>
        <div class="card border-0 shadow-sm"><div class="table-responsive">
            <table class="table table-hover align-middle mb-0 staff-table staff-list-table">
                <thead class="table-light"><tr><th class="ps-3">No</th><th>Status Kepegawaian</th><th>Nama Lengkap</th><th>NIK</th><th>Jenis Kelamin</th><th>Tugas</th><th>Mata Pelajaran</th><th>Status Keaktifan</th><th class="text-center">Aksi</th></tr></thead>
                <tbody>
                <?php mysqli_data_seek($daftar_guru, 0); $no = 1; while ($pegawai = mysqli_fetch_assoc($daftar_guru)): ?>
                    <tr>
                        <td class="ps-3"><?php echo $no++; ?></td>
                        <td><?php echo nilai_buku_guru($pegawai, 'status_kepegawaian', 'Belum diisi'); ?></td>
                        <td><div class="fw-semibold"><?php echo nilai_buku_guru($pegawai, 'nama_guru'); ?></div><small class="text-muted">NIP/NUPTK: <?php echo nilai_buku_guru($pegawai, 'nip', '-'); ?><?php if (!empty($pegawai['nuptk'])): ?> / <?php echo nilai_buku_guru($pegawai, 'nuptk'); ?><?php endif; ?><?php if (!empty($pegawai['npk'])): ?> | NPK: <?php echo nilai_buku_guru($pegawai, 'npk'); ?><?php endif; ?><?php if (!empty($pegawai['peg_id'])): ?> | PegID: <?php echo nilai_buku_guru($pegawai, 'peg_id'); ?><?php endif; ?></small></td>
                        <td><?php echo nilai_buku_guru($pegawai, 'nik', '-'); ?></td>
                        <td><?php echo nilai_buku_guru($pegawai, 'jenis_kelamin', '-'); ?></td>
                        <td><?php echo nilai_buku_guru($pegawai, 'jabatan', '-'); ?><?php if (!empty($pegawai['jabatan_tambahan']) && $pegawai['jabatan_tambahan'] !== '-'): ?><br><small class="text-muted"><?php echo nilai_buku_guru($pegawai, 'jabatan_tambahan'); ?></small><?php endif; ?></td>
                        <td><?php echo nilai_buku_guru($pegawai, 'mata_pelajaran', '-'); ?><?php if (!empty($pegawai['mata_pelajaran_lainnya'])): ?><br><small class="text-muted">Lainnya: <?php echo nilai_buku_guru($pegawai, 'mata_pelajaran_lainnya'); ?></small><?php endif; ?></td>
                        <?php $status_aktif = trim((string)($pegawai['status_keaktifan'] ?? 'Aktif')); ?>
                        <td><?php if ($status_aktif === 'Aktif'): ?><span class="badge bg-success-subtle text-success">Aktif</span><?php else: ?><span class="badge bg-danger-subtle text-danger"><?php echo htmlspecialchars($status_aktif); ?></span><?php endif; ?></td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <a href="index.php?page=buku_induk_guru&amp;bagian=pegawai&amp;edit=<?php echo (int)$pegawai['id']; ?>" class="btn btn-outline-primary" title="Edit profil"><i class="fa-solid fa-pen"></i></a>
                                <a href="index.php?page=buku_induk_guru&amp;bagian=pendidikan&amp;guru=<?php echo (int)$pegawai['id']; ?>" class="btn btn-outline-secondary" title="Riwayat pegawai"><i class="fa-solid fa-folder-open"></i></a>
                                <form action="index?page=buku_induk_guru" method="POST" onsubmit="return confirm('Hapus seluruh data buku induk pegawai ini?');">
                                    <input type="hidden" name="hapus_profil_guru" value="1"><input type="hidden" name="guru_id" value="<?php echo (int)$pegawai['id']; ?>">
                                    <button class="btn btn-outline-danger rounded-start-0" type="submit" title="Hapus pegawai"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
                <?php if ($jumlah_guru === 0): ?><tr><td colspan="9" class="text-center py-5 text-muted">Belum ada data pegawai. Gunakan tombol Tambah Pegawai untuk mulai mengisi.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div></div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (in_array($bagian, ['keaktifan', 'pendidikan', 'keluarga', 'jabatan', 'penghargaan', 'mutasi', 'sertifikasi'], true)): ?>
        <?php
        $judul_riwayat = $label_bagian[$bagian];
        $kategori_sql = mysqli_real_escape_string($conn, $bagian);
        $filter_per_guru = in_array($bagian, ['keaktifan', 'pendidikan', 'keluarga', 'jabatan', 'penghargaan', 'sertifikasi'], true);
        $kondisi_guru = $filter_per_guru
            ? ($filter_guru > 0 ? "AND rb.guru_id=$filter_guru " : "AND 1=0 ")
            : ($filter_guru > 0 ? "AND rb.guru_id=$filter_guru " : '');
        $urutan_riwayat = $bagian === 'jabatan' ? 'COALESCE(rb.tahun_riwayat, YEAR(rb.tanggal)) DESC, rb.id DESC' : 'rb.tanggal DESC, rb.id DESC';
        $daftar_riwayat = mysqli_query($conn, "
            SELECT rb.*, g.nama_guru, g.nip
            FROM riwayat_buku_guru rb JOIN guru g ON g.id=rb.guru_id
            WHERE rb.kategori='$kategori_sql' $kondisi_guru
            ORDER BY $urutan_riwayat
        ");
        if (!$daftar_riwayat) {
            die('Riwayat ' . htmlspecialchars($judul_riwayat) . ' gagal dimuat: ' . htmlspecialchars(mysqli_error($conn)));
        }
        $profil_sertifikasi = null;
        $jumlah_sertifikasi_guru = 0;
        $riwayat_pangkat_lama = [];
        $riwayat_inpassing = [];
        $profil_jabatan = null;
        if ($bagian === 'jabatan' && $filter_guru > 0) {
            $hasil_profil_jabatan = mysqli_query($conn, "SELECT g.nama_guru, g.nip, g.jabatan, g.jabatan_tambahan, g.mata_pelajaran, bg.mata_pelajaran_lainnya, bg.tanggal_mulai_jabatan, bg.tanggal_mulai_jabatan_tambahan FROM guru g LEFT JOIN buku_induk_guru bg ON bg.guru_id=g.id WHERE g.id=$filter_guru LIMIT 1");
            if (!$hasil_profil_jabatan) {
                die('Data jabatan pegawai gagal dimuat: ' . htmlspecialchars(mysqli_error($conn)));
            }
            $profil_jabatan = mysqli_fetch_assoc($hasil_profil_jabatan);
        }
        if ($bagian === 'sertifikasi' && $filter_guru > 0) {
            $hasil_profil_sertifikasi = mysqli_query($conn, "SELECT status_sertifikasi, status_inpassing, pangkat_golongan, tmt_pangkat, nomor_sk_inpassing, keterangan_inpassing FROM buku_induk_guru WHERE guru_id=$filter_guru LIMIT 1");
            $hasil_jumlah_sertifikasi = mysqli_query($conn, "SELECT COUNT(*) AS jumlah FROM riwayat_buku_guru WHERE guru_id=$filter_guru AND kategori='sertifikasi'");
            $hasil_pangkat_lama = mysqli_query($conn, "SELECT rb.*, g.nama_guru, g.nip FROM riwayat_buku_guru rb JOIN guru g ON g.id=rb.guru_id WHERE rb.guru_id=$filter_guru AND rb.kategori='pangkat' ORDER BY rb.tanggal DESC, rb.id DESC");
            $hasil_inpassing = mysqli_query($conn, "SELECT * FROM riwayat_buku_guru WHERE guru_id=$filter_guru AND kategori='inpassing' ORDER BY tanggal DESC, id DESC");
            if (!$hasil_profil_sertifikasi || !$hasil_jumlah_sertifikasi || !$hasil_pangkat_lama || !$hasil_inpassing) {
                die('Status sertifikasi gagal dimuat: ' . htmlspecialchars(mysqli_error($conn)));
            }
            $profil_sertifikasi = mysqli_fetch_assoc($hasil_profil_sertifikasi) ?: [];
            $jumlah_sertifikasi_guru = (int)(mysqli_fetch_assoc($hasil_jumlah_sertifikasi)['jumlah'] ?? 0);
            while ($item_pangkat_lama = mysqli_fetch_assoc($hasil_pangkat_lama)) {
                $riwayat_pangkat_lama[] = $item_pangkat_lama;
            }
            while ($item_inpassing = mysqli_fetch_assoc($hasil_inpassing)) {
                $riwayat_inpassing[] = $item_inpassing;
            }
            if (empty($profil_sertifikasi['status_sertifikasi']) && $jumlah_sertifikasi_guru > 0) {
                $profil_sertifikasi['status_sertifikasi'] = 'Sudah';
            }
        }
        $riwayat_keluarga = ['pasangan' => [], 'anak' => [], 'lainnya' => []];
        if ($bagian === 'keluarga') {
            while ($item_keluarga = mysqli_fetch_assoc($daftar_riwayat)) {
                $hubungan = strtolower(trim((string)($item_keluarga['instansi'] ?? '')));
                if (in_array($hubungan, ['suami', 'istri', 'pasangan', 'suami/istri'], true)) {
                    $riwayat_keluarga['pasangan'][] = $item_keluarga;
                } elseif ($hubungan === 'anak') {
                    $riwayat_keluarga['anak'][] = $item_keluarga;
                } else {
                    $riwayat_keluarga['lainnya'][] = $item_keluarga;
                }
            }
        }
        $riwayat_mutasi = ['masuk' => [], 'keluar' => []];
        if ($bagian === 'mutasi') {
            while ($item_mutasi = mysqli_fetch_assoc($daftar_riwayat)) {
                $jenis_mutasi = strtolower(trim((string)($item_mutasi['mutasi_jenis'] ?? '')));
                if ($jenis_mutasi === 'keluar' || strpos(strtolower((string)($item_mutasi['judul'] ?? '')), 'keluar') !== false) {
                    $riwayat_mutasi['keluar'][] = $item_mutasi;
                } else {
                    $riwayat_mutasi['masuk'][] = $item_mutasi;
                }
            }
        }
        ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white"><h3 class="h6 fw-bold mb-0"><?php echo htmlspecialchars($judul_riwayat) . ' Pegawai'; ?></h3></div>
            <div class="card-body">
                <?php if ($bagian === 'keaktifan'): ?><div class="alert alert-info">Gunakan bagian ini untuk mencatat perubahan keaktifan berdasarkan tanggal efektif. Jika Mutasi Keluar disimpan pada bagian Mutasi, riwayat Tidak Aktif akan tercatat otomatis dan status pegawai langsung berubah. Tidak perlu mencatat Tidak Aktif dua kali. Untuk pegawai yang kembali bertugas, catat status Aktif di sini. Dokumen SK tidak dikelola pada tab ini.</div><?php endif; ?>
                <?php if ($bagian === 'mutasi'): ?><div class="alert alert-info"><strong>Mutasi Keluar</strong> berarti pegawai tidak lagi bertugas di madrasah, tetapi data pegawai tetap disimpan sebagai arsip dan tidak dihapus. Saat disimpan, sistem otomatis membuat riwayat keaktifan Tidak Aktif dan langsung mengubah status pegawai—termasuk jika tanggal mutasi yang diisi masih akan datang. Untuk pegawai yang kembali bertugas, catat status Aktif pada bagian Keaktifan.</div><?php endif; ?>
                <?php if ($bagian === 'jabatan'): ?><div class="alert alert-info">Jabatan utama, jabatan tambahan, dan tanggal mulai dikelola pada Identitas. Riwayat jabatan tahunan di bawah tetap tersedia untuk mencatat perubahan jabatan.</div><?php endif; ?>
                <?php if ($filter_per_guru): ?>
                <form action="index" method="GET" class="row g-2 align-items-end mb-4">
                    <input type="hidden" name="page" value="buku_induk_guru"><input type="hidden" name="bagian" value="<?php echo htmlspecialchars($bagian); ?>">
                    <div class="col-md-8"><label class="form-label fw-semibold">Pilih guru untuk melihat <?php echo strtolower($judul_riwayat); ?></label>
                        <input type="search" class="form-control mb-2" data-filter-select="guru-filter" placeholder="Cari nama atau NIP guru">
                        <select class="form-select" id="guru-filter" name="guru" required onchange="this.form.submit()"><option value="">Pilih guru</option><?php mysqli_data_seek($daftar_guru, 0); while ($opsi_guru = mysqli_fetch_assoc($daftar_guru)): ?><option value="<?php echo (int)$opsi_guru['id']; ?>" <?php echo $filter_guru === (int)$opsi_guru['id'] ? 'selected' : ''; ?>><?php echo nilai_buku_guru($opsi_guru, 'nama_guru'); ?> (<?php echo nilai_buku_guru($opsi_guru, 'nip', 'tanpa NIP'); ?>)</option><?php endwhile; ?></select>
                    </div>
                    <div class="col-md-4"><button class="btn btn-outline-danger w-100" type="submit"><i class="fa-solid fa-filter me-1"></i><?php echo $bagian === 'keaktifan' ? 'Tampilkan Keaktifan Guru' : 'Tampilkan Riwayat Guru'; ?></button></div>
                </form>
                <?php if ($filter_guru < 1): ?><div class="alert alert-info">Pilih guru terlebih dahulu untuk mencatat dan melihat data <?php echo strtolower($judul_riwayat); ?> per guru.</div><?php endif; ?>
                <?php endif; ?>
                <?php if ($bagian === 'jabatan' && $profil_jabatan): ?>
                <section class="mb-4">
                    <h4 class="h6 fw-bold">Jabatan Saat Ini</h4>
                    <div class="table-responsive"><table class="table table-bordered align-middle staff-table mb-0">
                        <thead class="table-light"><tr><th>Pegawai</th><th>Jenis Jabatan</th><th>Jabatan</th><th>Tanggal Mulai</th><th>Mata Pelajaran</th></tr></thead>
                        <tbody>
                            <tr>
                                <td rowspan="<?php echo !empty($profil_jabatan['jabatan_tambahan']) && $profil_jabatan['jabatan_tambahan'] !== '-' ? 2 : 1; ?>"><?php echo nilai_buku_guru($profil_jabatan, 'nama_guru'); ?><br><small class="text-muted"><?php echo nilai_buku_guru($profil_jabatan, 'nip', '-'); ?></small></td>
                                <td>Jabatan Utama</td>
                                <td><?php echo nilai_buku_guru($profil_jabatan, 'jabatan', '-'); ?></td>
                                <td><?php echo !empty($profil_jabatan['tanggal_mulai_jabatan']) ? date('d-m-Y', strtotime($profil_jabatan['tanggal_mulai_jabatan'])) : '-'; ?></td>
                                <td><?php echo nilai_buku_guru($profil_jabatan, 'mata_pelajaran', '-'); ?><?php if (!empty($profil_jabatan['mata_pelajaran_lainnya'])): ?><br><small class="text-muted"><?php echo nilai_buku_guru($profil_jabatan, 'mata_pelajaran_lainnya'); ?></small><?php endif; ?></td>
                            </tr>
                            <?php if (!empty($profil_jabatan['jabatan_tambahan']) && $profil_jabatan['jabatan_tambahan'] !== '-'): ?>
                            <tr><td>Jabatan Tambahan</td><td><?php echo nilai_buku_guru($profil_jabatan, 'jabatan_tambahan'); ?></td><td><?php echo !empty($profil_jabatan['tanggal_mulai_jabatan_tambahan']) ? date('d-m-Y', strtotime($profil_jabatan['tanggal_mulai_jabatan_tambahan'])) : '-'; ?></td><td>-</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table></div>
                </section>
                <?php endif; ?>
                <?php if ($bagian === 'sertifikasi' && $filter_guru > 0): ?>
                <form action="index?page=buku_induk_guru&amp;bagian=sertifikasi&amp;guru=<?php echo $filter_guru; ?>" method="POST" class="border rounded p-3 mb-4" id="form-status-sertifikasi">
                    <input type="hidden" name="simpan_status_sertifikasi" value="1">
                    <input type="hidden" name="guru_id_status_sertifikasi" value="<?php echo $filter_guru; ?>">
                    <h4 class="h6 fw-bold">Status Sertifikasi Guru</h4>
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4"><label class="form-label">Sudah Sertifikasi?</label><select class="form-select" name="status_sertifikasi" id="status-sertifikasi" required><option value="" <?php echo empty($profil_sertifikasi['status_sertifikasi']) ? 'selected' : ''; ?>>Pilih status</option><?php foreach (['Sudah', 'Belum'] as $opsi_status): ?><option value="<?php echo $opsi_status; ?>" <?php echo ($profil_sertifikasi['status_sertifikasi'] ?? '') === $opsi_status ? 'selected' : ''; ?>><?php echo $opsi_status; ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-4 <?php echo $jumlah_sertifikasi_guru > 0 && ($profil_sertifikasi['status_sertifikasi'] ?? '') === 'Sudah' ? '' : 'd-none'; ?>" id="status-inpassing-wrapper"><label class="form-label">Sudah Inpassing?</label><select class="form-select" name="status_inpassing" id="status-inpassing"><option value="">Pilih status</option><?php foreach (['Sudah', 'Belum'] as $opsi_status): ?><option value="<?php echo $opsi_status; ?>" <?php echo ($profil_sertifikasi['status_inpassing'] ?? '') === $opsi_status ? 'selected' : ''; ?>><?php echo $opsi_status; ?></option><?php endforeach; ?></select></div>
                        <div class="col-12 <?php echo ($profil_sertifikasi['status_inpassing'] ?? '') === 'Sudah' ? '' : 'd-none'; ?>" id="tombol-edit-inpassing"><button class="btn btn-sm btn-outline-danger" type="button" id="edit-inpassing"><i class="fa-solid fa-pen me-1"></i>Edit Inpassing</button></div>
                        <div class="col-md-4 d-none" data-inpassing-detail>
                            <label class="form-label">Pangkat / Golongan *</label><input class="form-control" name="pangkat_golongan" value="<?php echo nilai_buku_guru($profil_sertifikasi ?? [], 'pangkat_golongan'); ?>" data-inpassing-input>
                        </div>
                        <div class="col-md-4 d-none" data-inpassing-detail>
                            <label class="form-label">TMT Inpassing *</label><input type="date" class="form-control" name="tmt_pangkat" value="<?php echo nilai_buku_guru($profil_sertifikasi ?? [], 'tmt_pangkat'); ?>" data-inpassing-input>
                        </div>
                        <div class="col-md-4 d-none" data-inpassing-detail>
                            <label class="form-label">Nomor SK Inpassing *</label><input class="form-control" name="nomor_sk_inpassing" value="<?php echo nilai_buku_guru($profil_sertifikasi ?? [], 'nomor_sk_inpassing'); ?>" data-inpassing-input>
                        </div>
                        <div class="col-md-8 d-none" data-inpassing-detail>
                            <label class="form-label">Keterangan Inpassing</label><textarea class="form-control" name="keterangan_inpassing" rows="2" data-inpassing-input><?php echo nilai_buku_guru($profil_sertifikasi ?? [], 'keterangan_inpassing'); ?></textarea>
                        </div>
                        <div class="col-md-4"><button class="btn btn-danger w-100 d-none" type="submit" id="simpan-status-sertifikasi"><i class="fa-solid fa-floppy-disk me-1"></i>Simpan Status</button></div>
                    </div>
                </form>
                <?php if (($profil_sertifikasi['status_sertifikasi'] ?? '') !== 'Sudah'): ?><div class="alert alert-info">Simpan status Sertifikasi menjadi Sudah untuk membuka form data sertifikasi.</div><?php endif; ?>
                <?php elseif ($bagian === 'sertifikasi'): ?><div class="alert alert-info">Pilih guru terlebih dahulu untuk mengatur status sertifikasi dan inpassing.</div><?php endif; ?>
                <?php if ($bagian === 'sertifikasi' && $filter_guru > 0): ?>
                <div class="card border mb-4">
                    <div class="card-header bg-white"><h4 class="h6 fw-bold mb-0">Daftar Inpassing</h4></div>
                    <div class="table-responsive"><table class="table table-hover align-middle mb-0 staff-table">
                        <thead class="table-light"><tr><th>TMT</th><th>Pangkat / Golongan</th><th>Nomor SK Inpassing</th><th>Keterangan</th></tr></thead>
                        <tbody><?php foreach ($riwayat_inpassing as $riwayat): ?>
                            <tr>
                                <td><?php echo !empty($riwayat['tanggal']) ? date('d-m-Y', strtotime($riwayat['tanggal'])) : '-'; ?></td>
                                <td><?php echo nilai_buku_guru($riwayat, 'judul', '-'); ?></td>
                                <td><?php echo nilai_buku_guru($riwayat, 'instansi', '-'); ?></td>
                                <td><?php echo nl2br(nilai_buku_guru($riwayat, 'detail', '-')); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($riwayat_inpassing)): ?><tr><td colspan="4" class="text-center text-muted py-3">Belum ada data inpassing yang tersimpan.</td></tr><?php endif; ?></tbody>
                    </table></div>
                </div>
                <?php endif; ?>
                <?php if ($bagian === 'keaktifan' && $filter_guru > 0): ?><h4 class="h6 fw-bold">Tambah Status Keaktifan Tahunan</h4><?php endif; ?>
                <?php if ($bagian === 'jabatan' && $filter_guru > 0): ?><h4 class="h6 fw-bold mt-4">Tambah Riwayat Jabatan Tahunan</h4><?php endif; ?>
                <form action="index?page=buku_induk_guru&amp;bagian=<?php echo urlencode($bagian); ?>" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="simpan_riwayat_guru" value="1"><input type="hidden" name="kategori" value="<?php echo htmlspecialchars($bagian); ?>">
                    <div class="row g-3 align-items-end">
                        <?php if (!$filter_per_guru): ?><div class="col-md-3"><label class="form-label">Pegawai *</label><input type="search" class="form-control mb-2" data-filter-select="guru-riwayat" placeholder="Cari nama atau NIP guru"><select class="form-select" name="guru_id_riwayat" id="guru-riwayat" required><option value="">Pilih pegawai</option><?php mysqli_data_seek($daftar_guru, 0); while ($opsi_guru = mysqli_fetch_assoc($daftar_guru)): ?><?php if ($bagian !== 'mutasi' || !isset($guru_sudah_mutasi_keluar[(int)$opsi_guru['id']])): ?><option value="<?php echo (int)$opsi_guru['id']; ?>" <?php echo $filter_guru === (int)$opsi_guru['id'] ? 'selected' : ''; ?>><?php echo nilai_buku_guru($opsi_guru, 'nama_guru'); ?> (<?php echo nilai_buku_guru($opsi_guru, 'nip', 'tanpa NIP'); ?>)</option><?php endif; ?><?php endwhile; ?></select><?php if ($bagian === 'mutasi'): ?><small class="text-muted">Guru yang sudah tercatat Mutasi Keluar tidak ditampilkan.</small><?php endif; ?></div>
                        <?php else: ?><input type="hidden" name="guru_id_riwayat" value="<?php echo $filter_guru; ?>"><div class="col-md-3"><label class="form-label">Pegawai</label><input class="form-control" value="<?php echo $filter_guru > 0 ? 'Pegawai terpilih' : 'Pilih guru di atas terlebih dahulu'; ?>" readonly></div><?php endif; ?>
                        <?php if ($bagian === 'keaktifan'): ?>
                        <div class="col-md-3"><label class="form-label">Tanggal Efektif *</label><input type="date" class="form-control" name="tanggal_efektif" required></div>
                        <div class="col-md-3"><label class="form-label">Lembaga Induk</label><input class="form-control" name="lembaga_induk"></div>
                        <div class="col-md-3"><label class="form-label">Status Keaktifan *</label><select class="form-select" name="status_keaktifan_tahunan" required><option value="">Pilih status</option><?php foreach (['Aktif', 'Tidak Aktif'] as $opsi_status_keaktifan): ?><option value="<?php echo $opsi_status_keaktifan; ?>"><?php echo $opsi_status_keaktifan; ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-3"><label class="form-label">Instansi yang Mengeluarkan SK</label><select class="form-select" name="instansi_sk"><option value="">Pilih instansi</option><?php foreach (['Kementerian Agama', 'Pemerintah Daerah', 'Kementerian Lainnya', 'Yayasan Penyelenggara Pendidikan', 'Satuan Pendidikan Sekolah', 'Lainnya'] as $opsi_instansi_sk): ?><option value="<?php echo htmlspecialchars($opsi_instansi_sk); ?>"><?php echo htmlspecialchars($opsi_instansi_sk); ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-3"><label class="form-label">No. SK</label><input class="form-control" name="nomor_sk"></div>
                        <div class="col-md-3"><label class="form-label">Tgl SK</label><input type="date" class="form-control" name="tanggal_sk"></div>
                        <div class="col-md-6"><label class="form-label">Unggah Berkas SK <span class="text-muted">(opsional)</span></label><input type="file" class="form-control" name="berkas_sk" accept="application/pdf,image/jpeg,image/png,image/webp"><div class="form-text">PDF, JPG, PNG, atau WebP; maksimal 5 MB. File dikirim ke Google Drive guru dan tidak disimpan permanen di server.</div></div>
                        <?php elseif ($bagian === 'pendidikan'): ?>
                        <div class="col-md-3"><label class="form-label">Jenjang *</label><select class="form-select" id="jenjang-pendidikan" name="jenjang" required><option value="">Pilih jenjang</option><?php foreach (['SD', 'MI', 'SMP', 'MTs', 'SMA', 'MA', 'SMK', 'D1', 'D2', 'D3', 'D4', 'S1', 'S2', 'S3'] as $jenjang): ?><option value="<?php echo htmlspecialchars($jenjang); ?>"><?php echo htmlspecialchars($jenjang); ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-3"><label class="form-label">Perguruan Tinggi / Sekolah *</label><input class="form-control" name="perguruan_tinggi" required></div>
                        <div class="col-md-3"><label class="form-label">Fakultas</label><input class="form-control" name="fakultas" data-jenjang-disabled></div>
                        <div class="col-md-3"><label class="form-label">Jurusan</label><input class="form-control" name="jurusan"></div>
                        <div class="col-md-3"><label class="form-label">Tahun Lulus</label><input type="number" class="form-control" name="tahun_lulus" min="1950" max="2100" placeholder="Contoh: 2005"></div>
                        <div class="col-md-3"><label class="form-label">LPTK *</label><select class="form-select" name="lptk" required data-jenjang-disabled><option value="">Pilih</option><option value="Ya">Ya</option><option value="Tidak">Tidak</option></select></div>
                        <div class="col-12"><small class="text-muted">Untuk jenjang SD sampai SMA/MA/SMK, kolom Fakultas dan LPTK tidak perlu diisi.</small></div>
                        <div class="col-12"><small class="text-muted">Klik Tambah Pendidikan untuk menyimpan satu jenjang. Ulangi untuk menambahkan riwayat jenjang lainnya.</small></div>
                        <?php elseif ($bagian === 'jabatan'): ?>
                        <div class="col-md-3"><label class="form-label">Tahun *</label><input type="number" class="form-control" name="tahun_riwayat" min="1950" max="2100" required></div>
                        <div class="col-md-3"><label class="form-label">Jabatan Utama *</label><select class="form-select" name="jabatan_tahunan" required><option value="">Pilih jabatan utama</option><?php foreach ($opsi_jabatan_utama as $opsi_jabatan): ?><option value="<?php echo htmlspecialchars($opsi_jabatan); ?>"><?php echo htmlspecialchars($opsi_jabatan); ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-3"><label class="form-label">Jabatan Tambahan</label><select class="form-select" name="jabatan_tambahan_tahunan"><option value="">Tidak ada</option><?php foreach ($opsi_jabatan_tambahan as $opsi_jabatan): ?><option value="<?php echo htmlspecialchars($opsi_jabatan); ?>"><?php echo htmlspecialchars($opsi_jabatan); ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-3"><label class="form-label">Mata Pelajaran Utama</label><input class="form-control" name="mapel_jabatan"></div>
                        <div class="col-md-3"><label class="form-label">Mata Pelajaran Lainnya</label><input class="form-control" name="mapel_jabatan_lainnya" placeholder="Pisahkan dengan koma"></div>
                        <?php elseif ($bagian === 'sertifikasi'): ?>
                        <?php $sertifikasi_aktif = $filter_guru > 0 && ($profil_sertifikasi['status_sertifikasi'] ?? '') === 'Sudah'; ?>
                        <?php if ($jumlah_sertifikasi_guru > 0): ?><div class="col-12"><small class="text-muted">Data sertifikasi sudah tersimpan. Gunakan tombol Edit pada daftar sertifikasi untuk mengubahnya.</small></div>
                        <?php else: ?>
                        <div class="col-md-3 <?php echo $sertifikasi_aktif ? '' : 'd-none'; ?>" data-sertifikasi-detail><label class="form-label">No. Piagam *</label><input class="form-control" name="no_piagam" data-sertifikasi-input></div>
                        <div class="col-md-3 <?php echo $sertifikasi_aktif ? '' : 'd-none'; ?>" data-sertifikasi-detail><label class="form-label">NRG</label><input class="form-control" name="nrg" maxlength="50" data-sertifikasi-input></div>
                        <div class="col-md-3 <?php echo $sertifikasi_aktif ? '' : 'd-none'; ?>" data-sertifikasi-detail><label class="form-label">Mapel Sertifikasi *</label><input class="form-control" name="mapel_sertifikasi" data-sertifikasi-input></div>
                        <div class="col-md-3 <?php echo $sertifikasi_aktif ? '' : 'd-none'; ?>" data-sertifikasi-detail><label class="form-label">Badan Sertifikasi *</label><input class="form-control" name="badan_sertifikasi" data-sertifikasi-input></div>
                        <div class="col-md-3 <?php echo $sertifikasi_aktif ? '' : 'd-none'; ?>" data-sertifikasi-detail><label class="form-label">Jalur *</label><input class="form-control" name="jalur_sertifikasi" data-sertifikasi-input></div>
                        <div class="col-md-3 <?php echo $sertifikasi_aktif ? '' : 'd-none'; ?>" data-sertifikasi-detail><label class="form-label">Tanggal Piagam *</label><input type="date" class="form-control" name="tanggal" data-sertifikasi-input></div>
                        <?php endif; ?>
                        <?php elseif ($bagian === 'keluarga'): ?>
                        <div class="col-12 mt-4"><h4 class="h6 fw-bold mb-0">Suami/Istri</h4></div>
                        <div class="col-md-4"><label class="form-label">Nama Suami/Istri</label><input class="form-control" name="keluarga[pasangan][nama]"></div>
                        <div class="col-md-3"><label class="form-label">Tempat Lahir</label><input class="form-control" name="keluarga[pasangan][tempat_lahir]"></div>
                        <div class="col-md-2"><label class="form-label">Tanggal Lahir</label><input type="date" class="form-control" name="keluarga[pasangan][tanggal]"></div>
                        <div class="col-md-3"><label class="form-label">Pendidikan</label><select class="form-select" name="keluarga[pasangan][pendidikan]"><option value="">Pilih pendidikan</option><?php foreach (['Tidak Sekolah', 'Belum Sekolah', 'SD', 'MI', 'SMP', 'MTs', 'SMA', 'MA', 'SMK', 'D1', 'D2', 'D3', 'D4', 'S1', 'S2', 'S3'] as $opsi_pendidikan): ?><option value="<?php echo htmlspecialchars($opsi_pendidikan); ?>"><?php echo htmlspecialchars($opsi_pendidikan); ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-3"><label class="form-label">Pekerjaan</label><input class="form-control" name="keluarga[pasangan][detail]"></div>
                        <div class="col-md-1 d-flex align-items-end"><button class="btn btn-outline-danger w-100" type="submit" name="simpan_keluarga_tipe" value="pasangan" <?php echo $filter_guru < 1 ? 'disabled' : ''; ?> title="Tambah data suami/istri"><i class="fa-solid fa-plus"></i></button></div>

                        <div class="col-12 mt-4"><h4 class="h6 fw-bold mb-0">Informasi Anak Kandung</h4></div>
                        <div class="col-md-4"><label class="form-label">Nama Anak Kandung</label><input class="form-control" name="keluarga[anak][nama]"></div>
                        <div class="col-md-3"><label class="form-label">Jenis Kelamin</label><select class="form-select" name="keluarga[anak][jenis_kelamin]"><option value="">Pilih jenis kelamin</option><option value="Laki-laki">Laki-laki</option><option value="Perempuan">Perempuan</option></select></div>
                        <div class="col-md-3"><label class="form-label">Tempat Lahir</label><input class="form-control" name="keluarga[anak][tempat_lahir]"></div>
                        <div class="col-md-2"><label class="form-label">Tanggal Lahir</label><input type="date" class="form-control" name="keluarga[anak][tanggal]"></div>
                        <div class="col-md-4"><label class="form-label">Pendidikan</label><select class="form-select" name="keluarga[anak][pendidikan]"><option value="">Pilih pendidikan</option><?php foreach (['Tidak Sekolah', 'Belum Sekolah', 'SD', 'MI', 'SMP', 'MTs', 'SMA', 'MA', 'SMK', 'D1', 'D2', 'D3', 'D4', 'S1', 'S2', 'S3'] as $opsi_pendidikan): ?><option value="<?php echo htmlspecialchars($opsi_pendidikan); ?>"><?php echo htmlspecialchars($opsi_pendidikan); ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-5"><label class="form-label">Pekerjaan</label><input class="form-control" name="keluarga[anak][detail]"></div>
                        <div class="col-md-1 d-flex align-items-end"><button class="btn btn-outline-danger w-100" type="submit" name="simpan_keluarga_tipe" value="anak" <?php echo $filter_guru < 1 ? 'disabled' : ''; ?> title="Tambah data anak kandung"><i class="fa-solid fa-plus"></i></button></div>
                        <?php elseif ($bagian === 'mutasi'): ?>
                        <div class="col-md-3"><label class="form-label">Jenis Mutasi *</label><select class="form-select" name="mutasi_jenis" id="jenis-mutasi" required><option value="">Pilih jenis mutasi</option><option value="masuk">Mutasi Masuk</option><option value="keluar">Mutasi Keluar</option></select></div>
                        <div class="col-md-3"><label class="form-label">Tanggal Mutasi *</label><input type="date" class="form-control" name="tanggal" required></div>
                        <div class="col-md-3" data-mutasi-field="masuk"><label class="form-label">Asal Sekolah Sebelumnya *</label><input class="form-control" name="mutasi_asal" data-mutasi-input="masuk" disabled></div>
                        <div class="col-md-3" data-mutasi-field="keluar"><label class="form-label">Tujuan Mutasi *</label><input class="form-control" name="mutasi_tujuan" data-mutasi-input="keluar" disabled></div>
                        <div class="col-md-6" data-mutasi-field="keluar"><label class="form-label">Alasan Mutasi Keluar *</label><textarea class="form-control" name="mutasi_alasan" rows="1" data-mutasi-input="keluar" disabled></textarea></div>
                        <?php else: ?>
                        <div class="col-md-3"><label class="form-label">Nama / Judul *</label><input class="form-control" name="judul" required placeholder="<?php echo $bagian === 'keluarga' ? 'Nama anggota keluarga' : 'Nama riwayat'; ?>"></div>
                        <div class="col-md-2"><label class="form-label">Tanggal</label><input type="date" class="form-control" name="tanggal"></div>
                        <div class="col-md-2"><label class="form-label">Instansi / Hubungan</label><input class="form-control" name="instansi"></div>
                        <div class="col-md-2"><label class="form-label">Detail</label><input class="form-control" name="detail" placeholder="<?php echo $bagian === 'keluarga' ? 'Hubungan, pekerjaan' : 'Keterangan'; ?>"></div>
                        <?php endif; ?>
                        <?php if ($bagian !== 'keluarga' && $bagian !== 'sertifikasi'): ?><div class="col-12 text-end"><button class="btn btn-danger" type="submit" <?php echo $filter_per_guru && $filter_guru < 1 ? 'disabled' : ''; ?>><i class="fa-solid fa-plus me-1"></i><?php echo $bagian === 'keaktifan' ? 'Simpan Status Keaktifan' : ($bagian === 'pendidikan' ? 'Tambah Pendidikan' : ($bagian === 'jabatan' ? 'Tambah Riwayat Jabatan' : ($bagian === 'mutasi' ? 'Simpan Mutasi' : 'Tambah Riwayat'))); ?></button></div><?php endif; ?>
                        <?php if ($bagian === 'sertifikasi' && $jumlah_sertifikasi_guru === 0): ?><div class="col-12 text-end <?php echo $sertifikasi_aktif ? '' : 'd-none'; ?>" data-sertifikasi-detail><button class="btn btn-danger" type="submit" <?php echo $filter_guru < 1 ? 'disabled' : ''; ?>><i class="fa-solid fa-plus me-1"></i>Tambah Sertifikasi</button></div><?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
        <div class="card border-0 shadow-sm"><div class="table-responsive">
            <table class="table table-hover align-middle mb-0 staff-table">
                <?php if ($bagian === 'keaktifan'): ?>
                <thead class="table-light"><tr><th>Tanggal Efektif</th><th>Lembaga Induk</th><th>Status Keaktifan</th><th>Instansi yang Mengeluarkan SK</th><th>No. SK</th><th>Tgl SK</th><th>Berkas SK</th><th>Aksi</th></tr></thead>
                <tbody><?php while ($riwayat = mysqli_fetch_assoc($daftar_riwayat)): ?>
                    <tr>
                        <td><?php echo !empty($riwayat['tanggal']) ? date('d-m-Y', strtotime($riwayat['tanggal'])) : '-'; ?></td>
                        <td><?php echo nilai_buku_guru($riwayat, 'instansi', '-'); ?></td>
                        <td><?php echo nilai_buku_guru($riwayat, 'judul', '-'); ?></td>
                        <td><?php echo nilai_buku_guru($riwayat, 'instansi_sk', '-'); ?></td>
                        <td><?php echo nilai_buku_guru($riwayat, 'nomor_sk', '-'); ?></td>
                        <td><?php echo !empty($riwayat['tanggal_sk']) ? date('d-m-Y', strtotime($riwayat['tanggal_sk'])) : '-'; ?></td>
                        <?php if (!empty($riwayat['berkas_sk']) || !empty($riwayat['google_drive_link'])): ?>
                        <td><a class="btn btn-sm btn-outline-success" href="<?php echo htmlspecialchars(!empty($riwayat['google_drive_link']) ? $riwayat['google_drive_link'] : $riwayat['berkas_sk'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener"><i class="fa-solid fa-file-arrow-down me-1"></i><?php echo htmlspecialchars($riwayat['nama_berkas_sk'] ?: 'Lihat SK', ENT_QUOTES, 'UTF-8'); ?></a></td>
                        <?php else: ?>
                        <td class="text-muted">Tidak ada berkas</td>
                        <?php endif; ?>
                        <td class="text-nowrap">
                            <?php if (empty($riwayat['sumber_mutasi_id'])): ?>
                            <button type="button" class="btn btn-sm btn-outline-primary me-1" data-edit-riwayat
                                data-id="<?php echo (int)$riwayat['id']; ?>" data-kategori="keaktifan"
                                data-tanggal="<?php echo nilai_buku_guru($riwayat, 'tanggal'); ?>"
                                data-judul="<?php echo nilai_buku_guru($riwayat, 'judul'); ?>"
                                data-instansi="<?php echo nilai_buku_guru($riwayat, 'instansi'); ?>"
                                data-instansi-sk="<?php echo nilai_buku_guru($riwayat, 'instansi_sk'); ?>"
                                data-nomor-sk="<?php echo nilai_buku_guru($riwayat, 'nomor_sk'); ?>"
                                data-tanggal-sk="<?php echo nilai_buku_guru($riwayat, 'tanggal_sk'); ?>"><i class="fa-solid fa-pen"></i></button>
                            <form class="d-inline" action="index?page=buku_induk_guru&amp;bagian=keaktifan&amp;guru=<?php echo $filter_guru; ?>" method="POST" onsubmit="return confirm('Hapus riwayat keaktifan ini?');"><input type="hidden" name="hapus_riwayat_guru" value="1"><input type="hidden" name="riwayat_id" value="<?php echo (int)$riwayat['id']; ?>"><button class="btn btn-sm btn-outline-danger" type="submit" title="Hapus"><i class="fa-solid fa-trash"></i></button></form>
                            <?php else: ?><span class="small text-muted">Dari mutasi</span><?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; if ($filter_guru > 0 && mysqli_num_rows($daftar_riwayat) === 0): ?><tr><td colspan="7" class="text-center text-muted py-3">Belum ada riwayat keaktifan pegawai ini.</td></tr><?php endif; ?></tbody>
                <?php elseif ($bagian === 'pendidikan'): ?>
                <thead class="table-light"><tr><th>No.</th><th>Jenjang</th><th>Perguruan Tinggi / Sekolah</th><th>Fakultas</th><th>Jurusan</th><th>Tahun Lulus</th><th>LPTK</th><th>Aksi</th></tr></thead>
                <tbody><?php $no_pendidikan = 1; while ($riwayat = mysqli_fetch_assoc($daftar_riwayat)): ?>
                    <tr><td><?php echo $no_pendidikan++; ?></td><td><?php echo nilai_buku_guru($riwayat, 'jenjang', $riwayat['judul'] ?? '-'); ?></td><td><?php echo nilai_buku_guru($riwayat, 'instansi', '-'); ?></td><td><?php echo nilai_buku_guru($riwayat, 'fakultas', '-'); ?></td><td><?php echo nilai_buku_guru($riwayat, 'jurusan', '-'); ?></td><td><?php echo nilai_buku_guru($riwayat, 'tahun_lulus', !empty($riwayat['tanggal']) ? date('Y', strtotime($riwayat['tanggal'])) : '-'); ?></td><td><?php echo nilai_buku_guru($riwayat, 'lptk', '-'); ?></td><td class="text-nowrap"><button type="button" class="btn btn-sm btn-outline-primary me-1" data-edit-riwayat data-id="<?php echo (int)$riwayat['id']; ?>" data-kategori="pendidikan" data-tanggal="<?php echo nilai_buku_guru($riwayat, 'tanggal'); ?>" data-judul="<?php echo nilai_buku_guru($riwayat, 'judul'); ?>" data-instansi="<?php echo nilai_buku_guru($riwayat, 'instansi'); ?>" data-detail="<?php echo nilai_buku_guru($riwayat, 'detail'); ?>" data-jenjang="<?php echo nilai_buku_guru($riwayat, 'jenjang'); ?>" data-fakultas="<?php echo nilai_buku_guru($riwayat, 'fakultas'); ?>" data-jurusan="<?php echo nilai_buku_guru($riwayat, 'jurusan'); ?>" data-tahun-lulus="<?php echo nilai_buku_guru($riwayat, 'tahun_lulus'); ?>" data-lptk="<?php echo nilai_buku_guru($riwayat, 'lptk'); ?>"><i class="fa-solid fa-pen"></i></button><form class="d-inline" action="index?page=buku_induk_guru&amp;bagian=<?php echo urlencode($bagian); ?>&amp;guru=<?php echo (int)$filter_guru; ?>" method="POST" onsubmit="return confirm('Hapus riwayat ini?');"><input type="hidden" name="hapus_riwayat_guru" value="1"><input type="hidden" name="riwayat_id" value="<?php echo (int)$riwayat['id']; ?>"><button class="btn btn-sm btn-outline-danger" type="submit" title="Hapus"><i class="fa-solid fa-trash"></i></button></form></td></tr>
                <?php endwhile; if (mysqli_num_rows($daftar_riwayat) === 0 && $filter_guru > 0): ?><tr><td colspan="8" class="text-center text-muted py-3">Belum ada riwayat pendidikan guru ini.</td></tr><?php endif; ?>
                </tbody>
                <?php elseif ($bagian === 'keluarga'): ?>
                <?php foreach (['pasangan' => 'Data Suami/Istri', 'anak' => 'Data Anak Kandung'] as $jenis_keluarga => $judul_keluarga): ?>
                <section class="mb-4">
                    <h4 class="h6 fw-bold"><?php echo $judul_keluarga; ?></h4>
                    <div class="table-responsive"><table class="table table-hover align-middle mb-0 staff-table">
                        <?php if ($jenis_keluarga === 'anak'): ?>
                        <thead class="table-light"><tr><th>No.</th><th>Nama Anak Kandung</th><th>Jenis Kelamin</th><th>Tempat, Tanggal Lahir</th><th>Pendidikan</th><th>Pekerjaan</th><th>Aksi</th></tr></thead>
                        <?php else: ?>
                        <thead class="table-light"><tr><th>No.</th><th>Nama Suami/Istri</th><th>Tempat, Tanggal Lahir</th><th>Pendidikan</th><th>Pekerjaan</th><th>Aksi</th></tr></thead>
                        <?php endif; ?>
                        <tbody><?php foreach ($riwayat_keluarga[$jenis_keluarga] as $nomor_keluarga => $item_keluarga): ?>
                            <tr><td><?php echo $nomor_keluarga + 1; ?></td><td><?php echo nilai_buku_guru($item_keluarga, 'judul'); ?></td>
                                <?php if ($jenis_keluarga === 'anak'): ?>
                                <td><?php echo nilai_buku_guru($item_keluarga, 'jenis_kelamin_keluarga', '-'); ?></td>
                                <td><?php echo nilai_buku_guru($item_keluarga, 'tempat_lahir_keluarga', '-'); ?><?php if (!empty($item_keluarga['tanggal'])): ?>, <?php echo date('d-m-Y', strtotime($item_keluarga['tanggal'])); ?><?php endif; ?></td>
                                <td><?php echo nilai_buku_guru($item_keluarga, 'pendidikan_anak', '-'); ?></td>
                                <?php else: ?><td><?php echo nilai_buku_guru($item_keluarga, 'tempat_lahir_keluarga', '-'); ?><?php if (!empty($item_keluarga['tanggal'])): ?>, <?php echo date('d-m-Y', strtotime($item_keluarga['tanggal'])); ?><?php endif; ?></td><td><?php echo nilai_buku_guru($item_keluarga, 'pendidikan_keluarga', '-'); ?></td><?php endif; ?>
                                <td><?php echo nl2br(nilai_buku_guru($item_keluarga, 'detail', '-')); ?></td>
                                <td class="text-nowrap"><button type="button" class="btn btn-sm btn-outline-primary me-1" data-edit-riwayat data-id="<?php echo (int)$item_keluarga['id']; ?>" data-kategori="keluarga" data-tanggal="<?php echo nilai_buku_guru($item_keluarga, 'tanggal'); ?>" data-judul="<?php echo nilai_buku_guru($item_keluarga, 'judul'); ?>" data-instansi="<?php echo nilai_buku_guru($item_keluarga, 'instansi'); ?>" data-detail="<?php echo nilai_buku_guru($item_keluarga, 'detail'); ?>" data-jenis-kelamin="<?php echo nilai_buku_guru($item_keluarga, 'jenis_kelamin_keluarga'); ?>" data-tempat-lahir="<?php echo nilai_buku_guru($item_keluarga, 'tempat_lahir_keluarga'); ?>" data-pendidikan-anak="<?php echo nilai_buku_guru($item_keluarga, 'pendidikan_anak'); ?>" data-pendidikan-keluarga="<?php echo nilai_buku_guru($item_keluarga, 'pendidikan_keluarga'); ?>"><i class="fa-solid fa-pen"></i></button><form class="d-inline" action="index?page=buku_induk_guru&amp;bagian=keluarga&amp;guru=<?php echo (int)$filter_guru; ?>" method="POST" onsubmit="return confirm('Hapus data keluarga ini?');"><input type="hidden" name="hapus_riwayat_guru" value="1"><input type="hidden" name="riwayat_id" value="<?php echo (int)$item_keluarga['id']; ?>"><button class="btn btn-sm btn-outline-danger" type="submit" title="Hapus"><i class="fa-solid fa-trash"></i></button></form></td></tr>
                        <?php endforeach; if (empty($riwayat_keluarga[$jenis_keluarga])): ?><tr><td colspan="<?php echo $jenis_keluarga === 'anak' ? 7 : 6; ?>" class="text-center text-muted py-3">Belum ada data.</td></tr><?php endif; ?></tbody>
                    </table></div>
                </section>
                <?php endforeach; ?>
                <?php if (!empty($riwayat_keluarga['lainnya'])): ?>
                <section><h4 class="h6 fw-bold">Data Keluarga Lainnya</h4><div class="table-responsive"><table class="table table-hover align-middle mb-0 staff-table">
                    <thead class="table-light"><tr><th>Nama Anggota Keluarga</th><th>Tanggal</th><th>Hubungan</th><th>Keterangan</th><th>Aksi</th></tr></thead>
                    <tbody><?php foreach ($riwayat_keluarga['lainnya'] as $item_keluarga): ?><tr><td><?php echo nilai_buku_guru($item_keluarga, 'judul'); ?></td><td><?php echo !empty($item_keluarga['tanggal']) ? date('d-m-Y', strtotime($item_keluarga['tanggal'])) : '-'; ?></td><td><?php echo nilai_buku_guru($item_keluarga, 'instansi', '-'); ?></td><td><?php echo nilai_buku_guru($item_keluarga, 'detail', '-'); ?></td><td><button type="button" class="btn btn-sm btn-outline-primary me-1" data-edit-riwayat data-id="<?php echo (int)$item_keluarga['id']; ?>" data-kategori="keluarga" data-tanggal="<?php echo nilai_buku_guru($item_keluarga, 'tanggal'); ?>" data-judul="<?php echo nilai_buku_guru($item_keluarga, 'judul'); ?>" data-instansi="<?php echo nilai_buku_guru($item_keluarga, 'instansi'); ?>" data-detail="<?php echo nilai_buku_guru($item_keluarga, 'detail'); ?>"><i class="fa-solid fa-pen"></i></button><form class="d-inline" action="index?page=buku_induk_guru&amp;bagian=keluarga&amp;guru=<?php echo (int)$filter_guru; ?>" method="POST" onsubmit="return confirm('Hapus data keluarga ini?');"><input type="hidden" name="hapus_riwayat_guru" value="1"><input type="hidden" name="riwayat_id" value="<?php echo (int)$item_keluarga['id']; ?>"><button class="btn btn-sm btn-outline-danger" type="submit" title="Hapus"><i class="fa-solid fa-trash"></i></button></form></td></tr><?php endforeach; ?></tbody>
                </table></div></section>
                <?php endif; ?>
                <?php elseif ($bagian === 'jabatan'): ?>
                <thead class="table-light"><tr><th>Tahun</th><th>Pegawai</th><th>Jabatan Utama</th><th>Jabatan Tambahan</th><th>Mata Pelajaran</th><th>Aksi</th></tr></thead>
                <tbody><?php while ($riwayat = mysqli_fetch_assoc($daftar_riwayat)): ?>
                    <tr><td><?php echo nilai_buku_guru($riwayat, 'tahun_riwayat', !empty($riwayat['tanggal']) ? date('Y', strtotime($riwayat['tanggal'])) : '-'); ?></td><td><?php echo nilai_buku_guru($riwayat, 'nama_guru'); ?><br><small class="text-muted"><?php echo nilai_buku_guru($riwayat, 'nip'); ?></small></td><td><?php echo nilai_buku_guru($riwayat, 'judul'); ?></td><td><?php echo nilai_buku_guru($riwayat, 'instansi', '-'); ?></td><td><?php echo nilai_buku_guru($riwayat, 'detail', '-'); ?><?php if (!empty($riwayat['mata_pelajaran_lainnya'])): ?><br><small class="text-muted">Lainnya: <?php echo nilai_buku_guru($riwayat, 'mata_pelajaran_lainnya'); ?></small><?php endif; ?></td><td><form action="index?page=buku_induk_guru&amp;bagian=jabatan&amp;guru=<?php echo (int)$filter_guru; ?>" method="POST" onsubmit="return confirm('Hapus riwayat jabatan ini?');"><input type="hidden" name="hapus_riwayat_guru" value="1"><input type="hidden" name="riwayat_id" value="<?php echo (int)$riwayat['id']; ?>"><button class="btn btn-sm btn-outline-danger" type="submit" title="Hapus"><i class="fa-solid fa-trash"></i></button></form></td></tr>
                <?php endwhile; if ($filter_guru > 0 && mysqli_num_rows($daftar_riwayat) === 0): ?><tr><td colspan="6" class="text-center text-muted py-3">Belum ada riwayat jabatan guru ini.</td></tr><?php endif; ?></tbody>
                <?php elseif ($bagian === 'sertifikasi'): ?>
                <?php if ($filter_guru > 0 && ($profil_sertifikasi['status_sertifikasi'] ?? '') === 'Sudah'): ?>
                <thead class="table-light"><tr><th>No. Piagam</th><th>NRG</th><th>Mapel Sertifikasi</th><th>Badan Sertifikasi</th><th>Jalur</th><th>Tanggal Piagam</th><th>Aksi</th></tr></thead>
                <tbody><?php while ($riwayat = mysqli_fetch_assoc($daftar_riwayat)): ?>
                    <tr><td><?php echo nilai_buku_guru($riwayat, 'judul'); ?></td><td><?php echo nilai_buku_guru($riwayat, 'nrg', '-'); ?></td><td><?php echo nilai_buku_guru($riwayat, 'mapel_sertifikasi', '-'); ?></td><td><?php echo nilai_buku_guru($riwayat, 'instansi', '-'); ?></td><td><?php echo nilai_buku_guru($riwayat, 'jalur_sertifikasi', '-'); ?></td><td><?php echo !empty($riwayat['tanggal']) ? date('d-m-Y', strtotime($riwayat['tanggal'])) : '-'; ?></td><td class="text-nowrap"><button type="button" class="btn btn-sm btn-outline-primary me-1" data-edit-riwayat data-id="<?php echo (int)$riwayat['id']; ?>" data-kategori="sertifikasi" data-tanggal="<?php echo nilai_buku_guru($riwayat, 'tanggal'); ?>" data-judul="<?php echo nilai_buku_guru($riwayat, 'judul'); ?>" data-instansi="<?php echo nilai_buku_guru($riwayat, 'instansi'); ?>" data-nrg="<?php echo nilai_buku_guru($riwayat, 'nrg'); ?>" data-mapel-sertifikasi="<?php echo nilai_buku_guru($riwayat, 'mapel_sertifikasi'); ?>" data-jalur-sertifikasi="<?php echo nilai_buku_guru($riwayat, 'jalur_sertifikasi'); ?>"><i class="fa-solid fa-pen"></i></button><form class="d-inline" action="index?page=buku_induk_guru&amp;bagian=sertifikasi&amp;guru=<?php echo (int)$filter_guru; ?>" method="POST" onsubmit="return confirm('Hapus sertifikasi ini?');"><input type="hidden" name="hapus_riwayat_guru" value="1"><input type="hidden" name="riwayat_id" value="<?php echo (int)$riwayat['id']; ?>"><button class="btn btn-sm btn-outline-danger" type="submit" title="Hapus"><i class="fa-solid fa-trash"></i></button></form></td></tr>
                <?php endwhile; if ($jumlah_sertifikasi_guru === 0): ?><tr><td colspan="7" class="text-center text-muted py-3">Belum ada sertifikasi guru.</td></tr><?php endif; ?></tbody>
                <?php elseif ($filter_guru > 0): ?><tbody><tr><td class="text-center text-muted py-3">Form sertifikasi akan tersedia setelah status diatur menjadi Sudah.</td></tr></tbody><?php endif; ?>
                <?php elseif ($bagian === 'mutasi'): ?>
                <?php foreach (['masuk' => 'Mutasi Masuk', 'keluar' => 'Mutasi Keluar'] as $jenis_mutasi_tabel => $judul_mutasi_tabel): ?>
                <section class="mb-4">
                    <h4 class="h6 fw-bold"><?php echo $judul_mutasi_tabel; ?></h4>
                    <div class="table-responsive"><table class="table table-hover align-middle mb-0 staff-table">
                        <?php if ($jenis_mutasi_tabel === 'masuk'): ?>
                        <thead class="table-light"><tr><th>Tanggal</th><th>Pegawai</th><th>Asal Sekolah Sebelumnya</th><th>Aksi</th></tr></thead>
                        <?php else: ?>
                        <thead class="table-light"><tr><th>Tanggal</th><th>Pegawai</th><th>Tujuan</th><th>Alasan</th><th>Aksi</th></tr></thead>
                        <?php endif; ?>
                        <tbody>
                        <?php foreach ($riwayat_mutasi[$jenis_mutasi_tabel] as $riwayat): ?>
                            <tr>
                                <td><?php echo !empty($riwayat['tanggal']) ? date('d-m-Y', strtotime($riwayat['tanggal'])) : '-'; ?></td>
                                <td><?php echo nilai_buku_guru($riwayat, 'nama_guru'); ?><br><small class="text-muted"><?php echo nilai_buku_guru($riwayat, 'nip'); ?></small></td>
                                <?php if ($jenis_mutasi_tabel === 'masuk'): ?>
                                <td><?php echo nilai_buku_guru($riwayat, 'mutasi_asal', $riwayat['instansi'] ?? '-'); ?></td>
                                <?php else: ?>
                                <td><?php echo nilai_buku_guru($riwayat, 'mutasi_tujuan', $riwayat['instansi'] ?? '-'); ?></td>
                                <td><?php echo nl2br(nilai_buku_guru($riwayat, 'mutasi_alasan', $riwayat['detail'] ?? '-')); ?></td>
                                <?php endif; ?>
                                <td><form action="index?page=buku_induk_guru&amp;bagian=mutasi&amp;guru=<?php echo (int)$filter_guru; ?>" method="POST" onsubmit="return confirm('Hapus riwayat mutasi ini?');"><input type="hidden" name="hapus_riwayat_guru" value="1"><input type="hidden" name="riwayat_id" value="<?php echo (int)$riwayat['id']; ?>"><button class="btn btn-sm btn-outline-danger" type="submit" title="Hapus"><i class="fa-solid fa-trash"></i></button></form></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($riwayat_mutasi[$jenis_mutasi_tabel])): ?><tr><td colspan="<?php echo $jenis_mutasi_tabel === 'masuk' ? 4 : 5; ?>" class="text-center text-muted py-3">Belum ada data <?php echo strtolower($judul_mutasi_tabel); ?>.</td></tr><?php endif; ?>
                        </tbody>
                    </table></div>
                </section>
                <?php endforeach; ?>
                <?php else: ?>
                <thead class="table-light"><tr><th>Tanggal</th><th>Pegawai</th><th>Nama / Judul</th><th>Instansi / Hubungan</th><th>Detail</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php while ($riwayat = mysqli_fetch_assoc($daftar_riwayat)): ?>
                    <tr><td><?php echo !empty($riwayat['tanggal']) ? date('d-m-Y', strtotime($riwayat['tanggal'])) : '-'; ?></td><td><?php echo nilai_buku_guru($riwayat, 'nama_guru'); ?><br><small class="text-muted"><?php echo nilai_buku_guru($riwayat, 'nip'); ?></small></td><td><?php echo nilai_buku_guru($riwayat, 'judul'); ?></td><td><?php echo nilai_buku_guru($riwayat, 'instansi', '-'); ?></td><td><?php echo nl2br(nilai_buku_guru($riwayat, 'detail', '-')); ?></td><td class="text-nowrap"><?php if ($bagian === 'penghargaan'): ?><button type="button" class="btn btn-sm btn-outline-primary me-1" data-edit-riwayat data-id="<?php echo (int)$riwayat['id']; ?>" data-kategori="penghargaan" data-tanggal="<?php echo nilai_buku_guru($riwayat, 'tanggal'); ?>" data-judul="<?php echo nilai_buku_guru($riwayat, 'judul'); ?>" data-instansi="<?php echo nilai_buku_guru($riwayat, 'instansi'); ?>" data-detail="<?php echo nilai_buku_guru($riwayat, 'detail'); ?>"><i class="fa-solid fa-pen"></i></button><?php endif; ?><form class="d-inline" action="index?page=buku_induk_guru&amp;bagian=<?php echo urlencode($bagian); ?>&amp;guru=<?php echo (int)$filter_guru; ?>" method="POST" onsubmit="return confirm('Hapus riwayat ini?');"><input type="hidden" name="hapus_riwayat_guru" value="1"><input type="hidden" name="riwayat_id" value="<?php echo (int)$riwayat['id']; ?>"><button class="btn btn-sm btn-outline-danger" type="submit" title="Hapus"><i class="fa-solid fa-trash"></i></button></form></td></tr>
                <?php endwhile; ?>
                </tbody>
                <?php endif; ?>
            </table>
        </div></div>
        <?php if (in_array($bagian, ['keaktifan', 'pendidikan', 'keluarga', 'penghargaan', 'sertifikasi'], true) && $filter_guru > 0): ?>
        <div class="modal fade" id="edit-riwayat-modal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content">
                <form action="index?page=buku_induk_guru&amp;bagian=<?php echo urlencode($bagian); ?>&amp;guru=<?php echo $filter_guru; ?>" method="POST" id="form-edit-riwayat">
                    <div class="modal-header"><h5 class="modal-title">Edit Riwayat</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
                    <div class="modal-body">
                        <input type="hidden" name="edit_riwayat_guru" value="1"><input type="hidden" name="riwayat_id" id="edit-riwayat-id"><input type="hidden" name="guru_id_riwayat" value="<?php echo $filter_guru; ?>"><input type="hidden" name="kategori" id="edit-riwayat-kategori">
                        <div class="row g-3">
                            <div class="col-md-4" data-edit-field="tanggal"><label class="form-label">Tanggal</label><input type="date" class="form-control" name="tanggal" id="edit-riwayat-tanggal"></div>
                            <div class="col-md-8" data-edit-field="judul"><label class="form-label" id="edit-riwayat-judul-label">Nama / Judul</label><input class="form-control" name="judul" id="edit-riwayat-judul"></div>
                            <div class="col-md-6" data-edit-field="instansi"><label class="form-label">Instansi / Lembaga Induk</label><input class="form-control" name="instansi" id="edit-riwayat-instansi"></div>
                            <div class="col-md-6" data-edit-field="detail"><label class="form-label">Keterangan</label><input class="form-control" name="detail" id="edit-riwayat-detail"></div>
                            <div class="col-md-4 d-none" data-edit-field="jenjang"><label class="form-label">Jenjang</label><input class="form-control" name="jenjang" id="edit-riwayat-jenjang"></div>
                            <div class="col-md-4 d-none" data-edit-field="fakultas"><label class="form-label">Fakultas</label><input class="form-control" name="fakultas" id="edit-riwayat-fakultas"></div>
                            <div class="col-md-4 d-none" data-edit-field="jurusan"><label class="form-label">Jurusan</label><input class="form-control" name="jurusan" id="edit-riwayat-jurusan"></div>
                            <div class="col-md-4 d-none" data-edit-field="tahun-lulus"><label class="form-label">Tahun Lulus</label><input class="number form-control" name="tahun_lulus" id="edit-riwayat-tahun-lulus"></div>
                            <div class="col-md-4 d-none" data-edit-field="lptk"><label class="form-label">LPTK</label><select class="form-select" name="lptk" id="edit-riwayat-lptk"><option value="Ya">Ya</option><option value="Tidak">Tidak</option></select></div>
                            <div class="col-md-4 d-none" data-edit-field="instansi-sk"><label class="form-label">Instansi Penerbit SK</label><select class="form-select" name="instansi_sk" id="edit-riwayat-instansi-sk"><option value="">Pilih</option><?php foreach (['Kementerian Agama', 'Pemerintah Daerah', 'Kementerian Lainnya', 'Yayasan Penyelenggara Pendidikan', 'Satuan Pendidikan Sekolah', 'Lainnya'] as $opsi_instansi): ?><option value="<?php echo htmlspecialchars($opsi_instansi); ?>"><?php echo htmlspecialchars($opsi_instansi); ?></option><?php endforeach; ?></select></div>
                            <div class="col-md-4 d-none" data-edit-field="nomor-sk"><label class="form-label">Nomor SK</label><input class="form-control" name="nomor_sk" id="edit-riwayat-nomor-sk"></div>
                            <div class="col-md-4 d-none" data-edit-field="tanggal-sk"><label class="form-label">Tanggal SK</label><input type="date" class="form-control" name="tanggal_sk" id="edit-riwayat-tanggal-sk"></div>
                            <div class="col-md-4 d-none" data-edit-field="hubungan"><label class="form-label">Hubungan</label><input class="form-control" name="hubungan" id="edit-riwayat-hubungan"></div>
                            <div class="col-md-4 d-none" data-edit-field="jenis-kelamin"><label class="form-label">Jenis Kelamin</label><select class="form-select" name="jenis_kelamin_keluarga" id="edit-riwayat-jenis-kelamin"><option value="">Pilih</option><option value="Laki-laki">Laki-laki</option><option value="Perempuan">Perempuan</option></select></div>
                            <div class="col-md-4 d-none" data-edit-field="tempat-lahir"><label class="form-label">Tempat Lahir</label><input class="form-control" name="tempat_lahir_keluarga" id="edit-riwayat-tempat-lahir"></div>
                            <div class="col-md-4 d-none" data-edit-field="pendidikan-keluarga"><label class="form-label">Pendidikan</label><input class="form-control" name="pendidikan_anggota" id="edit-riwayat-pendidikan-keluarga"></div>
                            <div class="col-md-4 d-none" data-edit-field="nrg"><label class="form-label">NRG</label><input class="form-control" name="nrg" id="edit-riwayat-nrg"></div>
                            <div class="col-md-4 d-none" data-edit-field="mapel-sertifikasi"><label class="form-label">Mapel Sertifikasi</label><input class="form-control" name="mapel_sertifikasi" id="edit-riwayat-mapel-sertifikasi"></div>
                            <div class="col-md-4 d-none" data-edit-field="jalur-sertifikasi"><label class="form-label">Jalur Sertifikasi</label><input class="form-control" name="jalur_sertifikasi" id="edit-riwayat-jalur-sertifikasi"></div>
                        </div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-danger"><i class="fa-solid fa-floppy-disk me-1"></i>Simpan Perubahan</button></div>
                </form>
            </div></div>
        </div>
        <?php endif; ?>
        <?php if ($bagian === 'sertifikasi' && !empty($riwayat_pangkat_lama)): ?>
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white"><h3 class="h6 fw-bold mb-0">Riwayat Pangkat Sebelumnya</h3></div>
            <div class="table-responsive"><table class="table table-hover align-middle mb-0 staff-table">
                <thead class="table-light"><tr><th>TMT</th><th>Pangkat / Golongan</th><th>Nomor SK / Instansi</th><th>Keterangan</th><th>Aksi</th></tr></thead>
                <tbody><?php foreach ($riwayat_pangkat_lama as $riwayat): ?>
                    <tr><td><?php echo !empty($riwayat['tanggal']) ? date('d-m-Y', strtotime($riwayat['tanggal'])) : '-'; ?></td><td><?php echo nilai_buku_guru($riwayat, 'judul'); ?></td><td><?php echo nilai_buku_guru($riwayat, 'instansi', '-'); ?></td><td><?php echo nl2br(nilai_buku_guru($riwayat, 'detail', '-')); ?></td><td><form action="index?page=buku_induk_guru&amp;bagian=sertifikasi&amp;guru=<?php echo (int)$filter_guru; ?>" method="POST" onsubmit="return confirm('Hapus riwayat pangkat ini?');"><input type="hidden" name="hapus_riwayat_guru" value="1"><input type="hidden" name="riwayat_id" value="<?php echo (int)$riwayat['id']; ?>"><button class="btn btn-sm btn-outline-danger" type="submit" title="Hapus"><i class="fa-solid fa-trash"></i></button></form></td></tr>
                <?php endforeach; ?></tbody>
            </table></div>
        </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($bagian === 'info_sertifikasi'): ?>
    <div class="card border-0 shadow-sm certification-report">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <div>
                <h3 class="h6 fw-bold mb-1">Rekap Sertifikasi dan Inpassing Guru</h3>
                <div class="small text-muted">Menampilkan guru yang sudah memiliki data sertifikasi dan/atau berstatus inpassing.</div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary no-print" onclick="window.print()"><i class="fa-solid fa-print me-1"></i>Cetak</button>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle mb-0 staff-list-table">
                <thead class="table-light">
                    <tr>
                        <th rowspan="2">No.</th><th rowspan="2">Nama Guru</th><th rowspan="2">NIP</th><th rowspan="2">NIK</th><th rowspan="2">NPK</th>
                        <th colspan="7" class="text-center">Sertifikasi</th><th colspan="5" class="text-center">Inpassing</th>
                    </tr>
                    <tr>
                        <th>Status</th><th>No. Piagam</th><th>NRG</th><th>Mata Pelajaran</th><th>Badan Sertifikasi</th><th>Jalur</th><th>Tanggal</th>
                        <th>Pangkat/Golongan</th><th>TMT</th><th>No. SK</th><th>Keterangan</th><th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no_info_sertifikasi = 1; while ($info_guru = mysqli_fetch_assoc($daftar_info_sertifikasi)): ?>
                    <tr>
                        <td><?php echo $no_info_sertifikasi++; ?></td>
                        <td><?php echo nilai_buku_guru($info_guru, 'nama_guru'); ?><br><small class="text-muted"><?php echo nilai_buku_guru($info_guru, 'jabatan'); ?></small></td>
                        <td><?php echo nilai_buku_guru($info_guru, 'nip', '-'); ?></td>
                        <td><?php echo nilai_buku_guru($info_guru, 'nik', '-'); ?></td>
                        <td><?php echo nilai_buku_guru($info_guru, 'npk', '-'); ?></td>
                        <td><?php echo (($info_guru['status_sertifikasi'] ?? '') === 'Sudah' || !empty($info_guru['no_piagam'])) ? 'Sudah' : '-'; ?></td>
                        <td><?php echo nilai_buku_guru($info_guru, 'no_piagam', '-'); ?></td>
                        <td><?php echo nilai_buku_guru($info_guru, 'nrg', '-'); ?></td>
                        <td><?php echo nilai_buku_guru($info_guru, 'mapel_sertifikasi', '-'); ?></td>
                        <td><?php echo nilai_buku_guru($info_guru, 'badan_sertifikasi', '-'); ?></td>
                        <td><?php echo nilai_buku_guru($info_guru, 'jalur_sertifikasi', '-'); ?></td>
                        <td><?php echo !empty($info_guru['tanggal_piagam']) ? date('d-m-Y', strtotime($info_guru['tanggal_piagam'])) : '-'; ?></td>
                        <td><?php echo nilai_buku_guru($info_guru, 'pangkat_golongan', '-'); ?></td>
                        <td><?php echo !empty($info_guru['tmt_pangkat']) && $info_guru['tmt_pangkat'] !== '0000-00-00' ? date('d-m-Y', strtotime($info_guru['tmt_pangkat'])) : '-'; ?></td>
                        <td><?php echo nilai_buku_guru($info_guru, 'nomor_sk_inpassing', '-'); ?></td>
                        <td><?php echo nilai_buku_guru($info_guru, 'keterangan_inpassing', '-'); ?></td>
                        <td><?php echo nilai_buku_guru($info_guru, 'status_inpassing', 'Belum'); ?></td>
                    </tr>
                    <?php endwhile; if (mysqli_num_rows($daftar_info_sertifikasi) === 0): ?>
                    <tr><td colspan="17" class="text-center py-4 text-muted">Belum ada data guru sertifikasi atau inpassing.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($bagian === 'foto'): ?>
    <div class="row g-3">
        <?php mysqli_data_seek($daftar_guru, 0); while ($pegawai = mysqli_fetch_assoc($daftar_guru)): ?>
        <div class="col-6 col-md-4 col-xl-3"><div class="card border-0 shadow-sm h-100 text-center p-3">
            <?php if (!empty($pegawai['foto'])): ?><img src="<?php echo nilai_buku_guru($pegawai, 'foto'); ?>" alt="Foto <?php echo nilai_buku_guru($pegawai, 'nama_guru'); ?>" class="rounded-circle mx-auto mb-3 object-fit-cover" width="120" height="120"><?php else: ?><div class="rounded-circle bg-light d-flex align-items-center justify-content-center mx-auto mb-3 text-muted" style="width:120px;height:120px"><i class="fa-solid fa-user fa-3x"></i></div><?php endif; ?>
            <div class="fw-semibold"><?php echo nilai_buku_guru($pegawai, 'nama_guru'); ?></div><div class="small text-muted mb-3"><?php echo nilai_buku_guru($pegawai, 'jabatan'); ?></div>
            <a href="index.php?page=buku_induk_guru&amp;bagian=pegawai&amp;edit=<?php echo (int)$pegawai['id']; ?>#profil-foto" class="btn btn-sm btn-outline-danger mt-auto">Atur Foto</a>
        </div></div>
        <?php endwhile; ?>
        <?php if ($jumlah_guru === 0): ?><div class="col-12"><div class="alert alert-light text-center">Belum ada foto pegawai.</div></div><?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($bagian === 'laporan'): ?>
    <div class="card border-0 shadow-sm mb-4 no-print">
        <div class="card-body">
            <h3 class="h5 fw-bold mb-3">Cetak Buku Induk Pegawai</h3>
            <div class="row g-3 align-items-end">
                <form action="index" method="GET" class="col-lg-8 row g-2 align-items-end">
                    <input type="hidden" name="page" value="buku_induk_guru"><input type="hidden" name="bagian" value="laporan"><input type="hidden" name="cetak" value="profil">
                    <div class="col-md-8"><label class="form-label">Pilih guru untuk mencetak seluruh data per guru</label>
                        <input type="search" class="form-control mb-2" data-filter-select="guru-laporan" placeholder="Cari nama atau NIP guru">
                        <select class="form-select" name="guru" id="guru-laporan" required><option value="">Pilih guru</option><?php mysqli_data_seek($daftar_guru, 0); while ($opsi_guru = mysqli_fetch_assoc($daftar_guru)): ?><option value="<?php echo (int)$opsi_guru['id']; ?>"><?php echo nilai_buku_guru($opsi_guru, 'nama_guru'); ?> (<?php echo nilai_buku_guru($opsi_guru, 'nip', 'tanpa NIP'); ?>)</option><?php endwhile; ?></select>
                    </div>
                    <div class="col-md-4"><button class="btn btn-outline-danger w-100" type="submit"><i class="fa-solid fa-print me-1"></i>Cetak Data Per Guru</button></div>
                </form>
                <div class="col-lg-4 d-grid gap-2">
                    <a class="btn btn-danger" target="_blank" rel="noopener" href="index.php?page=buku_induk_guru&amp;bagian=laporan&amp;cetak=semua"><i class="fa-solid fa-print me-1"></i>Cetak Daftar Guru</a>
                    <a class="btn btn-outline-danger" href="index.php?page=buku_induk_guru&amp;export_guru=excel"><i class="fa-solid fa-file-excel me-1"></i>Unduh Excel</a>
                    <a class="btn btn-outline-danger" target="_blank" rel="noopener" href="index.php?page=buku_induk_guru&amp;bagian=laporan&amp;cetak=semua&amp;pdf=1"><i class="fa-solid fa-file-pdf me-1"></i>Simpan PDF</a>
                </div>
            </div>
            <div class="form-text mt-2">Cetak dan simpan PDF menggunakan kertas Folio/F4 landscape dengan margin 10 mm. Pada dialog cetak, pilih "Save as PDF" untuk mengunduh PDF.</div>
        </div>
    </div>
    <div class="card border-0 shadow-sm"><div class="table-responsive">
        <table class="table table-hover align-middle mb-0 staff-table">
            <thead class="table-light"><tr><th>No</th><th>Nama</th><th>Tempat Lahir</th><th>Tanggal Lahir</th><th>NIP / NUPTK</th><th>NIK</th><th>NPK</th><th>NRG</th><th>Jabatan</th><th class="no-print">Cetak</th></tr></thead>
            <tbody><?php mysqli_data_seek($daftar_guru, 0); $no = 1; while ($pegawai = mysqli_fetch_assoc($daftar_guru)): ?>
                <tr><td><?php echo $no++; ?></td><td><?php echo nilai_buku_guru($pegawai, 'nama_guru'); ?></td><td><?php echo nilai_buku_guru($pegawai, 'tempat_lahir', '-'); ?></td><td><?php echo !empty($pegawai['tanggal_lahir']) && $pegawai['tanggal_lahir'] !== '0000-00-00' ? date('d-m-Y', strtotime($pegawai['tanggal_lahir'])) : '-'; ?></td><td><?php echo nilai_buku_guru($pegawai, 'nip', '-'); ?><br><?php echo nilai_buku_guru($pegawai, 'nuptk'); ?></td><td><?php echo nilai_buku_guru($pegawai, 'nik', '-'); ?></td><td><?php echo nilai_buku_guru($pegawai, 'npk', '-'); ?></td><td><?php echo nilai_buku_guru($pegawai, 'nrg', '-'); ?></td><td><?php echo nilai_buku_guru($pegawai, 'jabatan'); ?></td><td class="no-print"><a class="btn btn-sm btn-outline-danger" target="_blank" href="index.php?page=buku_induk_guru&amp;bagian=laporan&amp;cetak=profil&amp;guru=<?php echo (int)$pegawai['id']; ?>"><i class="fa-solid fa-print me-1"></i>Cetak Profil</a></td></tr>
            <?php endwhile; if ($jumlah_guru === 0): ?><tr><td colspan="10" class="text-center py-4 text-muted">Belum ada data pegawai.</td></tr><?php endif; ?></tbody>
        </table>
    </div></div>
    <?php endif; ?>

    <?php if ($bagian === 'dashboard'): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h3 class="h6 fw-bold mb-0">Pegawai Terdaftar</h3>
            <div class="d-flex flex-wrap gap-2 no-print">
                <a class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener" href="index.php?page=buku_induk_guru&amp;bagian=laporan&amp;cetak=semua"><i class="fa-solid fa-print me-1"></i>Cetak</a>
                <a class="btn btn-sm btn-outline-secondary" href="index.php?page=buku_induk_guru&amp;export_guru=excel"><i class="fa-solid fa-file-excel me-1"></i>Excel</a>
                <a class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener" href="index.php?page=buku_induk_guru&amp;bagian=laporan&amp;cetak=semua&amp;pdf=1"><i class="fa-solid fa-file-pdf me-1"></i>PDF</a>
            </div>
        </div>
        <?php foreach (['Aktif' => $daftar_guru_aktif, 'Nonaktif' => $daftar_guru_non_aktif] as $judul_status => $daftar_status): ?>
        <div class="p-3 <?php echo $judul_status === 'Nonaktif' ? 'border-top' : ''; ?>">
            <h4 class="h6 fw-bold"><?php echo htmlspecialchars($judul_status); ?> <span class="text-muted fw-normal">(<?php echo count($daftar_status); ?>)</span></h4>
            <div class="table-responsive"><table class="table table-hover align-middle mb-0 staff-table">
                <thead class="table-light"><tr><th>No</th><th>Nama</th><th>Tempat Lahir</th><th>Tanggal Lahir</th><th>NIP / NUPTK</th><th>NIK</th><th>NPK</th><th>NRG</th><th>Jabatan</th><th>Mapel</th><th>Kontak</th></tr></thead>
                <tbody><?php foreach ($daftar_status as $no => $pegawai): ?>
                    <tr><td><?php echo $no + 1; ?></td><td><?php echo nilai_buku_guru($pegawai, 'nama_guru'); ?></td><td><?php echo nilai_buku_guru($pegawai, 'tempat_lahir', '-'); ?></td><td><?php echo !empty($pegawai['tanggal_lahir']) && $pegawai['tanggal_lahir'] !== '0000-00-00' ? date('d-m-Y', strtotime($pegawai['tanggal_lahir'])) : '-'; ?></td><td><?php echo nilai_buku_guru($pegawai, 'nip', '-'); ?><?php if (!empty($pegawai['nuptk'])): ?><br><small class="text-muted"><?php echo nilai_buku_guru($pegawai, 'nuptk'); ?></small><?php endif; ?></td><td><?php echo nilai_buku_guru($pegawai, 'nik', '-'); ?></td><td><?php echo nilai_buku_guru($pegawai, 'npk', '-'); ?></td><td><?php echo nilai_buku_guru($pegawai, 'nrg', '-'); ?></td><td><?php echo nilai_buku_guru($pegawai, 'jabatan'); ?></td><td><?php echo nilai_buku_guru($pegawai, 'mata_pelajaran', '-'); ?></td><td><?php echo nilai_buku_guru($pegawai, 'no_hp', '-'); ?></td></tr>
                <?php endforeach; ?>
                <?php if (empty($daftar_status)): ?><tr><td colspan="11" class="text-center py-4 text-muted">Tidak ada pegawai pada daftar ini.</td></tr><?php endif; ?></tbody>
            </table></div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-filter-select]').forEach(function (searchInput) {
        var select = document.getElementById(searchInput.getAttribute('data-filter-select'));
        if (!select) {
            return;
        }
        searchInput.addEventListener('input', function () {
            var needle = searchInput.value.trim().toLocaleLowerCase();
            Array.prototype.forEach.call(select.options, function (option) {
                option.hidden = option.value !== '' && option.text.toLocaleLowerCase().indexOf(needle) === -1;
            });
        });
    });

    var editModalElement = document.getElementById('edit-riwayat-modal');
    if (editModalElement && window.bootstrap) {
        var editModal = new bootstrap.Modal(editModalElement);
        var fieldGroups = editModalElement.querySelectorAll('[data-edit-field]');
        var editForm = document.getElementById('form-edit-riwayat');
        var setEditValue = function (id, value) {
            var field = document.getElementById(id);
            if (field) {
                field.value = value || '';
            }
        };
        document.querySelectorAll('[data-edit-riwayat]').forEach(function (button) {
            button.addEventListener('click', function () {
                var row = button.dataset;
                var categoryFields = {
                    keaktifan: ['tanggal', 'judul', 'instansi', 'instansi-sk', 'nomor-sk', 'tanggal-sk'],
                    pendidikan: ['jenjang', 'instansi', 'fakultas', 'jurusan', 'tahun-lulus', 'lptk'],
                    keluarga: ['tanggal', 'judul', 'hubungan', 'jenis-kelamin', 'tempat-lahir', 'pendidikan-keluarga', 'detail'],
                    penghargaan: ['tanggal', 'judul', 'instansi', 'detail'],
                    sertifikasi: ['tanggal', 'judul', 'instansi', 'nrg', 'mapel-sertifikasi', 'jalur-sertifikasi']
                };
                var category = row.kategori;
                fieldGroups.forEach(function (group) {
                    var active = (categoryFields[category] || []).indexOf(group.getAttribute('data-edit-field')) !== -1;
                    group.classList.toggle('d-none', !active);
                    var input = group.querySelector('input, select, textarea');
                    if (input) {
                        input.required = false;
                    }
                });
                setEditValue('edit-riwayat-id', row.id);
                setEditValue('edit-riwayat-kategori', category);
                setEditValue('edit-riwayat-tanggal', row.tanggal);
                setEditValue('edit-riwayat-judul', row.judul);
                setEditValue('edit-riwayat-instansi', row.instansi);
                setEditValue('edit-riwayat-detail', row.detail);
                setEditValue('edit-riwayat-jenjang', row.jenjang);
                setEditValue('edit-riwayat-fakultas', row.fakultas);
                setEditValue('edit-riwayat-jurusan', row.jurusan);
                setEditValue('edit-riwayat-tahun-lulus', row.tahunLulus);
                setEditValue('edit-riwayat-lptk', row.lptk || 'Tidak');
                setEditValue('edit-riwayat-instansi-sk', row.instansiSk);
                setEditValue('edit-riwayat-nomor-sk', row.nomorSk);
                setEditValue('edit-riwayat-tanggal-sk', row.tanggalSk);
                setEditValue('edit-riwayat-hubungan', row.instansi || 'Pasangan');
                setEditValue('edit-riwayat-jenis-kelamin', row.jenisKelamin);
                setEditValue('edit-riwayat-tempat-lahir', row.tempatLahir);
                setEditValue('edit-riwayat-pendidikan-keluarga', row.instansi === 'Anak' ? row.pendidikanAnak : row.pendidikanKeluarga);
                setEditValue('edit-riwayat-nrg', row.nrg);
                setEditValue('edit-riwayat-mapel-sertifikasi', row.mapelSertifikasi);
                setEditValue('edit-riwayat-jalur-sertifikasi', row.jalurSertifikasi);
                var title = editModalElement.querySelector('.modal-title');
                title.textContent = category === 'keaktifan' ? 'Edit Status Keaktifan' : (category === 'pendidikan' ? 'Edit Pendidikan' : (category === 'keluarga' ? 'Edit Data Keluarga' : (category === 'sertifikasi' ? 'Edit Data Sertifikasi' : 'Edit Penghargaan')));
                editModal.show();
            });
        });
        editForm.addEventListener('submit', function (event) {
            var category = document.getElementById('edit-riwayat-kategori').value;
            var requiredIds = category === 'keaktifan' ? ['edit-riwayat-tanggal', 'edit-riwayat-judul']
                : category === 'pendidikan' ? ['edit-riwayat-jenjang', 'edit-riwayat-instansi']
                : category === 'keluarga' ? ['edit-riwayat-judul', 'edit-riwayat-hubungan']
                : category === 'sertifikasi' ? ['edit-riwayat-tanggal', 'edit-riwayat-judul', 'edit-riwayat-instansi', 'edit-riwayat-mapel-sertifikasi', 'edit-riwayat-jalur-sertifikasi']
                : ['edit-riwayat-judul'];
            if (requiredIds.some(function (id) { return !document.getElementById(id).value.trim(); })) {
                event.preventDefault();
                window.alert('Mohon lengkapi isian yang wajib.');
            }
        });
    }

    var jenjangPendidikan = document.getElementById('jenjang-pendidikan');
    if (jenjangPendidikan) {
        var aturKolomPendidikan = function () {
            var jenjangSekolah = ['SD', 'MI', 'SMP', 'MTs', 'SMA', 'MA', 'SMK'].indexOf(jenjangPendidikan.value) !== -1;
            document.querySelectorAll('[data-jenjang-disabled]').forEach(function (field) {
                field.disabled = jenjangSekolah;
                field.required = !jenjangSekolah && field.name === 'lptk';
                if (jenjangSekolah) {
                    field.value = '';
                }
            });
        };
        jenjangPendidikan.addEventListener('change', aturKolomPendidikan);
        aturKolomPendidikan();
    }
    var jenisMutasi = document.getElementById('jenis-mutasi');
    if (jenisMutasi) {
        var aturKolomMutasi = function () {
            document.querySelectorAll('[data-mutasi-field]').forEach(function (fieldGroup) {
                var aktif = fieldGroup.getAttribute('data-mutasi-field') === jenisMutasi.value;
                fieldGroup.classList.toggle('d-none', !aktif);
                fieldGroup.querySelectorAll('[data-mutasi-input]').forEach(function (field) {
                    field.disabled = !aktif;
                    field.required = aktif;
                    if (!aktif) {
                        field.value = '';
                    }
                });
            });
        };
        jenisMutasi.addEventListener('change', aturKolomMutasi);
        aturKolomMutasi();
    }
    var statusSertifikasi = document.getElementById('status-sertifikasi');
    var statusInpassing = document.getElementById('status-inpassing');
    var jumlahSertifikasiGuru = <?php echo (int)($jumlah_sertifikasi_guru ?? 0); ?>;
    var sertifikasiSudahTersimpan = <?php echo isset($sertifikasi_aktif) && $sertifikasi_aktif ? 'true' : 'false'; ?>;
    var inpassingTersimpan = <?php echo ($profil_sertifikasi['status_inpassing'] ?? '') === 'Sudah' ? 'true' : 'false'; ?>;
    var editInpassingAktif = false;
    var tombolSimpanStatus = document.getElementById('simpan-status-sertifikasi');
    var formStatusSertifikasi = document.getElementById('form-status-sertifikasi');
    var tombolEditInpassing = document.getElementById('edit-inpassing');
    var aturFormSertifikasi = function () {
        var sertifikasiAktif = sertifikasiSudahTersimpan && statusSertifikasi && statusSertifikasi.value === 'Sudah';
        document.querySelectorAll('[data-sertifikasi-detail]').forEach(function (bagian) {
            bagian.classList.toggle('d-none', !sertifikasiAktif);
        });
        document.querySelectorAll('[data-sertifikasi-input]').forEach(function (field) {
            field.disabled = !sertifikasiAktif;
            field.required = sertifikasiAktif;
        });
        var statusInpassingWrapper = document.getElementById('status-inpassing-wrapper');
        var statusInpassingAktif = sertifikasiAktif && jumlahSertifikasiGuru > 0;
        if (statusInpassingWrapper) {
            statusInpassingWrapper.classList.toggle('d-none', !statusInpassingAktif);
        }
        if (statusInpassing) {
            statusInpassing.disabled = !statusInpassingAktif;
            statusInpassing.required = statusInpassingAktif;
        }
        var inpassingAktif = statusInpassingAktif && statusInpassing.value === 'Sudah' && (!inpassingTersimpan || editInpassingAktif);
        document.querySelectorAll('[data-inpassing-detail]').forEach(function (bagian) {
            bagian.classList.toggle('d-none', !inpassingAktif);
        });
        document.querySelectorAll('[data-inpassing-input]').forEach(function (field) {
            field.disabled = !inpassingAktif;
            field.required = inpassingAktif && field.name !== 'keterangan_inpassing';
        });
        if (tombolSimpanStatus) {
            tombolSimpanStatus.classList.toggle('d-none', inpassingTersimpan && !editInpassingAktif);
        }
    };
    if (statusSertifikasi) {
        statusSertifikasi.addEventListener('change', aturFormSertifikasi);
        statusSertifikasi.addEventListener('change', function () {
            if (tombolSimpanStatus) {
                tombolSimpanStatus.classList.remove('d-none');
            }
        });
        if (statusInpassing) {
            statusInpassing.addEventListener('change', aturFormSertifikasi);
            statusInpassing.addEventListener('change', function () {
                if (tombolSimpanStatus) {
                    tombolSimpanStatus.classList.remove('d-none');
                }
                if (statusInpassing.value !== 'Sudah') {
                    editInpassingAktif = false;
                }
            });
        }
        if (tombolEditInpassing) {
            tombolEditInpassing.addEventListener('click', function () {
                editInpassingAktif = !editInpassingAktif;
                tombolEditInpassing.innerHTML = editInpassingAktif
                    ? '<i class="fa-solid fa-xmark me-1"></i>Batal Edit Inpassing'
                    : '<i class="fa-solid fa-pen me-1"></i>Edit Inpassing';
                if (tombolSimpanStatus) {
                    tombolSimpanStatus.classList.toggle('d-none', !editInpassingAktif);
                }
                aturFormSertifikasi();
            });
        }
        if (formStatusSertifikasi && !inpassingTersimpan) {
            tombolSimpanStatus.classList.remove('d-none');
        }
        aturFormSertifikasi();
    }
});
</script>
<?php if ($bagian === 'laporan' && $mode_cetak === 'semua' && isset($_GET['pdf'])): ?>
<script>window.addEventListener('load', function () { window.print(); });</script>
<?php endif; ?>
<?php endif; ?>
