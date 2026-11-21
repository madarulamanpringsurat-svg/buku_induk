<?php
// Koneksi database placeholder (Silakan un-comment dan sesuaikan dengan konfigurasi Anda)
// $conn = mysqli_connect("localhost", "root", "", "db_madrasah");

// Ambil list tahun ajaran untuk opsi dropdown
// $list_ta = mysqli_query($conn, "SELECT tahun_ajaran FROM tahun_ajaran ORDER BY tahun_ajaran DESC");

// Validasi ID Siswa yang akan diedit
$id_siswa = isset($_GET['id']) ? $_GET['id'] : '';

// Jika ID kosong, kembalikan ke halaman utama data siswa
if (empty($id_siswa)) {
    header("Location: data_siswa.php");
    exit;
}

// SIMULASI AMBIL DATA SISWA DARI DATABASE BERDASARKAN ID
// Skenario Riil Database Anda:
// $query = mysqli_query($conn, "SELECT * FROM siswa WHERE id = '$id_siswa'");
// $siswa = mysqli_fetch_assoc($query);

// Data Dummy Siswa (sebagai fallback tampilan jika belum dikoneksikan ke database)
$siswa = [
    "id" => 1, 
    "tahun_ajaran" => "2025/2026", 
    "nomor_induk" => "25261001", 
    "nis_lokal" => "NISL-25001",
    "nisn" => "0098765432",
    "nama_siswa" => "AHMAD KHOLIL AL-MANSUR", 
    "nik" => "3323011212090001", 
    "jenis_kelamin" => "Laki-laki", 
    "tempat_lahir" => "Temanggung", 
    "tanggal_lahir" => "2009-12-12", 
    "agama" => "Islam",
    "jumlah_saudara" => "2",
    "alamat" => "Jl. Raya Pringsurat No. 45", 
    "desa_kelurahan" => "Pringsurat",
    "kecamatan" => "Pringsurat",
    "kabupaten" => "Temanggung",
    "provinsi" => "Jawa Tengah",
    "kodepos" => "56272",
    "jarak_rumah" => "2 KM",
    
    "nama_sekolah_asal" => "MTs Negeri 1 Temanggung",
    "npsn_sekolah" => "20363123",
    "total_nilai_un" => "340.50",
    "no_blangko_skhu_smp" => "DN-01/M-MTs/06/1234567",
    "no_seri_ijazah_smp" => "DN-01/M-MTs/06/7654321",
    "no_peserta_un_smp" => "2-19-03-23-001-045-2",
    "tanggal_kelulusan_smp" => "2025-06-10",
    "tanggal_masuk" => "2025-07-15",
    "diterima_kelas" => "10",
    
    "nomor_kk" => "3323010203040005", 
    "nomor_kip" => "KIP-887623", 
    "nomor_kks" => "-", 
    "nomor_pkh" => "PKH-99211", 
    "nama_ayah" => "M. Mansur", 
    "nik_ayah" => "3323010101700002",
    "tanggal_lahir_ayah" => "1970-01-01",
    "pendidikan_ayah" => "SMA / Sederajat",
    "pekerjaan_ayah" => "Wiraswasta",
    "penghasilan_ayah" => "Rp 2.500.000",
    "status_ayah" => "Hidup",
    "nama_ibu" => "Siti Aminah", 
    "nik_ibu" => "3323014105750003",
    "tanggal_lahir_ibu" => "1975-05-01",
    "pendidikan_ibu" => "SMP / Sederajat",
    "pekerjaan_ibu" => "Ibu Rumah Tangga",
    "penghasilan_ibu" => "Tidak Ada",
    "status_ibu" => "Hidup",
    "nama_wali" => "-",
    "nik_wali" => "-",
    "pekerjaan_wali" => "-",
    
    "alasan_putus_sekolah" => "-", 
    "tanggal_putus_sekolah" => "",
    "alasan_mutasi" => "-", 
    "tujuan_mutasi" => "-", 
    "tanggal_mutasi" => "",
    "tanggal_lulus_ma" => "", 
    "no_ijazah_ma" => "",
    "no_skhu_ma" => ""
];

// PROSES SIMPAN PERUBAHAN DATA (UPDATE)
$pesan_sukses = false;
if (isset($_POST['update_siswa'])) {
    // Skenario Riil Sanitasi Input Database Anda:
    // $ta = mysqli_real_escape_string($conn, $_POST['tahun_ajaran']);
    // $nama = mysqli_real_escape_string($conn, $_POST['nama_siswa']);
    // ... (lakukan untuk semua field input)
    
    /* Query Update Database Anda:
    $sql = "UPDATE siswa SET 
                tahun_ajaran = '{$_POST['tahun_ajaran']}',
                nomor_induk = '{$_POST['nomor_induk']}',
                nis_lokal = '{$_POST['nis_lokal']}',
                nisn = '{$_POST['nisn']}',
                nik = '{$_POST['nik']}',
                nama_siswa = '{$_POST['nama_siswa']}',
                jenis_kelamin = '{$_POST['jenis_kelamin']}',
                agama = '{$_POST['agama']}',
                tempat_lahir = '{$_POST['tempat_lahir']}',
                tanggal_lahir = '{$_POST['tanggal_lahir']}',
                jumlah_saudara = '{$_POST['jumlah_saudara']}',
                alamat = '{$_POST['alamat']}',
                desa_kelurahan = '{$_POST['desa_kelurahan']}',
                kecamatan = '{$_POST['kecamatan']}',
                kabupaten = '{$_POST['kabupaten']}',
                provinsi = '{$_POST['provinsi']}',
                kodepos = '{$_POST['kodepos']}',
                jarak_rumah = '{$_POST['jarak_rumah']}',
                nama_sekolah_asal = '{$_POST['nama_sekolah_asal']}',
                npsn_sekolah = '{$_POST['npsn_sekolah']}',
                total_nilai_un = '{$_POST['total_nilai_un']}',
                no_peserta_un_smp = '{$_POST['no_peserta_un_smp']}',
                no_blangko_skhu_smp = '{$_POST['no_blangko_skhu_smp']}',
                no_seri_ijazah_smp = '{$_POST['no_seri_ijazah_smp']}',
                tanggal_kelulusan_smp = '{$_POST['tanggal_kelulusan_smp']}',
                tanggal_masuk = '{$_POST['tanggal_masuk']}',
                diterima_kelas = '{$_POST['diterima_kelas']}',
                nomor_kk = '{$_POST['nomor_kk']}',
                nama_ayah = '{$_POST['nama_ayah']}',
                nik_ayah = '{$_POST['nik_ayah']}',
                tanggal_lahir_ayah = '{$_POST['tanggal_lahir_ayah']}',
                pendidikan_ayah = '{$_POST['pendidikan_ayah']}',
                pekerjaan_ayah = '{$_POST['pekerjaan_ayah']}',
                penghasilan_ayah = '{$_POST['penghasilan_ayah']}',
                status_ayah = '{$_POST['status_ayah']}',
                nama_ibu = '{$_POST['nama_ibu']}',
                nik_ibu = '{$_POST['nik_ibu']}',
                tanggal_lahir_ibu = '{$_POST['tanggal_lahir_ibu']}',
                pendidikan_ibu = '{$_POST['pendidikan_ibu']}',
                pekerjaan_ibu = '{$_POST['pekerjaan_ibu']}',
                penghasilan_ibu = '{$_POST['penghasilan_ibu']}',
                status_ibu = '{$_POST['status_ibu']}',
                nama_wali = '{$_POST['nama_wali']}',
                nik_wali = '{$_POST['nik_wali']}',
                pekerjaan_wali = '{$_POST['pekerjaan_wali']}',
                nomor_kip = '{$_POST['nomor_kip']}',
                nomor_kks = '{$_POST['nomor_kks']}',
                nomor_pkh = '{$_POST['nomor_pkh']}',
                alasan_putus_sekolah = '{$_POST['alasan_putus_sekolah']}',
                tanggal_putus_sekolah = '{$_POST['tanggal_putus_sekolah']}',
                alasan_mutasi = '{$_POST['alasan_mutasi']}',
                tujuan_mutasi = '{$_POST['tujuan_mutasi']}',
                tanggal_mutasi = '{$_POST['tanggal_mutasi']}',
                tanggal_lulus_ma = '{$_POST['tanggal_lulus_ma']}',
                no_ijazah_ma = '{$_POST['no_ijazah_ma']}',
                no_skhu_ma = '{$_POST['no_skhu_ma']}'
            WHERE id = '$id_siswa'";
            
    if(mysqli_query($conn, $sql)) {
        $pesan_sukses = true;
    }
    */

    // Mengubah data dummy lokal agar langsung merefleksikan perubahan saat tombol simpan ditekan
    foreach ($_POST as $key => $value) {
        if (array_key_exists($key, $siswa)) {
            $siswa[$key] = $value;
        }
    }
    $pesan_sukses = true;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Data Siswa - MA Ma'arif Pringsurat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #008744;
            --secondary-color: #2b2b2b;
            --light-bg: #f8f9fa;
        }
        body {
            background-color: var(--light-bg);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .navbar-madrasah {
            background-color: var(--primary-color);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .card-main {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        }
        .section-title {
            background-color: #f2f2f2;
            padding: 8px 12px;
            font-weight: 700;
            border-left: 4px solid var(--primary-color);
            color: #333;
            font-size: 0.95rem;
            margin-top: 20px;
            margin-bottom: 15px;
            text-transform: uppercase;
        }
        .form-label {
            font-weight: 600;
            color: #495057;
            font-size: 0.85rem;
            margin-bottom: 4px;
        }
        .form-control, .form-select {
            font-size: 0.9rem;
            padding: 6px 12px;
            border-radius: 6px;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(0, 135, 68, 0.15);
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark navbar-madrasah mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center" href="data_siswa.php">
            <i class="fa-solid fa-graduation-cap me-2"></i>
            MA MA'ARIF PRINGSURAT <span class="badge bg-white text-success ms-2 small" style="font-size:0.7rem;">DARUL AMAN</span>
        </a>
    </div>
</nav>

<div class="container mb-5">
    <?php if ($pesan_sukses): ?>
        <div class="alert alert-success d-flex align-items-center shadow mb-4" role="alert">
            <i class="fa-solid fa-circle-check fs-4 me-2"></i>
            <div>
                <strong>Berhasil!</strong> Perubahan data siswa atas nama <strong><?php echo $siswa['nama_siswa']; ?></strong> telah sukses disimpan ke database.
            </div>
        </div>
    <?php endif; ?>

    <div class="card card-main bg-white p-4">
        <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3">
            <div>
                <h4 class="fw-bold text-dark m-0"><i class="fa-solid fa-user-pen text-success me-2"></i>Edit Formulir Profil Siswa</h4>
                <p class="text-muted small m-0">Perbarui rincian data induktif siswa secara berkala di bawah ini.</p>
            </div>
            <div>
                <a href="data_siswa.php" class="btn btn-outline-secondary fw-semibold btn-sm">
                    <i class="fa-solid fa-arrow-left me-1"></i> Kembali ke List
                </a>
            </div>
        </div>

        <form action="" method="POST">
            
            <div class="section-title"><i class="fa-solid fa-user me-2 text-success"></i>A. Data Pribadi Siswa</div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Tahun Ajaran *</label>
                    <select name="tahun_ajaran" class="form-select" required>
                        <option value="2025/2026" <?php echo $siswa['tahun_ajaran'] == '2025/2026' ? 'selected' : ''; ?>>2025/2026</option>
                        <option value="2024/2025" <?php echo $siswa['tahun_ajaran'] == '2024/2025' ? 'selected' : ''; ?>>2024/2025</option>
                        <option value="2023/2024" <?php echo $siswa['tahun_ajaran'] == '2023/2024' ? 'selected' : ''; ?>>2023/2024</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Diterima di Kelas *</label>
                    <select name="diterima_kelas" class="form-select" required>
                        <option value="10" <?php echo $siswa['diterima_kelas'] == '10' ? 'selected' : ''; ?>>Kelas 10 (Sepuluh)</option>
                        <option value="11" <?php echo $siswa['diterima_kelas'] == '11' ? 'selected' : ''; ?>>Kelas 11 (Sebelas)</option>
                        <option value="12" <?php echo $siswa['diterima_kelas'] == '12' ? 'selected' : ''; ?>>Kelas 12 (Dua Belas)</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Nomor Induk Siswa (NIS) *</label>
                    <input type="text" name="nomor_induk" class="form-control" value="<?php echo $siswa['nomor_induk']; ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">NIS Lokal</label>
                    <input type="text" name="nis_lokal" class="form-control" value="<?php echo $siswa['nis_lokal']; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">NISN</label>
                    <input type="text" name="nisn" class="form-control" value="<?php echo $siswa['nisn']; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">NIK Siswa</label>
                    <input type="text" name="nik" class="form-control" value="<?php echo $siswa['nik']; ?>">
                </div>
                <div class="col-md-8">
                    <label class="form-label">Nama Lengkap Siswa *</label>
                    <input type="text" name="nama_siswa" class="form-control" value="<?php echo $siswa['nama_siswa']; ?>" required style="text-transform: uppercase;">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Jenis Kelamin *</label>
                    <select name="jenis_kelamin" class="form-select" required>
                        <option value="Laki-laki" <?php echo $siswa['jenis_kelamin'] == 'Laki-laki' ? 'selected' : ''; ?>>Laki-laki</option>
                        <option value="Perempuan" <?php echo $siswa['jenis_kelamin'] == 'Perempuan' ? 'selected' : ''; ?>>Perempuan</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Agama *</label>
                    <input type="text" name="agama" class="form-control" value="<?php echo $siswa['agama']; ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tempat Lahir</label>
                    <input type="text" name="tempat_lahir" class="form-control" value="<?php echo $siswa['tempat_lahir']; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tanggal Lahir</label>
                    <input type="date" name="tanggal_lahir" class="form-control" value="<?php echo $siswa['tanggal_lahir']; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Jumlah Saudara Kandung</label>
                    <input type="number" name="jumlah_saudara" class="form-control" value="<?php echo $siswa['jumlah_saudara']; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Jarak Rumah ke Madrasah</label>
                    <input type="text" name="jarak_rumah" class="form-control" placeholder="Contoh: 2 KM" value="<?php echo $siswa['jarak_rumah']; ?>">
                </div>
                <div class="col-md-12">
                    <label class="form-label">Alamat Jalan / RT / RW</label>
                    <input type="text" name="alamat" class="form-control" value="<?php echo $siswa['alamat']; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Desa / Kelurahan</label>
                    <input type="text" name="desa_kelurahan" class="form-control" value="<?php echo $siswa['desa_kelurahan']; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Kecamatan</label>
                    <input type="text" name="kecamatan" class="form-control" value="<?php echo $siswa['kecamatan']; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Kabupaten</label>
                    <input type="text" name="kabupaten" class="form-control" value="<?php echo $siswa['kabupaten']; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Provinsi</label>
                    <input type="text" name="provinsi" class="form-control" value="<?php echo $siswa['provinsi']; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Kode Pos</label>
                    <input type="text" name="kodepos" class="form-control" value="<?php echo $siswa['kodepos']; ?>">
                </div>
            </div>

            <div class="section-title"><i class="fa-solid fa-graduation-cap me-2 text-primary"></i>B. Histori Akademik Asal Sekolah</div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nama Sekolah Asal (SMP/MTs)</label>
                    <input type="text" name="nama_sekolah_asal" class="form-control" value="<?php echo $siswa['nama_sekolah_asal']; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">NPSN Sekolah Asal</label>
                    <input type="text" name="npsn_sekolah" class="form-control" value="<?php echo $siswa['npsn_sekolah']; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Total Nilai UN SMP</label>
                    <input type="text" name="total_nilai_un" class="form-control" value="<?php echo $siswa['total_nilai_un']; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">No. Peserta UN SMP</label>
                    <input type="text" name="no_peserta_un_smp" class="form-control" value="<?php echo $siswa['no_peserta_un_smp']; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">No. Blangko SKHU SMP</label>
                    <input type="text" name="no_blangko_skhu_smp" class="form-control" value="<?php echo $siswa['no_blangko_skhu_smp']; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">No. Seri Ijazah SMP</label>
                    <input type="text" name="no_seri_ijazah_smp" class="form-control" value="<?php echo $siswa['no_seri_ijazah_smp']; ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Tanggal Kelulusan SMP</label>
                    <input type="date" name="tanggal_kelulusan_smp" class="form-control" value="<?php echo $siswa['tanggal_kelulusan_smp']; ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Tanggal Masuk MA Ini</label>
                    <input type="date" name="tanggal_masuk" class="form-control" value="<?php echo $siswa['tanggal_masuk']; ?>">
                </div>
            </div>

            <div class="section-title"><i class="fa-solid fa-users me-2 text-danger"></i>C. Data Orang Tua / Wali Siswa</div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">No. Kartu Keluarga (KK)</label>
                    <input type="text" name="nomor_kk" class="form-control" value="<?php echo $siswa['nomor_kk']; ?>">
                </div>
                <div class="col-12"><span class="badge bg-secondary p-2"><i class="fa-solid fa-user-tie me-1"></i> Rincian Data Ayah Kandung</span></div>
                <div class="col-md-4">
                    <label class="form-label">Nama Ayah Kandung</label>
                    <input type="text" name="nama_ayah" class="form-control" value="<?php echo $siswa['nama_ayah']; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">NIK Ayah</label>
                    <input type="text" name="nik_ayah" class="form-control" value="<?php echo $siswa['nik_ayah']; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tanggal Lahir Ayah</label>
                    <input type="date" name="tanggal_lahir_ayah" class="form-control" value="<?php echo $siswa['tanggal_lahir_ayah']; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Pendidikan Ayah</label>
                    <input type="text" name="pendidikan_ayah" class="form-control" value="<?php echo $siswa['pendidikan_ayah']; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Pekerjaan Ayah</label>
                    <input type="text" name="pekerjaan_ayah" class="form-control" value="<?php echo $siswa['pekerjaan_ayah']; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Penghasilan Ayah</label>
                    <input type="text" name="penghasilan_ayah" class="form-control" value="<?php echo $siswa['penghasilan_ayah']; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status Ayah</label>
                    <select name="status_ayah" class="form-select">
                        <option value="Hidup" <?php echo $siswa['status_ayah'] == 'Hidup' ? 'selected' : ''; ?>>Hidup</option>
                        <option value="Wafat" <?php echo $siswa['status_ayah'] == 'Wafat' ? 'selected' : ''; ?>>Wafat</option>
                    </select>
                </div>

                <div class="col-12 mt-4"><span class="badge bg-secondary p-2"><i class="fa-solid fa-user-astronaut me-1"></i> Rincian Data Ibu Kandung</span></div>
                <div class="col-md-4">
                    <label class="form-label">Nama Ibu Kandung</label>
                    <input type="text" name="nama_ibu" class="form-control" value="<?php echo $siswa['nama_ibu']; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">NIK Ibu</label>
                    <input type="text" name="nik_ibu" class="form-control" value="<?php echo $siswa['nik_ibu']; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tanggal Lahir Ibu</label>
                    <input type="date" name="tanggal_lahir_ibu" class="form-control" value="<?php echo $siswa['tanggal_lahir_ibu']; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Pendidikan Ibu</label>
                    <input type="text" name="pendidikan_ibu" class="form-control" value="<?php echo $siswa['pendidikan_ibu']; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Pekerjaan Ibu</label>
                    <input type="text" name="pekerjaan_ibu" class="form-control" value="<?php echo $siswa['pekerjaan_ibu']; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Penghasilan Ibu</label>
                    <input type="text" name="penghasilan_ibu" class="form-control" value="<?php echo $siswa['penghasilan_ibu']; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status Ibu</label>
                    <select name="status_ibu" class="form-select">
                        <option value="Hidup" <?php echo $siswa['status_ibu'] == 'Hidup' ? 'selected' : ''; ?>>Hidup</option>
                        <option value="Wafat" <?php echo $siswa['status_ibu'] == 'Wafat' ? 'selected' : ''; ?>>Wafat</option>
                    </select>
                </div>

                <div class="col-12 mt-4"><span class="badge bg-secondary p-2"><i class="fa-solid fa-user-shield me-1"></i> Rincian Data Wali (Jika Ada)</span></div>
                <div class="col-md-4">
                    <label class="form-label">Nama Wali</label>
                    <input type="text" name="nama_wali" class="form-control" value="<?php echo $siswa['nama_wali']; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">NIK Wali</label>
                    <input type="text" name="nik_wali" class="form-control" value="<?php echo $siswa['nik_wali']; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Pekerjaan Wali</label>
                    <input type="text" name="pekerjaan_wali" class="form-control" value="<?php echo $siswa['pekerjaan_wali']; ?>">
                </div>
            </div>

            <div class="section-title"><i class="fa-solid fa-hand-holding-dollar me-2 text-warning"></i>D. Jaminan / Bantuan Sosial</div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Nomor KIP (Kartu Indonesia Pintar)</label>
                    <input type="text" name="nomor_kip" class="form-control" value="<?php echo $siswa['nomor_kip']; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Nomor KKS</label>
                    <input type="text" name="nomor_kks" class="form-control" value="<?php echo $siswa['nomor_kks']; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Nomor PKH</label>
                    <input type="text" name="nomor_pkh" class="form-control" value="<?php echo $siswa['nomor_pkh']; ?>">
                </div>
            </div>

            <div class="section-title"><i class="fa-solid fa-clock-history me-2 text-info"></i>E. Informasi Perkembangan Khusus Status Siswa</div>
            <div class="row g-3">
                <div class="col-12"><span class="badge bg-light text-dark border"><i class="fa-solid fa-user-minus me-1 text-danger"></i> Siswa Putus Sekolah / Keluar</span></div>
                <div class="col-md-6">
                    <label class="form-label">Alasan Putus Sekolah</label>
                    <input type="text" name="alasan_putus_sekolah" class="form-control" value="<?php echo $siswa['alasan_putus_sekolah']; ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Tanggal Putus Sekolah</label>
                    <input type="date" name="tanggal_putus_sekolah" class="form-control" value="<?php echo $siswa['tanggal_putus_sekolah']; ?>">
                </div>
                
                <div class="col-12 mt-3"><span class="badge bg-light text-dark border"><i class="fa-solid fa-arrows-turn-to-dots me-1 text-info"></i> Siswa Mutasi / Pindah</span></div>
                <div class="col-md-4">
                    <label class="form-label">Alasan Mutasi</label>
                    <input type="text" name="alasan_mutasi" class="form-control" value="<?php echo $siswa['alasan_mutasi']; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tujuan Sekolah Mutasi</label>
                    <input type="text" name="tujuan_mutasi" class="form-control" value="<?php echo $siswa['tujuan_mutasi']; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tanggal Mutasi</label>
                    <input type="date" name="tanggal_mutasi" class="form-control" value="<?php echo $siswa['tanggal_mutasi']; ?>">
                </div>

                <div class="col-12 mt-3"><span class="badge bg-light text-dark border"><i class="fa-solid fa-graduation-cap me-1 text-success"></i> Siswa Lulus Sekolah</span></div>
                <div class="col-md-4">
                    <label class="form-label">Tanggal Lulus MA</label>
                    <input type="date" name="tanggal_lulus_ma" class="form-control" value="<?php echo $siswa['tanggal_lulus_ma']; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">No. Ijazah MA</label>
                    <input type="text" name="no_ijazah_ma" class="form-control" value="<?php echo $siswa['no_ijazah_ma']; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">No. SKHU MA</label>
                    <input type="text" name="no_skhu_ma" class="form-control" value="<?php echo $siswa['no_skhu_ma']; ?>">
                </div>
            </div>

            <div class="mt-5 pt-3 border-top d-flex gap-2 justify-content-end">
                <a href="data_siswa.php" class="btn btn-secondary px-4 fw-semibold"><i class="fa-solid fa-xmark me-1"></i> Batal</a>
                <button type="submit" name="update_siswa" class="btn btn-success px-4 fw-semibold"><i class="fa-solid fa-floppy-disk me-1"></i> Simpan Perubahan</button>
            </div>

        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>