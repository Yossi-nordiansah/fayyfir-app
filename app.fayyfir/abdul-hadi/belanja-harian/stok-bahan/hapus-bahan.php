<?php
if (isset($_GET['id'])) {
    header("Location: ../data-bahan/hapus-bahan.php?id=" . $_GET['id'] . "&ref=stok-bahan");
    exit();
}
header("Location: index.php");
exit();
