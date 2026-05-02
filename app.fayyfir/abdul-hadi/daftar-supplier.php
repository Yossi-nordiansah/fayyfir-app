<?php
session_start();
if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

require "config.php";

$sql = "SELECT s.*, p.name AS province_name, r.name AS regency_name, d.name AS district_name, v.name AS village_name
        FROM suppliers s
        LEFT JOIN reg_provinces p ON s.province_id = p.id
        LEFT JOIN reg_regencies r ON s.regency_id = r.id
        LEFT JOIN reg_districts d ON s.district_id = d.id
        LEFT JOIN reg_villages v ON s.village_id = v.id
        ORDER BY s.created_at DESC";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Data Petani/Supplier - Fayyfir</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen">
  <header class="bg-gray-900 text-white py-4 px-6 fixed top-0 left-0 right-0 z-40">
    <div class="flex justify-between items-center">
      <a href="index" class="flex items-center space-x-1 text-yellow-400 hover:underline text-sm">
        <span class="material-symbols-outlined text-base">chevron_left</span>
        <span class="hidden lg:inline">Kembali ke Dashboard</span>
      </a>
      <h1 class="text-lg font-semibold">Data Petani / Supplier</h1>
    </div>
  </header>

  <main class="pt-24 px-4 pb-32 max-w-6xl mx-auto space-y-6">
    <div class="flex justify-end items-center flex-wrap mb-4">
      <a href="tambah-supplier" class="mt-2 sm:mt-0 inline-flex items-center bg-gray-800 hover:bg-yellow-500 text-yellow-400 hover:text-black text-sm px-4 py-2 rounded shadow space-x-1">
        <span class="material-symbols-outlined">add</span>
        <span>Tambah Supplier</span>
      </a>
    </div>
    <div class="overflow-auto bg-white shadow rounded-lg">
      <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-800 text-yellow-400">
          <tr>
            <th class="px-12 py-2 text-center">Nama</th>
            <th class="px-4 py-2 text-center">No. HP</th>
            <th class="px-16 py-2 text-center">Alamat</th>
            <th class="px-4 py-2 text-center">Keterangan</th>
            <th class="px-4 py-2 text-center">Aksi</th>
          </tr>
        </thead>
        <tbody class="text-gray-800 divide-y divide-gray-200">
          <?php while ($row = $result->fetch_assoc()):

            $id = $row["id"];
            $name = htmlspecialchars($row["name"]);
            $phone = htmlspecialchars($row["phone"]);
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
            <td class="px-4 py-2 text-left"><?= $name ?></td>
            <td class="px-4 py-2 text-left"><?= $phone ?></td>
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
                <a href="rincian-dp-supplier?id=<?= $id ?>" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gtext-sm text-sm">Rincian DP</a>
                <a href="edit-supplier?id=<?= $id ?>" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 text-sm">Edit</a>
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