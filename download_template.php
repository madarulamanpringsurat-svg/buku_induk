<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Template Siswa');

$headers = [
    'tahun_ajaran', 'nomor_induk', 'nis_lokal', 'nisn', 'nik', 'nama_siswa', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin', 'agama',
    'jumlah_saudara', 'alamat', 'desa_kelurahan', 'kecamatan', 'kabupaten', 'provinsi', 'kodepos', 'jarak_rumah', 'nama_sekolah_asal', 'npsn_sekolah',
    'no_blangko_skhu_smp', 'no_seri_ijazah_smp', 'total_nilai_un', 'tanggal_kelulusan_smp', 'no_peserta_un_smp', 'tanggal_masuk', 'diterima_di_kelas', 'nomor_kk',
    'nama_ayah', 'tanggal_lahir_ayah', 'nik_ayah', 'pendidikan_ayah', 'pekerjaan_ayah', 'penghasilan_ayah', 'status_ayah',
    'nama_ibu', 'tanggal_lahir_ibu', 'nik_ibu', 'pendidikan_ibu', 'pekerjaan_ibu', 'penghasilan_ibu', 'status_ibu',
    'nomor_kip', 'nomor_kks', 'nomor_pkh', 'nama_wali', 'tanggal_lahir_wali', 'nik_wali', 'pendidikan_wali', 'pekerjaan_wali', 'penghasilan_wali'
];

foreach ($headers as $key => $header_title) {
    $kolomHuruf = Coordinate::stringFromColumnIndex($key + 1);
    $sheet->setCellValue($kolomHuruf . '1', $header_title);
}

$contoh_data = [
    '2026/2027', '26271001', '121133...', '0087654321', '3303112203090001', 'Ahmad Dani', 'Temanggung', '2009-03-22', 'L', 'Islam',
    '2', 'Jl. Raya Pringsurat No. 10', 'Pringsurat', 'Pringsurat', 'Temanggung', 'Jawa Tengah', '56272', '1.5 km', 'SMP N 1 Pringsurat', '20301234',
    'DN-010203', 'IJZ-12345', '245.5', '2026-05-15', '2-26-03-...', '2026-07-15', 'X-A', '3303111212780002',
    'Budi Santoso', '1978-12-12', '330311...', 'SMA', 'Buruh', '1500000', 'Hidup',
    'Siti Aminah', '1982-04-05', '330311...', 'SMP', 'Ibu Rumah Tangga', '0', 'Hidup',
    '', '', '', '', '', '', '', '', ''
];

foreach ($contoh_data as $key => $value) {
    $kolomHuruf = Coordinate::stringFromColumnIndex($key + 1);
    $sheet->setCellValue($kolomHuruf . '2', $value);
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="template_upload_siswa_buku_induk.xlsx"');
header('Cache-Control: max-age=0');

$writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
$writer->save('php://output');
exit;