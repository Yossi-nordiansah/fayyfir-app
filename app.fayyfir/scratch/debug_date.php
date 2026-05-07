<?php
echo "Current Time: " . date('Y-m-d H:i:s') . "\n";
echo "Monday this week: " . date('Y-m-d', strtotime('monday this week')) . "\n";
echo "-14 days: " . date('Y-m-d', strtotime('-14 days')) . "\n";
echo "Bulan ini: " . date('Y-m-01') . "\n";
?>
