<?php
// ajax/get_regencies.php
require '../config.php';
if (isset($_GET['province_id'])) {
  $province_id = $_GET['province_id'];
  $query = $conn->prepare("SELECT id, name FROM reg_regencies WHERE province_id = ? ORDER BY name");
  $query->bind_param("s", $province_id);
  $query->execute();
  $result = $query->get_result();
  echo "<option value=''>-- Pilih Kabupaten/Kota --</option>";
  while ($row = $result->fetch_assoc()) {
    echo "<option value='{$row['id']}'>{$row['name']}</option>";
  }
}
?>