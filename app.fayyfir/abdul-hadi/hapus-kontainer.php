<?php
session_start();
require "config.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["id"])) {
    $id = (int) $_POST["id"];

    // Hapus data terkait di tabel expenses
    $stmt_expenses = $conn->prepare("DELETE FROM expenses WHERE container_id = ?");
    $stmt_expenses->bind_param("i", $id);
    $stmt_expenses->execute();
    $stmt_expenses->close();

    // Hapus data di tabel containers
    $stmt_container = $conn->prepare("DELETE FROM containers WHERE id = ?");
    $stmt_container->bind_param("i", $id);

    if ($stmt_container->execute()) {
        header("Location: index?deleted=1");
        exit();
    } else {
        echo "Gagal menghapus kontainer.";
    }

    $stmt_container->close();
}
?>