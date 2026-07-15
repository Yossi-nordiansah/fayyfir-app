<?php
session_start();
if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

require "config.php";

$container_id = isset($_GET["container_id"]) ? intval($_GET["container_id"]) : 0;
if ($container_id === 0) {
  header("Location: daftar-supplier");
  exit();
}

// Ambil detail kontainer dan region
$stmt = $conn->prepare("SELECT container_number, region_name FROM containers WHERE id = ?");
$stmt->bind_param("i", $container_id);
$stmt->execute();
$kontainer = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$kontainer) {
  echo "Data kontainer tidak ditemukan.";
  exit();
}

$region_name = $kontainer["region_name"] ?? "";
$container_number = $kontainer["container_number"] ?? "";

// Ambil data supplier yang terfilter berdasarkan region_name kontainer
$sql = "SELECT s.*, p.name AS province_name, r.name AS regency_name, d.name AS district_name, v.name AS village_name
        FROM suppliers s
        LEFT JOIN reg_provinces p ON s.province_id = p.id
        LEFT JOIN reg_regencies r ON s.regency_id = r.id
        LEFT JOIN reg_districts d ON s.district_id = d.id
        LEFT JOIN reg_villages v ON s.village_id = v.id
        WHERE s.region_name = ?
        ORDER BY s.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $region_name);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Supplier Area <?= htmlspecialchars($region_name) ?> - Fayyfir</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
</head>

<body class="bg-gray-100 text-gray-800 min-h-screen">
  <header class="bg-gray-900 text-white py-4 px-6 fixed top-0 left-0 right-0 z-40">
    <div class="flex justify-between items-center">
      <a href="tambah-transaksi.php?container_id=<?= $container_id ?>" class="flex items-center space-x-1 text-yellow-400 hover:underline text-sm">
        <span class="material-symbols-outlined text-base">chevron_left</span>
        <span>Kembali ke Tambah Transaksi</span>
      </a>
      <h1 class="text-lg font-semibold">Data Petani / Supplier Area <?= htmlspecialchars($region_name) ?></h1>
    </div>
  </header>

  <main class="pt-24 px-4 pb-32 max-w-6xl mx-auto space-y-6">
    <!-- Kontrol Alur Kerja (Workflow Controls) -->
    <div class="bg-white p-4 rounded-lg shadow flex flex-col sm:flex-row justify-between items-center gap-4">
      <div>
        <h2 class="text-md font-semibold">Alur Pengisian Kontainer: <?= htmlspecialchars($container_number) ?></h2>
        <p class="text-sm text-gray-600">Menampilkan supplier khusus area <strong><?= htmlspecialchars($region_name) ?></strong></p>
      </div>
      <div class="flex flex-wrap gap-2">
        <a href="tambah-transaksi.php?container_id=<?= $container_id ?>" class="inline-flex items-center bg-yellow-500 hover:bg-yellow-600 text-gray-900 text-sm font-semibold px-4 py-2 rounded transition">
          <span class="material-symbols-outlined text-base mr-1">add_shopping_cart</span>
          Lanjutkan Tambah Transaksi
        </a>
        <a href="riwayat-kontainer.php?id=<?= $container_id ?>" class="inline-flex items-center bg-gray-800 hover:bg-gray-950 text-white text-sm px-4 py-2 rounded transition">
          <span class="material-symbols-outlined text-base mr-1">inventory_2</span>
          Detail Kontainer
        </a>
        <a href="tambah-supplier.php?container_id=<?= $container_id ?>" class="inline-flex items-center bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-2 rounded transition">
          <span class="material-symbols-outlined text-base mr-1">add</span>
          Tambah Supplier Baru
        </a>
        <a href="daftar-supplier" class="inline-flex items-center bg-gray-500 hover:bg-gray-600 text-white text-sm px-4 py-2 rounded transition">
          <span class="material-symbols-outlined text-base mr-1">list</span>
          Semua Supplier
        </a>
      </div>
    </div>

    <!-- Tabel Supplier -->
    <div class="overflow-auto bg-white shadow rounded-lg">
      <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-800 text-yellow-400">
          <tr>
            <th class="px-12 py-2 text-center">Nama</th>
            <th class="px-4 py-2 text-center">No. HP</th>
            <th class="px-4 py-2 text-center">Regional</th>
            <th class="px-16 py-2 text-center">Alamat</th>
            <th class="px-4 py-2 text-center">Keterangan</th>
            <th class="px-4 py-2 text-center">Aksi</th>
          </tr>
        </thead>
        <tbody class="text-gray-800 divide-y divide-gray-200">
          <?php if ($result->num_rows === 0): ?>
            <tr>
              <td colspan="6" class="px-4 py-8 text-center text-gray-500">Belum ada supplier terdaftar di area ini.</td>
            </tr>
          <?php endif; ?>
          <?php while ($row = $result->fetch_assoc()):
            $id = $row["id"];
            $name = htmlspecialchars($row["name"]);
            $phone = htmlspecialchars($row["phone"]);
            $region = htmlspecialchars($row["region_name"]);
            $address = htmlspecialchars(
              $row["address"] .
                ", " .
                $row["village_name"] .
                ", " .
                $row["district_name"] .
                ", " .
                $row["regency_name"] .
                ", " .
                $row["province_name"]
            );
            $notes = htmlspecialchars($row["notes"]);
          ?>
            <tr>
              <td class="px-4 py-2 text-left font-medium"><?= $name ?></td>
              <td class="px-4 py-2 text-left"><?= $phone ?></td>
              <td class="px-4 py-2 text-left"><?= $region ?></td>
              <td class="px-4 py-2 text-left"><?= $address ?></td>
              <td class="px-4 py-2 text-left"><?= $notes ?></td>
              <td class="px-4 py-2 text-center">
                <button onclick="openModal(<?= $id ?>)" title="Lihat Detail" class="text-gray-700 hover:text-blue-600">
                  <span class="material-symbols-outlined">visibility</span>
                </button>
              </td>
            </tr>

            <!-- Modal -->
            <div id="modal-<?= $id ?>" class="fixed z-50 inset-0 hidden bg-black bg-opacity-50 items-center justify-center">
              <div class="bg-white p-6 rounded-lg shadow-lg max-w-lg w-full mx-4 relative">
                <!-- Tombol Tutup (X) di pojok kanan atas -->
                <button onclick="closeModal(<?= $id ?>)" class="absolute top-2 right-2 text-gray-500 hover:text-gray-700">
                  <span class="material-symbols-outlined">close</span>
                </button>

                <h2 class="text-xl font-semibold mb-4">Detail Supplier</h2>
                <p><strong>Nama:</strong> <?= $name ?></p>
                <p><strong>No. HP:</strong> <?= $phone ?></p>
                <p><strong>Alamat:</strong><br> <?= nl2br($address) ?></p>
                <p><strong>Keterangan:</strong><br> <?= nl2br($notes) ?></p>

                <div class="mt-6 flex justify-end space-x-3">
                  <a href="rincian-dp-supplier?id=<?= $id ?>" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 text-sm">Rincian DP</a>
                  <a href="edit-supplier?id=<?= $id ?>&container_id=<?= $container_id ?>" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 text-sm">Edit</a>
                  <form method="POST" action="hapus-supplier.php" onsubmit="return confirm('Yakin ingin menghapus supplier ini?')">
                    <input type="hidden" name="id" value="<?= $id ?>" />
                    <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 text-sm">Hapus</button>
                  </form>
                </div>
              </div>
            </div>
          <?php
          endwhile; ?>
        </tbody>
      </table>
    </div>
  </main>

  <script>
    function openModal(id) {
      const modal = document.getElementById('modal-' + id);
      if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
      }
    }

    function closeModal(id) {
      const modal = document.getElementById('modal-' + id);
      if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
      }
    }
  </script>
</body>

</html>
<?php
$stmt->close();
?>
