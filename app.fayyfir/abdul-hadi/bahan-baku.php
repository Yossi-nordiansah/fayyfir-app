<?php
session_start();
if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

require "config.php";
$level = $_SESSION["role_id"] ?? "";
$region = $_SESSION["region"] ?? "";

// Ambil data bahan baku lengkap dengan stok, unit, dan harga rata-rata dari pembelian
$query = "
SELECT
    m.id,
    ms.quantity,
    m.name AS material_name,
    u.symbol AS unit_symbol,

    /* STOK AKTUAL */
    COALESCE(SUM(
        CASE 
            WHEN sm.change_type = 'in' THEN sm.quantity
            WHEN sm.change_type = 'out' THEN -sm.quantity
            ELSE 0
        END
    ), 0) AS stock_quantity,

    /* TOTAL NILAI PERSEDIAAN */
    COALESCE(SUM(
        CASE 
            WHEN sm.change_type = 'in' THEN sm.amount
            WHEN sm.change_type = 'out' THEN -sm.amount
            ELSE 0
        END
    ), 0) AS total_value,

    /* HARGA TERAKHIR (opsional, hanya display) */
    (
        SELECT sm2.unit_price
        FROM stock_movements sm2
        WHERE sm2.material_id = m.id
          AND sm2.change_type = 'in'
        ORDER BY sm2.created_at DESC
        LIMIT 1
    ) AS last_price

FROM materials m
LEFT JOIN units u ON m.unit_id = u.id
LEFT JOIN stock_movements sm ON sm.material_id = m.id
LEFT JOIN material_stocks ms ON ms.material_id = m.id
GROUP BY m.id, m.name, u.symbol
ORDER BY m.name ASC
";

$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->get_result();
$materials = $result->fetch_all(MYSQLI_ASSOC);

$grand_total = 0;
foreach ($materials as $material) {
    $grand_total += $material['total_value'];
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Bahan Baku - Fayyfir</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen">
  <header class="bg-gray-900 text-white py-4 px-6 fixed top-0 left-0 right-0 z-40">
    <div class="flex justify-between items-center">
      <?php if ($level != "1" && $region != "Gaharu"): ?>
      <a href="index" class="flex items-center space-x-1 text-yellow-400 hover:underline text-sm">
        <span class="material-symbols-outlined text-base">chevron_left</span>
        <span class="hidden lg:inline">Kembali</span>
      </a>
      <?php endif; ?>
      
      <h1 class="text-lg font-semibold">Stock Bahan Baku</h1>
    </div>
  </header>
  <main class="pt-20 px-4 pb-32 max-w-6xl mx-auto space-y-6">
    
    <div class="flex justify-between items-center mb-4">
      <a href="produksi" class="group flex items-center bg-gray-800 hover:bg-yellow-400 text-white px-4 py-2 rounded text-sm transition">
        <span class="material-symbols-outlined text-sm text-yellow-400 group-hover:text-gray-800">chevron_right</span>
        <span class="ml-2 group-hover:text-gray-800">Produksi</span>
      </a>
      <a href="bahan-baku-tambah" class="group flex items-center bg-gray-800 hover:bg-yellow-400 text-white px-4 py-2 rounded text-sm transition">
        <span class="material-symbols-outlined text-sm text-yellow-400 group-hover:text-gray-800">add_circle</span>
        <span class="ml-2 group-hover:text-gray-800">Bahan Baku</span>
      </a>
    </div>

    <div class="mb-4 flex justify-between items-center flex-wrap gap-2">
      <input id="searchInput" type="text" placeholder="Cari bahan baku..." class="w-full md:w-1/3 px-3 py-2 border border-gray-300 rounded">
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
            <th class="px-4 py-3 text-center whitespace-nowrap">No</th>
            <th class="px-4 py-3 text-center whitespace-nowrap">Bahan Baku</th>
            <th class="px-4 py-3 text-center whitespace-nowrap">Stok</th>
            <th class="px-4 py-3 text-center whitespace-nowrap">Satuan</th>
            <th class="px-4 py-3 text-center">Harga/Satuan</th>
            <th class="px-4 py-3 text-center whitespace-nowrap">Total Harga</th>
            <th class="px-4 py-3 text-center whitespace-nowrap">Aksi</th>
          </tr>
        </thead>
        <tbody id="materialTable" class="text-gray-700 text-sm divide-y divide-gray-200">
          <?php foreach ($materials as $index => $m):
          ?>
            <tr class="data-row hover:bg-gray-100">
              <td class="px-4 py-2 text-center"><?= $index + 1 ?></td>
              <td class="px-4 py-2 text-left whitespace-nowrap"><?= htmlspecialchars($m["material_name"]) ?></td>
              <td class="px-4 py-2 text-right"><?= number_format($m["stock_quantity"], 0, ',', '.') ?></td>
              <td class="px-4 py-2 text-center"><?= $m["unit_symbol"] ?></td>
              <td class="px-4 py-2 text-right"><?= number_format($m["last_price"], 0, ',', '.') ?></td>
              <td class="px-4 py-2 text-right"><?= number_format($m["total_value"], 0, ',', '.') ?></td>
              <td class="px-4 py-2 text-center">
                <a href="bahan-baku-rincian?id=<?= $m["id"] ?>" class="text-blue-600 hover:text-blue-800">
                  <span class="material-symbols-outlined text-base">visibility</span>
                </a>
              </td>
            </tr>
          <?php endforeach ?>
          <?php if (empty($materials)): ?>
            <tr>
              <td colspan="9" class="px-4 py-2 text-center text-gray-500">Belum ada transaksi.</td>
            </tr>
          <?php endif; ?>
            <tr class="bg-gray-100 font-semibold">
              <td colspan="5" class="px-4 py-2 text-right">TOTAL</td>
              <td class="px-4 py-2 text-right"><?= number_format($grand_total, 0, ',', '.') ?></td>
              <td class="px-4 py-2 text-center"></td>
            </tr>
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
      if (text.includes(query)) {
        row.classList.add("match");
      } else {
        row.classList.remove("match");
        row.style.display = "none"; // pastikan hilang
      }
    }
    currentPage = 1;
    paginate();
  }
  
  function paginate() {
    const maxRows = parseInt(rowsPerPage.value);
    const visibleRows = [...rows].filter(r => r.classList.contains("match"));
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
  
  for (let row of rows) row.classList.add("match");

  rowsPerPage.addEventListener("change", paginate);
  searchInput.addEventListener("keyup", filterRows);
  window.onload = paginate;
</script>
</body>
</html>