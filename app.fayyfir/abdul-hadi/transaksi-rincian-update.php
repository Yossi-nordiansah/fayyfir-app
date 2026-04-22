<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login");
    exit();
}

require "config.php";

/* ==========================
   Validasi Request
========================== */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: transaksi-produk");
    exit();
}

$invoice = $_POST['invoice'] ?? null;
$item_ids = $_POST['item_id'] ?? [];
$product_ids = $_POST['product_id'] ?? [];
$qtys = $_POST['qty'] ?? [];
$prices = $_POST['price'] ?? [];
$dp = $_POST['dp'] ?? 0;

$deleted_items = $_POST['deleted_items'] ?? '';

if (!$invoice) {
    die("Data tidak valid.");
}

/* ==========================
   Helper
========================== */

function num($v){
    return floatval($v);
}

/* ==========================
   Start Transaction
========================== */

$conn->begin_transaction();

try {

    /* ==========================
       Ambil data lama
    ========================== */

    $stmt_old = $conn->prepare("
        SELECT id, product_id, qty
        FROM selling_products
        WHERE invoice_number = ?
        FOR UPDATE
    ");

    $stmt_old->bind_param("s",$invoice);
    $stmt_old->execute();
    $res_old = $stmt_old->get_result();

    $old_items = [];

    while($r = $res_old->fetch_assoc()){
        $old_items[$r['id']] = $r;
    }

    $stmt_old->close();


    /* ==========================
       HAPUS ITEM
    ========================== */

    if(!empty($deleted_items)){

        $ids = explode(",", $deleted_items);

        foreach($ids as $del_id){

            $del_id = intval($del_id);

            if(isset($old_items[$del_id])){

                $old = $old_items[$del_id];

                $product_id = $old['product_id'];
                $qty_return = num($old['qty']);

                /* kembalikan stok */
                $stmt_return = $conn->prepare("
                    UPDATE product_stocks
                    SET quantity = quantity + ?
                    WHERE id = ?
                ");
                $stmt_return->bind_param("di",$qty_return,$product_id);
                $stmt_return->execute();
                $stmt_return->close();

                /* hapus item */
                $stmt_delete = $conn->prepare("
                    DELETE FROM selling_products
                    WHERE id = ?
                ");
                $stmt_delete->bind_param("i",$del_id);
                $stmt_delete->execute();
                $stmt_delete->close();

                unset($old_items[$del_id]);
            }
        }
    }


    /* ==========================
       UPDATE / INSERT ITEM
    ========================== */

    $total_selling = 0;

    foreach ($item_ids as $i => $item_id) {

        $qty = num($qtys[$i]);
        $price = num($prices[$i]);

        if ($qty <= 0) continue;

        $subtotal = ($qty * $price) / 1000;

        /* ======================
           ITEM BARU
        ====================== */

        if ($item_id === "new") {

            $product_id = intval($product_ids[$i]);

            if(!$product_id){
                throw new Exception("Produk belum dipilih.");
            }

            /* cek stok */
            $stmt_stock = $conn->prepare("
                SELECT quantity
                FROM product_stocks
                WHERE id = ?
                FOR UPDATE
            ");
            $stmt_stock->bind_param("i",$product_id);
            $stmt_stock->execute();
            $stock = $stmt_stock->get_result()->fetch_assoc();
            $stmt_stock->close();

            if(!$stock){
                throw new Exception("Produk tidak ditemukan.");
            }

            if($stock['quantity'] < $qty){
                throw new Exception("Stok tidak mencukupi.");
            }

            /* kurangi stok */
            $stmt_update_stock = $conn->prepare("
                UPDATE product_stocks
                SET quantity = quantity - ?
                WHERE id = ?
            ");
            $stmt_update_stock->bind_param("di",$qty,$product_id);
            $stmt_update_stock->execute();
            $stmt_update_stock->close();

            /* insert item */
            $stmt_insert = $conn->prepare("
                INSERT INTO selling_products
                (selling_date,invoice_number,product_id,buyer_id,qty,price,total_selling,dp,status)
                VALUES (NOW(),?,?,?,?,?,?,0,'Lunas')
            ");

            $buyer_id = $_POST['buyer_id'];

            $stmt_insert->bind_param(
                "siiddd",
                $invoice,
                $product_id,
                $buyer_id,
                $qty,
                $price,
                $subtotal
            );

            $stmt_insert->execute();
            $stmt_insert->close();
        }

        /* ======================
           ITEM LAMA
        ====================== */

        else {

            $item_id = intval($item_id);

            if (!isset($old_items[$item_id])) {
                continue;
            }

            $old = $old_items[$item_id];

            $old_qty = num($old['qty']);
            $product_id = intval($old['product_id']);

            $diff = $qty - $old_qty;

            /* tambah qty */
            if ($diff > 0) {

                $stmt_stock = $conn->prepare("
                    SELECT quantity
                    FROM product_stocks
                    WHERE id = ?
                    FOR UPDATE
                ");
                $stmt_stock->bind_param("i",$product_id);
                $stmt_stock->execute();
                $stock = $stmt_stock->get_result()->fetch_assoc();
                $stmt_stock->close();

                if ($stock['quantity'] < $diff) {
                    throw new Exception("Stok tidak mencukupi.");
                }

                $stmt_reduce = $conn->prepare("
                    UPDATE product_stocks
                    SET quantity = quantity - ?
                    WHERE id = ?
                ");
                $stmt_reduce->bind_param("di",$diff,$product_id);
                $stmt_reduce->execute();
                $stmt_reduce->close();
            }

            /* kurangi qty */
            if ($diff < 0) {

                $return_qty = abs($diff);

                $stmt_return = $conn->prepare("
                    UPDATE product_stocks
                    SET quantity = quantity + ?
                    WHERE id = ?
                ");
                $stmt_return->bind_param("di",$return_qty,$product_id);
                $stmt_return->execute();
                $stmt_return->close();
            }

            /* update item */
            $stmt_update = $conn->prepare("
                UPDATE selling_products
                SET qty=?, price=?, total_selling=?
                WHERE id=?
            ");

            $stmt_update->bind_param(
                "dddi",
                $qty,
                $price,
                $subtotal,
                $item_id
            );

            $stmt_update->execute();
            $stmt_update->close();
        }

        $total_selling += $subtotal;
    }


    /* ==========================
       Update DP & Status
    ========================== */

    $dp = num($dp);

    // 🔥 LOGIKA FINAL SESUAI REQUIREMENT
    $status = ($dp > 0) ? "DP" : "Lunas";

    $stmt_dp = $conn->prepare("
        UPDATE selling_products
        SET dp=?, status=?
        WHERE invoice_number=?
    ");

    $stmt_dp->bind_param("dss",$dp,$status,$invoice);
    $stmt_dp->execute();
    $stmt_dp->close();


    /* ==========================
       Commit
    ========================== */

    $conn->commit();

    header("Location: transaksi-rincian.php?invoice=" . urlencode($invoice));
    exit();

} catch (Exception $e) {

    $conn->rollback();

    echo "<h2>Terjadi kesalahan</h2>";
    echo "<p>".$e->getMessage()."</p>";
}