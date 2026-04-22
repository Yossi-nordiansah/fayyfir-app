<?php
session_start();
if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

require "config.php";

// Ambil data biaya operasional dari database
$sql = "SELECT * FROM operational_costs ORDER BY created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Biaya Operasional - Fayyfir</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen">
  <header class="bg-gray-900 text-white py-4 px-6 fixed top-0 left-0 right-0 z-40">
    <div class="flex justify-between items-center">
      <a href="index" class="flex items-center space-x-1 text-yellow-400 hover:underline text-sm">
        <span class="material-symbols-outlined text-base">chevron_left</span>
        <span class="hidden lg:inline">Kembali ke Dashboard</span>
      </a>
      <h1 class="text-lg font-semibold">Biaya Operasional</h1>
    </div>
  </header>

  <main class="pt-20 px-4 pb-32 max-w-6xl mx-auto space-y-6">
    <!-- Header dan Tombol Tambah -->
    <div class="flex justify-end items-center flex-wrap mb-4">
      <a href="tambah-operasional" class="mt-2 sm:mt-0 inline-flex items-center bg-gray-800 hover:bg-yellow-500 text-yellow-400 hover:text-black text-sm px-4 py-2 rounded shadow space-x-1">
        <span class="material-symbols-outlined">add</span>
        <span>Tambah Operasional</span>
      </a>
    </div>

    <!-- Tabel -->
    <div class="overflow-auto bg-white shadow rounded-lg">
      <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-800 text-yellow-400">
          <tr>
            <th class="px-4 py-2 text-center">Tanggal</th>
            <th class="px-4 py-2 text-left">Deskripsi</th>
            <th class="px-4 py-2 text-right">Jumlah (Rp)</th>
            <th class="px-4 py-2 text-left">Keterangan</th>
            <th class="px-4 py-2 text-center">Aksi</th>
          </tr>
        </thead>
        <tbody class="text-gray-800 divide-y divide-gray-200">
          <?php
        $total_amount = 0; // inisialisasi total
        
        while ($row = $result->fetch_assoc()):
          $id = $row["id"];
          $date = $row["tanggal"];
          $nama = json_encode($row["nama_biaya"]);
          $desc = json_encode($row["deskripsi"]);
          $amount = $row["jumlah"]; // simpan angka asli
        
          $total_amount += $amount; // jumlahkan angka asli
        ?>
          <tr>
            <td class="px-4 py-2 text-center"><?= htmlspecialchars(date("d/m/Y", strtotime($date))) ?></td>
            <td class="px-4 py-2 text-left"><?= htmlspecialchars($row["nama_biaya"]) ?></td>
            <td class="px-4 py-2 text-right"><?= number_format($amount, 0, ",", ".") ?></td>
            <td class="px-4 py-2 text-left"><?= htmlspecialchars($row["deskripsi"]) ?></td>
            <td class="px-4 py-2 text-center">
              <button onclick='showModal(<?= $id ?>, <?= $nama ?>, <?= $desc ?>, "<?= number_format($amount, 0, ",", ".") ?>", "<?= $date ?>")' 
                class="text-blue-700 hover:text-blue-800">
                <span class="material-symbols-outlined text-base">visibility</span>
              </button>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
        <tfoot class="bg-gray-100 font-semibold">
          <td colspan="2" class="px-4 py-2 text-right">TOTAL</td>
          <td class="px-4 py-2 text-right"><?= number_format($total_amount, 0, ",", ".") ?></td>
          <td colspan="2" class="px-4 py-2 text-center"></td>
        </tfoot>
      </table>
    </div>
  </main>

  <!-- Modal -->
  <div id="detailModal" style="display: none;" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6 relative text-gray-800">
      <button onclick="closeModal()" class="absolute top-2 right-2 text-gray-500 hover:text-gray-700">
        <span class="material-symbols-outlined">close</span>
      </button>
      <h2 class="text-lg font-semibold mb-4">Detail Biaya</h2>
      <div class="space-y-2 text-sm">
        <p><strong>Tanggal:</strong> <span id="modalTanggal"></span></p>
        <p><strong>Deskripsi:</strong> <span id="modalNama"></span></p>
        <p><strong>Jumlah:</strong> <span id="modalJumlah"></span></p>
        <p><strong>Keterangan:</strong> <span id="modalDesc"></span></p>
      </div>
      <div class="mt-6 flex justify-end space-x-3">
        <a href="#" id="btnEdit" class="bg-yellow-400 text-white px-4 py-2 rounded hover:bg-yellow-500 text-sm">Edit</a>
        <form method="POST" action="hapus-operasional.php" onsubmit="return confirm('Yakin ingin menghapus data ini?')">
          <input type="hidden" name="id" id="hiddenDeleteId" />
          <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 text-sm">Hapus</button>
        </form>
      </div>
    </div>
  </div>

<script>
  function showModal(id, nama, desc, amount, date) {
    document.getElementById("modalTanggal").textContent = date;
    document.getElementById("modalNama").textContent = nama;
    document.getElementById("modalDesc").textContent = desc;
    document.getElementById("modalJumlah").textContent = "Rp " + amount;
    document.getElementById("btnEdit").href = "edit-operasional?id=" + id;
    document.getElementById("hiddenDeleteId").value = id;
    document.getElementById("detailModal").style.display = "flex";
  }

  function closeModal() {
    document.getElementById("detailModal").style.display = "none";
  }
</script>
</body>
</html>