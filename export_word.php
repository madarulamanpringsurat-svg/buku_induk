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

// Ambil Informasi Kepala Sekolah & Tahun Ajaran
$id_ta = $data['id_tahun_ajaran'] ?? 0;
$query_ta = mysqli_query($koneksi, "SELECT * FROM tahun_ajaran WHERE id = '$id_ta'");
$data_ta = mysqli_fetch_assoc($query_ta);

$tahun_ajaran_aktif = $data_ta ? $data_ta['tahun_ajaran'] : '2026/2027';
$nama_kepala        = $data_ta ? $data_ta['nama_kepala'] : 'MUHAMMAD UTSMAN WIBOWO, S.Pd.I.';
$nip_kepala         = ($data_ta && !empty($data_ta['nip'])) ? $data_ta['nip'] : '-';

// Penanganan data siswa untuk jenis surat umum
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
elseif ($type == 'diterima') $judul_surat = "SURAT KETERANGAN";

function tanggal_indo($tanggal) {
    if (empty($tanggal) || $tanggal == '0000-00-00') return '-';
    $bulan = array(1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember');
    $pecahkan = explode('-', date('Y-m-d', strtotime($tanggal)));
    return $pecahkan[2] . ' ' . $bulan[(int)$pecahkan[1]] . ' ' . $pecahkan[0];
}

// -------------------------------------------------------------
// PENANGANAN GAMBAR KOP SURAT (MIME BASE64)
// -------------------------------------------------------------
$path_kop_lokal = 'kop_surat.png';
$image_base64 = '';

if (file_exists($path_kop_lokal)) {
    $image_base64 = chunk_split(base64_encode(file_get_contents($path_kop_lokal)));
}

// Nama File Word Output
$clean_nomor = preg_replace('/[^a-zA-Z0-9_-]/', '_', $data['nomor_surat']);
$filename = "Surat_" . ucfirst($type) . "_" . $clean_nomor . ".doc";

// Header MIME untuk Word
header("Content-Type: application/msword");
header("Content-Disposition: attachment; filename=\"{$filename}\"");
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Pragma: public");

$boundary = "----=_NextPart_000_0000_01D11111.11111111";

// MEMBANGUN FILE MHTML LENGKAP
ob_start();
?>
MIME-Version: 1.0
Content-Type: multipart/related; boundary="<?php echo $boundary; ?>"; type="text/html"

--<?php echo $boundary; ?>

Content-Type: text/html; charset="utf-8"
Content-Location: file:///C:/document.htm

<html xmlns:o='urn:schemas-microsoft-com:office:office' 
      xmlns:w='urn:schemas-microsoft-com:office:word' 
      xmlns='http://www.w3.org/TR/REC-html40'>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title><?php echo $judul_surat; ?></title>
    <!--[if gte mso 9]>
    <xml>
        <w:WordDocument>
            <w:View>Print</w:View>
            <w:Zoom>100</w:Zoom>
        </w:WordDocument>
    </xml>
    <![endif]-->
    <style>
        @page {
            size: 21.59cm 33.0cm; /* Ukuran Standar Folio / F4 */
            margin: 1.0cm 1.5cm 1.0cm 1.5cm;
        }
        body { 
            font-family: 'Times New Roman', serif; 
            font-size: 11pt; 
            line-height: 1.25; 
            color: #000;
            margin: 0;
            padding: 0;
        }
        .kop-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 0;
            margin-bottom: 5px;
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
        .title-container {
            text-align: center;
            margin-top: 5px;
            margin-bottom: 12px;
        }
        .title { 
            font-weight: bold; 
            text-decoration: underline; 
            font-size: 12pt; 
            text-transform: uppercase; 
        }
        .subtitle { 
            font-size: 11pt; 
            margin-top: 2px;
        }
        p {
            margin: 6px 0;
        }
        table.content-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 4px 0; 
        }
        table.content-table td { 
            padding: 2px 4px; 
            vertical-align: top; 
        }
        .ttd-table { 
            width: 100%; 
            margin-top: 20px; 
        }
    </style>
</head>
<body>

    <!-- KOP SURAT -->
    <?php if (!empty($image_base64)): ?>
    <table class="kop-table" align="center">
        <tr>
            <td align="center">
                <img src="file:///C:/kop_surat.png" class="kop-img" alt="Kop Surat">
            </td>
        </tr>
    </table>
    <?php endif; ?>

    <!-- JUDUL SURAT -->
    <div class="title-container">
        <div class="title"><?php echo $judul_surat; ?></div>
        <div class="subtitle">Nomor: <?php echo htmlspecialchars($data['nomor_surat']); ?></div>
    </div>

    <?php if ($type == 'diterima'): ?>
        
        <!-- STRUKTUR KHUSUS SURAT KETERANGAN DITERIMA -->
        <p style="text-align: justify;">Yang bertanda tangan di bawah ini :</p>

        <!-- DATA KEPALA MADRASAH -->
        <table class="content-table">
            <tr>
                <td width="28%">N a m a</td>
                <td width="3%">:</td>
                <td><strong><?php echo htmlspecialchars($nama_kepala); ?></strong></td>
            </tr>
            <tr>
                <td>N I P</td>
                <td>:</td>
                <td><?php echo htmlspecialchars($nip_kepala); ?></td>
            </tr>
            <tr>
                <td>Jabatan</td>
                <td>:</td>
                <td>Kepala Madrasah</td>
            </tr>
            <tr>
                <td>Unit Kerja</td>
                <td>:</td>
                <td>MA Ma’arif Darul Aman Pringsurat Kab. Temanggung</td>
            </tr>
        </table>

        <p style="text-align: justify; margin-top: 8px;">Dengan ini menerangkan bahwa :</p>

        <!-- DATA SISWA PENERIMA -->
        <table class="content-table">
            <tr>
                <td width="28%">N a m a</td>
                <td width="3%">:</td>
                <td><strong><?php echo htmlspecialchars(strtoupper($data['nama_penerima'] ?? '-')); ?></strong></td>
            </tr>
            <tr>
                <td>Tempat / Tanggal Lahir</td>
                <td>:</td>
                <td><?php echo htmlspecialchars(strtoupper($data['ttl_manual'] ?? '-')); ?></td>
            </tr>
            <tr>
                <td>Jenis Kelamin</td>
                <td>:</td>
                <td><?php echo htmlspecialchars($data['jk_manual'] ?? '-'); ?></td>
            </tr>
            <tr>
                <td>Nama Orangtua/Wali</td>
                <td>:</td>
                <td><?php echo htmlspecialchars(strtoupper($data['nama_ortu'] ?? '-')); ?></td>
            </tr>
            <tr>
                <td>Alamat</td>
                <td>:</td>
                <td><?php echo htmlspecialchars($data['alamat'] ?? '-'); ?></td>
            </tr>
            <tr>
                <td>Sekolah Asal</td>
                <td>:</td>
                <td><?php echo htmlspecialchars(strtoupper($data['asal_sekolah'] ?? '-')); ?></td>
            </tr>
        </table>

        <p style="text-align: justify; margin-top: 8px;">
            Telah diterima sebagai Siswa MA Ma’arif Darul Aman Pringsurat di Kelas <b><?php echo htmlspecialchars($data['kelas_pilihan'] ?? '-'); ?></b> Tahun Ajaran <b><?php echo htmlspecialchars($tahun_ajaran_aktif); ?></b>.
        </p>
        
        <p style="text-align: justify;">
            Demikian surat keterangan ini dibuat dengan sebenar-benarnya agar dapat dipergunakan sebagaimana perlunya.
        </p>

    <?php else: ?>

        <!-- STRUKTUR STANDAR SURAT LAINNYA -->
        <p style="text-indent: 30px; text-align: justify;">Yang bertanda tangan di bawah ini Kepala MA Ma'arif Darul Aman Pringsurat, Temanggung, menerangkan dengan sesungguhnya bahwa:</p>

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
            <?php endif; ?>
        </table>

        <p style="text-indent: 30px; text-align: justify;">Demikian surat keterangan ini dibuat dengan sebenarnya agar dapat dipergunakan sebagaimana mestinya.</p>

    <?php endif; ?>

    <!-- TANDA TANGAN DENGAN SPASI DIPERKETAT -->
    <table class="ttd-table">
        <tr>
            <td width="50%"></td>
            <td width="50%" style="text-align: center;">
                Pringsurat, <?php echo tanggal_indo($data['tanggal_buat'] ?? date('Y-m-d')); ?><br>
                Kepala Madrasah,<br><br><br><br>
                <strong><u><?php echo htmlspecialchars($nama_kepala); ?></u></strong>
            </td>
        </tr>
    </table>

</body>
</html>

<?php if (!empty($image_base64)): ?>
--<?php echo $boundary; ?>

Content-Type: image/png
Content-Transfer-Encoding: base64
Content-Location: file:///C:/kop_surat.png

<?php echo $image_base64; ?>
<?php endif; ?>

--<?php echo $boundary; ?>--
<?php
$content = ob_get_clean();
echo $content;
exit;
?>