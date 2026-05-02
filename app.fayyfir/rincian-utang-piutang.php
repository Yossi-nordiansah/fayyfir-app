<?php
session_start();
require "config.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login");
    exit();
}

// Ambil parameter user_id dari URL
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

// 1️⃣ Cek langsung di tabel user_cash_flows
$stmtUser = $conn->prepare("SELECT * FROM user_cash_flows WHERE id = ?");
$stmtUser->bind_param("i", $user_id);
$stmtUser->execute();
$resultUser = $stmtUser->get_result();
$user = $resultUser->fetch_assoc();

// 2️⃣ Kalau tidak ketemu, coba cek apakah parameter itu ID dari cash_flows
if (!$user && $user_id > 0) {
    $stmtCF = $conn->prepare("SELECT u.* 
                              FROM cash_flows c
                              JOIN user_cash_flows u ON c.user_id = u.id
                              WHERE c.id = ?");
    $stmtCF->bind_param("i", $user_id);
    $stmtCF->execute();
    $resultCF = $stmtCF->get_result();
    $user = $resultCF->fetch_assoc();

    // Kalau ketemu, ganti $user_id ke ID yang benar
    if ($user) {
        $user_id = $user['id'];
    }
}

// 3️⃣ Kalau tetap tidak ketemu, tampilkan error
if (!$user) {
    echo "User tidak ditemukan.";
    exit();
}

// Ambil transaksi kas berdasarkan user_id
$kas = [];
$stmtKas = $conn->prepare("SELECT * FROM cash_flows WHERE user_id = ? ORDER BY date ASC, id ASC");
$stmtKas->bind_param("i", $user_id);
$stmtKas->execute();
$resultKas = $stmtKas->get_result();

$saldo = 0;
$totalDebit = 0;
$totalCredit = 0;

while ($row = $resultKas->fetch_assoc()) {
    $debit = (int)$row['debit'];
    $credit = (int)$row['credit'];
    $saldo += $debit - $credit;

    $kas[] = [
        "id" => $row["id"],
        "date" => $row["date"],
        "description" => $row["description"],
        "debit" => $debit,
        "credit" => $credit,
        "saldo" => $saldo
    ];

    $totalDebit += $debit;
    $totalCredit += $credit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Rincian Utang Piutang - Fayyfir</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen">
  <header class="bg-gray-900 text-white py-4 px-6 fixed top-0 left-0 right-0 z-40">
    <div class="flex justify-between items-center">
      <a href="utang-piutang" class="flex items-center space-x-1 text-yellow-400 hover:underline text-sm">
        <span class="material-symbols-outlined text-base">chevron_left</span>
        <span class="hidden lg:inline">Kembali</span>
      </a>
      <h1 class="text-lg font-semibold">Rincian Utang</h1>
    </div>
  </header>

  <main class="pt-20 px-4 pb-32 max-w-6xl mx-auto space-y-6">
    
    <!-- Ringkasan -->
    <div class="bg-white p-4 rounded-lg shadow">
      <h2 class="text-md font-semibold mb-2">Ringkasan Kas</h2>
      <table class="min-w-full divide-y divide-gray-200 text-sm">
        <tbody class="text-gray-800 divide-y divide-gray-200">
          <tr>
            <td class="pr-4 py-2 font-semibold">Nama</td>
            <td>:</td>
            <td class="pl-2"><?= htmlspecialchars($user['name']) ?></td>
          </tr>
          <tr>
            <td class="pr-4 py-2 font-semibold">Nomor HP</td>
            <td>:</td>
            <td class="pl-2"><?= htmlspecialchars($user['phone']) ?></td>
          </tr>
          <tr>
            <td class="pr-4 py-2 font-semibold">Alamat</td>
            <td>:</td>
            <td class="pl-2"><?= htmlspecialchars($user['address']) ?></td>
          </tr>
          <tr>
            <td class="pr-4 py-2 font-semibold">Sisa Saldo</td>
            <td>:</td>
            <td class="pl-2 font-semibold text-green-700">Rp <?= number_format($saldo, 0, ",", ".") ?></td>
          </tr>
        </tbody>
      </table>
      <div class="mt-6 flex justify-end space-x-3">
        <a href="edit-nama.php?id=<?= $user_id ?>" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 text-sm">Edit</a>
        <form method="POST" action="hapus-nama.php" onsubmit="return confirm('Yakin ingin menghapus data ini?')">
          <input type="hidden" name="id" value="<?= $user_id ?>"/>
          <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 text-sm">Hapus</button>
        </form>
      </div>
    </div>
    
    <!-- Tombol aksi -->
    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-4">
      <a href="pemasukan.php?user_id=<?= $user_id ?>" class="group flex items-center bg-gray-800 hover:bg-yellow-400 text-white px-4 py-2 rounded text-sm transition">
        <span class="material-symbols-outlined text-sm text-yellow-400 group-hover:text-gray-800">add_circle</span>
        <span class="ml-2">Credit</span>
      </a>
      <a href="pengeluaran.php?user_id=<?= $user_id ?>" class="group flex items-center bg-gray-800 hover:bg-yellow-400 text-white px-4 py-2 rounded text-sm transition">
        <span class="material-symbols-outlined text-sm text-yellow-400 group-hover:text-gray-800">do_not_disturb_on</span>
        <span class="ml-2">Debit</span>
      </a>
      <a href="rincian-kas-pdf.php?user_id=<?= $user_id ?>" class="group flex items-center bg-gray-800 hover:bg-yellow-400 text-white px-4 py-2 rounded text-sm transition">
        <span class="material-symbols-outlined text-sm text-yellow-400 group-hover:text-gray-800">picture_as_pdf</span>
        <span class="ml-2">Export PDF</span>
      </a>
    </div>

    <!-- Tabel transaksi -->
    <div class="overflow-auto bg-white shadow rounded-lg">
      <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-100 text-gray-600">
          <tr>
            <th class="px-4 py-2 text-center">Tanggal</th>
            <th class="px-4 py-2 text-center">Deskripsi</th>
            <th class="px-4 py-2 text-center">Credit</th>
            <th class="px-4 py-2 text-center">Debit</th>
            <th class="px-4 py-2 text-center">Sisa Saldo</th>
            <th class="px-4 py-2 text-center">Aksi</th>
          </tr>
        </thead>
        <tbody class="text-gray-800 divide-y divide-gray-200">
          <?php if (empty($kas)): ?>
            <tr>
              <td colspan="7" class="px-4 py-2 text-center text-gray-500">Belum ada transaksi kas.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($kas as $row): ?>
              <tr>
                <td class="px-4 py-2 text-center"><?= date("d/m/Y", strtotime($row['date'])) ?></td>
                <td class="px-4 py-2 text-left"><?= htmlspecialchars($row['description']) ?></td>
                <td class="px-4 py-2 text-right"><?= $row['debit'] ? number_format($row['debit'], 0, ",", ".") : "-" ?></td>
                <td class="px-4 py-2 text-right"><?= $row['credit'] ? number_format($row['credit'], 0, ",", ".") : "-" ?></td>
                <td class="px-4 py-2 text-right font-semibold"><?= number_format($row['saldo'], 0, ",", ".") ?></td>
                <td class="flex justify-center space-x-3 px-4 py-4 text-center">
                  <a href="edit-saldo.php?id=<?= $row['id'] ?>&user_id=<?= $user_id ?>" class="text-blue-500 hover:text-blue-600 text-sm">
                    <span class="material-symbols-outlined text-base">edit</span>
                  </a>
                  <form method="POST" action="hapus-saldo.php" onsubmit="return confirm('Yakin ingin menghapus data ini?')">
                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                    <input type="hidden" name="user_id" value="<?= $user_id ?>">
                    <button type="submit" class="text-red-500 hover:text-red-600 text-sm">
                      <span class="material-symbols-outlined text-base">delete</span>
                    </button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
            <tr class="bg-gray-100 font-semibold">
              <td colspan="2" class="px-4 py-2 text-right">Total</td>
              <td class="px-4 py-2 text-right"><?= number_format($totalDebit, 0, ",", ".") ?></td>
              <td class="px-4 py-2 text-right"><?= number_format($totalCredit, 0, ",", ".") ?></td>
              <td class="px-4 py-2 text-right"><?= number_format($saldo, 0, ",", ".") ?></td>
              <td></td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </main>
</body>
</html>