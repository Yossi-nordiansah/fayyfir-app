<?php
session_start();
require "config.php";

// Pastikan user login
if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

// Ambil container_id dari URL
$container_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($container_id === 0) {
  echo "ID Kontainer tidak ditemukan.";
  exit();
}

// Ambil info kontainer
$container = $conn->query("SELECT * FROM containers WHERE id = $container_id")->fetch_assoc();
if (!$container) {
  echo "Data kontainer tidak valid.";
  exit();
}

// Submit form
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $invoice_from    = $_POST["invoice_from"];
  $invoice_to      = $_POST["invoice_to"];
  $address         = $_POST["address"];
  $containers      = $_POST["containers"];
  $container_no    = $_POST["container_no"];
  $invoice_no      = $_POST["invoice_no"];
  $invoice_date    = $_POST["invoice_date"];
  $account_name    = $_POST["account_name"];
  $account_number  = $_POST["account_number"];
  $bank_name       = $_POST["bank_name"];
  $user_id         = $_SESSION["user_id"];

  // 🔎 Cek apakah container_id sudah ada di invoice_info
  $check = $conn->prepare("SELECT id FROM invoice_info WHERE container_id = ?");
  $check->bind_param("i", $container_id);
  $check->execute();
  $result = $check->get_result();

  if ($result->num_rows > 0) {
    // Jika sudah ada, tolak input
    $error = "❌ Invoice untuk kontainer ini sudah dibuat, tidak bisa input ganda.";
  } else {
    // Jika belum ada, simpan data
    $stmt = $conn->prepare("
      INSERT INTO invoice_info 
      (container_id, invoice_from, invoice_to, address, containers, container_no, invoice_no, invoice_date, account_name, account_number, bank_name) 
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param(
      "issssssssss",
      $container_id,
      $invoice_from,
      $invoice_to,
      $address,
      $containers,
      $container_no,
      $invoice_no,
      $invoice_date,
      $account_name,
      $account_number,
      $bank_name
    );

    if ($stmt->execute()) {
      header("Location: invoice-pdf.php?invoice_id=" . $stmt->insert_id);
      exit();
    } else {
      $error = "❌ Gagal menyimpan data invoice.";
    }
  }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Invoice - Fayyfir</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen">

  <header class="bg-gray-900 text-white py-4 px-6 fixed top-0 left-0 right-0 z-40">
    <div class="flex justify-between items-center">
      <a href="riwayat-kontainer.php?id=<?= $container_id ?>" class="flex items-center space-x-1 text-yellow-400 hover:underline text-sm">
        <span class="material-symbols-outlined text-base">chevron_left</span>
        <span class="hidden lg:inline">Kembali</span>
      </a>
      <h1 class="text-lg font-semibold">Informasi Invoice</h1>
    </div>
  </header>

  <main class="pt-24 px-4 pb-32 max-w-2xl mx-auto">
    <div class="bg-white p-6 rounded shadow space-y-6">
      <?php if (isset($error)): ?>
        <div class="p-3 bg-red-100 text-red-700 border border-red-300 rounded"><?= $error ?></div>
      <?php endif; ?>

      <form method="POST" class="space-y-4">
        <div>
          <label class="block text-sm font-medium mb-1">Invoice From</label>
          <input type="text" name="invoice_from" placeholder="Invoice dari..."
                 class="w-full px-3 py-2 border border-gray-300 rounded" />
        </div>
        
        <div>
          <label class="block text-sm font-medium mb-1">Invoice To</label>
          <input type="text" name="invoice_to" placeholder="Invoice untuk..."
                 class="w-full px-3 py-2 border border-gray-300 rounded" />
        </div>

        <div>
          <label class="block text-sm font-medium mb-1">Address</label>
          <input type="text" name="address" placeholder="Alamat..."
                    class="w-full px-3 py-2 border border-gray-300 rounded">
        </div>
        
        <div>
          <label class="block text-sm font-medium mb-1">Container</label>
          <input type="text" name="containers" placeholder="Kontainer..."
                 class="w-full px-3 py-2 border border-gray-300 rounded" />
        </div>
        
        <div>
          <label class="block text-sm font-medium mb-1">Container No.</label>
          <input type="text" name="container_no" placeholder="Nomor kontainer..."
                 class="w-full px-3 py-2 border border-gray-300 rounded" />
        </div>
        
        <div>
          <label class="block text-sm font-medium mb-1">Invoice No.</label>
          <input type="text" name="invoice_no" placeholder="Nomor Invoice..."
                 class="w-full px-3 py-2 border border-gray-300 rounded" />
        </div>

        <div>
          <label class="block text-sm font-medium mb-1">Invoice Date</label>
          <input type="date" name="invoice_date"
                 value="<?= date('Y-m-d') ?>"
                 class="w-full px-3 py-2 border border-gray-300 rounded" />
        </div>

        <hr class="my-4 border-t">

        <div>
          <label class="block text-sm font-medium mb-1">Account Name (Nama Rekening)</label>
          <input type="text" name="account_name" placeholder="A.n. PT Fayyfir Sejahtera"
                 class="w-full px-3 py-2 border border-gray-300 rounded" />
        </div>

        <div>
          <label class="block text-sm font-medium mb-1">Account Number (Nomor Rekening)</label>
          <input type="text" name="account_number" placeholder="Contoh: 1234567890"
                 class="w-full px-3 py-2 border border-gray-300 rounded" />
        </div>

        <div>
          <label class="block text-sm font-medium mb-1">Bank Name (Nama Bank)</label>
          <input type="text" name="bank_name" placeholder="Contoh: BCA / Mandiri"
                 class="w-full px-3 py-2 border border-gray-300 rounded" />
        </div>

        <div class="flex justify-end">
          <button type="submit" class="bg-yellow-500 hover:bg-yellow-600 text-white font-semibold px-6 py-2 rounded">
            Lanjut Cetak Invoice
          </button>
        </div>
      </form>
    </div>
  </main>

</body>
</html>