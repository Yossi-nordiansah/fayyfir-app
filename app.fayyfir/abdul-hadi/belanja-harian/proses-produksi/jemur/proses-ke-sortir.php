<?php
session_start();
require "../../../config.php";
$conn = $conn2;

// Cek login
if (!isset($_SESSION["user_id"])) {
  header("Location: ../../../login");
  exit();
}

// Pastikan ada ID jemur
if (!isset($_GET["id"])) {
  header("Location: list-jemur");
  exit();
}

$id_jemur = intval($_GET["id"]);

// Ambil data jemur + batch
$query = "
  SELECT pj.id_pembelian, pa.kode_batch, pj.berat_setelah_jemur
  FROM bb_proses_jemur pj
  JOIN bb_pembelian_awal pa ON pj.id_pembelian = pa.id
  WHERE pj.id = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id_jemur);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
  echo "<script>alert('Data proses tidak ditemukan!'); window.location='list-jemur';</script>";
  exit();
}

$data = $res->fetch_assoc();
$id_pembelian = $data["id_pembelian"];
$berat_jemur = $data["berat_setelah_jemur"];

// Pastikan proses jemur sudah selesai (berat setelah jemur harus ada)
if ($berat_jemur === null) {
  echo "<script>alert('Proses penyusutan belum selesai! Lengkapi dulu datanya.'); window.location='list-jemur';</script>";
  exit();
}

// Pastikan batch ini belum masuk ke proses sortir
$cek = $conn->prepare("SELECT id FROM bb_proses_sortir WHERE id_pembelian = ?");
$cek->bind_param("i", $id_pembelian);
$cek->execute();
$cek_res = $cek->get_result();
if ($cek_res->num_rows > 0) {
  echo "<script>alert('Batch ini sudah pernah dipindahkan ke tahap akhir.'); window.location='list-jemur';</script>";
  exit();
}

// Update status pembelian ke 'sortir'
$upd = $conn->prepare("UPDATE bb_pembelian_awal SET status = 'sortir' WHERE id = ?");
$upd->bind_param("i", $id_pembelian);
$upd->execute();

// Tambahkan dummy record ke tabel kupas
$insertKupas = $conn->prepare("
  INSERT INTO bb_proses_kupas (id_pembelian, berat_setelah_kupas, penyusutan_kupas, tanggal_proses, keterangan)
  VALUES (?, ?, 0.00, NOW(), 'Langsung ke proses akhir')
");
$insertKupas->bind_param("id", $id_pembelian, $berat_jemur);
$insertKupas->execute();

// Buat entri baru di tabel proses sortir
$insertSortir = $conn->prepare("
  INSERT INTO bb_proses_sortir (id_pembelian, tanggal_simpan, berat_akhir, penyusutan_total, lokasi_simpan, catatan)
  VALUES (?, CURDATE(), NULL, NULL, NULL, 'Proses akhir dimulai')
");
$insertSortir->bind_param("i", $id_pembelian);
$insertSortir->execute();

// Redirect ke daftar sortir
echo "<script>
  alert('Batch berhasil dipindahkan langsung ke tahap akhir!');
  window.location='../sortir-simpan/list-sortir';
</script>";
?>