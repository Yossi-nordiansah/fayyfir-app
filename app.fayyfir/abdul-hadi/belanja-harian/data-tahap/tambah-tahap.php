<?php
session_start();
require "../../config.php";
$conn = $conn2;

if (!isset($_SESSION["user_id"])) {
  header("Location: ../../login.php");
  exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_bahan = (int)$_POST['id_bahan'];
    $nama_proses = $_POST['nama_proses'];
    $urutan_tahap = (int)$_POST['urutan_tahap'];

    // Cek apakah urutan sudah ada untuk bahan ini
    $check = $conn->prepare("SELECT id FROM bb_proses_master WHERE id_bahan = ? AND urutan_tahap = ?");
    $check->bind_param("ii", $id_bahan, $urutan_tahap);
    $check->execute();
    $res = $check->get_result();
    
    if ($res->num_rows > 0) {
        $error = "Urutan tahap $urutan_tahap sudah digunakan oleh proses lain untuk bahan ini!";
    } else {
        $stmt = $conn->prepare("INSERT INTO bb_proses_master (id_bahan, nama_proses, urutan_tahap) VALUES (?, ?, ?)");
        $stmt->bind_param("isi", $id_bahan, $nama_proses, $urutan_tahap);

        if ($stmt->execute()) {
            header("Location: index?success=added");
            exit();
        } else {
            $error = "Gagal menambahkan data: " . $conn->error;
        }
        $stmt->close();
    }
    $check->close();
}

// Ambil daftar bahan (filter yang belum dihapus dan group agar tidak double)
$bahan_result = $conn->query("SELECT MAX(id) as id, nama_bahan FROM bb_bahan_master WHERE deleted_at IS NULL GROUP BY nama_bahan ORDER BY nama_bahan ASC");

$activeMenu = "purchases";
$activeModule = "Tambah Tahap";
include "../partials/header.php";
include "../partials/sidebar.php";
include "../partials/navbar.php";
?>

<main class="lg:ml-64 bg-gray-50 min-h-screen p-4 sm:p-6 lg:p-8">
    <div class="max-w-2xl mx-auto bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-6">Tambah Tahapan Proses Baru</h2>

            <?php if (isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="id_bahan">
                        Pilih Bahan
                    </label>
                    <?php $preselected_id = isset($_GET['id_bahan']) ? (int)$_GET['id_bahan'] : 0; ?>
                    <select class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                            id="id_bahan" name="id_bahan" required>
                        <option value="">-- Pilih Bahan --</option>
                        <?php while($b = $bahan_result->fetch_assoc()): ?>
                            <option value="<?= $b['id'] ?>" <?= ($preselected_id == $b['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($b['nama_bahan']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="nama_proses">
                        Nama Proses
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                           id="nama_proses" name="nama_proses" type="text" placeholder="Contoh: Sortir, Jemur, dsb" required>
                </div>
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="urutan_tahap">
                        Urutan Tahap (Angka)
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                           id="urutan_tahap" name="urutan_tahap" type="number" placeholder="1, 2, 3..." required>
                    <p class="text-xs text-gray-500 mt-1">Urutan ini menentukan alur proses dari awal sampai akhir.</p>
                </div>
                <div class="flex items-center justify-between">
                    <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" type="submit">
                        Simpan
                    </button>
                    <a href="index" class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
</main>

<?php include "../partials/footer.php"; ?>
