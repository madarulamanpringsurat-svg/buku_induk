<?php
// 1. Inisialisasi Koneksi Database (Sesuaikan dengan nama file koneksi asli Anda)
include "koneksi.php"; 

// 2. Tangkap parameter filter tahun ajaran dari URL
$filter_ta = isset($_GET['tahun_ajaran']) ? mysqli_real_escape_string($conn, $_GET['tahun_ajaran']) : '';

// 3. Menentukan nama Tahun Ajaran untuk penamaan file Excel saat diunduh
$nama_ta_BukuInduk = "Semua_Tahun_Ajaran";
if (!empty($filter_ta)) {
    // Memeriksa nama tahun ajaran dari database berdasarkan ID atau string tahun ajaran
    $q_ta = mysqli_query($conn, "SELECT tahun_ajaran FROM tahun_ajaran WHERE id = '$filter_ta' OR tahun_ajaran = '$filter_ta'");
    if ($q_ta && mysqli_num_rows($q_ta) > 0) {
        $d_ta = mysqli_fetch_assoc($q_ta);
        $nama_ta_BukuInduk = $d_ta['tahun_ajaran'];
    } else {
        $nama_ta_BukuInduk = $filter_ta;
    }
}

// Bersihkan nama file dari karakter ilegal agar berkas tidak rusak (corrupt)
$nama_file_clean = str_replace(array('/', '\\', ':', '*', '?', '"', '<', '>', '|', ' '), '_', $nama_ta_BukuInduk);

// 4. Pengaturan Header HTTP agar peramban (browser) mengenali skrip ini sebagai unduhan Excel (.xls)
header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=Buku_Induk_Siswa_TA_" . $nama_file_clean . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

// 5. Susun Query Pengambilan Data Siswa Berdasarkan Filter
if (!empty($filter_ta)) {
    // Jika difilter per tahun ajaran
    $query_siswa = "SELECT * FROM siswa WHERE tahun_ajaran = '$nama_ta_BukuInduk' ORDER BY nama_siswa ASC";
} else {
    // Jika ekspor seluruh data tanpa filter
    $query_siswa = "SELECT * FROM siswa ORDER BY tahun_ajaran DESC, nama_siswa ASC";
}
$baca_siswa = mysqli_query($conn, $query_siswa);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Export Buku Induk Siswa</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
        }
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th {
            background-color: #1e4620;
            color: #ffffff;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
            height: 35px;
        }
        th, td {
            border: 1px solid #000000;
            padding: 6px;
        }
        /* Mencegah nomor berawalan 0 hilang atau berubah menjadi format ilmiah (scientific) di Excel */
        .text-format {
            mso-number-format: "\@";
            text-align: left;
        }
        .text-center {
            text-align: center;
        }
    </style>
</head>
<body>

    <div style="text-align: center; margin-bottom: 20px;">
        <h2>BUKU INDUK PESERTA DIDIK</h2>
        <h3>TAHUN AJARAN: <?php echo strtoupper(str_replace('_', ' ', $nama_ta_BukuInduk)); ?></h3>
    </div>

    <table>
        <thead>
            <tr>
                <th rowspan="2">No</th>
                <th colspan="12">A. KETERANGAN PRIBADI SISWA</th>
                <th colspan="10">B. KETERANGAN ORANG TUA / WALI</th>
                <th colspan="3">C. BANTUAN/KIP</th>
                <th colspan="7">D. STATUS KHUSUS / KELUARAN</th>
            </tr>
            <tr>
                <th>Tahun Ajaran</th>
                <th>No. Induk</th>
                <th>NIS Lokal</th>
                <th>Nama Lengkap</th>
                <th>NISN</th>
                <th>NIK</th>
                <th>Tempat Lahir</th>
                <th>Tanggal Lahir</th>
                <th>L/P</th>
                <th>Agama</th>
                <th>Jumlah Saudara</th>
                <th>Alamat Tempat Tinggal</th>
                
                <th>Nama Ayah</th>
                <th>NIK Ayah</th>
                <th>Status Ayah</th>
                <th>Nama Ibu</th>
                <th>NIK Ibu</th>
                <th>Status Ibu</th>
                <th>Nama Wali</th>
                <th>NIK Wali</th>
                <th>Pekerjaan Wali</th>
                <th>Penghasilan Wali</th>
                
                <th>No. KIP</th>
                <th>No. KKS</th>
                <th>No. PKH</th>
                
                <th>Alasan Putus Sekolah</th>
                <th>Tgl Putus Sekolah</th>
                <th>Alasan Mutasi</th>
                <th>Tujuan Mutasi</th>
                <th>Tgl Mutasi</th>
                <th>Tgl Lulus MA</th>
                <th>No. Ijazah MA</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            if ($baca_siswa && mysqli_num_rows($baca_siswa) > 0) {
                $no = 1;
                while ($row = mysqli_fetch_assoc($baca_siswa)) {
            ?>
                <tr>
                    <td class="text-center"><?php echo $no++; ?></td>
                    
                    <td class="text-center"><?php echo htmlspecialchars($row['tahun_ajaran'] ?? ''); ?></td>
                    <td class="text-format"><?php echo htmlspecialchars($row['nomor_induk'] ?? ''); ?></td>
                    <td class="text-format"><?php echo htmlspecialchars($row['nis_lokal'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['nama_siswa'] ?? ''); ?></td>
                    <td class="text-format"><?php echo htmlspecialchars($row['nisn'] ?? ''); ?></td>
                    <td class="text-format"><?php echo htmlspecialchars($row['nik'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['tempat_lahir'] ?? ''); ?></td>
                    <td class="text-center"><?php echo htmlspecialchars($row['tanggal_lahir'] ?? ''); ?></td>
                    <td class="text-center"><?php echo htmlspecialchars($row['jenis_kelamin'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['agama'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['jumlah_saudara'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['alamat'] ?? ''); ?></td>
                    
                    <td><?php echo htmlspecialchars($row['nama_ayah'] ?? ''); ?></td>
                    <td class="text-format"><?php echo htmlspecialchars($row['nik_ayah'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['status_ayah'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['nama_ibu'] ?? ''); ?></td>
                    <td class="text-format"><?php echo htmlspecialchars($row['nik_ibu'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['status_ibu'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['nama_wali'] ?? ''); ?></td>
                    <td class="text-format"><?php echo htmlspecialchars($row['nik_wali'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['pekerjaan_wali'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['penghasilan_wali'] ?? ''); ?></td>
                    
                    <td class="text-format"><?php echo htmlspecialchars($row['nomor_kip'] ?? ''); ?></td>
                    <td class="text-format"><?php echo htmlspecialchars($row['nomor_kks'] ?? ''); ?></td>
                    <td class="text-format"><?php echo htmlspecialchars($row['nomor_pkh'] ?? ''); ?></td>
                    
                    <td><?php echo htmlspecialchars($row['alasan_putus_sekolah'] ?? ''); ?></td>
                    <td class="text-center"><?php echo htmlspecialchars($row['tanggal_putus_sekolah'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['alasan_mutasi'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['tujuan_mutasi'] ?? ''); ?></td>
                    <td class="text-center"><?php echo htmlspecialchars($row['tanggal_mutasi'] ?? ''); ?></td>
                    <td class="text-center"><?php echo htmlspecialchars($row['tanggal_lulus_ma'] ?? ''); ?></td>
                    <td class="text-format"><?php echo htmlspecialchars($row['no_ijazah_ma'] ?? ''); ?></td>
                </tr>
            <?php 
                }
            } else {
                echo "<tr><td colspan='33' style='text-align:center;'>Tidak ada data siswa ditemukan untuk kriteria ini.</td></tr>";
            }
            ?>
        </tbody>
    </table>

</body>
</html>