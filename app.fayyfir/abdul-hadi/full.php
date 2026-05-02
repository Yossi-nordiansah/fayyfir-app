<?php  
session_start();  
if (!isset($_SESSION["user_id"])) {  
  header("Location: login");  
  exit();  
}  

require "config.php";  
$level = $_SESSION["role_id"] ?? "";  

// Ambil semua kontainer status full
$query = "    
  SELECT c.*, p.name AS product_name     
  FROM containers c    
  LEFT JOIN products p ON c.product_id = p.id    
  WHERE c.status = 'full'    
  ORDER BY c.updated_at DESC    
";    
    
$result = $conn->query($query);    

// Ambil nomor urut terakhir
$q_last_number = $conn->query("SELECT MAX(number) AS last_number FROM containers");
$row_last = $q_last_number->fetch_assoc();
$last_number = $row_last['last_number'] ?? 0;  

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['nomor_urut'], $_POST['container_id'])) {  
    $nomor_urut = intval($_POST['nomor_urut']);  
    $container_id = intval($_POST['container_id']);  

    // Validasi agar nomor tidak mundur
    if ($nomor_urut < $last_number) {
        $_SESSION["status_pesan"] = "Nomor urut tidak boleh lebih kecil dari $last_number";
        header("Location: full.php");
        exit();
    }

    $stmt = $conn->prepare("UPDATE containers SET number = ? WHERE id = ?");  
    $stmt->bind_param("ii", $nomor_urut, $container_id);  

    if ($stmt->execute()) {  
        header("Location: riwayat-kontainer?id=$container_id");  
        exit();  
    } else {  
        echo "<script>alert('Gagal menyimpan nomor urut');</script>";  
    }  
}  
?>  

<!DOCTYPE html>  
<html lang="id">  
<head>  
  <meta charset="UTF-8" />  
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />  
  <title>Verifikasi Kontainer - Fayyfir</title>  
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
      <h1 class="text-lg font-semibold">Kontainer Full</h1>  
    </div>  
  </header>  
    
  <main class="pt-20 px-4 pb-32 max-w-6xl mx-auto space-y-6">  
      
    <!-- Notifikasi Kontainer -->  
      <?php if (isset($_SESSION["status_pesan"])): ?>  
        <div class="mb-4 p-3 bg-green-100 text-green-700 border border-green-300 rounded">  
          <?=  
          $_SESSION["status_pesan"];  
          unset($_SESSION["status_pesan"]);  
          ?>  
        </div>  
      <?php endif; ?>  
        
    <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">  
      <?php while ($row = $result->fetch_assoc()): ?>  
        <div class="bg-white rounded-lg shadow p-4 text-gray-800 flex justify-between items-center" onclick="openModal(<?= $row['id'] ?>)">  
          <div class="flex items-center space-x-4">  
            <span class="material-symbols-outlined text-yellow-400 text-4xl">inventory_2</span>  
            <div>  
              <h2 class="text-sm text-gray-500">Area: <?= htmlspecialchars($row["region_name"] ?? "-") ?></h2>  
              <p class="text-xl font-bold text-gray-500"><?= htmlspecialchars($row["container_number"]) ?></p>  
              <h2 class="text-sm text-gray-500">Produk: <?= htmlspecialchars($row["product_name"] ?? "-") ?></h2>  
            </div>  
          </div>  
          <div class="flex flex-col items-center">  
            <h2 class="text-sm text-gray-500">Status</h2>  
            <?php if ($row["status"] == "full"): ?>  
              <span class="text-green-500 mt-1 text-sm font-semibold">Full</span>  
            <?php else: ?>  
              <span class="text-red-500 mt-1 text-sm font-semibold">Load</span>  
            <?php endif; ?>  
          </div>  
        </div>  
      <?php endwhile; ?>  
    </section>  

  </main>  
    
<!-- Modal -->  
<div id="modalNomorUrut" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">  
  <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6">  
    <h2 class="text-xl font-semibold mb-4">Isi Nomor Urut Kontainer</h2>  
    <form id="formNomorUrut" method="POST">  
      <div class="mb-4">  
        <label class="block mb-1">Nomor Urut</label>  
        <input type="number" name="nomor_urut" id="nomorUrutInput" required class="w-full border rounded px-3 py-2">  
        <!-- Tambahan teks merah -->
        <p class="text-xs text-red-500 mt-1">Nomor terakhir adalah <?= (int)$last_number ?></p>
      </div>  
      <input type="hidden" name="container_id" id="containerIdInput">  
      <div class="flex justify-end space-x-2">  
        <a id="isiKembaliBtn" href="#" class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600">Isi Kembali</a>  
        <button type="button" onclick="closeModal()" class="px-4 py-2 bg-gray-300 rounded">Batal</button>  
        <button type="submit" class="px-4 py-2 bg-yellow-500 text-white rounded hover:bg-yellow-600">Simpan</button>  
      </div>  
    </form>  
  </div>  
</div>  

<script>  
// Ambil nomor terakhir dari PHP
const lastNumber = <?= (int)$last_number ?>;  

function openModal(containerId) {  
  document.getElementById('modalNomorUrut').classList.remove('hidden');  
  document.getElementById('containerIdInput').value = containerId;  
  document.getElementById('isiKembaliBtn').href = "ubah-status-draft.php?id=" + containerId;  

  // isi field dengan nomor berikutnya
  const input = document.getElementById('nomorUrutInput');  
  input.value = lastNumber + 1;  
  input.min = lastNumber; // validasi agar tidak bisa lebih kecil dari nomor terakhir
}  

function closeModal() {  
  document.getElementById('modalNomorUrut').classList.add('hidden');  
}  
</script>  
</body>  
</html>