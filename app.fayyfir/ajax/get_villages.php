<?php
// ajax/get_villages.php
require '../config.php';
if (isset($_GET['district_id'])) {
  $district_id = $_GET['district_id'];
  $query = $conn->prepare("SELECT id, name FROM reg_villages WHERE district_id = ? ORDER BY name");
  $query->bind_param("s", $district_id);
  $query->execute();
  $result = $query->get_result();
  echo "<option value=''>-- Pilih Desa --</option>";
  while ($row = $result->fetch_assoc()) {
    echo "<option value='{$row['id']}'>{$row['name']}</option>";
  }
}
?>