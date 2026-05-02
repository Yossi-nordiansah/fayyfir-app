<?php
require "../../config.php";
$conn = $conn2;

$result = $conn->query("SELECT * FROM bb_proses_master");
if ($result) {
    echo "Table bb_proses_master contents:\n";
    while ($row = $result->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "Error querying bb_proses_master: " . $conn->error;
}
?>
