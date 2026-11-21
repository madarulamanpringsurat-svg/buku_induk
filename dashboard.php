<?php
// Koneksi database dan penghitungan data utama
$total_siswa = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM siswa"));
$total_ta = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM tahun_ajaran"));

// Query mengambil data jumlah siswa per tahun ajaran
$query_siswa_ta = mysqli_query($conn, "SELECT tahun_ajaran, COUNT(*) as jumlah FROM siswa GROUP BY tahun_ajaran ORDER BY tahun_ajaran DESC");

// Siapkan array kosong untuk menampung data Grafik Chart.js
$chart_labels = [];
$chart_data = [];

// Duplikasi hasil query untuk charting tanpa mengganggu loop tabel di bawah
$data_untuk_chart = [];
if (mysqli_num_rows($query_siswa_ta) > 0) {
    while ($row = mysqli_fetch_assoc($query_siswa_ta)) {
        $data_untuk_chart[] = $row;
    }
    // Urutkan dari tahun terlama ke terbaru khusus untuk tampilan grafik yang ideal
    $data_chart_sorted = array_reverse($data_untuk_chart);
    foreach ($data_chart_sorted as $c_row) {
        $chart_labels[] = $c_row['tahun_ajaran'];
        $chart_data[] = $c_row['jumlah'];
    }
}
?>

<div class="p-4 bg-white rounded shadow-sm mb-4 text-center" style="border-top: 5px solid #1e4620;">
    <h2 class="fw-bold text-success">Selamat Datang</h2>
    <h4>Sistem Aplikasi Buku Induk Digital</h4>
    <p class="text-muted mb-0">MA Ma'arif Darul Aman Pringsurat, Temanggung, Jawa Tengah</p>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <a href="index.php?page=data_siswa" class="card border-0 shadow-sm h-100 text-decoration-none text-dark">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-primary-subtle text-primary p-3"><i class="fa-solid fa-user-graduate fa-2x"></i></div>
                <div><h5 class="fw-bold mb-1">Buku Induk Siswa</h5><p class="small text-muted mb-0">Biodata, keluarga, riwayat pendidikan, dan dokumen siswa.</p></div>
                <i class="fa-solid fa-arrow-right text-primary ms-auto"></i>
            </div>
        </a>
    </div>
    <div class="col-md-6">
        <a href="index.php?page=buku_induk_guru" class="card border-0 shadow-sm h-100 text-decoration-none text-dark">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-danger-subtle text-danger p-3"><i class="fa-solid fa-address-card fa-2x"></i></div>
                <div><h5 class="fw-bold mb-1">Buku Induk Guru</h5><p class="small text-muted mb-0">Identitas, pendidikan, keluarga, jabatan, sertifikasi/inpassing, penghargaan, dan mutasi pegawai.</p></div>
                <i class="fa-solid fa-arrow-right text-danger ms-auto"></i>
            </div>
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm bg-primary text-white p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-1 opacity-75">Total Siswa Terdaftar</h6>
                    <h2 class="fw-bold m-0"><?= $total_siswa ?></h2>
                </div>
                <i class="fa-solid fa-users fa-3x opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm bg-success text-white p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-1 opacity-75">Total Tahun Ajaran</h6>
                    <h2 class="fw-bold m-0"><?= $total_ta ?></h2>
                </div>
                <i class="fa-solid fa-calendar-days fa-3x opacity-50"></i>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <!-- Baris Menu Jalan Pintas -->
    <div class="col-md-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-0 pt-3 pb-0">
                <h5 class="fw-bold text-dark mb-0">
                    <i class="fa-solid fa-bolt text-warning me-2"></i>Menu Jalan Pintas
                </h5>
                <small class="text-muted">Akses cepat ke fitur-fitur utama sistem</small>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-6 col-sm-4 col-md-6 col-lg-4">
                        <a href="index.php?page=tambah_siswa" class="text-decoration-none text-center d-block p-3 border rounded shortcut-card bg-light text-dark">
                            <i class="fa-solid fa-user-plus fa-2x text-success mb-2"></i>
                            <div class="small fw-semibold">Tambah Siswa</div>
                        </a>
                    </div>
                    <div class="col-6 col-sm-4 col-md-6 col-lg-4">
                        <a href="index.php?page=data_siswa" class="text-decoration-none text-center d-block p-3 border rounded shortcut-card bg-light text-dark">
                            <i class="fa-solid fa-address-book fa-2x text-primary mb-2"></i>
                            <div class="small fw-semibold">Data Siswa</div>
                        </a>
                    </div>
                    <div class="col-6 col-sm-4 col-md-6 col-lg-4">
                        <a href="index.php?page=tahun_ajaran" class="text-decoration-none text-center d-block p-3 border rounded shortcut-card bg-light text-dark">
                            <i class="fa-solid fa-sliders fa-2x text-secondary mb-2"></i>
                            <div class="small fw-semibold">Kelola TA</div>
                        </a>
                    </div>
                    <div class="col-6 col-sm-4 col-md-6 col-lg-4">
                        <a href="index.php?page=upload_siswa" class="text-decoration-none text-center d-block p-3 border rounded shortcut-card bg-light text-dark">
                            <i class="fa-solid fa-file-excel fa-2x text-success mb-2"></i>
                            <div class="small fw-semibold">Export Excel</div>
                        </a>
                    </div>
                    <div class="col-6 col-sm-4 col-md-6 col-lg-4">
                        <a href="index.php?page=rekap_status" class="text-decoration-none text-center d-block p-3 border rounded shortcut-card bg-light text-dark">
                            <i class="fa-solid fa-chart-pie fa-2x text-info mb-2"></i>
                            <div class="small fw-semibold">Rekap Status</div>
                        </a>
                    </div>
                    <div class="col-6 col-sm-4 col-md-6 col-lg-4">
                        <a href="index.php?page=pengaturan" class="text-decoration-none text-center d-block p-3 border rounded shortcut-card bg-light text-dark">
                            <i class="fa-solid fa-gear fa-2x text-danger mb-2"></i>
                            <div class="small fw-semibold">Pengaturan</div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Baris Informasi & Petunjuk -->
    <div class="col-md-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-0 pt-3 pb-0">
                <h5 class="fw-bold text-dark mb-0">
                    <i class="fa-solid fa-circle-info text-info me-2"></i>Informasi & Petunjuk
                </h5>
                <small class="text-muted">Catatan penggunaan sistem induk digital</small>
            </div>
            <div class="card-body">
                <div class="mb-3 p-3 bg-light rounded border-start border-success border-4">
                    <div class="fw-bold text-success small mb-1"><i class="fa-solid fa-check-circle me-1"></i>Penyimpanan Link Raport</div>
                    <p class="small text-muted mb-0">Pastikan link Google Drive rapot siswa diset ke status <strong>"Siapa saja yang memiliki link dapat melihat"</strong> agar tidak terkunci saat diakses.</p>
                </div>

                <div class="mb-3 p-3 bg-light rounded border-start border-warning border-4">
                    <div class="fw-bold text-warning small mb-1"><i class="fa-solid fa-triangle-exclamation me-1"></i>Validasi Data Wali</div>
                    <p class="small text-muted mb-0">Gunakan fitur salin otomatis (checklist) pada modal edit siswa untuk mempercepat penyalinan data Ayah/Ibu ke form Wali siswa.</p>
                </div>

                <div class="p-3 bg-light rounded border-start border-primary border-4">
                    <div class="fw-bold text-primary small mb-1"><i class="fa-solid fa-circle-question me-1"></i>Butuh Bantuan?</div>
                    <p class="small text-muted mb-0">Jika terjadi kendala sistem atau memerlukan sinkronisasi data sekunder, silakan hubungi tim Administrator IT Madrasah.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- BARIS DATA: DIAGRAM BALOK & TABEL ANALISIS -->
<div class="row g-3">
    <!-- Kolom Kiri: Diagram Balok (Chart.js) -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 pt-3 pb-2">
                <h5 class="fw-bold text-dark mb-0">
                    <i class="fa-solid fa-chart-column text-success me-2"></i>Grafik Pertumbuhan Siswa
                </h5>
                <small class="text-muted">Visualisasi jumlah pendaftaran siswa per periode tahun ajaran</small>
            </div>
            <div class="card-body d-flex align-items-center justify-content-center" style="position: relative; min-height: 320px;">
                <?php if (!empty($chart_data)): ?>
                    <canvas id="siswaBarChart" style="max-height: 320px;"></canvas>
                <?php else: ?>
                    <div class="text-center text-muted">
                        <i class="fa-solid fa-chart-bar fa-3x opacity-25 mb-2"></i>
                        <p class="small mb-0">Data grafik tidak tersedia</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Kolom Kanan: Rincian Tabel -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 pt-3 pb-2">
                <h5 class="fw-bold text-dark mb-0">
                    <i class="fa-solid fa-list-ol text-success me-2"></i>Rincian Jumlah Per TA
                </h5>
                <small class="text-muted">Data tabular distribusi dokumen siswa</small>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 320px; overflow-y: auto;">
                    <table class="table table-striped table-hover align-middle m-0">
                        <thead class="table-light sticky-top" style="z-index: 1;">
                            <tr>
                                <th style="width: 70px;" class="ps-4">No</th>
                                <th>Tahun Ajaran</th>
                                <th class="text-end pe-4">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1; 
                            if (!empty($data_untuk_chart)):
                                foreach ($data_untuk_chart as $row): 
                            ?>
                            <tr>
                                <td class="ps-4"><?= $no++ ?></td>
                                <td><strong><?= htmlspecialchars($row['tahun_ajaran']) ?></strong></td>
                                <td class="text-end pe-4">
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill fw-semibold">
                                        <?= $row['jumlah'] ?> Siswa
                                    </span>
                                </td>
                            </tr>
                            <?php 
                                endforeach; 
                            else:
                            ?>
                            <tr>
                                <td colspan="3" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-folder-open fa-2x mb-2 d-block opacity-50"></i>
                                    Belum ada data siswa terinput.
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Load Chart.js Library via CDN resmi -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    <?php if (!empty($chart_data)): ?>
    const ctx = document.getElementById('siswaBarChart').getContext('2d');
    
    // Data yang dikirim dari PHP ke JavaScript Object
    const labelsData = <?php echo json_encode($chart_labels); ?>;
    const siswaData = <?php echo json_encode($chart_data); ?>;

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labelsData,
            datasets: [{
                label: 'Jumlah Siswa Baru',
                data: siswaData,
                backgroundColor: 'rgba(25, 135, 84, 0.85)', // Warna hijau Bootstrap utama (var--si-primary-light)
                borderColor: 'rgba(15, 81, 50, 1)',         // Border hijau gelap (var--si-primary)
                borderWidth: 1.5,
                borderRadius: 6,                            // Membuat ujung balok sedikit melengkung (modern)
                borderSkipped: false,
                hoverBackgroundColor: 'rgba(15, 81, 50, 0.9)'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false // Mematikan legend karena judul tabel sudah memperjelas konteks data
                },
                tooltip: {
                    padding: 12,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(context) {
                            return ` ${context.parsed.y} Siswa Terdaftar`;
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false // Menghilangkan garis vertikal di latar agar terlihat bersih
                    },
                    ticks: {
                        font: {
                            family: "'Plus Jakarta Sans', sans-serif",
                            weight: '600'
                        }
                    }
                },
                y: {
                    beginAtZero: true,
                    grace: '10%', // Memberikan ruang kosong 10% di atas balok tertinggi agar Chart tidak terpotong pembatas atas
                    ticks: {
                        precision: 0, // Memaksa angka skala menjadi bilangan bulat (tidak ada pecahan siswa)
                        font: {
                            family: "'Plus Jakarta Sans', sans-serif"
                        }
                    }
                }
            }
        }
    });
    <?php endif; ?>
});
</script>

<style>
.shortcut-card {
    transition: all 0.2s ease-in-out;
}
.shortcut-card:hover {
    background-color: #ffffff !important;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    transform: translateY(-3px);
    border-color: #1e4620 !important;
}
</style>