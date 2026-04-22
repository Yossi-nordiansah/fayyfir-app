<?php    
session_start();    
if (!isset($_SESSION["user_id"])) {    
  header("Location: login");    
  exit();    
}    

require "config.php";    

// Ambil data dari product_stocks sebagai sumber utama
$sql = "  
  SELECT ps.id, ps.product_name, ps.quantity, u.symbol AS unit_symbol
  FROM product_stocks ps
  LEFT JOIN units u ON ps.unit_id = u.id
  ORDER BY ps.updated_at DESC
";  
$result = $conn->query($sql);  

$productions = [];  
while ($row = $result->fetch_assoc()) {  
    $productions[] = $row;  
}  
?>    

<!DOCTYPE html>    
<html lang="id">    
<head>    
  <meta charset="UTF-8" />    
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />    
  <title>Hasil Produksi - Fayyfir</title>    
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
    <h1 class="text-lg font-semibold">Hasil Produksi</h1>    
  </div>    
</header>    

<main class="pt-20 px-4 pb-32 max-w-6xl mx-auto space-y-6">  

  <!-- Filter & jumlah baris -->    
  <div class="mb-4 flex justify-between items-center flex-wrap gap-2">    
    <input id="searchInput" type="text" placeholder="Cari produk..." class="w-full md:w-1/3 px-3 py-2 border border-gray-300 rounded">    
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

  <!-- Tabel Data -->    
  <div class="overflow-x-auto bg-white shadow rounded-lg">    
    <table class="min-w-full divide-y divide-gray-200 text-sm">    
      <thead class="bg-gray-800 text-yellow-400 text-sm">    
        <tr>    
          <th class="px-4 py-3 text-center whitespace-nowrap">No.</th>    
          <th class="px-4 py-3 text-center whitespace-nowrap">Nama Produk</th>    
          <th class="px-4 py-3 text-center whitespace-nowrap">Stok</th>  
          <th class="px-4 py-3 text-center whitespace-nowrap">Aksi</th>    
        </tr>    
      </thead>    
      <tbody id="materialTable" class="text-gray-700 text-sm divide-y divide-gray-200">    
        <?php if (!empty($productions)): ?>    
          <?php $no = 1; foreach ($productions as $row): ?>    
            <tr class="data-row">    
              <td class="px-4 py-2 text-center"><?= $no++ ?></td>    
              <td class="px-4 py-2"><?= htmlspecialchars($row['product_name']) ?></td>    
              <td class="px-4 py-2 text-right">  
                <?= number_format($row['quantity'], 0, ',', '.') ?> <?= htmlspecialchars($row['unit_symbol'] ?? '') ?>  
              </td>  
              <td class="px-4 py-2 text-center space-x-2">    
                <a href="hasil-produksi-riwayat.php?product_name=<?= urlencode($row['product_name']) ?>" class="text-blue-600 hover:text-blue-800">    
                  <span class="material-symbols-outlined text-base">visibility</span>    
                </a>
                <button type="button" class="text-yellow-400 hover:text-yellow-600 openEditModal" data-id="<?= $row['id'] ?>" data-name="<?= htmlspecialchars($row['product_name'], ENT_QUOTES) ?>">
                  <span class="material-symbols-outlined text-base">edit</span>
                </button>
              </td>    
            </tr>    
          <?php endforeach; ?>    
        <?php else: ?>    
          <tr>    
            <td colspan="4" class="px-4 py-2 text-center text-gray-500">Belum ada data produksi.</td>    
          </tr>    
        <?php endif; ?>    
      </tbody>    
    </table>    
  </div>    

  <!-- Pagination -->    
  <div class="flex justify-between items-center mt-4 text-sm text-gray-600">    
    <div id="totalRowsInfo"></div>    
    <div id="paginationControls" class="flex gap-1"></div>    
  </div>    
</main>    

<!-- Edit Product Modal -->
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-40 hidden items-center justify-center z-50">
  <div class="bg-white w-full max-w-md rounded-lg shadow-lg p-6">
    
    <h2 class="text-lg font-semibold mb-4">Ubah Nama Produk</h2>
    
    <form id="editProductForm" class="space-y-4">
      <input type="hidden" id="editProductId">
      
      <div>
        <label class="text-sm font-semibold">Nama Produk</label>
        <input 
          type="text" 
          id="editProductName"
          class="w-full border rounded px-3 py-2 mt-1"
          required>
      </div>

      <div class="flex justify-end gap-2 pt-2">
        <button type="button" id="closeModal" class="px-4 py-2 rounded border">
          Batal
        </button>
        <button type="submit" class="px-4 py-2 rounded bg-yellow-500 hover:bg-yellow-600 text-white">
          Simpan
        </button>
      </div>
    </form>

  </div>
</div>

<!-- Script Pagination & Search -->    
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
      row.querySelector("td:first-child").textContent = index + 1 + (currentPage - 1) * maxRows;    
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
  
/* ============================
   Modal Logic
============================ */
const modal = document.getElementById("editModal");
const closeModalBtn = document.getElementById("closeModal");
const form = document.getElementById("editProductForm");
const idInput = document.getElementById("editProductId");
const nameInput = document.getElementById("editProductName");

document.addEventListener("click", e => {
  const btn = e.target.closest(".openEditModal");
  if (!btn) return;

  idInput.value = btn.dataset.id;
  nameInput.value = btn.dataset.name;
  modal.classList.remove("hidden");
  modal.classList.add("flex");
});

closeModalBtn.onclick = () => {
  modal.classList.add("hidden");
  modal.classList.remove("flex");
};

modal.addEventListener("click", e => {
  if (e.target === modal) closeModalBtn.onclick();
});

/* ============================
   Submit Edit (AJAX)
============================ */
form.addEventListener("submit", async e => {
  e.preventDefault();

  const id = idInput.value;
  const name = nameInput.value.trim();

  if (!name) return alert("Nama produk wajib diisi.");

  const res = await fetch("produk-update-nama.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ id, name })
  });

  const data = await res.json();

  if (data.success) {
    // Update nama di tabel tanpa reload
    document.querySelectorAll(`.openEditModal[data-id='${id}']`)
      .forEach(btn => {
        btn.dataset.name = name;
        btn.closest("tr").children[1].textContent = name;
      });

    closeModalBtn.onclick();
  } else {
    alert(data.message || "Gagal update data.");
  }
});
</script>

</body>    
</html>