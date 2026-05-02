<?php
session_start();
require "../../config.php";
$conn = $conn2;

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

$p = $result->fetch_assoc();

// Jika batch sudah selesai proses atau siap jual, tolak
if ($p["status"] === "siap_jual" || $p["status"] === "terjual") {
    echo "<script>alert('Batch ini sudah berada pada status siap jual atau telah terjual.'); window.location='index';</script>";
    exit();
}

// Ambil berat awal sebagai acuan (langsung dianggap final)
$berat_awal = floatval($p["berat_awal"]);

// Hitung dummy penyusutan
$penyusutan0 = 0.00;

// Mulai transaksi
$conn->begin_transaction();

try {

    // Update status ke siap_jual
    $update = "UPDATE bb_pembelian_awal SET status='siap_jual' WHERE id=?";
    $stmt_upd = $conn->prepare($update);
    $stmt_upd->bind_param("i", $id);
    $stmt_upd->execute();

    // Insert proses jemur otomatis
    $insert_jemur = "INSERT INTO bb_proses_jemur 
            (id_pembelian, tanggal_mulai, tanggal_selesai, berat_setelah_jemur, penyusutan_jemur, keterangan)
            VALUES (?, CURDATE(), CURDATE(), ?, ?, 'Dilewati — langsung siap jual')";
    $stmt_j = $conn->prepare($insert_jemur);
    $stmt_j->bind_param("idd", $id, $berat_awal, $penyusutan0);
    $stmt_j->execute();

    // Insert proses kupas otomatis
    $insert_kupas = "INSERT INTO bb_proses_kupas
            (id_pembelian, berat_setelah_kupas, penyusutan_kupas, tanggal_proses, keterangan)
            VALUES (?, ?, ?, CURDATE(), 'Dilewati — langsung siap jual')";
    $stmt_k = $conn->prepare($insert_kupas);
    $stmt_k->bind_param("idd", $id, $berat_awal, $penyusutan0);
    $stmt_k->execute();

    // Insert proses sortir otomatis
    $insert_sortir = "INSERT INTO bb_proses_sortir
            (id_pembelian, berat_akhir, penyusutan_total, tanggal_simpan, lokasi_simpan, catatan)
            VALUES (?, ?, ?, CURDATE(), 'Gudang', 'Otomatis — langsung siap jual')";
    $stmt_s = $conn->prepare($insert_sortir);
    $stmt_s->bind_param("idd", $id, $berat_awal, $penyusutan0);
    $stmt_s->execute();

    // Commit
    $conn->commit();

    echo "<script>
      alert('Batch berhasil dipindahkan langsung ke status SIAP JUAL!');
      window.location='../penjualan/index';
    </script>";

} catch (Exception $e) {
    $conn->rollback();
    echo "<script>alert('Terjadi kesalahan: ".$e->getMessage()."'); window.location='index';</script>";
}

?>