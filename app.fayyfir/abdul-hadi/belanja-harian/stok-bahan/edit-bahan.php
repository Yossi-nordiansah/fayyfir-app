<?php
if (isset($_GET['id'])) {
    header("Location: ../data-bahan/edit-bahan.php?id=" . $_GET['id']);
    exit();
}
header("Location: index.php");
exit();
