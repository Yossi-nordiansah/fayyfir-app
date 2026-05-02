<?php
session_start();
if (!isset($_SESSION["user_id"])) {
  header("Location: login");
  exit();
}

require "config.php";

/* =========================
   PROSES UPDATE TANGGAL
========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

  if (isset($_POST['update_tanggal'])) {

    $id = intval($_POST['container_id']);
    $tanggal = trim($_POST['tanggal']);
    $field = $_POST['field'] ?? '';

    $allowed_fields = ['verified_at','accepted_at','lunas_at'];

    if ($id && $tanggal !== '' && in_array($field, $allowed_fields)) {

      $query = "UPDATE containers SET $field = ? WHERE id = ?";
      $stmt = $conn->prepare($query);
      $stmt->bind_param("si", $tanggal, $id);
      $stmt->execute();
      $stmt->close();

      $_SESSION['status_pesan'] = "Tanggal berhasil diperbarui.";

      header("Location: lunas.php");
      exit();
    }
  }
}

$query = "
SELECT c.*, p.name AS product_name
FROM containers c
LEFT JOIN products p ON c.product_id = p.id
WHERE c.status = 'lunas'
ORDER BY c.number ASC
";

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="id">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Kontainer Lunas - Fayyfir</title>

<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">

</head>

<body class="bg-gray-100 text-gray-800 min-h-screen">

<header class="bg-gray-900 text-white py-4 px-6 fixed top-0 left-0 right-0 z-40">
  <div class="flex justify-between items-center">

    <a href="index" class="flex items-center space-x-1 text-yellow-400 hover:underline text-sm">
      <span class="material-symbols-outlined text-base">chevron_left</span>
      <span class="hidden lg:inline">Kembali ke Dashboard</span>
    </a>

    <h1 class="text-lg font-semibold">Kontainer Lunas</h1>

  </div>
</header>


<main class="pt-20 px-4 pb-32 max-w-6xl mx-auto space-y-6">

<?php if (isset($_SESSION["status_pesan"])): ?>

<div class="mb-4 p-3 bg-green-100 text-green-700 border border-green-300 rounded">
<?= $_SESSION["status_pesan"]; unset($_SESSION["status_pesan"]); ?>
</div>

<?php endif; ?>


<section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

<?php while ($row = $result->fetch_assoc()): ?>

<a href="riwayat-kontainer2?id=<?= $row["id"] ?>" class="bg-white rounded-lg shadow p-4">

<div class="text-gray-800 flex justify-between items-center mb-2">

<div class="flex items-center space-x-4">

<span class="material-symbols-outlined text-yellow-400 text-4xl">inventory_2</span>

<div>

<h2 class="text-sm text-gray-500">
<?= htmlspecialchars($row["container_number"]) ?>
</h2>

<p class="text-2xl font-bold text-gray-500">
<?= htmlspecialchars($row["number"]) ?>
</p>

<h2 class="text-sm text-gray-500">
Produk: <?= htmlspecialchars($row["product_name"] ?? "-") ?> |
Area: <?= htmlspecialchars($row["region_name"] ?? "-") ?>
</h2>

</div>
</div>


<div class="flex flex-col items-center">

<h2 class="text-sm text-gray-500">Status</h2>

<?php if ($row["status"] == "lunas"): ?>

<span class="text-green-500 mt-1 text-sm font-semibold">
Lunas
</span>

<?php else: ?>

<span class="text-red-500 mt-1 text-sm font-semibold">
Load
</span>

<?php endif; ?>

</div>
</div>


<div class="flex items-center justify-between gap-1">

<span class="text-sm text-gray-300">Closed</span>
<span class="text-sm text-gray-300">Diterima</span>
<span class="text-sm text-gray-300">Lunas</span>

</div>


<div class="flex items-center justify-between gap-1">

<!-- VERIFIED -->

<button
onclick="event.preventDefault();event.stopPropagation();openTanggalModal(<?= $row['id'] ?>,'verified_at','<?= htmlspecialchars($row['verified_at']) ?>')"
class="text-sm text-gray-500">

<?= !empty($row["verified_at"]) ? date("d/m/Y", strtotime($row["verified_at"])) : "-" ?>

</button>


<!-- ACCEPTED -->

<button
onclick="event.preventDefault();event.stopPropagation();openTanggalModal(<?= $row['id'] ?>,'accepted_at','<?= htmlspecialchars($row['accepted_at']) ?>')"
class="text-sm text-gray-500">

<?= !empty($row["accepted_at"]) ? date("d/m/Y", strtotime($row["accepted_at"])) : "-" ?>

</button>


<!-- LUNAS -->

<button
onclick="event.preventDefault();event.stopPropagation();openTanggalModal(<?= $row['id'] ?>,'lunas_at','<?= htmlspecialchars($row['lunas_at']) ?>')"
class="text-sm text-gray-500">

<?= !empty($row["lunas_at"]) ? date("d/m/Y", strtotime($row["lunas_at"])) : "-" ?>

</button>

</div>

</a>

<?php endwhile; ?>

</section>

</main>



<!-- ========================
     MODAL EDIT TANGGAL
======================== -->

<div id="modalTanggal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 hidden">

<div class="bg-white rounded-lg p-6 w-full max-w-sm relative">

<h2 class="text-lg font-semibold mb-4">Ubah Tanggal</h2>

<form method="POST" id="formTanggal">

<input type="hidden" name="container_id" id="modalContainerId">
<input type="hidden" name="field" id="modalField">
<input type="hidden" name="tanggal" id="modalHidden">

<label class="block text-sm font-medium mb-2">
Tanggal & Waktu
</label>

<input
type="datetime-local"
id="modalVisible"
class="w-full border px-3 py-2 rounded mb-4"
/>

<div class="flex justify-end gap-2">

<button
type="button"
onclick="closeTanggalModal()"
class="bg-gray-300 hover:bg-gray-400 px-4 py-2 rounded">
Batal
</button>

<button
type="submit"
name="update_tanggal"
class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded">
Simpan
</button>

</div>

</form>

<button
onclick="closeTanggalModal()"
class="absolute top-2 right-2 text-xl text-gray-500 hover:text-black">
&times;
</button>

</div>
</div>



<script>

/* =========================
   MYSQL -> DATETIME LOCAL
========================= */

function mysqlToDatetimeLocal(mysqlDt){

if(!mysqlDt) return '';

var parts = mysqlDt.trim().split(' ');

var date = parts[0];
var time = parts[1] || '00:00:00';

var hm = time.split(':');

return date + 'T' + hm[0] + ':' + hm[1];

}

/* =========================
   DATETIME LOCAL -> MYSQL
========================= */

function datetimeLocalToMysql(dt){

if(!dt) return null;

return dt.replace('T',' ') + ':00';

}


/* =========================
   OPEN MODAL
========================= */

function openTanggalModal(id, field, mysqlDatetime){

document.getElementById('modalContainerId').value = id;
document.getElementById('modalField').value = field;

var visible = mysqlToDatetimeLocal(mysqlDatetime);

document.getElementById('modalVisible').value = visible;
document.getElementById('modalHidden').value = datetimeLocalToMysql(visible);

document.getElementById('modalTanggal').classList.remove('hidden');

}


/* =========================
   CLOSE MODAL
========================= */

function closeTanggalModal(){

document.getElementById('modalTanggal').classList.add('hidden');

}


/* =========================
   SUBMIT FORM
========================= */

document.getElementById('formTanggal').addEventListener('submit', function(e){

var visible = document.getElementById('modalVisible').value;

if(!visible){

alert('Silakan isi tanggal terlebih dahulu');
e.preventDefault();
return false;

}

document.getElementById('modalHidden').value = datetimeLocalToMysql(visible);

});

</script>

</body>
</html>