<?php
session_start();
require "../../config.php";
$conn = $conn2;

header('Content-Type: application/json');

if (!isset($_SESSION["user_id"])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$id_bahan = (int)$_POST['id_bahan'];
$nama_penampungan = $_POST['nama_penampungan'] ?? '';
$items = isset($_POST['items']) ? json_decode($_POST['items'], true) : []; 

if (empty($items) || empty($nama_penampungan)) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
    exit();
}

$conn->begin_transaction();
try {
    // Gunakan penampungan yang sudah ada, atau buat baru
    if (!empty($_POST['existing_penampungan_id'])) {
        $id_penampungan = (int)$_POST['existing_penampungan_id'];
    } else {
        // 1. Create Penampungan Baru
        $stmt = $conn->prepare("INSERT INTO bb_penampungan (id_bahan, nama_penampungan) VALUES (?, ?)");
        $stmt->bind_param("is", $id_bahan, $nama_penampungan);
        $stmt->execute();
        $id_penampungan = $stmt->insert_id;
    }

    // 2. Validasi stok & Insert Details
    $stmtCheck = $conn->prepare("
        SELECT 
            pa.berat_awal,
            s.nama_supplier,
            COALESCE((SELECT SUM(pd.berat_masuk) FROM bb_proses_detail pd WHERE pd.id_pembelian = pa.id AND pd.tahap_ke = 0 AND pd.status = 'aktif'), 0) as terpakai_produksi,
            COALESCE((SELECT SUM(pnd.berat_masuk) FROM bb_penampungan_detail pnd WHERE pnd.id_pembelian = pa.id), 0) as terpakai_penampungan
        FROM bb_pembelian_awal pa
        JOIN bb_supplier s ON pa.id_supplier = s.id
        WHERE pa.id = ?
    ");

    $stmtDetail = $conn->prepare("INSERT INTO bb_penampungan_detail (id_penampungan, id_pembelian, berat_masuk) VALUES (?, ?, ?)");
    
    foreach ($items as $item) {
        $id_pembelian = (int)$item['id_pembelian'];
        $qty = (float)$item['qty'];
        if ($qty <= 0) continue;

        // Validasi stok tersedia
        $stmtCheck->bind_param("i", $id_pembelian);
        $stmtCheck->execute();
        $stock = $stmtCheck->get_result()->fetch_assoc();
        $sisa = (float)$stock['berat_awal'] - (float)$stock['terpakai_produksi'] - (float)$stock['terpakai_penampungan'];

        if ($qty > $sisa) {
            $conn->rollback();
            echo json_encode([
                'success' => false,
                'message' => "Qty untuk {$stock['nama_supplier']} ({$qty}) melebihi stok tersedia (" . number_format($sisa, 0, ',', '.') . ")."
            ]);
            exit();
        }

        $stmtDetail->bind_param("iid", $id_penampungan, $id_pembelian, $qty);
        $stmtDetail->execute();
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Berhasil menggabungkan bahan ke penampungan: ' . $nama_penampungan]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
