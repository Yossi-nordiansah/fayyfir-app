<?php
session_start();
require "../../config.php";
$conn = $conn2;
require "../includes/helpers.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../../login");
    exit();
}

// Validasi ID penjualan
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id === 0) {
    die("ID penjualan tidak valid.");
}

// Ambil data penjualan + info pembelian + bahan + buyer
$sql = "SELECT pj.id, pj.tanggal_jual, pj.berat_jual, pj.total_penjualan,
               b.nama_bahan, br.nama_buyer, pa.kode_batch
        FROM bb_penjualan pj
        LEFT JOIN bb_pembelian_awal pa ON pj.id_pembelian = pa.id
        LEFT JOIN bb_bahan_master b ON pa.id_bahan = b.id
        LEFT JOIN bb_buyer br ON pj.id_buyer = br.id
        WHERE pj.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Data penjualan tidak ditemukan.");
}

$data = $result->fetch_assoc();

// Proses penghapusan data
if (isset($_POST['confirm_delete'])) {
    $delStmt = $conn->prepare("DELETE FROM bb_penjualan WHERE id = ?");
    $delStmt->bind_param("i", $id);
    if ($delStmt->execute()) {
        header("Location: index.php?deleted=1");
        exit();
    } else {
        $error = "Gagal menghapus data penjualan.";
    }
}

$activeMenu = "sales";
$activeModule = "Hapus Penjualan";
include "../partials/header.php";
include "../partials/sidebar.php";
include "../partials/navbar.php";
?>

<style>
    .delete-container {
        max-width: 700px;
        margin: 40px auto;
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 8px 20px rgba(0,0,0,0.05);
        padding: 32px;
        transition: all 0.3s ease;
    }
    .delete-container:hover {
        box-shadow: 0 10px 25px rgba(0,0,0,0.08);
    }
    .delete-header {
        text-align: center;
        margin-bottom: 24px;
    }
    .delete-header h2 {
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 8px;
    }
    .delete-header p {
        color: #64748b;
        font-size: 0.95rem;
    }
    .delete-summary {
        background: #f8fafc;
        border-radius: 12px;
        padding: 16px 24px;
        margin-bottom: 24px;
    }
    .delete-summary li {
        padding: 6px 0;
        font-size: 0.95rem;
    }
    .delete-summary strong {
        color: #334155;
    }
    .delete-buttons {
        display: flex;
        justify-content: center;
        gap: 16px;
    }
    .btn-danger {
        background: #dc2626;
        border: none;
        color: #fff;
        padding: 10px 20px;
        border-radius: 10px;
        font-weight: 500;
        transition: all 0.25s ease;
    }
    .btn-danger:hover {
        background: #b91c1c;
        transform: translateY(-1px);
    }
    .btn-secondary {
        background: #e2e8f0;
        border: none;
        color: #334155;
        padding: 10px 20px;
        border-radius: 10px;
        font-weight: 500;
        transition: all 0.25s ease;
        text-decoration: none;
    }
    .btn-secondary:hover {
        background: #cbd5e1;
        transform: translateY(-1px);
    }
</style>

<div class="delete-container">
    <div class="delete-header">
        <h2>Konfirmasi Hapus Penjualan</h2>
        <p>Data di bawah ini akan dihapus secara permanen dari sistem.</p>
    </div>

    <?php if(isset($error)): ?>
        <div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <ul class="delete-summary">
        <li><strong>Kode Batch:</strong> <?= htmlspecialchars($data['kode_batch']) ?></li>
        <li><strong>Bahan:</strong> <?= htmlspecialchars($data['nama_bahan']) ?></li>
        <li><strong>Buyer:</strong> <?= htmlspecialchars($data['nama_buyer']) ?></li>
        <li><strong>Tanggal Jual:</strong> <?= format_tanggal($data['tanggal_jual']) ?></li>
        <li><strong>Berat Jual:</strong> <?= number_format($data['berat_jual'],2) ?> kg</li>
        <li><strong>Total Penjualan:</strong> <?= format_rupiah($data['total_penjualan']) ?></li>
    </ul>

    <form method="POST" class="delete-buttons">
        <button type="submit" name="confirm_delete" class="btn-danger">Ya, Hapus</button>
        <a href="index.php" class="btn-secondary">Batal</a>
    </form>
</div>

<?php include "../partials/footer.php"; ?>