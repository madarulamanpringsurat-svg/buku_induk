<?php
session_start();
include 'koneksi.php';

if (!isset($koneksi) && isset($conn)) {
    $koneksi = $conn;
}

$id   = isset($_GET['id']) ? intval($_GET['id']) : 0;
$type = isset($_GET['type']) ? $_GET['type'] : 'aktif';

if ($id <= 0) {
    die("Error: ID Surat tidak valid.");
}

$tabel_map = [
    'aktif'       => 'surat_aktif',
    'tidak_mampu' => 'surat_tidak_mampu',
    'pindah'      => 'surat_pindah',
    'undangan'    => 'surat_undangan',
    'tugas'       => 'surat_tugas',
    'mgmp'        => 'surat_mgmp',
    'diterima'    => 'surat_diterima'
];

if (!array_key_exists($type, $tabel_map)) {
    die("Error: Jenis surat tidak dikenali.");
}

$table = $tabel_map[$type];

// Query Data Surat dan Data Siswa
if (in_array($type, ['aktif', 'tidak_mampu', 'pindah'])) {
    $query = "SELECT s.*, 
                     sw.nama_siswa AS db_nama, 
                     sw.nomor_induk AS db_nomor_induk,
                     sw.nisn AS db_nisn, 
                     sw.tempat_lahir AS db_tempat_lahir, 
                     sw.tanggal_lahir AS db_tanggal_lahir, 
                     sw.alamat AS db_alamat 
              FROM {$table} s 
              LEFT JOIN siswa sw ON s.id_siswa = sw.id 
              WHERE s.id = {$id}";
} else {
    $query = "SELECT * FROM {$table} WHERE id = {$id}";
}

$result = mysqli_query($koneksi, $query);
if (!$result || mysqli_num_rows($result) == 0) {
    die("Error: Data surat tidak ditemukan.");
}

$data = mysqli_fetch_assoc($result);

// Fallback variabel siswa (Database vs Input Manual)
if (in_array($type, ['aktif', 'tidak_mampu', 'pindah'])) {
    $nama_siswa    = !empty($data['db_nama']) ? $data['db_nama'] : ($data['nama_siswa_manual'] ?? '-');
    $nis           = !empty($data['db_nomor_induk']) ? $data['db_nomor_induk'] : ($data['nis_manual'] ?? '-');
    $nisn          = !empty($data['db_nisn']) ? $data['db_nisn'] : ($data['nisn_manual'] ?? '-');
    $tempat_lahir  = !empty($data['db_tempat_lahir']) ? $data['db_tempat_lahir'] : ($data['tempat_lahir_manual'] ?? '-');
    $tanggal_lahir = !empty($data['db_tanggal_lahir']) ? $data['db_tanggal_lahir'] : ($data['tanggal_lahir_manual'] ?? '-');
    $alamat        = !empty($data['db_alamat']) ? $data['db_alamat'] : ($data['alamat_manual'] ?? '-');
}

$judul_surat = "SURAT KETERANGAN";
if ($type == 'aktif') $judul_surat = "SURAT KETERANGAN AKTIF BELAJAR";
elseif ($type == 'tidak_mampu') $judul_surat = "SURAT KETERANGAN TIDAK MAMPU";
elseif ($type == 'pindah') $judul_surat = "SURAT KETERANGAN PINDAH / KELUAR";
elseif ($type == 'undangan') $judul_surat = "SURAT UNDANGAN WALI MURID";
elseif ($type == 'tugas') $judul_surat = "SURAT TUGAS";
elseif ($type == 'mgmp') $judul_surat = "SURAT TUGAS MGMP";
elseif ($type == 'diterima') $judul_surat = "SURAT KETERANGAN DITERIMA";

function tanggal_indo($tanggal) {
    if (empty($tanggal) || $tanggal == '0000-00-00') return '-';
    $bulan = array(1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember');
    $pecahkan = explode('-', date('Y-m-d', strtotime($tanggal)));
    return $pecahkan[2] . ' ' . $bulan[(int)$pecahkan[1]] . ' ' . $pecahkan[0];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?php echo $judul_surat . " - " . htmlspecialchars($data['nomor_surat'] ?? ''); ?></title>
    <style>
        /* UKURAN KERTAS FOLIO F4 DAN MARGIN SAMA DENGAN WORD */
        @page {
            size: 21.59cm 33.0cm; /* Ukuran Standar Folio / F4 */
            margin: 1.0cm 1.5cm 1.0cm 1.5cm; /* Top, Right, Bottom, Left */
        }
        body { 
            font-family: 'Times New Roman', serif; 
            font-size: 12pt; 
            line-height: 1.5; 
            color: #000; 
            margin: 0; 
            padding: 0; 
        }

        /* KOP SURAT SAMAKAN DENGAN EXPORT WORD */
        .kop-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 0;
            margin-bottom: 10px;
        }
        .kop-table td {
            text-align: center;
            vertical-align: top;
            padding: 0;
        }
        .kop-img {
            display: block;
            margin: 0 auto;
            max-width: 100%;
            height: auto;
        }

        /* TITLE CONTAINER */
        .title-container {
            text-align: center;
            margin-top: 10px;
            margin-bottom: 20px;
        }
        .title { 
            font-weight: bold; 
            text-decoration: underline; 
            font-size: 13pt; 
            text-transform: uppercase; 
        }
        .subtitle { 
            font-size: 11pt; 
            margin-top: 3px;
        }

        /* TABEL KONTEN SURAT */
        table.content-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 15px 0; 
        }
        table.content-table td { 
            padding: 4px 6px; 
            vertical-align: top; 
        }

        /* TABEL TANDA TANGAN */
        .ttd-table { 
            width: 100%; 
            margin-top: 35px; 
            border-collapse: collapse;
        }

        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

    <!-- KOP SURAT GAMBAR PRESISI DI TENGAH -->
    <?php if (file_exists('kop_surat.png')): ?>
    <table class="kop-table" align="center">
        <tr>
            <td align="center">
                <img src="kop_surat.png" class="kop-img" alt="Kop Surat">
            </td>
        </tr>
    </table>
    <?php endif; ?>

    <!-- JUDUL SURAT -->
    <div class="title-container">
        <div class="title"><?php echo $judul_surat; ?></div>
        <div class="subtitle">Nomor: <?php echo htmlspecialchars($data['nomor_surat'] ?? '-'); ?></div>
    </div>

    <!-- PARAGRAF PEMBUKA -->
    <p style="text-indent: 30px; text-align: justify;">Yang bertanda tangan di bawah ini Kepala MA Ma'arif Darul Aman Pringsurat, Temanggung, menerangkan dengan sesungguhnya bahwa:</p>

    <!-- DATA KONTEN SURAT -->
    <table class="content-table">
        <?php if (in_array($type, ['aktif', 'tidak_mampu', 'pindah'])): ?>
            <tr>
                <td width="28%">Nama Lengkap</td>
                <td width="3%">:</td>
                <td><strong><?php echo htmlspecialchars(strtoupper($nama_siswa)); ?></strong></td>
            </tr>
            <tr>
                <td>NIS / NISN</td>
                <td>:</td>
                <td><?php echo htmlspecialchars($nis . ' / ' . $nisn); ?></td>
            </tr>
            <tr>
                <td>Tempat, Tanggal Lahir</td>
                <td>:</td>
                <td><?php echo htmlspecialchars($tempat_lahir . ', ' . tanggal_indo($tanggal_lahir)); ?></td>
            </tr>
            <tr>
                <td>Kelas Saat Ini</td>
                <td>:</td>
                <td>Kelas <?php echo htmlspecialchars($data['kelas_siswa'] ?? '-'); ?></td>
            </tr>
            <tr>
                <td>Alamat Lengkap</td>
                <td>:</td>
                <td><?php echo htmlspecialchars($alamat); ?></td>
            </tr>
            <?php if ($type == 'pindah'): ?>
            <tr>
                <td>Pindah ke Sekolah</td>
                <td>:</td>
                <td><strong><?php echo htmlspecialchars($data['sekolah_tujuan'] ?? '-'); ?></strong></td>
            </tr>
            <?php endif; ?>
        <?php elseif ($type == 'undangan'): ?>
            <tr>
                <td width="28%">Kelas Target</td>
                <td width="3%">:</td>
                <td><strong><?php echo htmlspecialchars($data['kelas_pilihan'] ?? '-'); ?></strong></td>
            </tr>
            <tr>
                <td>Acara / Agenda</td>
                <td>:</td>
                <td><strong><?php echo htmlspecialchars($data['acara'] ?? '-'); ?></strong></td>
            </tr>
            <tr>
                <td>Hari, Tanggal</td>
                <td>:</td>
                <td><?php echo htmlspecialchars(($data['hari'] ?? '') . ', ' . ($data['tanggal'] ?? '')); ?></td>
            </tr>
            <tr>
                <td>Waktu / Jam</td>
                <td>:</td>
                <td><?php echo htmlspecialchars($data['jam'] ?? '-'); ?></td>
            </tr>
            <tr>
                <td>Keterangan</td>
                <td>:</td>
                <td><?php echo htmlspecialchars($data['keterangan'] ?? '-'); ?></td>
            </tr>
        <?php elseif (in_array($type, ['tugas', 'mgmp'])): ?>
            <tr>
                <td width="28%">Nama / Personel</td>
                <td width="3%">:</td>
                <td><strong><?php echo htmlspecialchars($data['jabatan_pilihan'] ?? '-'); ?></strong></td>
            </tr>
            <tr>
                <td>Agenda Kegiatan</td>
                <td>:</td>
                <td><?php echo htmlspecialchars($data['agenda_kegiatan'] ?? '-'); ?></td>
            </tr>
            <tr>
                <td>Hari / Tanggal</td>
                <td>:</td>
                <td><?php echo htmlspecialchars($data['hari_tanggal'] ?? '-'); ?></td>
            </tr>
            <tr>
                <td>Waktu & Tempat</td>
                <td>:</td>
                <td><?php echo htmlspecialchars(($data['waktu_kegiatan'] ?? '-') . ' / ' . ($data['tempat_kegiatan'] ?? '-')); ?></td>
            </tr>
        <?php elseif ($type == 'diterima'): ?>
            <tr>
                <td width="28%">Nama Penerima</td>
                <td width="3%">:</td>
                <td><strong><?php echo htmlspecialchars($data['nama_penerima'] ?? '-'); ?></strong></td>
            </tr>
            <tr>
                <td>TTL / Jenis Kelamin</td>
                <td>:</td>
                <td><?php echo htmlspecialchars(($data['ttl_manual'] ?? '-') . ' / ' . ($data['jk_manual'] ?? '-')); ?></td>
            </tr>
            <tr>
                <td>Kelas Diterima</td>
                <td>:</td>
                <td><?php echo htmlspecialchars($data['kelas_pilihan'] ?? '-'); ?></td>
            </tr>
            <tr>
                <td>Asal Sekolah</td>
                <td>:</td>
                <td><?php echo htmlspecialchars($data['asal_sekolah'] ?? '-'); ?></td>
            </tr>
        <?php endif; ?>
    </table>

    <!-- PARAGRAF PENUTUP -->
    <p style="text-indent: 30px; text-align: justify;">Demikian surat keterangan ini dibuat dengan sebenarnya agar dapat dipergunakan sebagaimana mestinya.</p>

    <!-- TANDA TANGAN -->
    <table class="ttd-table">
        <tr>
            <td width="50%"></td>
            <td width="50%" style="text-align: center;">
                Pringsurat, <?php echo tanggal_indo($data['tanggal_buat'] ?? date('Y-m-d')); ?><br>
                Kepala Madrasah,<br><br><br><br><br>
                <strong><u>MUHAMMAD UTSMAN WIBOWO, S.Pd.I</u></strong>
            </td>
        </tr>
    </table>

    <!-- Pustaka html2pdf.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        window.onload = function() {
            const element = document.body;
            const cleanNomor = "<?php echo preg_replace('/[^a-zA-Z0-9_-]/', '_', $data['nomor_surat'] ?? 'surat'); ?>";
            const filename = "Surat_<?php echo ucfirst($type); ?>_" + cleanNomor + ".pdf";

            const opt = {
                margin:       [10, 15, 10, 15], // Top, Left, Bottom, Right (mm)
                filename:     filename,
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2, useCORS: true },
                jsPDF:        { unit: 'mm', format: [215.9, 330], orientation: 'portrait' } // Ukuran F4
            };

            // Menjalankan proses konversi ke PDF lalu mengunduhnya secara otomatis
            html2pdf().set(opt).from(element).save();
        };
    </script>
</body>
</html>