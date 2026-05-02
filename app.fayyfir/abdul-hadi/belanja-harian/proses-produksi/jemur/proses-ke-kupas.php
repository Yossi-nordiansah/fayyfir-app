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

// Pastikan proses jemur sudah selesai (berat setelah jemur harus ada)
if ($data["berat_setelah_jemur"] === null) {
  echo "<script>alert('Proses penyusutan belum selesai! Lengkapi dulu datanya.'); window.location='list-jemur';</script>";
  exit();
}

// Pastikan batch ini belum masuk ke proses kupas
$cek = $conn->prepare("SELECT id FROM bb_proses_kupas WHERE id_pembelian = ?");
$cek->bind_param("i", $id_pembelian);
$cek->execute();
$cek_res = $cek->get_result();
if ($cek_res->num_rows > 0) {
  echo "<script>alert('Batch ini sudah pernah dipindahkan ke tahap proses penyusutan 2.'); window.location='list-jemur';</script>";
  exit();
}

// Update status pembelian ke 'kupas'
$upd = $conn->prepare("UPDATE bb_pembelian_awal SET status = 'kupas' WHERE id = ?");
$upd->bind_param("i", $id_pembelian);
$upd->execute();

// Buat entri baru di tabel proses kupas
$ins = $conn->prepare("
  INSERT INTO bb_proses_kupas (id_pembelian, tanggal_proses, berat_setelah_kupas, penyusutan_kupas, keterangan)
  VALUES (?, CURDATE(), NULL, NULL, 'Proses kupas dimulai')
");
$ins->bind_param("i", $id_pembelian);
$ins->execute();

// Redirect ke daftar kupas
echo "<script>
  alert('Batch berhasil dipindahkan ke tahap kupas!');
  window.location='../kupas/list-kupas';
</script>";
?>