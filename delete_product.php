<?php
require_once 'config/db.php';
if (!empty($_GET['id'])) {
    $id = (int)$_GET['id'];
    mysqli_query($conn, "DELETE FROM products WHERE id=$id");
}
header('Location: products.php?msg=deleted');
exit;
