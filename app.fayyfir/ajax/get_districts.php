<?php
// ajax/get_districts.php
require '../config.php';
if (isset($_GET['regency_id'])) {
  $regency_id = $_GET['regency_id'];
  $query = $conn->prepare("SELECT id, name FROM reg_districts WHERE regency_id = ? ORDER BY name");
  $query->bind_param("s", $regency_id);
  $query->execute();
  $result = $query->get_result();
  echo "<option value=''>-- Pilih Kecamatan --</option>";
  while ($row = $result->fetch_assoc()) {
    echo "<option value='{$row['id']}'>{$row['name']}</option>";
  }
}
?>