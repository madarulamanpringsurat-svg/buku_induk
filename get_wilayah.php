<?php
// Masukkan koneksi database Anda disini
include "koneksi.php"; 
// Pastikan variabel $conn sudah tersedia setelah include

if (isset($_GET['action'])) {
    $action = $_GET['action'];
    
    if ($action == 'get_provinsi') {
        $query = mysqli_query($conn, "SELECT kode, nama FROM wilayah WHERE LENGTH(kode) = 2 ORDER BY nama ASC");
        $data = [];
        while ($row = mysqli_fetch_assoc($query)) {
            $data[] = $row;
        }
        echo json_encode($data);
    }
    
    elseif ($action == 'get_kabupaten' && isset($_GET['provinsi_id'])) {
        $prov_id = mysqli_real_escape_string($conn, $_GET['provinsi_id']);
        $query = mysqli_query($conn, "SELECT kode, nama FROM wilayah WHERE kode LIKE '$prov_id.%' AND LENGTH(kode) = 5 ORDER BY nama ASC");
        $data = [];
        while ($row = mysqli_fetch_assoc($query)) {
            $data[] = $row;
        }
        echo json_encode($data);
    }
    
    elseif ($action == 'get_kecamatan' && isset($_GET['kabupaten_id'])) {
        $kab_id = mysqli_real_escape_string($conn, $_GET['kabupaten_id']);
        $query = mysqli_query($conn, "SELECT kode, nama FROM wilayah WHERE kode LIKE '$kab_id.%' AND LENGTH(kode) = 8 ORDER BY nama ASC");
        $data = [];
        while ($row = mysqli_fetch_assoc($query)) {
            $data[] = $row;
        }
        echo json_encode($data);
    }
    
    elseif ($action == 'get_desa' && isset($_GET['kecamatan_id'])) {
        $kec_id = mysqli_real_escape_string($conn, $_GET['kecamatan_id']);
        $query = mysqli_query($conn, "SELECT kode, nama FROM wilayah WHERE kode LIKE '$kec_id.%' AND LENGTH(kode) = 13 ORDER BY nama ASC");
        $data = [];
        while ($row = mysqli_fetch_assoc($query)) {
            $data[] = $row;
        }
        echo json_encode($data);
    }
    exit;
}