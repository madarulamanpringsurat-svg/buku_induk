<?php
if (($_SESSION['role'] ?? '') !== 'guru') {
    header('Location: index.php');
    exit;
}

$guruIdSesi = (int)($_SESSION['guru_id'] ?? 0);
$bagianGuruValid = ['pegawai', 'keaktifan', 'pendidikan', 'keluarga', 'jabatan', 'penghargaan', 'sertifikasi'];
$bagianGuru = $_GET['bagian'] ?? 'pegawai';
if (!in_array($bagianGuru, $bagianGuruValid, true)) {
    $bagianGuru = 'pegawai';
}

$_GET['page'] = 'buku_induk_guru';
$_GET['bagian'] = $bagianGuru;
$_GET['guru'] = $guruIdSesi;
$_GET['edit'] = $bagianGuru === 'pegawai' ? $guruIdSesi : 0;

require __DIR__ . '/buku_induk_guru.php';

if ($bagianGuru === 'pegawai' && isset($_GET['foto'])):
?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var foto = document.getElementById('profil-foto');
    if (foto) {
        foto.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
});
</script>
<?php endif;
