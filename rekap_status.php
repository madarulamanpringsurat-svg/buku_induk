<?php
// Pastikan koneksi database tersedia (karena di-include dari index.php)
if (!isset($conn)) {
    include 'koneksi.php';
}

// 1. Query untuk menghitung jumlah siswa per status
$query_rekap = mysqli_query($conn, "
    SELECT status_siswa, COUNT(*) as jumlah 
    FROM siswa 
    GROUP BY status_siswa 
    ORDER BY jumlah DESC
");

// 2. Query total siswa keseluruhan untuk persentase
$total_siswa_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM siswa");
$total_siswa_data = mysqli_fetch_assoc($total_siswa_query);
$total_keseluruhan = $total_siswa_data['total'] ?? 0;
?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent border-0 pt-4 pb-0">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="fw-bold text-dark mb-1">
                    <i class="fa-solid fa-chart-pie text-success me-2"></i>Rekapitulasi Status Siswa
                </h4>
                <p class="text-muted small mb-0">Ringkasan status operasional data siswa di dalam sistem buku induk.</p>
            </div>
            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm d-none d-sm-block">
                <i class="fa-solid fa-print me-1"></i> Cetak Laporan
            </button>
        </div>
    </div>
    
    <div class="card-body">
        <hr class="text-muted opacity-25 mt-2 mb-4">
        
        <?php if ($total_keseluruhan == 0): ?>
            <div class="alert alert-warning text-center my-4" role="alert">
                <i class="fa-solid fa-triangle-exclamation fa-2x mb-2 d-block"></i>
                Belum ada data siswa yang terekam di dalam database.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle border-light">
                    <thead class="table-light text-secondary small text-uppercase">
                        <tr>
                            <th style="width: 80px;" class="text-center">No</th>
                            <th>Status Siswa</th>
                            <th class="text-center" style="width: 200px;">Jumlah Siswa</th>
                            <th class="text-center" style="width: 250px;">Persentase Grafik</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        while ($row = mysqli_fetch_assoc($query_rekap)): 
                            // Membersihkan status kosong jika ada data tidak valid
                            $status = !empty($row['status_siswa']) ? $row['status_siswa'] : 'Tidak Diketahui';
                            $jumlah = $row['jumlah'];
                            
                            // Menghitung persentase
                            $persentase = $total_keseluruhan > 0 ? round(($jumlah / $total_keseluruhan) * 100, 1) : 0;
                            
                            // Mengatur warna badge berdasarkan nama status secara dinamis
                            $bg_badge = 'bg-secondary';
                            if (strtolower($status) == 'aktif') $bg_badge = 'bg-success';
                            elseif (strtolower($status) == 'lulus') $bg_badge = 'bg-primary';
                            elseif (strtolower($status) == 'mutasi' || strtolower($status) == 'pindah') $bg_badge = 'bg-warning text-dark';
                            elseif (strtolower($status) == 'drop out' || strtolower($status) == 'keluar') $bg_badge = 'bg-danger';
                        ?>
                            <tr>
                                <td class="text-center fw-bold text-muted"><?= $no++ ?></td>
                                <td>
                                    <span class="badge <?= $bg_badge ?> px-3 py-2 rounded-pill fs-6 text-capitalize">
                                        <?= htmlspecialchars($status) ?>
                                    </span>
                                </td>
                                <td class="text-center fw-bold fs-5 text-dark"><?= number_format($jumlah, 0, ',', '.') ?> <span class="fs-6 fw-normal text-muted">Siswa</span></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" style="height: 10px;">
                                            <div class="progress-bar <?= str_contains($bg_badge, 'success') ? 'bg-success' : (str_contains($bg_badge, 'primary') ? 'bg-primary' : (str_contains($bg_badge, 'warning') ? 'bg-warning' : (str_contains($bg_badge, 'danger') ? 'bg-danger' : 'bg-secondary'))) ?>" 
                                                 role="progressbar" 
                                                 style="width: <?= $persentase ?>%;" 
                                                 aria-valuenow="<?= $persentase ?>" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100">
                                            </div>
                                        </div>
                                        <span class="small fw-bold text-muted" style="min-width: 45px; text-align: right;"><?= $persentase ?>%</span>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="2" class="text-end py-3">TOTAL KESELURUHAN:</td>
                            <td class="text-center py-3 fs-5 text-success"><?= number_format($total_keseluruhan, 0, ',', '.') ?> <span class="fs-6 fw-normal text-muted">Siswa</span></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>