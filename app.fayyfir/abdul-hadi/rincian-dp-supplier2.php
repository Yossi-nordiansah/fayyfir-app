<?php
session_start();
require "config.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

$id = (int) $_GET["id"];

// get supplier data
$query = "SELECT s.*, p.name AS province_name, r.name AS regency_name, d.name AS district_name, v.name AS village_name
          FROM suppliers s
          LEFT JOIN reg_provinces p ON s.province_id = p.id
          LEFT JOIN reg_regencies r ON s.regency_id = r.id
          LEFT JOIN reg_districts d ON s.district_id = d.id
          LEFT JOIN reg_villages v ON s.village_id = v.id
          WHERE s.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$supplier = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$supplier) {
  echo "Data tidak ditemukan.";
  exit();
}

// get manual DP
$query1 = "SELECT id, deposit_date AS created_at, description, debit, credit
           FROM deposits_supplier
           WHERE supplier_id = ?
           ORDER BY deposit_date ASC, id ASC";
$stmt1 = $conn->prepare($query1);
$stmt1->bind_param("i", $id);
$stmt1->execute();
$manual_result = $stmt1->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt1->close();

$runningSaldo  = 0;
$total_debit   = 0;
$total_credit  = 0;
$grand_total   = 0;

foreach ($manual_result as $i => $row) {
    $debit  = (int)($row['debit']  ?? 0);
    $credit = (int)($row['credit'] ?? 0);

    $total_debit  += $debit;
    $total_credit += $credit;

    if ($i === 0) {
        $runningSaldo = $debit - $credit;
    } else {
        $runningSaldo += $debit - $credit;
    }

    // tempel saldo per baris
    $manual_result[$i]['saldo'] = $runningSaldo;
}

$grand_total = $total_debit - $total_credit;

// get container transaction
$sql = "SELECT t.id, t.created_at, t.container_id, c.container_number, t.weight_kg, t.total_price
        FROM transactions t
        JOIN containers c ON t.container_id = c.id
        WHERE t.supplier_id = ?
        ORDER BY t.created_at ASC, t.id ASC";
$stmt2 = $conn->prepare($sql);
$stmt2->bind_param("i", $id);
$stmt2->execute();
$container_result = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

$total_weight   = 0;
$grand_price   = 0;

foreach ($container_result as $i => $row) {
    $weight  = (int)($row['weight_kg']  ?? 0);
    $price = (int)($row['total_price'] ?? 0);
    $total_weight  += $weight;
    $grand_price += $price;
}

$sisa_dp = $grand_total - $grand_price;
// helper rupiah
function formatRupiah($angka) {
  return "Rp " . number_format($angka, 0, ",", ".");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Rincian DP Supplier</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen">
  <header class="bg-gray-900 text-white py-4 px-6 fixed top-0 left-0 right-0 z-40">
    <div class="flex justify-between items-center">
      <a href="riwayat-dp-supplier.php" class="flex items-center space-x-1 text-yellow-400 hover:underline text-sm">
        <span class="material-symbols-outlined text-base">chevron_left</span>
        <span class="hidden lg:inline">Kembali</span>
      </a>
      <h1 class="text-lg font-semibold">Rincian DP Supplier</h1>
    </div>
  </header>

  <main class="pt-24 px-4 pb-32 max-w-6xl mx-auto space-y-6">
    <!-- Ringkasan -->
    <section class="bg-white p-4 rounded-lg shadow">
      <h2 class="text-md font-semibold mb-2">Ringkasan DP</h2>
      <table class="min-w-full divide-y divide-gray-200 text-sm">
        <tbody class="text-gray-800 divide-y divide-gray-200">
          <tr><td class="pr-4 py-2 font-semibold">Nama</td><td>:</td><td class="pl-2"><?= htmlspecialchars($supplier["name"]) ?></td></tr>
          <tr><td class="pr-4 py-2 font-semibold">Nomor HP</td><td>:</td><td class="pl-2"><?= htmlspecialchars($supplier["phone"]) ?></td></tr>
          <tr><td class="pr-4 py-2 font-semibold">Alamat</td><td>:</td><td class="pl-2"><?= htmlspecialchars($supplier["address"]) ?>, <?= $supplier["village_name"] ?>, <?= $supplier["district_name"] ?>, <?= $supplier["regency_name"] ?>, <?= $supplier["province_name"] ?></td></tr>
          <tr><td class="pr-4 py-2 font-semibold">Sisa DP</td><td>:</td><td class="pl-2 font-semibold text-green-700"><?= formatRupiah($sisa_dp) ?></td></tr>
        </tbody>
      </table>
      <div class="mt-6 flex justify-end space-x-3">
        <a href="edit-supplier?id=<?= $id ?>" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 text-sm">Edit Supplier</a>
        <form method="POST" action="hapus-supplier.php" onsubmit="return confirm('Yakin ingin menghapus supplier ini?')">
          <input type="hidden" name="id" value="<?= $id ?>"/>
          <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 text-sm">Hapus Supplier</button>
        </form>
      </div>
    </section>

    <!-- DP Manual -->
    <section>
      <div class="flex justify-end gap-2 mb-4">
        <a href="tambah-dp?supplier_id=<?= $id ?>" class="group flex items-center bg-gray-800 hover:bg-yellow-400 text-white px-4 py-2 rounded text-sm transition">
          <span class="material-symbols-outlined text-sm text-yellow-400 group-hover:text-gray-800">add_circle</span>
          <span class="ml-2">TopUp</span>
        </a>
        <a href="refund-dp?supplier_id=<?= $id ?>" class="group flex items-center bg-gray-800 hover:bg-yellow-400 text-white px-4 py-2 rounded text-sm transition">
          <span class="material-symbols-outlined text-sm text-yellow-400 group-hover:text-gray-800">refresh</span>
          <span class="ml-2">Refund</span>
        </a>
        <a href="rincian-dp-supplier-pdf.php?id=<?= $id ?>" target="_blank" class="group flex items-center bg-gray-800 hover:bg-yellow-400 text-white px-4 py-2 rounded text-sm transition">
          <span class="material-symbols-outlined text-sm text-yellow-400 group-hover:text-gray-800">picture_as_pdf</span>
          <span class="ml-2">PDF</span>
        </a>
      </div>
      <h2 class="text-md mb-2 font-semibold">Riwayat Transaksi DP</h2>
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
        <table class="min-w-full divide-y divide-gray-200 text-sm">
          <thead class="bg-gray-100 text-gray-600">
            <tr>
              <th class="px-4 py-2 text-center">Tanggal</th>
              <th class="px-4 py-2 text-center">Deskripsi</th>
              <th class="px-4 py-2 text-center">Debit</th>
              <th class="px-4 py-2 text-center">Kredit</th>
              <th class="px-4 py-2 text-center">Sisa Saldo</th>
              <th class="px-4 py-2 text-center whitespace-nowrap">Aksi</th>
            </tr>
          </thead>
          <tbody id="materialTable" class="text-gray-800 divide-y divide-gray-200">
            <?php if ($manual_result): ?>
            <?php foreach ($manual_result as $t): ?>
              <tr class="data-row">
                <td class="px-4 py-2 text-center whitespace-nowrap"><?= date("d/m/Y", strtotime($t['created_at'])) ?></td>
                <td class="px-4 py-2 text-left whitespace-nowrap"><?= htmlspecialchars($t['description']) ?></td>
                <td class="px-4 py-2 text-right whitespace-nowrap"><?= $t['debit'] ? number_format($t['debit'], 0, ",", ".") : "-" ?></td>
                <td class="px-4 py-2 text-right whitespace-nowrap"><?= $t['credit'] ? number_format($t['credit'], 0, ",", ".") : "-" ?></td>
                <td class="px-4 py-2 text-right whitespace-nowrap"><?= number_format($t['saldo'] ?? 0, 0, ",", ".") ?></td>
                <td class="px-4 py-2 text-center whitespace-nowrap">
                  <a href="edit-dp.php?id=<?= $t['id'] ?>&supplier_id=<?= $id ?>" class="text-blue-500 hover:text-blue-700 mr-2 text-sm" title="Edit">
                    <span class="material-symbols-outlined text-base">edit</span>
                  </a>
                  <a href="hapus-dp.php?id=<?= $t['id'] ?>&supplier_id=<?= $id ?>" onclick="return confirm('Yakin ingin menghapus data ini?')" class="text-red-500 hover:text-red-700 text-sm" title="Hapus">
                    <span class="material-symbols-outlined text-base">delete</span>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="7" class="px-4 py-2 text-center text-gray-500">Belum ada transaksi.</td></tr>
          <?php endif; ?>
            <tr class="bg-gray-100 font-semibold">
              <td colspan="2" class="px-4 py-2 text-right">TOTAL</td>
              <td class="px-4 py-2 text-right whitespace-nowrap"><?= number_format($total_debit, 0, ',', '.') ?></td>
              <td class="px-4 py-2 text-right whitespace-nowrap"><?= number_format($total_credit, 0, ',', '.') ?></td>
              <td class="px-4 py-2 text-right whitespace-nowrap"><?= number_format($grand_total, 0, ',', '.') ?></td>
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
    
    <!-- Transaksi Kontainer -->
    <section>
      <h2 class="text-md mb-2 font-semibold">Transaksi Kontainer</h2>
      <div class="mb-4 flex justify-between items-center flex-wrap gap-2">
        <input id="logSearchInput" type="text" placeholder="Cari..." class="w-full md:w-1/3 px-3 py-2 border border-gray-300 rounded">
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
        <table class="min-w-full divide-y divide-gray-200 text-sm">
          <thead class="bg-gray-100 text-gray-600">
            <tr>
              <th class="px-4 py-2 text-center">Tanggal</th>
              <th class="px-4 py-2 text-center">Deskripsi</th>
              <th class="px-4 py-2 text-center whitespace-nowrap">Berat (Kg)</th>
              <th class="px-4 py-2 text-center">Nilai</th>
              <th class="px-4 py-2 text-center whitespace-nowrap">Aksi</th>
            </tr>
          </thead>
          <tbody id="logMaterialTable" class="text-gray-800 divide-y divide-gray-200">
            <?php if ($container_result): ?>
            <?php foreach ($container_result as $lm): ?>
              <tr class="log-row">
                <td class="px-4 py-2 text-center whitespace-nowrap"><?= date("d/m/Y", strtotime($lm['created_at'])) ?></td>
                <td class="px-4 py-2 text-left whitespace-nowrap">Pengisian (<?= htmlspecialchars($lm['container_number']) ?>)</td>
                <td class="px-4 py-2 text-right whitespace-nowrap"><?= number_format($lm['weight_kg'], 0, ",", ".") ?></td>
                <td class="px-4 py-2 text-right whitespace-nowrap"><?= number_format($lm['total_price'], 0, ",", ".") ?></td>
                <td class="px-4 py-2 text-center">
                  <a href="rincian-kontainer.php?id=<?= $lm['container_id'] ?>" class="text-blue-500 hover:text-blue-700 text-sm" title="Lihat Detail Kontainer"><span class="material-symbols-outlined text-base">visibility</span>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="7" class="px-4 py-2 text-center text-gray-500">Belum ada transaksi.</td></tr>
          <?php endif; ?>
            <tr class="bg-gray-100 font-semibold">
              <td colspan="2" class="px-4 py-2 text-right">TOTAL</td>
              <td class="px-4 py-2 text-right whitespace-nowrap"><?= number_format($total_weight, 0, ',', '.') ?></td>
              <td class="px-4 py-2 text-right whitespace-nowrap"><?= number_format($grand_price, 0, ',', '.') ?></td>
              <td></td>
            </tr>
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

  // Setup untuk tabel 1
  setupPagination({
    rows: document.querySelectorAll("#materialTable .data-row"),
    rowsPerPage: document.getElementById("rowsPerPage"),
    searchInput: document.getElementById("searchInput"),
    pagination: document.getElementById("paginationControls"),
    totalInfo: document.getElementById("totalRowsInfo")
  });

  // Setup untuk tabel 2
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