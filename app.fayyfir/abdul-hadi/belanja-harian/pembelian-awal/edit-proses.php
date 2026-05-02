<?php
session_start();
require "../../config.php";
$conn = $conn2;

if (!isset($_SESSION["user_id"])) {
    header("Location: ../../login");
    exit();
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$pembelian_id = isset($_GET['pembelian_id']) ? intval($_GET['pembelian_id']) : 0;

if ($id <= 0) {
    header("Location: index");
    exit();
}

// Ambil data proses
$stmt = $conn->prepare("
    SELECT pd.*, pm.nama_proses 
    FROM bb_proses_detail pd
    JOIN bb_proses_master pm ON pd.id_proses_master = pm.id
    WHERE pd.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$data) {
    header("Location: index");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $berat_masuk = floatval($_POST['berat_masuk']);
    $berat_keluar = floatval($_POST['berat_keluar']);
    $catatan = trim($_POST['catatan']);

    $stmt_upd = $conn->prepare("UPDATE bb_proses_detail SET berat_masuk = ?, berat_keluar = ?, catatan = ? WHERE id = ?");
    $stmt_upd->bind_param("ddsi", $berat_masuk, $berat_keluar, $catatan, $id);

    if ($stmt_upd->execute()) {
        header("Location: detail-penyusutan?id=$pembelian_id&success=updated");
    } else {
        $error = "Gagal memperbarui data: " . $conn->error;
    }
    $stmt_upd->close();
}

$activeMenu = "purchases";
$activeModule = "Edit Proses";
include "../partials/header.php";
include "../partials/sidebar.php";
include "../partials/navbar.php";
?>

<main class="lg:ml-64 bg-gray-50 min-h-screen p-4 sm:p-6 lg:p-8">
    <div class="max-w-2xl mx-auto bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-6">Edit Data Proses: <?= htmlspecialchars($data['nama_proses']) ?></h2>

            <?php if (isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="berat_masuk">
                        Berat Masuk (Kg)
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                           id="berat_masuk" name="berat_masuk" type="number" step="0.01" value="<?= htmlspecialchars($data['berat_masuk']) ?>" required>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="berat_keluar">
                        Berat Keluar (Kg)
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                           id="berat_keluar" name="berat_keluar" type="number" step="0.01" value="<?= htmlspecialchars($data['berat_keluar']) ?>" required>
                </div>
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="catatan">
                        Catatan
                    </label>
                    <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                              id="catatan" name="catatan" rows="3"><?= htmlspecialchars($data['catatan']) ?></textarea>
                </div>
                <div class="flex items-center justify-between">
                    <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" type="submit">
                        Simpan Perubahan
                    </button>
                    <a href="detail-penyusutan?id=<?= $pembelian_id ?>" class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
</main>

<?php include "../partials/footer.php"; ?>
