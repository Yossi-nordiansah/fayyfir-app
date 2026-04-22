<?php  
session_start();  
require "../../../config.php";  
$conn = $conn2;  
  
// Cek login  
if (!isset($_SESSION["user_id"])) {  
  header("Location: ../../../login");  
  exit();  
}  
  
// Pastikan ada ID sortir  
if (!isset($_GET["id"])) {  
  header("Location: list-sortir");  
  exit();  
}  
  
$id_sortir = intval($_GET["id"]);  
  
// Ambil data sortir + batch  
$query = "  
  SELECT ps.id_pembelian, pa.kode_batch, ps.berat_akhir
  FROM bb_proses_sortir ps  
  JOIN bb_pembelian_awal pa ON ps.id_pembelian = pa.id
  WHERE ps.id = ?  
";  
$stmt = $conn->prepare($query);  
$stmt->bind_param("i", $id_sortir);  
$stmt->execute();  
$res = $stmt->get_result();  
  
if ($res->num_rows === 0) {  
  echo "<script>alert('Data sortir tidak ditemukan!'); window.location='list-sortir';</script>";  
  exit();  
}  
  
$data = $res->fetch_assoc();  
$id_pembelian = $data["id_pembelian"];
  
// Pastikan proses sortir sudah selesai (berat setelah jemur atau kupas harus ada)  
if ($data["berat_akhir"] === null) {  
  echo "<script>alert('Proses sortir belum selesai! Lengkapi dulu datanya.'); window.location='list-sortir';</script>";  
  exit();  
}  
  
// Pastikan batch ini belum masuk ke proses jual  
$cek = $conn->prepare("SELECT id FROM bb_penjualan WHERE id_pembelian = ?");  
$cek->bind_param("i", $id_pembelian);  
$cek->execute();  
$cek_res = $cek->get_result();  
if ($cek_res->num_rows > 0) {  
  echo "<script>alert('Batch ini sudah pernah dipindahkan ke tahap penjualan.'); window.location='list-sortir';</script>";  
  exit();  
}  
  
// Update status pembelian ke 'sortir'  
$upd = $conn->prepare("UPDATE bb_pembelian_awal SET status = 'siap_jual' WHERE id = ?");  
$upd->bind_param("i", $id_pembelian);  
$upd->execute();  
  
// Buat entri baru di tabel proses penjualan  
$ins = $conn->prepare("  
  INSERT INTO bb_penjualan (id_pembelian, keterangan)  
  VALUES (?, 'Bahan siap jual.')  
");  
$ins->bind_param("i", $id_pembelian);  
$ins->execute();  
  
// Redirect ke daftar sortir  
echo "<script>  
  alert('Batch ini sudah siap jual!');  
  window.location='list-sortir';  
</script>";  
?>