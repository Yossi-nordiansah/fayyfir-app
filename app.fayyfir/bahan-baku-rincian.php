<?php  
session_start();  
require "config.php";  
  
if (!isset($_SESSION["user_id"])) {  
  header("Location: login");  
  exit();  
}  
  
$id = (int) $_GET["id"];  
  
// Ambil info bahan baku  
$stmt = $conn->prepare("
  SELECT m.name, m.category, m.description, u.symbol AS unit, ms.quantity, ms.minimum_quantity,
    COALESCE(SUM(mp.quantity), 0) AS sum_quantity,
    COALESCE(SUM(mp.total_price), 0) AS sum_price,
    COALESCE(AVG(mp.unit_price), 0) AS avg_price
  FROM materials m
  LEFT JOIN units u ON m.unit_id = u.id
  LEFT JOIN material_stocks ms ON ms.material_id = m.id
  LEFT JOIN material_purchases mp ON mp.material_id = m.id
  WHERE m.id = ?
  GROUP BY m.id
");
$stmt->bind_param("i", $id);
$stmt->execute();
$material = $stmt->get_result()->fetch_assoc();

// Ambil harga terakhir dari material_purchases
$stmt_last = $conn->prepare("
  SELECT mp.unit_price, u.symbol AS units_kg
  FROM material_purchases mp
  LEFT JOIN units u ON mp.unit_id = u.id
  WHERE material_id = ?
  ORDER BY created_at DESC
  LIMIT 1
");
$stmt_last->bind_param("i", $id);
$stmt_last->execute();
$last_price = $stmt_last->get_result()->fetch_assoc();

// Masukkan harga terakhir ke $material
$material['last_unit_price'] = $last_price['unit_price'] ?? 0;
$material['last_kg_unit']   = $last_price['units_kg'] ?? 0;
  
// Ambil riwayat pembelian  
$stmt2 = $conn->prepare("
  SELECT mp.id, mp.purchase_date, mp.quantity, mp.unit_price, u.symbol AS unit,
         mp.supplier_name, mp.total_price
  FROM material_purchases mp
  LEFT JOIN units u ON mp.unit_id = u.id
  WHERE mp.material_id = ?
  ORDER BY mp.created_at DESC
");
$stmt2->bind_param("i", $id);  
$stmt2->execute();  
$history = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);  
  
$grand_total = 0;  
$qty_total = 0;  
foreach ($history as $hs) {  
    $grand_total += $hs['total_price'];  
    $qty_total += $hs['quantity'];  
    $satuan = $hs['unit'];  
}  
  
// Ambil Log Bahan  
$stmt3 = $conn->prepare("  
  SELECT sm.id, sm.change_type, sm.quantity, sm.unit_price, sm.amount, sm.note, sm.created_at, u.symbol AS unit  
  FROM stock_movements sm  
  LEFT JOIN materials m ON sm.material_id = m.id  
  LEFT JOIN units u ON m.unit_id = u.id  
  WHERE sm.material_id = ?  
  ORDER BY sm.created_at DESC  
");  
$stmt3->bind_param("i", $id);  
$stmt3->execute();  
$log_material = $stmt3->get_result()->fetch_all(MYSQLI_ASSOC);
?>  
  
<!DOCTYPE html>  
<html lang="id">  
<head>  
  <meta charset="UTF-8" />  
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>  
  <title>Rincian Bahan Baku</title>  
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet"/>  
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">  
</head>  
<body class="bg-gray-100 text-gray-800 min-h-screen">  
  <header class="bg-gray-900 text-white py-4 px-6 fixed top-0 left-0 right-0 z-40">  
    <div class="flex justify-between items-center">  
      <a href="bahan-baku" class="flex items-center space-x-1 text-yellow-400 hover:underline text-sm">  
        <span class="material-symbols-outlined text-base">chevron_left</span>  
        <span class="hidden lg:inline">Kembali</span>  
      </a>  
      <h1 class="text-lg font-semibold">Rincian Bahan Baku</h1>  
    </div>  
  </header>  
  
  <main class="pt-24 px-4 pb-32 max-w-6xl mx-auto space-y-6">  
    <section class="bg-white max-w-lg p-4 rounded-lg shadow">  
      <h2 class="text-md font-semibold mb-2">BAHAN BAKU</h2>  
      <table class="min-w-full divide-y divide-gray-200 text-sm">  
        <tbody class="text-gray-700 text-sm divide-y divide-gray-200">  
          <tr>  
            <td class="px-4 py-3 font-semibold max-w-fit">Nama</td>  
            <td>:</td>  
            <td class="px-4 py-2"><?= htmlspecialchars($material["name"]) ?></td>  
          </tr>  
          <tr>  
            <td class="px-4 py-3 font-semibold max-w-fit">Kategori</td>  
            <td>:</td>  
            <td class="px-4 py-2"><?= htmlspecialchars($material["category"]) ?></td>  
          </tr>  
          <tr>  
            <td class="px-4 py-3 font-semibold max-w-fit">Stok</td>  
            <td>:</td>  
            <td class="px-4 py-2"><?= number_format($material["quantity"], 0, ',', '.') ?> <?= htmlspecialchars($material["unit"]) ?></td>  
          </tr>  
          <tr>  
            <td class="px-4 py-3 font-semibold max-w-fit">Min Stock</td>  
            <td>:</td>  
            <td class="px-4 py-2"><?= number_format($material["minimum_quantity"], 0, ',', '.') ?> <?= htmlspecialchars($material["unit"]) ?></td>  
          </tr>  
          <tr>  
            <td class="px-4 py-3 font-semibold max-w-fit">Total Nilai</td>  
            <td>:</td>  
            <td class="px-4 py-2 font-semibold text-green-700">Rp <?= number_format($material["sum_price"], 0, ',', '.') ?></td>  
          </tr>  
        </tbody>  
      </table>  
        
      <!-- Peringatan Stock Hampir Habis -->  
      <?php if (isset($_GET['min_qty']) && $_GET['min_qty'] <= ($material['minimum_quantity'])): ?>  
        <div class="mb-4 bg-gray-100 border gray-red-400 text-gray-700 px-4 py-3 rounded relative flex items-start space-x-2">  
          <span class="material-symbols-outlined mt-0.5">error</span>  
          <span>  
            Stock sudah hampir habis, silahkan isi ulang stock.  
          </span>  
        </div>  
      <?php endif; ?>  
        
      <!-- Tombol Aksi -->  
      <div class="mt-6 flex justify-end space-x-3">  
        <a href="bahan-baku-edit?id=<?= $id ?>" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 text-sm">Edit</a>  
        <a href="bahan-baku-hapus?id=<?= $id ?>" onclick="return confirm('Yakin ingin menghapus data bahan baku ini?\nMenghapus data bahan baku berarti turut menghapus semua data transaksinya.')"   
           class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 text-sm"   
           title="Hapus">Hapus</a>  
      </div>  
        
    </section>  
    <section>  
      <div class="flex justify-end gap-2 mb-4">  
        <a href="bahan-baku-tambah-stock?id=<?= $id ?>" class="group flex items-center bg-gray-800 hover:bg-yellow-400 text-white px-4 py-2 rounded text-sm transition">  
          <span class="material-symbols-outlined text-sm text-yellow-400 group-hover:text-gray-800">add_circle</span>  
          <span class="ml-2 group-hover:text-gray-800">Stock</span>  
        </a>  
      </div>  
        
      <h2 class="text-md mb-2 font-semibold">RIWAYAT BELANJA BAHAN</h2>  
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
        
      <div class="overflow-auto bg-white shadow rounded-lg">  
        <table class="min-w-full divide-y divide-gray-200 text-sm shadow">  
          <thead class="bg-gray-800 text-yellow-400">  
            <tr>  
              <th class="border-r border-gray-700 px-4 py-2 text-center">Tanggal</th>  
              <th class="border-r border-gray-700 px-4 py-2 text-center">Keterangan</th>  
              <th class="border-r border-gray-700 px-4 py-2 text-center">Qty (gram)</th>  
              <th class="border-r border-gray-700 px-4 py-2 text-center">Harga/gram</th>  
              <th class="border-r border-gray-700 px-4 py-2 text-center whitespace-nowrap">Jumlah</th>
              <th class="px-4 py-2 text-center">Aksi</th>  
            </tr>  
          </thead>  
          <tbody id="materialTable" class="text-gray-800 divide-y divide-gray-200">  
            <?php if ($history): ?>  
            <?php foreach ($history as $t): ?>  
              <tr class="data-row hover:bg-gray-100">  
                <td class="px-4 py-2 text-center whitespace-nowrap"><?= date("d-m-Y", strtotime($t['purchase_date'])) ?></td>  
                <td class="px-4 py-2 text-left whitespace-nowrap">Beli dari <?= htmlspecialchars($t['supplier_name'] ?? '-') ?></td>  
                <td class="px-4 py-2 text-right whitespace-nowrap"><?= number_format($t['quantity'], 0, ',', '.') ?></td>  
                <td class="px-4 py-2 text-right whitespace-nowrap">Rp <?= number_format($t['unit_price'], 0, ',', '.') ?></td>
                
                <td class="px-4 py-2 text-right whitespace-nowrap">Rp <?= number_format($t['total_price'], 0, ',', '.') ?></td>  
                <td class="px-4 py-2 text-center whitespace-nowrap">  
                  <a href="bahan-baku-edit-stock?id=<?= $t['id'] ?>&supplier_id=<?= $id ?>" class="inline-block text-blue-500 hover:text-blue-700 mr-2 text-sm" title="Edit">  
                    <span class="material-symbols-outlined text-base">edit</span>  
                  </a>  
                  <a href="bahan-baku-hapus-stock?id=<?= $t['id'] ?>&supplier_id=<?= $id ?>" onclick="return confirm('Yakin ingin menghapus data ini?')" class="inline-block text-red-500 hover:text-red-700 text-sm" title="Hapus">  
                    <span class="material-symbols-outlined text-base">delete</span>  
                  </a>  
                </td>  
              </tr>  
            <?php endforeach; ?>  
          <?php else: ?>  
            <tr><td colspan="7" class="px-4 py-2 text-center text-gray-500">Belum ada transaksi.</td></tr>  
          <?php endif; ?>  
            <tr class="bg-gray-800 text-yellow-400 font-semibold">  
              <td colspan="2" class="px-4 py-2 text-right">TOTAL</td>  
              <td class="px-4 py-2 text-right whitespace-nowrap"><?= number_format($qty_total, 0, ',', '.') ?></td>  
              <td colspan="2" class="px-4 py-2 text-right whitespace-nowrap">Rp <?= number_format($grand_total, 0, ',', '.') ?></td>  
              <td></td>  
            </tr>  
          </tbody>  
        </table>  
      </div>  
        
      <div class="flex justify-between items-center mt-4 text-sm text-gray-600">  
        <div id="totalRowsInfo"></div>  
        <div id="paginationControls" class="flex gap-1"></div>  
      </div>  
        
    </section>  
    <section>  
      <h2 class="text-md mb-2 font-semibold">LOG BAHAN</h2>  
      <div class="mb-4 flex justify-between items-center flex-wrap gap-2">  
        <input id="logSearchInput" type="text" placeholder="Cari log bahan..." class="w-full md:w-1/3 px-3 py-2 border border-gray-300 rounded">  
        <div class="text-sm">  
          Tampilkan   
          <select id="logRowsPerPage" class="border border-gray-300 rounded px-2 py-1">  
            <option value="10" selected>10</option>  
            <option value="25">25</option>  
            <option value="50">50</option>  
          </select>   
          baris  
        </div>  
      </div>  
        
      <div class="overflow-auto bg-white shadow rounded-lg">  
        <table class="min-w-full divide-y divide-gray-200 text-sm shadow">  
          <thead class="bg-gray-800 text-yellow-400">  
            <tr>  
              <th class="px-4 py-2 text-center border-r border-gray-700">Tanggal</th>  
              <th class="px-4 py-2 text-center border-r border-gray-700">Keterangan</th>  
              <th class="px-4 py-2 text-center border-r border-gray-700">Qty (gram)</th>  
              <th class="px-4 py-2 text-center border-r border-gray-700">Harga/gram</th>  
              <th class="px-4 py-2 text-center whitespace-nowrap">Jumlah</th>  
            </tr>  
          </thead>  
          <tbody id="logMaterialTable" class="text-gray-800 divide-y divide-gray-200">  
            <?php if ($log_material): ?>  
            <?php foreach ($log_material as $lm): ?>  
              <tr class="log-row hover:bg-gray-100">  
                <td class="px-4 py-2 text-center whitespace-nowrap"><?= date("d-m-Y", strtotime($lm['created_at'])) ?></td>  
                <td class="px-4 py-2 text-left whitespace-nowrap"><?= htmlspecialchars($lm['note'] ?? '-') ?></td>  
                <td class="px-4 py-2 text-right whitespace-nowrap"><?= number_format($lm['quantity'], 0, ',', '.') ?></td>  
                <td class="px-4 py-2 text-right whitespace-nowrap">Rp <?= number_format($lm['unit_price'], 0, ',', '.') ?></td>  
                <td class="px-4 py-2 text-right whitespace-nowrap">Rp <?= number_format($lm['amount'], 0, ',', '.') ?></td>  
              </tr>  
            <?php endforeach; ?>  
          <?php else: ?>  
            <tr><td colspan="7" class="px-4 py-2 text-center text-gray-500">Belum ada transaksi.</td></tr>  
          <?php endif; ?>  
          </tbody>  
        </table>  
      </div>  
        
      <div class="flex justify-between items-center mt-4 text-sm text-gray-600">  
        <div id="logTotalRowsInfo"></div>  
        <div id="logPaginationControls" class="flex gap-1"></div>  
      </div>  
        
    </section>  
  </main>  
  
<script>  
  // Fungsi reusable  
  function setupPagination({   
    rows,   
    rowsPerPage,   
    searchInput,   
    pagination,   
    totalInfo   
  }) {  
    let currentPage = 1;  
  
    function filterRows() {  
      const query = searchInput.value.toLowerCase();  
      for (let row of rows) {  
        const text = row.innerText.toLowerCase();  
        if (text.includes(query)) {  
          row.classList.add("match");  
        } else {  
          row.classList.remove("match");  
          row.style.display = "none";  
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
      
      let showingCount = 0;  
      visibleRows.forEach((row, index) => {  
        if (index >= (currentPage - 1) * maxRows && index < currentPage * maxRows) {  
          row.style.display = "";  
          showingCount++;  
        } else {  
          row.style.display = "none";  
        }  
      });  
      
      pagination.innerHTML = "";  
      for (let i = 1; i <= totalPages; i++) {  
        const btn = document.createElement("button");  
        btn.className =  
          "px-2 py-1 border rounded " +  
          (i === currentPage  
            ? "bg-yellow-500 text-white"  
            : "hover:bg-yellow-100");  
        btn.textContent = i;  
        btn.onclick = () => {  
          currentPage = i;  
          paginate();  
        };  
        pagination.appendChild(btn);  
      }  
      
      totalInfo.textContent = `Menampilkan ${showingCount} data dari total ${visibleRows.length}`;  
    }  
  
    // init  
    for (let row of rows) row.classList.add("match");  
    rowsPerPage.addEventListener("change", paginate);  
    searchInput.addEventListener("keyup", filterRows);  
    paginate();  
  }  
  
  // Setup untuk tabel RIWAYAT BELANJA BAHAN  
  setupPagination({  
    rows: document.querySelectorAll("#materialTable .data-row"),  
    rowsPerPage: document.getElementById("rowsPerPage"),  
    searchInput: document.getElementById("searchInput"),  
    pagination: document.getElementById("paginationControls"),  
    totalInfo: document.getElementById("totalRowsInfo")  
  });  
  
  // Setup untuk tabel LOG BAHAN  
  setupPagination({  
    rows: document.querySelectorAll("#logMaterialTable .log-row"),  
    rowsPerPage: document.getElementById("logRowsPerPage"),  
    searchInput: document.getElementById("logSearchInput"),  
    pagination: document.getElementById("logPaginationControls"),  
    totalInfo: document.getElementById("logTotalRowsInfo")  
  });  
</script>  
  
</body>  
</html>