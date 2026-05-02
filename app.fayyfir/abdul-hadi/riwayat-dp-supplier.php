<?php
session_start();
require "config.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

// Ambil data supplier dan total debit/credit dari deposits_supplier
$sql = "
  SELECT s.id, s.name, s.phone, s.address, s.notes,
         p.name AS province_name, r.name AS regency_name, d.name AS district_name, v.name AS village_name,
         COALESCE(SUM(ds.debit), 0) AS total_debit,
         COALESCE(SUM(ds.credit), 0) AS total_credit
  FROM suppliers s
  LEFT JOIN reg_provinces p ON s.province_id = p.id
  LEFT JOIN reg_regencies r ON s.regency_id = r.id
  LEFT JOIN reg_districts d ON s.district_id = d.id
  LEFT JOIN reg_villages v ON s.village_id = v.id
  LEFT JOIN deposits_supplier ds ON s.id = ds.supplier_id
  GROUP BY s.id
  ORDER BY s.name ASC
";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Riwayat DP Supplier</title>
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
      <h1 class="text-lg font-semibold">Riwayat DP Supplier</h1>
    </div>
  </header>

  <main class="pt-20 px-4 pb-32 max-w-6xl mx-auto space-y-6">
    <div class="flex flex-col sm:flex-row sm:justify-between gap-4 items-end">
      <input type="text" id="searchPetani" placeholder="Cari nama petani/suplier..." class="w-full sm:max-w-xs px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring focus:ring-yellow-300" />
    </div>

    <div class="overflow-auto bg-white shadow rounded-lg">
      <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-100 text-gray-600">
          <tr>
            <th class="px-4 py-2 text-center">Nama</th>
            <th class="px-4 py-2 text-center">Alamat</th>
            <th class="px-4 py-2 text-center">Nomor HP</th>
            <th class="px-4 py-2 text-center">Sisa DP</th>
            <th class="px-4 py-2 text-center">Aksi</th>
          </tr>
        </thead>
        <tbody class="text-gray-800 divide-y divide-gray-200">
          <?php
          $total_all = 0;
          while ($row = $result->fetch_assoc()):
            $supplier_id = $row["id"];

            // Hitung total transaksi pembelian dari tabel transactions
            $queryTotalTrans = $conn->query("SELECT SUM(total_price) AS total_trans FROM transactions WHERE supplier_id = '$supplier_id'");
            $rowTrans = $queryTotalTrans->fetch_assoc();
            $total_trans = $rowTrans['total_trans'] ?? 0;

            // Hitung Sisa DP: Total DP - Total Transaksi
            $sisa_dp = $row["total_debit"] - $row["total_credit"] - $total_trans;
            $total_all += $sisa_dp;

            $alamat = $row["address"] . ", " . $row["village_name"] . ", " . $row["district_name"] . ", " . $row["regency_name"] . ", " . $row["province_name"];
          ?>
          <tr>
            <td class="px-4 py-2"><?= htmlspecialchars($row["name"]) ?></td>
            <td class="px-4 py-2"><?= htmlspecialchars($alamat) ?></td>
            <td class="px-4 py-2"><?= htmlspecialchars($row["phone"]) ?></td>
            <td class="px-4 py-2 text-right"><?= number_format($sisa_dp, 0, ',', '.') ?></td>
            <td class="px-4 py-2 text-center">
              <a href="rincian-dp-supplier?id=<?= $row["id"] ?>" class="text-blue-600 hover:text-blue-800">
                <span class="material-symbols-outlined text-base">visibility</span>
              </a>
            </td>
          </tr>
          <?php endwhile; ?>
          <tr>
            <td colspan="3" class="px-4 py-2 text-right font-semibold">TOTAL DP</td>
            <td class="px-4 py-2 text-right font-semibold"><?= number_format($total_all, 0, ',', '.') ?></td>
            <td class="px-4 py-2"></td>
          </tr>
        </tbody>
      </table>
    </div>
  </main>

  <script>
    // Aktifkan pencarian
    document.getElementById("searchPetani").addEventListener("input", function () {
      const keyword = this.value.toLowerCase();
      const rows = document.querySelectorAll("tbody tr");

      rows.forEach((row) => {
        const namaCell = row.querySelector("td:nth-child(1)");
        if (!namaCell) return;

        const nama = namaCell.textContent.toLowerCase();

        // Jangan filter baris total
        const isTotalRow = row.querySelector("td[colspan]");
        if (isTotalRow) return;

        row.style.display = nama.includes(keyword) ? "" : "none";
      });
    });
  </script>
</body>
</html>