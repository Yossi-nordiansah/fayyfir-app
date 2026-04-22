<?php  
session_start();  
require "../../config.php";  
$conn = $conn2; // gunakan koneksi aktif

// Pastikan user login  
if (!isset($_SESSION["user_id"])) {  
    header("Location: ../../login.php");  
    exit();  
}

// Pastikan ada ID pembelian  
if (!isset($_GET["id"])) {  
    header("Location: index");  
    exit();  
}

$id = intval($_GET["id"]);  
$berat = $_POST["berat"] ?? null;

// Ambil data pembelian  
$query = "SELECT * FROM bb_pembelian_awal WHERE id = ?";  
$stmt = $conn->prepare($query);  
$stmt->bind_param("i", $id);  
$stmt->execute();  
$result = $stmt->get_result();

if ($result->num_rows === 0) {  
    echo "<script>alert('Data pembelian tidak ditemukan!'); window.location='list-pembelian.php';</script>";  
    exit();  
}

// Tidak ada lagi pengecekan status dan tidak ada update status

// Simpan ke tabel proses jemur (tahap pertama produksi)  
$insert_jemur = "INSERT INTO bb_proses_jemur 
    (id_pembelian, tanggal_mulai, berat_sebelum_jemur, berat_setelah_jemur, penyusutan_jemur, keterangan)  
    VALUES (?, CURDATE(), ?, NULL, NULL, 'Proses awal dimulai')";
$stmt_ins = $conn->prepare($insert_jemur);
$stmt_ins->bind_param("id", $id, $berat);

if ($stmt_ins->execute()) {
    echo "<script>
        alert('Batch berhasil dipindahkan ke tahap proses susut pertama!');
        window.location='../proses-produksi/jemur/list-jemur';
    </script>";
} else {
    echo "<script>
        alert('Terjadi kesalahan saat memindahkan data!');
        window.location='index';
    </script>";
}
?>