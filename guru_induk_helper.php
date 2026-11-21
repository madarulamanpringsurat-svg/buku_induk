<?php
function pastikan_tabel_buku_induk($koneksi) {
    $kueri = [
        "CREATE TABLE IF NOT EXISTS buku_induk_guru (
            id INT AUTO_INCREMENT PRIMARY KEY,
            guru_id INT NOT NULL UNIQUE,
            nik VARCHAR(20) DEFAULT NULL,
            nuptk VARCHAR(30) DEFAULT NULL,
            npk VARCHAR(30) DEFAULT NULL,
            peg_id VARCHAR(30) DEFAULT NULL,
            jenis_kelamin VARCHAR(20) DEFAULT NULL,
            tempat_lahir VARCHAR(100) DEFAULT NULL,
            tanggal_lahir DATE DEFAULT NULL,
            agama VARCHAR(30) DEFAULT NULL,
            status_perkawinan VARCHAR(40) DEFAULT NULL,
            nama_ibu_kandung VARCHAR(150) DEFAULT NULL,
            alamat TEXT DEFAULT NULL,
            no_hp VARCHAR(30) DEFAULT NULL,
            email VARCHAR(150) DEFAULT NULL,
            tanggal_masuk DATE DEFAULT NULL,
            tanggal_mulai_jabatan DATE DEFAULT NULL,
            tanggal_mulai_jabatan_tambahan DATE DEFAULT NULL,
            status_kepegawaian VARCHAR(60) DEFAULT NULL,
            status_keaktifan VARCHAR(30) DEFAULT NULL,
            pendidikan_terakhir VARCHAR(60) DEFAULT NULL,
            program_studi VARCHAR(150) DEFAULT NULL,
            perguruan_tinggi VARCHAR(150) DEFAULT NULL,
            tahun_lulus VARCHAR(4) DEFAULT NULL,
            nama_pasangan VARCHAR(150) DEFAULT NULL,
            jumlah_anak INT DEFAULT NULL,
            pangkat_golongan VARCHAR(100) DEFAULT NULL,
            tmt_pangkat DATE DEFAULT NULL,
            status_sertifikasi VARCHAR(10) DEFAULT NULL,
            status_inpassing VARCHAR(10) DEFAULT NULL,
            nomor_sk_inpassing VARCHAR(100) DEFAULT NULL,
            keterangan_inpassing TEXT DEFAULT NULL,
            foto VARCHAR(255) DEFAULT NULL,
            diperbarui_pada TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS riwayat_buku_guru (
            id INT AUTO_INCREMENT PRIMARY KEY,
            guru_id INT NOT NULL,
            kategori VARCHAR(30) NOT NULL,
            tanggal DATE DEFAULT NULL,
            judul VARCHAR(200) NOT NULL,
            instansi VARCHAR(200) DEFAULT NULL,
            detail TEXT DEFAULT NULL,
            dibuat_pada TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (guru_id),
            INDEX (kategori)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS identitas_madrasah (
            id INT PRIMARY KEY,
            nama_madrasah VARCHAR(200) NOT NULL,
            nsm VARCHAR(30) DEFAULT NULL,
            npsn VARCHAR(30) DEFAULT NULL,
            alamat TEXT DEFAULT NULL,
            telepon VARCHAR(40) DEFAULT NULL,
            email VARCHAR(150) DEFAULT NULL,
            website VARCHAR(150) DEFAULT NULL,
            diperbarui_pada TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    ];

    foreach ($kueri as $sql) {
        if (!mysqli_query($koneksi, $sql)) {
            throw new RuntimeException('Gagal menyiapkan tabel buku induk: ' . mysqli_error($koneksi));
        }
    }

    $sql_awal = "INSERT IGNORE INTO identitas_madrasah
        (id, nama_madrasah, nsm, npsn, alamat, telepon, email, website)
        VALUES
        (1, 'MA Ma''arif Darul Aman Pringsurat', '131233230008', '20363247',
        'Jl. Raya Yogya - Semarang, Pingit, Kec. Pringsurat, Kab. Temanggung',
        '(0298) 6502666', 'madarulaman.pringsurat@gmail.com', '')";

    if (!mysqli_query($koneksi, $sql_awal)) {
        throw new RuntimeException('Gagal menyiapkan identitas madrasah: ' . mysqli_error($koneksi));
    }

    $kolom_profil = [
        'nama_ibu_kandung' => "ALTER TABLE buku_induk_guru ADD COLUMN nama_ibu_kandung VARCHAR(150) DEFAULT NULL AFTER status_perkawinan",
        'status_sertifikasi' => "ALTER TABLE buku_induk_guru ADD COLUMN status_sertifikasi VARCHAR(10) DEFAULT NULL AFTER tmt_pangkat",
        'status_inpassing' => "ALTER TABLE buku_induk_guru ADD COLUMN status_inpassing VARCHAR(10) DEFAULT NULL AFTER status_sertifikasi",
        'nomor_sk_inpassing' => "ALTER TABLE buku_induk_guru ADD COLUMN nomor_sk_inpassing VARCHAR(100) DEFAULT NULL AFTER status_inpassing",
        'keterangan_inpassing' => "ALTER TABLE buku_induk_guru ADD COLUMN keterangan_inpassing TEXT DEFAULT NULL AFTER nomor_sk_inpassing",
        'mata_pelajaran_lainnya' => "ALTER TABLE buku_induk_guru ADD COLUMN mata_pelajaran_lainnya TEXT DEFAULT NULL AFTER email",
        'npk' => "ALTER TABLE buku_induk_guru ADD COLUMN npk VARCHAR(30) DEFAULT NULL AFTER nuptk",
        'peg_id' => "ALTER TABLE buku_induk_guru ADD COLUMN peg_id VARCHAR(30) DEFAULT NULL AFTER npk",
        'status_keaktifan' => "ALTER TABLE buku_induk_guru ADD COLUMN status_keaktifan VARCHAR(30) DEFAULT NULL AFTER status_kepegawaian",
        'tanggal_mulai_jabatan' => "ALTER TABLE buku_induk_guru ADD COLUMN tanggal_mulai_jabatan DATE DEFAULT NULL AFTER tanggal_masuk",
        'tanggal_mulai_jabatan_tambahan' => "ALTER TABLE buku_induk_guru ADD COLUMN tanggal_mulai_jabatan_tambahan DATE DEFAULT NULL AFTER tanggal_mulai_jabatan"
    ];
    foreach ($kolom_profil as $nama_kolom_profil => $sql_tambah_kolom_profil) {
        $hasil_kolom_profil = mysqli_query($koneksi, "SHOW COLUMNS FROM buku_induk_guru LIKE '$nama_kolom_profil'");
        if (!$hasil_kolom_profil) {
            throw new RuntimeException('Gagal memeriksa kolom ' . $nama_kolom_profil . ': ' . mysqli_error($koneksi));
        }
        if (mysqli_num_rows($hasil_kolom_profil) === 0) {
            try {
                $berhasil_tambah_kolom_profil = mysqli_query($koneksi, $sql_tambah_kolom_profil);
            } catch (mysqli_sql_exception $error) {
                if ($error->getCode() !== 1060) {
                    throw new RuntimeException('Gagal menambahkan kolom ' . $nama_kolom_profil . ': ' . $error->getMessage(), 0, $error);
                }
                $berhasil_tambah_kolom_profil = true;
            }
            if (!$berhasil_tambah_kolom_profil) {
                throw new RuntimeException('Gagal menambahkan kolom ' . $nama_kolom_profil . ': ' . mysqli_error($koneksi));
            }
        }
    }
    if (!mysqli_query($koneksi, "UPDATE buku_induk_guru SET status_keaktifan=CASE status_kepegawaian WHEN 'Aktif' THEN 'Aktif' WHEN 'Tidak Aktif' THEN 'Tidak Aktif' WHEN 'Pensiun' THEN 'Tidak Aktif' WHEN 'Mutasi Keluar' THEN 'Tidak Aktif' ELSE status_keaktifan END, status_kepegawaian=CASE WHEN status_kepegawaian IN ('Aktif', 'Tidak Aktif', 'Pensiun', 'Mutasi Keluar') THEN NULL ELSE status_kepegawaian END WHERE status_kepegawaian IN ('Aktif', 'Tidak Aktif', 'Pensiun', 'Mutasi Keluar')")) {
        throw new RuntimeException('Gagal memisahkan status kepegawaian dan keaktifan: ' . mysqli_error($koneksi));
    }

    $kolom_guru = [
        'google_email' => "ALTER TABLE guru ADD COLUMN google_email VARCHAR(150) DEFAULT NULL",
        'google_id' => "ALTER TABLE guru ADD COLUMN google_id VARCHAR(255) DEFAULT NULL",
        'google_login_enabled' => "ALTER TABLE guru ADD COLUMN google_login_enabled TINYINT(1) NOT NULL DEFAULT 0",
        'google_refresh_token' => "ALTER TABLE guru ADD COLUMN google_refresh_token TEXT DEFAULT NULL",
        'google_drive_folder_id' => "ALTER TABLE guru ADD COLUMN google_drive_folder_id VARCHAR(255) DEFAULT NULL",
        'google_drive_connected' => "ALTER TABLE guru ADD COLUMN google_drive_connected TINYINT(1) NOT NULL DEFAULT 0"
    ];
    foreach ($kolom_guru as $nama_kolom_guru => $sql_tambah_kolom_guru) {
        $hasil_kolom_guru = mysqli_query($koneksi, "SHOW COLUMNS FROM guru LIKE '$nama_kolom_guru'");
        if (!$hasil_kolom_guru) {
            throw new RuntimeException('Gagal memeriksa kolom guru ' . $nama_kolom_guru . ': ' . mysqli_error($koneksi));
        }
        if (mysqli_num_rows($hasil_kolom_guru) === 0) {
            if (!mysqli_query($koneksi, $sql_tambah_kolom_guru)) {
                throw new RuntimeException('Gagal menambahkan kolom guru ' . $nama_kolom_guru . ': ' . mysqli_error($koneksi));
            }
        }
    }
    $hasil_indeks_google = mysqli_query($koneksi, "SHOW INDEX FROM guru WHERE Key_name='google_id_unik'");
    if (!$hasil_indeks_google) {
        throw new RuntimeException('Gagal memeriksa indeks Google ID guru: ' . mysqli_error($koneksi));
    }
    if (mysqli_num_rows($hasil_indeks_google) === 0) {
        try {
            if (!mysqli_query($koneksi, "CREATE UNIQUE INDEX google_id_unik ON guru (google_id)")) {
                throw new RuntimeException('Gagal menyiapkan indeks Google ID guru: ' . mysqli_error($koneksi));
            }
        } catch (mysqli_sql_exception $error) {
            if ($error->getCode() !== 1061) {
                throw new RuntimeException('Gagal menyiapkan indeks Google ID guru: ' . $error->getMessage(), 0, $error);
            }
        }
    }

    if (!mysqli_query($koneksi, "UPDATE guru g JOIN buku_induk_guru bg ON bg.guru_id=g.id SET g.google_email=LOWER(TRIM(bg.email)) WHERE (g.google_email IS NULL OR g.google_email='') AND bg.email IS NOT NULL AND TRIM(bg.email)<>''")) {
        throw new RuntimeException('Gagal menyelaraskan email guru dengan akun Google: ' . mysqli_error($koneksi));
    }
    if (!mysqli_query($koneksi, "UPDATE guru g JOIN buku_induk_guru bg ON bg.guru_id=g.id SET g.google_login_enabled=1 WHERE g.google_login_enabled=0 AND bg.email IS NOT NULL AND TRIM(bg.email)<>''")) {
        throw new RuntimeException('Gagal mengaktifkan login Google untuk email guru yang sudah terdaftar: ' . mysqli_error($koneksi));
    }

    $kolom_riwayat = [
        'jenjang' => "ALTER TABLE riwayat_buku_guru ADD COLUMN jenjang VARCHAR(40) DEFAULT NULL AFTER kategori",
        'fakultas' => "ALTER TABLE riwayat_buku_guru ADD COLUMN fakultas VARCHAR(150) DEFAULT NULL AFTER instansi",
        'jurusan' => "ALTER TABLE riwayat_buku_guru ADD COLUMN jurusan VARCHAR(150) DEFAULT NULL AFTER fakultas",
        'tahun_lulus' => "ALTER TABLE riwayat_buku_guru ADD COLUMN tahun_lulus VARCHAR(4) DEFAULT NULL AFTER jurusan",
        'lptk' => "ALTER TABLE riwayat_buku_guru ADD COLUMN lptk VARCHAR(10) DEFAULT NULL AFTER tahun_lulus",
        'mapel_sertifikasi' => "ALTER TABLE riwayat_buku_guru ADD COLUMN mapel_sertifikasi VARCHAR(150) DEFAULT NULL AFTER lptk",
        'nrg' => "ALTER TABLE riwayat_buku_guru ADD COLUMN nrg VARCHAR(50) DEFAULT NULL AFTER mapel_sertifikasi",
        'jalur_sertifikasi' => "ALTER TABLE riwayat_buku_guru ADD COLUMN jalur_sertifikasi VARCHAR(150) DEFAULT NULL AFTER nrg",
        'jenis_kelamin_keluarga' => "ALTER TABLE riwayat_buku_guru ADD COLUMN jenis_kelamin_keluarga VARCHAR(20) DEFAULT NULL AFTER jalur_sertifikasi",
        'tempat_lahir_keluarga' => "ALTER TABLE riwayat_buku_guru ADD COLUMN tempat_lahir_keluarga VARCHAR(100) DEFAULT NULL AFTER jenis_kelamin_keluarga",
        'urutan_anak' => "ALTER TABLE riwayat_buku_guru ADD COLUMN urutan_anak INT DEFAULT NULL AFTER tempat_lahir_keluarga",
        'pendidikan_anak' => "ALTER TABLE riwayat_buku_guru ADD COLUMN pendidikan_anak VARCHAR(150) DEFAULT NULL AFTER urutan_anak",
        'status_hubungan_keluarga' => "ALTER TABLE riwayat_buku_guru ADD COLUMN status_hubungan_keluarga VARCHAR(30) DEFAULT NULL AFTER pendidikan_anak",
        'pendidikan_keluarga' => "ALTER TABLE riwayat_buku_guru ADD COLUMN pendidikan_keluarga VARCHAR(40) DEFAULT NULL AFTER status_hubungan_keluarga",
        'mutasi_jenis' => "ALTER TABLE riwayat_buku_guru ADD COLUMN mutasi_jenis VARCHAR(20) DEFAULT NULL AFTER pendidikan_keluarga",
        'mutasi_asal' => "ALTER TABLE riwayat_buku_guru ADD COLUMN mutasi_asal VARCHAR(200) DEFAULT NULL AFTER mutasi_jenis",
        'mutasi_tujuan' => "ALTER TABLE riwayat_buku_guru ADD COLUMN mutasi_tujuan VARCHAR(200) DEFAULT NULL AFTER mutasi_asal",
        'mutasi_alasan' => "ALTER TABLE riwayat_buku_guru ADD COLUMN mutasi_alasan TEXT DEFAULT NULL AFTER mutasi_tujuan",
        'sumber_mutasi_id' => "ALTER TABLE riwayat_buku_guru ADD COLUMN sumber_mutasi_id INT DEFAULT NULL AFTER mutasi_alasan",
        'status_sebelum_mutasi' => "ALTER TABLE riwayat_buku_guru ADD COLUMN status_sebelum_mutasi VARCHAR(30) DEFAULT NULL AFTER sumber_mutasi_id",
        'tahun_riwayat' => "ALTER TABLE riwayat_buku_guru ADD COLUMN tahun_riwayat VARCHAR(4) DEFAULT NULL AFTER mutasi_alasan",
        'mata_pelajaran_lainnya' => "ALTER TABLE riwayat_buku_guru ADD COLUMN mata_pelajaran_lainnya TEXT DEFAULT NULL AFTER tahun_riwayat",
        'instansi_sk' => "ALTER TABLE riwayat_buku_guru ADD COLUMN instansi_sk VARCHAR(200) DEFAULT NULL AFTER mata_pelajaran_lainnya",
        'nomor_sk' => "ALTER TABLE riwayat_buku_guru ADD COLUMN nomor_sk VARCHAR(100) DEFAULT NULL AFTER instansi_sk",
        'tanggal_sk' => "ALTER TABLE riwayat_buku_guru ADD COLUMN tanggal_sk DATE DEFAULT NULL AFTER nomor_sk",
        'berkas_sk' => "ALTER TABLE riwayat_buku_guru ADD COLUMN berkas_sk VARCHAR(255) DEFAULT NULL AFTER tanggal_sk",
        'nama_berkas_sk' => "ALTER TABLE riwayat_buku_guru ADD COLUMN nama_berkas_sk VARCHAR(255) DEFAULT NULL AFTER berkas_sk",
        'google_drive_file_id' => "ALTER TABLE riwayat_buku_guru ADD COLUMN google_drive_file_id VARCHAR(255) DEFAULT NULL AFTER nama_berkas_sk",
        'google_drive_link' => "ALTER TABLE riwayat_buku_guru ADD COLUMN google_drive_link VARCHAR(500) DEFAULT NULL AFTER google_drive_file_id",
    ];
    $hasil_kolom = mysqli_query($koneksi, "SHOW COLUMNS FROM riwayat_buku_guru");
    if (!$hasil_kolom) {
        throw new RuntimeException('Gagal memeriksa kolom riwayat pendidikan: ' . mysqli_error($koneksi));
    }
    $kolom_tersedia = [];
    while ($kolom = mysqli_fetch_assoc($hasil_kolom)) {
        $kolom_tersedia[$kolom['Field']] = true;
    }
    foreach ($kolom_riwayat as $nama_kolom => $sql_tambah_kolom) {
        if (!isset($kolom_tersedia[$nama_kolom])) {
            try {
                $berhasil_tambah_kolom = mysqli_query($koneksi, $sql_tambah_kolom);
            } catch (mysqli_sql_exception $error) {
                if ($error->getCode() !== 1060) {
                    throw new RuntimeException('Gagal menambahkan kolom ' . $nama_kolom . ' untuk riwayat pendidikan: ' . $error->getMessage(), 0, $error);
                }
                $berhasil_tambah_kolom = true;
            }
            if (!$berhasil_tambah_kolom) {
                throw new RuntimeException('Gagal menambahkan kolom ' . $nama_kolom . ' untuk riwayat pendidikan: ' . mysqli_error($koneksi));
            }
        }
        if (!mysqli_query($koneksi, "UPDATE buku_induk_guru SET status_keaktifan='Tidak Aktif' WHERE status_keaktifan='Pensiun'")) {
            throw new RuntimeException('Gagal menormalkan status keaktifan pegawai: ' . mysqli_error($koneksi));
        }
        if (!mysqli_query($koneksi, "UPDATE riwayat_buku_guru SET judul='Tidak Aktif' WHERE kategori='keaktifan' AND judul='Pensiun'")) {
            throw new RuntimeException('Gagal menormalkan riwayat keaktifan pegawai: ' . mysqli_error($koneksi));
        }
    }
}

function hapus_guru_dan_data_terkait($koneksi, $guru_id) {
    $guru_id = (int)$guru_id;
    if ($guru_id < 1) {
        return false;
    }

    mysqli_begin_transaction($koneksi);
    try {
        foreach (['riwayat_buku_guru', 'buku_induk_guru'] as $tabel) {
            if (!mysqli_query($koneksi, "DELETE FROM $tabel WHERE guru_id=$guru_id")) {
                throw new RuntimeException('Gagal menghapus data terkait guru: ' . mysqli_error($koneksi));
            }
        }

        if (!mysqli_query($koneksi, "DELETE FROM guru WHERE id=$guru_id")) {
            throw new RuntimeException('Gagal menghapus data guru: ' . mysqli_error($koneksi));
        }
        if (mysqli_affected_rows($koneksi) !== 1) {
            mysqli_rollback($koneksi);
            return false;
        }
        if (!mysqli_commit($koneksi)) {
            throw new RuntimeException('Transaksi penghapusan guru gagal disimpan: ' . mysqli_error($koneksi));
        }

        return true;
    } catch (Throwable $error) {
        mysqli_rollback($koneksi);
        throw $error;
    }
}
?>
