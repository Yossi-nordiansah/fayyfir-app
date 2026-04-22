<?php  
session_start();  
require "../../../config.php";  
$conn = $conn2;  
  
// Cek login  
if (!isset($_SESSION["user_id"])) {  
  header("Location: ../../../login");  
  exit();  
}  
  
// Pastikan ada ID kupas  
if (!isset($_GET["id"])) {  
  header("Location: list-kupas");  
  exit();  
}  
  
$id_kupas = intval($_GET["id"]);  
  
// Ambil data kupas + batch  
$query = "  
  SELECT pk.id_pembelian, pa.kode_batch, pk.berat_setelah_kupas  
  FROM bb_proses_kupas pk  
  JOIN bb_pembelian_awal pa ON pk.id_pembelian = pa.id
  WHERE pk.id = ?  
";  
$stmt = $conn->prepare($query);  
$stmt->bind_param("i", $id_kupas);  
$stmt->execute();  
$res = $stmt->get_result();  
  
if ($res->num_rows === 0) {  
  echo "<script>alert('Data kupas tidak ditemukan!'); window.location='list-kupas';</script>";  
  exit();  
}  
  
$data = $res->fetch_assoc();  
$id_pembelian = $data["id_pembelian"];  
  
// Pastikan proses kupas sudah selesai (berat setelah jemur atau kupas harus ada)  
if ($data["berat_setelah_kupas"] === null) {  
  echo "<script>alert('Proses kupas belum selesai! Lengkapi dulu datanya.'); window.location='list-kupas';</script>";  
  exit();  
}  
  
// Pastikan batch ini belum masuk ke proses sortir  
$cek = $conn->prepare("SELECT id FROM bb_proses_sortir WHERE id_pembelian = ?");  
$cek->bind_param("i", $id_pembelian);  
$cek->execute();  
$cek_res = $cek->get_result();  
if ($cek_res->num_rows > 0) {  
  echo "<script>alert('Batch ini sudah pernah dipindahkan ke tahap sortir.'); window.location='list-kupas';</script>";  
  exit();  
}  
  
// Update status pembelian ke 'sortir'  
$upd = $conn->prepare("UPDATE bb_pembelian_awal SET status = 'sortir' WHERE id = ?");  
$upd->bind_param("i", $id_pembelian);  
$upd->execute();  
  
// Buat entri baru di tabel proses sortir  
$ins = $conn->prepare("  
  INSERT INTO bb_proses_sortir (id_pembelian, tanggal_simpan, berat_akhir, penyusutan_total, lokasi_simpan, catatan)  
  VALUES (?, CURDATE(), NULL, NULL, NULL, 'Proses sortir dimulai')  
");  
$ins->bind_param("i", $id_pembelian);  
$ins->execute();  
  
// Redirect ke daftar sortir  
echo "<script>  
  alert('Batch berhasil dipindahkan ke tahap sortir!');  
  window.location='../sortir-simpan/list-sortir';  
</script>";  
?>