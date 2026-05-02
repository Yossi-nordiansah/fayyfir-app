<?php
session_start();
require "config.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

// Pastikan ID kontainer dikirim
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = intval($_GET['id']);
$user_id = intval($_SESSION["user_id"]); // ID user dari sesi

// Ambil container_number & selling_price untuk validasi
$query = $conn->prepare("SELECT container_number FROM containers WHERE id = ?");
$query->bind_param("i", $id);
$query->execute();
$result = $query->get_result();

if ($result->num_rows === 0) {
    $_SESSION["status_pesan"] = "Kontainer tidak ditemukan.";
    header("Location: index");
    exit();
}

$row = $result->fetch_assoc();
$container_number = $row['container_number'];

// ---------------- Hitung ulang total_berat ----------------
$sql_berat = "
    SELECT SUM(weight_kg) AS total_berat
    FROM transactions
    WHERE container_id = ?
";
$stmt_berat = $conn->prepare($sql_berat);
$stmt_berat->bind_param("i", $id);
$stmt_berat->execute();
$res_berat = $stmt_berat->get_result();
$data_berat = $res_berat->fetch_assoc();

$total_berat = (float)($data_berat['total_berat'] ?? 0);
$total_timbang = $total_berat * 50; // rumus tetap

// ---------------- Cari ID expenses untuk Bayar timbang ----------------
$sql_expense = "
    SELECT id 
    FROM expenses
    WHERE container_id = ? 
      AND TRIM(LOWER(expense_type)) = 'Bayar timbang'
    LIMIT 1
";
$stmt_expense = $conn->prepare($sql_expense);
$stmt_expense->bind_param("i", $id);
$stmt_expense->execute();
$res_expense = $stmt_expense->get_result();
$expense_row = $res_expense->fetch_assoc();

$id_expense_timbang = $expense_row['id'] ?? null;

// ---------------- Update status kontainer ----------------
$sql = "UPDATE containers 
        SET status = 'full', created_by = ?, updated_at = NOW() 
        WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $id);
$stmt->execute();

// ---------------- Update nilai Bayar timbang jika ada ----------------
if ($id_expense_timbang) {
    $sql_update_exp = "UPDATE expenses SET amount = ? WHERE id = ?";
    $stmt_update_exp = $conn->prepare($sql_update_exp);
    $stmt_update_exp->bind_param("di", $total_timbang, $id_expense_timbang);
    $stmt_update_exp->execute();
}

$_SESSION["status_pesan"] = "Kontainer ($container_number) full terisi, biaya timbang diperbarui.";
header("Location: index");
exit();
?>