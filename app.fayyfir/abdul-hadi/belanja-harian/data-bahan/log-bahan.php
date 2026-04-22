<?php
session_start();
require "../../config.php";
$conn = $conn2;

// Cek login
if (!isset($_SESSION["user_id"])) {
  header("Location: ../../login");
  exit();
}

// Ambil ID bahan dari URL
$id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;

// =============================
// Ambil data log bahan
// =============================
$query = "
SELECT
  l.id,
  l.aksi,
  l.data_lama,
  l.data_baru,
  l.created_at,
  u.username,
  b.nama_bahan
FROM bb_bahan_log l
LEFT JOIN users u ON u.id = l.user_id
LEFT JOIN bb_bahan_master b ON b.id = l.bahan_id
ORDER BY l.created_at DESC
LIMIT 200
";

$result = $conn->query($query);

$activeMenu = "materials";
$activeModule = "Log Perubahan Bahan";

include "../partials/header.php";
include "../partials/sidebar.php";
include "../partials/navbar.php";
?><main class="lg:ml-64 bg-gray-50 min-h-screen px-4 py-6 sm:px-6 lg:px-8"><div class="flex flex-col sm:flex-row justify-between sm:items-center mb-8"><a href="detail-bahan?id=<?= $id ?>"
class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-800 transition text-sm font-medium"> <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"> <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/> </svg> Kembali ke detail bahan </a>

<h1 class="mt-4 sm:mt-0 text-2xl font-semibold text-gray-900 tracking-tight">
Log Perubahan Bahan
</h1></div><div class="bg-white rounded-2xl shadow-md border border-gray-100 overflow-hidden"><div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200 text-sm"><thead class="bg-gray-50">
<tr>
<th class="px-6 py-3 text-left font-semibold text-gray-600">Tanggal</th>
<th class="px-6 py-3 text-left font-semibold text-gray-600">User</th>
<th class="px-6 py-3 text-left font-semibold text-gray-600">Bahan</th>
<th class="px-6 py-3 text-left font-semibold text-gray-600">Aksi</th>
<th class="px-6 py-3 text-left font-semibold text-gray-600">Perubahan</th>
</tr>
</thead><tbody class="divide-y divide-gray-100"><?php if ($result && $result->num_rows > 0): ?><?php while ($row = $result->fetch_assoc()): ?><?php
$data_lama = $row['data_lama'] ? json_decode($row['data_lama'], true) : [];
$data_baru = $row['data_baru'] ? json_decode($row['data_baru'], true) : [];
?><tr class="hover:bg-gray-50"><td class="px-6 py-4 text-gray-600 whitespace-nowrap">
<?= date("d M Y H:i", strtotime($row['created_at'])) ?>
</td><td class="px-6 py-4 text-gray-700">
<?= htmlspecialchars($row['username'] ?? 'Unknown') ?>
</td><td class="px-6 py-4 font-medium text-gray-800">
<?= htmlspecialchars($row['nama_bahan'] ?? '-') ?>
</td><td class="px-6 py-4">
<?php if ($row['aksi'] === 'create'): ?>
<span class="px-2 py-1 text-xs rounded-lg bg-green-100 text-green-700">Create</span>
<?php elseif ($row['aksi'] === 'update'): ?>
<span class="px-2 py-1 text-xs rounded-lg bg-yellow-100 text-yellow-700">Update</span>
<?php elseif ($row['aksi'] === 'delete'): ?>
<span class="px-2 py-1 text-xs rounded-lg bg-red-100 text-red-700">Delete</span>
<?php else: ?>
<span class="px-2 py-1 text-xs rounded-lg bg-gray-100 text-gray-700">
<?= htmlspecialchars($row['aksi']) ?>
</span>
<?php endif; ?>
</td><td class="px-6 py-4 text-gray-600"><?php if ($row['aksi'] === 'update'): ?><div class="space-y-1">
<?php foreach ($data_baru as $key => $value): ?><?php
$lama = $data_lama[$key] ?? '';
$baru = $value ?? '';

if ($lama !== $baru):
?><div>
<span class="font-medium text-gray-700"><?= htmlspecialchars($key) ?></span>
:
<span class="text-red-500"><?= htmlspecialchars($lama) ?></span>
→
<span class="text-green-600"><?= htmlspecialchars($baru) ?></span>
</div><?php endif; ?><?php endforeach; ?></div><?php elseif ($row['aksi'] === 'create'): ?><div class="text-green-600">
Bahan dibuat
</div><?php elseif ($row['aksi'] === 'delete'): ?><div class="text-red-600">
Bahan dihapus (soft delete)
</div><?php endif; ?></td></tr><?php endwhile; ?><?php else: ?><tr>
<td colspan="5" class="px-6 py-10 text-center text-gray-500">
Belum ada riwayat perubahan bahan.
</td>
</tr><?php endif; ?></tbody></table></div></div></main><?php include "../partials/footer.php"; ?>