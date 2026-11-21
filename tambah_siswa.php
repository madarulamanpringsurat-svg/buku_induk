<?php
if(isset($_POST['simpan_siswa'])) {
    // Escaping input untuk keamanan data (Bawaan Sebelumnya)
    $ta = mysqli_real_escape_string($conn, $_POST['tahun_ajaran']);
    $nama = mysqli_real_escape_string($conn, $_POST['nama_siswa']);
    $nomor_induk = mysqli_real_escape_string($conn, $_POST['nomor_induk']);
    
    // Ambil seluruh data post bansos & wali yang baru dipisah/diperbarui (Bawaan Sebelumnya)
    $nomor_kip = mysqli_real_escape_string($conn, $_POST['nomor_kip']);
    $nomor_kks = mysqli_real_escape_string($conn, $_POST['nomor_kks']);
    $nomor_pkh = mysqli_real_escape_string($conn, $_POST['nomor_pkh']);
    
    $nama_wali = mysqli_real_escape_string($conn, $_POST['nama_wali']);
    $tanggal_lahir_wali = mysqli_real_escape_string($conn, $_POST['tanggal_lahir_wali']);
    $nik_wali = mysqli_real_escape_string($conn, $_POST['nik_wali']);
    $pendidikan_wali = mysqli_real_escape_string($conn, $_POST['pendidikan_wali']);
    $pekerjaan_wali = mysqli_real_escape_string($conn, $_POST['pekerjaan_wali']);
    $penghasilan_wali = mysqli_real_escape_string($conn, $_POST['penghasilan_wali']);

    // --- TAMBAHAN PERBAIKAN: Menangkap seluruh sisa field yang belum tersimpan ---
    $agama = mysqli_real_escape_string($conn, $_POST['agama']);
    $jumlah_saudara = mysqli_real_escape_string($conn, $_POST['jumlah_saudara']);
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
    $desa_kelurahan = mysqli_real_escape_string($conn, $_POST['desa_kelurahan']);
    $kecamatan = mysqli_real_escape_string($conn, $_POST['kecamatan']);
    $kabupaten = mysqli_real_escape_string($conn, $_POST['kabupaten']);
    $provinsi = mysqli_real_escape_string($conn, $_POST['provinsi']);
    $kodepos = mysqli_real_escape_string($conn, $_POST['kodepos']);
    $jarak_rumah = mysqli_real_escape_string($conn, $_POST['jarak_rumah']);

    $nama_sekolah_asal = mysqli_real_escape_string($conn, $_POST['nama_sekolah_asal']);
    $npsn_sekolah = mysqli_real_escape_string($conn, $_POST['npsn_sekolah']);
    $total_nilai_un = mysqli_real_escape_string($conn, $_POST['total_nilai_un']);
    $no_blangko_skhu_smp = mysqli_real_escape_string($conn, $_POST['no_blangko_skhu_smp']);
    $no_seri_ijazah_smp = mysqli_real_escape_string($conn, $_POST['no_seri_ijazah_smp']);
    $no_peserta_un_smp = mysqli_real_escape_string($conn, $_POST['no_peserta_un_smp']);
    $tanggal_kelulusan_smp = mysqli_real_escape_string($conn, $_POST['tanggal_kelulusan_smp']);
    $tanggal_masuk = mysqli_real_escape_string($conn, $_POST['tanggal_masuk']);
    $diterima_di_kelas = mysqli_real_escape_string($conn, $_POST['diterima_di_kelas']);

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

    $alasan_putus_sekolah = mysqli_real_escape_string($conn, $_POST['alasan_putus_sekolah']);
    $tanggal_putus_sekolah = mysqli_real_escape_string($conn, $_POST['tanggal_putus_sekolah']);
    $alasan_mutasi = mysqli_real_escape_string($conn, $_POST['alasan_mutasi']);
    $tujuan_mutasi = mysqli_real_escape_string($conn, $_POST['tujuan_mutasi']);
    $tanggal_mutasi = mysqli_real_escape_string($conn, $_POST['tanggal_mutasi']);
    $tanggal_lulus_ma = mysqli_real_escape_string($conn, $_POST['tanggal_lulus_ma']);
    $no_ijazah_ma = mysqli_real_escape_string($conn, $_POST['no_ijazah_ma']);
    $no_skhu_ma = mysqli_real_escape_string($conn, $_POST['no_skhu_ma']);

    // Query Insert yang telah disesuaikan agar memasukkan seluruh nama kolom tabel secara lengkap
    $sql = "INSERT INTO siswa (
                tahun_ajaran, nomor_induk, nama_siswa, nis_lokal, nisn, nik, 
                tempat_lahir, tanggal_lahir, jenis_kelamin, agama, jumlah_saudara,
                alamat, desa_kelurahan, kecamatan, kabupaten, provinsi, 
                kodepos, jarak_rumah, nama_sekolah_asal, npsn_sekolah, total_nilai_un, 
                no_blangko_skhu_smp, no_seri_ijazah_smp, no_peserta_un_smp, tanggal_kelulusan_smp, 
                tanggal_masuk, diterima_di_kelas, nomor_kk, nomor_kip, nomor_kks, 
                nomor_pkh, nama_ayah, nik_ayah, tanggal_lahir_ayah, pendidikan_ayah, 
                pekerjaan_ayah, penghasilan_ayah, status_ayah, nama_ibu, nik_ibu, 
                tanggal_lahir_ibu, pendidikan_ibu, pekerjaan_ibu, penghasilan_ibu, status_ibu, 
                nama_wali, tanggal_lahir_wali, nik_wali, pendidikan_wali, pekerjaan_wali, 
                penghasilan_wali, alasan_putus_sekolah, tanggal_putus_sekolah, alasan_mutasi, tujuan_mutasi, 
                tanggal_mutasi, tanggal_lulus_ma, no_ijazah_ma, no_skhu_ma
            ) 
            VALUES (
                '$ta', '$nomor_induk', '$nama', '{$_POST['nis_lokal']}', '{$_POST['nisn']}', '{$_POST['nik']}', 
                '{$_POST['tempat_lahir']}', '{$_POST['tanggal_lahir']}', '{$_POST['jenis_kelamin']}', '$agama', '$jumlah_saudara',
                '$alamat', '$desa_kelurahan', '$kecamatan', '$kabupaten', '$provinsi', 
                '$kodepos', '$jarak_rumah', '$nama_sekolah_asal', '$npsn_sekolah', '$total_nilai_un', 
                '$no_blangko_skhu_smp', '$no_seri_ijazah_smp', '$no_peserta_un_smp', '$tanggal_kelulusan_smp', 
                '$tanggal_masuk', '$diterima_di_kelas', '{$_POST['nomor_kk']}', '$nomor_kip', '$nomor_kks', 
                '$nomor_pkh', '$nama_ayah', '$nik_ayah', '$tanggal_lahir_ayah', '$pendidikan_ayah', 
                '$pekerjaan_ayah', '$penghasilan_ayah', '$status_ayah', '$nama_ibu', '$nik_ibu', 
                '$tanggal_lahir_ibu', '$pendidikan_ibu', '$pekerjaan_ibu', '$penghasilan_ibu', '$status_ibu', 
                '$nama_wali', '$tanggal_lahir_wali', '$nik_wali', '$pendidikan_wali', '$pekerjaan_wali', 
                '$penghasilan_wali', '$alasan_putus_sekolah', '$tanggal_putus_sekolah', '$alasan_mutasi', '$tujuan_mutasi', 
                '$tanggal_mutasi', '$tanggal_lulus_ma', '$no_ijazah_ma', '$no_skhu_ma'
            )";
    
    if(mysqli_query($conn, $sql)) {
        echo "<div class='alert alert-success shadow-sm'>Data Siswa atas nama <b>$nama</b> berhasil disimpan!</div>";
    } else {
        echo "<div class='alert alert-danger shadow-sm'>Gagal menyimpan data ke database: " . mysqli_error($conn) . "</div>";
    }
}
$list_ta = mysqli_query($conn, "SELECT tahun_ajaran FROM tahun_ajaran ORDER BY tahun_ajaran DESC");
?>

<style>
    /* Styling khusus meningkatkan kontras kolom input */
    .form-group-row {
        border-bottom: 1px solid #e2e8f0;
        padding-bottom: 0.6rem;
        padding-top: 0.6rem;
        align-items: center;
    }
    .form-group-row:last-child {
        border-bottom: none;
    }
    .form-control, .form-select {
        border: 1.8px solid #718096 !important; /* Batas warna lebih tebal dan gelap agar terlihat jelas */
        background-color: #ffffff;
        color: #0f172a;
        font-weight: 500;
        transition: all 0.2s ease-in-out;
    }
    .form-control:focus, .form-select:focus {
        border-color: #198754 !important;
        box-shadow: 0 0 0 0.25rem rgba(25, 135, 84, 0.25) !important;
    }
    .label-side {
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 0.2rem;
        font-size: 0.9rem;
    }
    .section-divider {
        font-size: 0.95rem;
        padding: 0.5rem;
        margin-top: 1rem;
        margin-bottom: 0.5rem;
    }
</style>

<form action="" method="POST">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold text-dark m-0">Tambah Siswa Baru</h4>
        <button type="submit" name="simpan_siswa" class="btn btn-success px-4 py-2 fw-semibold shadow-sm">
            <i class="fa-solid fa-floppy-disk me-2"></i>Simpan Seluruh Data
        </button>
    </div>

    <!-- Tab yang diringkas menjadi 2 Bagian Utama -->
    <ul class="nav nav-tabs mb-3" id="siswaTab" role="tablist">
        <li class="nav-item">
            <button class="nav-link active fw-semibold" id="pribadi-akademik-tab" data-bs-toggle="tab" data-bs-target="#pribadi-akademik" type="button">
                <i class="fa-solid fa-user-graduation me-1"></i> Data Siswa & Sekolah
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link fw-semibold" id="ortu-tab" data-bs-toggle="tab" data-bs-target="#ortu" type="button">
                <i class="fa-solid fa-users me-1"></i> Orang Tua / Wali & Bansos
            </button>
        </li>
    </ul>

    <div class="tab-content bg-white p-4 rounded-3 shadow-sm" id="siswaTabContent">
        
        <!-- TAB 1: GABUNGAN DATA PRIBADI, ASAL SEKOLAH, DAN STATUS KHUSUS -->
        <div class="tab-pane fade show active" id="pribadi-akademik">
            <div class="row">
                
                <!-- KOLOM KIRI: DATA PRIBADI SISWA -->
                <div class="col-md-6 pe-md-4 border-end">
                    <div class="bg-light p-2 mb-3 rounded-2 fw-bold text-dark border-start border-primary border-3">
                        <i class="fa-solid fa-address-card me-2 text-primary"></i>DATA PRIBADI SISWA
                    </div>
                    
                    <div class="form-group-row">
                        <label class="label-side">Tahun Ajaran *</label>
                        <select name="tahun_ajaran" class="form-select" required>
                            <?php while($t = mysqli_fetch_assoc($list_ta)) echo "<option value='{$t['tahun_ajaran']}'>{$t['tahun_ajaran']}</option>"; ?>
                        </select>
                    </div>
                    <div class="form-group-row">
                        <label class="label-side">Nomor Induk *</label>
                        <input type="text" name="nomor_induk" class="form-control" id="nomor_induk" required>
                    </div>
                    <div class="form-group-row">
                        <label class="label-side">NIS Lokal</label>
                        <input type="text" name="nis_lokal" class="form-control">
                    </div>
                    <div class="form-group-row">
                        <label class="label-side">NISN</label>
                        <input type="text" name="nisn" class="form-control" maxlength="10">
                    </div>
                    <div class="form-group-row">
                        <label class="label-side">Nama Lengkap Siswa *</label>
                        <input type="text" name="nama_siswa" class="form-control" required>
                    </div>
                    <div class="form-group-row">
                        <label class="label-side">NIK</label>
                        <input type="text" name="nik" class="form-control" maxlength="16">
                    </div>
                    <div class="form-group-row">
                        <label class="label-side">Jenis Kelamin</label>
                        <select name="jenis_kelamin" class="form-select">
                            <option value="Laki-laki">Laki-laki</option>
                            <option value="Perempuan">Perempuan</option>
                        </select>
                    </div>
                    <div class="form-group-row">
                        <label class="label-side">Tempat Lahir</label>
                        <input type="text" name="tempat_lahir" class="form-control">
                    </div>
                    <div class="form-group-row">
                        <label class="label-side">Tanggal Lahir</label>
                        <input type="date" name="tanggal_lahir" class="form-control">
                    </div>
                    <div class="form-group-row">
                        <label class="label-side">Agama</label>
                        <select name="agama" class="form-select">
                            <option value="Islam" selected>Islam</option>
                            <option value="Kristen">Kristen</option>
                            <option value="Katolik">Katolik</option>
                            <option value="Budha">Budha</option>
                            <option value="Hindu">Hindu</option>
                            <option value="Konghuchu">Konghuchu</option>
                        </select>
                    </div>
                    <div class="form-group-row">
                        <label class="label-side">Jumlah Saudara</label>
                        <input type="number" name="jumlah_saudara" class="form-control" value="0">
                    </div>
                    <div class="form-group-row">
    <label class="label-side">Alamat Jalan</label>
    <input type="text" name="alamat" class="form-control">
</div>
<div class="form-group-row">
    <label class="label-side">Provinsi</label>
    <select name="provinsi" id="provinsi" class="form-select">
        <option value="">-- Pilih Provinsi --</option>
    </select>
</div>
<div class="form-group-row">
    <label class="label-side">Kabupaten</label>
    <select name="kabupaten" id="kabupaten" class="form-select" disabled>
        <option value="">-- Pilih Kabupaten --</option>
    </select>
</div>
<div class="form-group-row">
    <label class="label-side">Kecamatan</label>
    <select name="kecamatan" id="kecamatan" class="form-select" disabled>
        <option value="">-- Pilih Kecamatan --</option>
    </select>
</div>
<div class="form-group-row">
    <label class="label-side">Desa/Kelurahan</label>
    <select name="desa_kelurahan" id="desa_kelurahan" class="form-select" disabled>
        <option value="">-- Pilih Desa/Kelurahan --</option>
    </select>
</div>
                    <div class="form-group-row">
                        <label class="label-side">Kode Pos</label>
                        <input type="text" name="kodepos" class="form-control">
                    </div>
                    <div class="form-group-row">
                        <label class="label-side">Jarak Rumah ke Madrasah</label>
                        <input type="text" name="jarak_rumah" class="form-control" placeholder="Misal: 2 KM">
                    </div>
                </div>

                <!-- KOLOM KANAN: ASAL SEKOLAH & MASUK DAN STATUS KHUSUS -->
                <div class="col-md-6 ps-md-4 mt-4 mt-md-0">
                    
                    <!-- SEKSI: ASAL SEKOLAH & MASUK -->
                    <div class="bg-light p-2 mb-3 rounded-2 fw-bold text-dark border-start border-success border-3">
                        <i class="fa-solid fa-school me-2 text-success"></i>ASAL SEKOLAH & MASUK
                    </div>
                    
                    <div class="form-group-row">
                        <label class="label-side">Nama Sekolah Asal</label>
                        <input type="text" name="nama_sekolah_asal" class="form-control" placeholder="SMP / MTs Asal">
                    </div>
                    <div class="form-group-row">
                        <label class="label-side">NPSN Sekolah Asal</label>
                        <input type="text" name="npsn_sekolah" class="form-control">
                    </div>
                    <div class="form-group-row">
                        <label class="label-side">Total Nilai UN</label>
                        <input type="text" name="total_nilai_un" class="form-control">
                    </div>
                    <div class="form-group-row">
                        <label class="label-side">Nomor Blangko SKHU</label>
                        <input type="text" name="no_blangko_skhu_smp" class="form-control">
                    </div>
                    <div class="form-group-row">
                        <label class="label-side">Nomor Seri Ijazah</label>
                        <input type="text" name="no_seri_ijazah_smp" class="form-control">
                    </div>
                    <div class="form-group-row">
                        <label class="label-side">No Peserta UN SMP</label>
                        <input type="text" name="no_peserta_un_smp" class="form-control">
                    </div>
                    <div class="form-group-row">
                        <label class="label-side">Tanggal Kelulusan SMP</label>
                        <input type="date" name="tanggal_kelulusan_smp" class="form-control">
                    </div>
                    <div class="form-group-row">
                        <label class="label-side">Tanggal Masuk MA</label>
                        <input type="date" name="tanggal_masuk" class="form-control">
                    </div>
                    <div class="form-group-row mb-4">
                        <label class="label-side">Diterima di Kelas</label>
                        <input type="text" name="diterima_di_kelas" class="form-control" value="10">
                    </div>

                    <!-- SEKSI: STATUS KHUSUS -->
                    <div class="bg-light p-2 mb-3 rounded-2 fw-bold text-dark border-start border-danger border-3">
                        <i class="fa-solid fa-star-of-life me-2 text-danger"></i>STATUS KHUSUS SISWA
                    </div>
                    
                    <div class="section-divider bg-light text-warning fw-bold rounded-2"><i class="fa-solid fa-user-slash me-2"></i>Siswa Putus Sekolah</div>
                    <div class="form-group-row">
                        <label class="label-side">Alasan Putus Sekolah</label>
                        <input type="text" name="alasan_putus_sekolah" class="form-control">
                    </div>
                    <div class="form-group-row">
                        <label class="label-side">Tanggal Putus Sekolah</label>
                        <input type="date" name="tanggal_putus_sekolah" class="form-control">
                    </div>
                    
                    <div class="section-divider bg-light text-info fw-bold rounded-2"><i class="fa-solid fa-arrow-right-arrows-left me-2"></i>Siswa Mutasi / Pindah</div>
                    <div class="form-group-row">
                        <label class="label-side">Alasan Mutasi</label>
                        <input type="text" name="alasan_mutasi" class="form-control">
                    </div>
                    <div class="form-group-row">
                        <label class="label-side">Tujuan Sekolah Mutasi</label>
                        <input type="text" name="tujuan_mutasi" class="form-control">
                    </div>
                    <div class="form-group-row">
                        <label class="label-side">Tanggal Mutasi</label>
                        <input type="date" name="tanggal_mutasi" class="form-control">
                    </div>

                    <div class="section-divider bg-light text-success fw-bold rounded-2"><i class="fa-solid fa-graduation-cap me-2"></i>Siswa Lulus Sekolah</div>
                    <div class="form-group-row">
                        <label class="label-side">Tanggal Lulus MA</label>
                        <input type="date" name="tanggal_lulus_ma" class="form-control">
                    </div>
                    <div class="form-group-row">
                        <label class="label-side">No. Ijazah MA</label>
                        <input type="text" name="no_ijazah_ma" class="form-control">
                    </div>
                    <div class="form-group-row">
                        <label class="label-side">No. SKHU MA</label>
                        <input type="text" name="no_skhu_ma" class="form-control">
                    </div>
                </div>

            </div>
        </div>

        <!-- TAB 2: DATA ORANG TUA / WALI & BANSOS -->
        <div class="tab-pane fade" id="ortu">
            <div class="d-flex flex-column">
                <div class="row form-group-row">
                    <label class="col-sm-3 label-side">No Kartu Keluarga (KK)</label>
                    <div class="col-sm-9"><input type="text" name="nomor_kk" id="nomor_kk" class="form-control"></div>
                </div>
                
                <div class="bg-light p-2 my-2 rounded-2 fw-bold text-success border-start border-success border-3"><i class="fa-solid fa-user me-2"></i>Data Ayah Kandung</div>
                <div class="row form-group-row">
                    <label class="col-sm-3 label-side">Nama Ayah</label>
                    <div class="col-sm-9"><input type="text" name="nama_ayah" id="nama_ayah" class="form-control"></div>
                </div>
                <div class="row form-group-row">
                    <label class="col-sm-3 label-side">NIK Ayah</label>
                    <div class="col-sm-9"><input type="text" name="nik_ayah" id="nik_ayah" class="form-control"></div>
                </div>
                <div class="row form-group-row">
                    <label class="col-sm-3 label-side">Tanggal Lahir Ayah</label>
                    <div class="col-sm-9"><input type="date" name="tanggal_lahir_ayah" id="tanggal_lahir_ayah" class="form-control"></div>
                </div>
                <div class="row form-group-row">
                    <label class="col-sm-3 label-side">Pendidikan Ayah</label>
                    <div class="col-sm-9">
                        <select name="pendidikan_ayah" id="pendidikan_ayah" class="form-select">
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
                <div class="row form-group-row">
                    <label class="col-sm-3 label-side">Pekerjaan Ayah</label>
                    <div class="col-sm-9">
                        <select name="pekerjaan_ayah" id="pekerjaan_ayah" class="form-select">
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
                <div class="row form-group-row">
                    <label class="col-sm-3 label-side">Penghasilan Ayah</label>
                    <div class="col-sm-9">
                        <select name="penghasilan_ayah" id="penghasilan_ayah" class="form-select">
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
                <div class="row form-group-row">
                    <label class="col-sm-3 label-side">Status Ayah</label>
                    <div class="col-sm-9">
                        <select name="status_ayah" class="form-select">
                            <option value="Masih Hidup" selected>Masih Hidup</option>
                            <option value="Sudah Meninggal">Sudah Meninggal</option>
                        </select>
                    </div>
                </div>
                
                <div class="bg-light p-2 my-2 rounded-2 fw-bold text-danger border-start border-danger border-3"><i class="fa-solid fa-user me-2"></i>Data Ibu Kandung</div>
                <div class="row form-group-row">
                    <label class="col-sm-3 label-side">Nama Ibu</label>
                    <div class="col-sm-9"><input type="text" name="nama_ibu" id="nama_ibu" class="form-control"></div>
                </div>
                <div class="row form-group-row">
                    <label class="col-sm-3 label-side">NIK Ibu</label>
                    <div class="col-sm-9"><input type="text" name="nik_ibu" id="nik_ibu" class="form-control"></div>
                </div>
                <div class="row form-group-row">
                    <label class="col-sm-3 label-side">Tanggal Lahir Ibu</label>
                    <div class="col-sm-9"><input type="date" name="tanggal_lahir_ibu" id="tanggal_lahir_ibu" class="form-control"></div>
                </div>
                <div class="row form-group-row">
                    <label class="col-sm-3 label-side">Pendidikan Ibu</label>
                    <div class="col-sm-9">
                        <select name="pendidikan_ibu" id="pendidikan_ibu" class="form-select">
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
                <div class="row form-group-row">
                    <label class="col-sm-3 label-side">Pekerjaan Ibu</label>
                    <div class="col-sm-9">
                        <select name="pekerjaan_ibu" id="pekerjaan_ibu" class="form-select">
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
                <div class="row form-group-row">
                    <label class="col-sm-3 label-side">Penghasilan Ibu</label>
                    <div class="col-sm-9">
                        <select name="penghasilan_ibu" id="penghasilan_ibu" class="form-select">
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
                <div class="row form-group-row">
                    <label class="col-sm-3 label-side">Status Ibu</label>
                    <div class="col-sm-9">
                        <select name="status_ibu" class="form-select">
                            <option value="Masih Hidup" selected>Masih Hidup</option>
                            <option value="Sudah Meninggal">Sudah Meninggal</option>
                        </select>
                    </div>
                </div>

                <div class="bg-light p-2 my-2 rounded-2 d-flex flex-wrap align-items-center justify-content-between gap-2 border-start border-warning border-3">
                    <span class="fw-bold text-dark"><i class="fa-solid fa-user-shield me-2 text-warning"></i>Data Wali Siswa</span>
                    <div class="d-flex gap-3">
                        <div class="form-check m-0">
                            <input class="form-check-input" type="checkbox" id="sama_dengan_ayah" onclick="salinDataWali('ayah')">
                            <label class="form-check-label small fw-bold text-primary" style="cursor:pointer;" for="sama_dengan_ayah">Sama dengan Ayah</label>
                        </div>
                        <div class="form-check m-0">
                            <input class="form-check-input" type="checkbox" id="sama_dengan_ibu" onclick="salinDataWali('ibu')">
                            <label class="form-check-label small fw-bold text-danger" style="cursor:pointer;" for="sama_dengan_ibu">Sama dengan Ibu</label>
                        </div>
                    </div>
                </div>
                <div class="row form-group-row">
                    <label class="col-sm-3 label-side">Nama Wali</label>
                    <div class="col-sm-9"><input type="text" name="nama_wali" id="nama_wali" class="form-control"></div>
                </div>
                <div class="row form-group-row">
                    <label class="col-sm-3 label-side">NIK Wali</label>
                    <div class="col-sm-9"><input type="text" name="nik_wali" id="nik_wali" class="form-control"></div>
                </div>
                <div class="row form-group-row">
                    <label class="col-sm-3 label-side">Tanggal Lahir Wali</label>
                    <div class="col-sm-9"><input type="date" name="tanggal_lahir_wali" id="tanggal_lahir_wali" class="form-control"></div>
                </div>
                <div class="row form-group-row">
                    <label class="col-sm-3 label-side">Pendidikan Wali</label>
                    <div class="col-sm-9">
                        <select name="pendidikan_wali" id="pendidikan_wali" class="form-select">
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
                <div class="row form-group-row">
                    <label class="col-sm-3 label-side">Pekerjaan Wali</label>
                    <div class="col-sm-9">
                        <select name="pekerjaan_wali" id="pekerjaan_wali" class="form-select">
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
                <div class="row form-group-row">
                    <label class="col-sm-3 label-side">Penghasilan Wali</label>
                    <div class="col-sm-9">
                        <select name="penghasilan_wali" id="penghasilan_wali" class="form-select">
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

                <div class="bg-light p-2 my-2 rounded-2 fw-bold text-dark border-start border-dark border-3"><i class="fa-solid fa-hand-holding-dollar me-2 text-secondary"></i>Jaminan Sosial / Bantuan Pemerintah</div>
                <div class="row form-group-row">
                    <label class="col-sm-3 label-side">Nomor KIP</label>
                    <div class="col-sm-9"><input type="text" name="nomor_kip" class="form-control" placeholder="Kartu Indonesia Pintar"></div>
                </div>
                <div class="row form-group-row">
                    <label class="col-sm-3 label-side">Nomor KKS</label>
                    <div class="col-sm-9"><input type="text" name="nomor_kks" class="form-control" placeholder="Kartu Keluarga Sejahtera"></div>
                </div>
                <div class="row form-group-row">
                    <label class="col-sm-3 label-side">Nomor PKH</label>
                    <div class="col-sm-9"><input type="text" name="nomor_pkh" class="form-control" placeholder="Program Keluarga Harapan"></div>
                </div>
            </div>
        </div>
        
    </div>
</form>

<script>
function salinDataWali(tipe) {
    const chkAyah = document.getElementById('sama_dengan_ayah');
    const chkIbu = document.getElementById('sama_dengan_ibu');
    
    if (tipe === 'ayah' && chkAyah.checked) {
        chkIbu.checked = false;
        
        document.getElementById('nama_wali').value = document.getElementById('nama_ayah').value;
        document.getElementById('nik_wali').value = document.getElementById('nik_ayah').value;
        document.getElementById('tanggal_lahir_wali').value = document.getElementById('tanggal_lahir_ayah').value;
        document.getElementById('pendidikan_wali').value = document.getElementById('pendidikan_ayah').value;
        document.getElementById('pekerjaan_wali').value = document.getElementById('pekerjaan_ayah').value;
        document.getElementById('penghasilan_wali').value = document.getElementById('penghasilan_ayah').value;
    } else if (tipe === 'ibu' && chkIbu.checked) {
        chkAyah.checked = false;
        
        document.getElementById('nama_wali').value = document.getElementById('nama_ibu').value;
        document.getElementById('nik_wali').value = document.getElementById('nik_ibu').value;
        document.getElementById('tanggal_lahir_wali').value = document.getElementById('tanggal_lahir_ibu').value;
        document.getElementById('pendidikan_wali').value = document.getElementById('pendidikan_ibu').value;
        document.getElementById('pekerjaan_wali').value = document.getElementById('pekerjaan_ibu').value;
        document.getElementById('penghasilan_wali').value = document.getElementById('penghasilan_ibu').value;
    } else {
        document.getElementById('nama_wali').value = '';
        document.getElementById('nik_wali').value = '';
        document.getElementById('tanggal_lahir_wali').value = '';
        document.getElementById('pendidikan_wali').value = '';
        document.getElementById('pekerjaan_wali').value = '';
        document.getElementById('penghasilan_wali').value = '';
    }
}
document.addEventListener("DOMContentLoaded", function() {
    const provSelect = document.getElementById('provinsi');
    const kabSelect = document.getElementById('kabupaten');
    const kecSelect = document.getElementById('kecamatan');
    const desaSelect = document.getElementById('desa_kelurahan');

    // 1. Load Data Provinsi saat halaman siap
    fetch('get_wilayah.php?action=get_provinsi')
        .then(response => response.json())
        .then(data => {
            data.forEach(prov => {
                let opt = document.createElement('option');
                opt.value = prov.nama; // Menyimpan Nama untuk dikirim ke database sesuai struktur tabel siswa
                opt.setAttribute('data-id', prov.kode); // Menyimpan Kode Wilayah untuk relasi antar select
                opt.textContent = prov.nama;
                provSelect.appendChild(opt);
            });
        });

    // 2. Ketika Provinsi Berubah -> Load Kabupaten
    provSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const provId = selectedOption.getAttribute('data-id');

        kabSelect.innerHTML = '<option value="">-- Pilih Kabupaten --</option>';
        kecSelect.innerHTML = '<option value="">-- Pilih Kecamatan --</option>';
        desaSelect.innerHTML = '<option value="">-- Pilih Desa/Kelurahan --</option>';
        kecSelect.disabled = true;
        desaSelect.disabled = true;

        if (!provId) {
            kabSelect.disabled = true;
            return;
        }

        fetch(`get_wilayah.php?action=get_kabupaten&provinsi_id=${provId}`)
            .then(response => response.json())
            .then(data => {
                data.forEach(kab => {
                    let opt = document.createElement('option');
                    opt.value = kab.nama;
                    opt.setAttribute('data-id', kab.kode);
                    opt.textContent = kab.nama;
                    kabSelect.appendChild(opt);
                });
                kabSelect.disabled = false;
            });
    });

    // 3. Ketika Kabupaten Berubah -> Load Kecamatan
    kabSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const kabId = selectedOption.getAttribute('data-id');

        kecSelect.innerHTML = '<option value="">-- Pilih Kecamatan --</option>';
        desaSelect.innerHTML = '<option value="">-- Pilih Desa/Kelurahan --</option>';
        desaSelect.disabled = true;

        if (!kabId) {
            kecSelect.disabled = true;
            return;
        }

        fetch(`get_wilayah.php?action=get_kecamatan&kabupaten_id=${kabId}`)
            .then(response => response.json())
            .then(data => {
                data.forEach(kec => {
                    let opt = document.createElement('option');
                    opt.value = kec.nama;
                    opt.setAttribute('data-id', kec.kode);
                    opt.textContent = kec.nama;
                    kecSelect.appendChild(opt);
                });
                kecSelect.disabled = false;
            });
    });

    // 4. Ketika Kecamatan Berubah -> Load Desa
    kecSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const kecId = selectedOption.getAttribute('data-id');

        desaSelect.innerHTML = '<option value="">-- Pilih Desa/Kelurahan --</option>';

        if (!kecId) {
            desaSelect.disabled = true;
            return;
        }

        fetch(`get_wilayah.php?action=get_desa&kecamatan_id=${kecId}`)
            .then(response => response.json())
            .then(data => {
                data.forEach(desa => {
                    let opt = document.createElement('option');
                    opt.value = desa.nama;
                    opt.textContent = desa.nama;
                    desaSelect.appendChild(opt);
                });
                desaSelect.disabled = false;
            });
    });
});
</script>