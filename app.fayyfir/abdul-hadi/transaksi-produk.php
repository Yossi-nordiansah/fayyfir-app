<?php
session_start();
if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

require "config.php";

// Ambil data buyer dari database
// Ambil data buyer dari database
$sql = "SELECT b.*, 
               IFNULL(SUM(sp.total_selling),0) AS total_transaksi
        FROM buyer_products b
        LEFT JOIN selling_products sp ON sp.buyer_id = b.id
        GROUP BY b.id, b.name, b.contact, b.address
        ORDER BY b.name ASC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Daftar Buyer - Fayyfir</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen">
  <header class="bg-gray-900 text-white py-4 px-6 fixed top-0 left-0 right-0 z-40">
    <div class="flex justify-between items-center">
      <a href="index" class="flex items-center space-x-1 text-yellow-400 hover:underline text-sm">
        <span class="material-symbols-outlined text-base">chevron_left</span>
        <span class="hidden lg:inline">Kembali</span>
      </a>
      <h1 class="text-lg font-semibold">Daftar Buyer</h1>
    </div>
  </header>

  <main class="pt-20 px-4 pb-32 max-w-6xl mx-auto space-y-6">    
    <div class="flex justify-between items-center mb-4">
      <a href="hasil-produksi" class="group flex items-center bg-gray-800 hover:bg-yellow-400 text-white px-4 py-2 rounded text-sm transition">    
        <span class="material-symbols-outlined text-sm text-yellow-400 group-hover:text-gray-800">chevron_left</span>    
        <span class="ml-2 group-hover:text-gray-800">Hasil Produksi</span>    
      </a>
      <a href="buyer-tambah" class="group flex items-center bg-gray-800 hover:bg-yellow-400 text-white px-4 py-2 rounded text-sm transition">    
        <span class="material-symbols-outlined text-sm text-yellow-400 group-hover:text-gray-800">add</span>    
        <span class="ml-2 group-hover:text-gray-800">Tambah Buyer</span>    
      </a>
    </div>    

    <div class="mb-4 flex justify-between items-center flex-wrap gap-2">    
      <input id="searchInput" type="text" placeholder="Cari buyer..." class="w-full md:w-1/3 px-3 py-2 border border-gray-300 rounded">    
      <div class="text-sm">    
        Tampilkan     
        <select id="rowsPerPage" class="border border-gray-300 rounded px-2 py-1">    
          <option value="10" selected>10</option>    
          <option value="25">25</option>    
          <option value="50">50</option>    
        </select>     
        baris    
      </div>    
    </div>    

    <div class="overflow-x-auto bg-white shadow rounded-lg">    
      <table class="min-w-full divide-y divide-gray-200 text-sm">    
        <thead class="bg-gray-800 text-yellow-400 text-sm">    
          <tr>    
            <th class="px-4 py-3 text-center whitespace-nowrap">No.</th>
            <th class="px-4 py-3 text-center whitespace-nowrap">Nama Buyer</th>    
            <th class="px-4 py-3 text-center whitespace-nowrap">Kontak</th>    
            <th class="px-4 py-3 text-center whitespace-nowrap">Alamat</th>
            <th class="px-4 py-3 text-center whitespace-nowrap">Total Transaksi</th>
            <th class="px-4 py-3 text-center whitespace-nowrap">Aksi</th>    
          </tr>    
        </thead>    
        <tbody id="materialTable" class="text-gray-700 text-sm divide-y divide-gray-200">    
          <?php if ($result && $result->num_rows > 0): ?>    
            <?php $no = 1; while ($row = $result->fetch_assoc()): ?>    
              <tr class="data-row">    
                <td class="px-4 py-2 text-center"><?= $no++; ?></td>    
                <td class="px-4 py-2 text-left whitespace-nowrap"><?= htmlspecialchars($row['name']); ?></td>    
                <td class="px-4 py-2 text-right whitespace-nowrap"><?= htmlspecialchars($row['contact']); ?></td>    
                <td class="px-4 py-2 text-left whitespace-nowrap"><?= htmlspecialchars($row['address']); ?></td>    
                <td class="px-4 py-2 text-right whitespace-nowrap">Rp <?= number_format($row['total_transaksi'], 0, ',', '.'); ?></td>
                <td class="px-4 py-2 text-center whitespace-nowrap">    
                  <a href="transaksi-rincian?id=<?= htmlspecialchars($row['id']); ?>" class="text-blue-600 hover:text-blue-800">    
                    <span class="material-symbols-outlined text-base">visibility</span>    
                  </a>    
                </td>    
              </tr>    
            <?php endwhile; ?>    
          <?php else: ?>    
            <tr>    
              <td colspan="6" class="px-4 py-2 text-center text-gray-500">Belum ada data buyer.</td>    
            </tr>    
          <?php endif; ?>    
        </tbody>    
      </table>    
    </div>    

    <div class="flex justify-between items-center mt-4 text-sm text-gray-600">    
      <div id="totalRowsInfo"></div>    
      <div id="paginationControls" class="flex gap-1"></div>    
    </div>
  </main>

<script>    
  const rowsPerPage = document.getElementById("rowsPerPage");    
  const searchInput = document.getElementById("searchInput");    
  const table = document.getElementById("materialTable");    
  const rows = table.getElementsByClassName("data-row");    
  const pagination = document.getElementById("paginationControls");    
  const totalInfo = document.getElementById("totalRowsInfo");    

  let currentPage = 1;    

  function filterRows() {    
    const query = searchInput.value.toLowerCase();    
    for (let row of rows) {    
      const text = row.innerText.toLowerCase();    
      row.style.display = text.includes(query) ? "" : "none";    
    }    
    paginate();    
  }    

  function paginate() {    
    const maxRows = parseInt(rowsPerPage.value);    
    const visibleRows = [...rows].filter(r => r.style.display !== "none");    
    const totalPages = Math.ceil(visibleRows.length / maxRows);    
    currentPage = Math.min(currentPage, totalPages || 1);    

    visibleRows.forEach((row, index) => {    
      row.style.display = (index >= (currentPage - 1) * maxRows && index < currentPage * maxRows) ? "" : "none";    
    });    

    pagination.innerHTML = "";    
    for (let i = 1; i <= totalPages; i++) {    
      const btn = document.createElement("button");    
      btn.className = "px-2 py-1 border rounded " + (i === currentPage ? "bg-yellow-500 text-white" : "hover:bg-yellow-100");    
      btn.textContent = i;    
      btn.onclick = () => { currentPage = i; paginate(); };    
      pagination.appendChild(btn);    
    }    

    totalInfo.textContent = `Menampilkan ${visibleRows.length} data dari total ${rows.length}`;    
  }    

  rowsPerPage.addEventListener("change", paginate);    
  searchInput.addEventListener("keyup", filterRows);    
  window.onload = paginate;    
</script>

</body>
</html>