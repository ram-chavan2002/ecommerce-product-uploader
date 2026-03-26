<?php
require_once 'config/db.php';
$page    = 'add';
$errors  = [];
$product = ['name'=>'','sku'=>'','price'=>'','mrp'=>'','stock'=>'','category'=>'Other','description'=>'','on_flipkart'=>0];
$edit_id = 0;

if (!empty($_GET['id'])) {
    $edit_id = (int)$_GET['id'];
    $res = mysqli_query($conn, "SELECT * FROM products WHERE id=$edit_id");
    if ($row = mysqli_fetch_assoc($res)) { $product = $row; $page='products'; }
    else { header('Location: products.php'); exit; }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = clean($conn, $_POST['name']        ?? '');
    $sku         = clean($conn, $_POST['sku']         ?? '');
    $price       = floatval($_POST['price']  ?? 0);
    $mrp         = floatval($_POST['mrp']    ?? 0);
    $stock       = intval($_POST['stock']    ?? 0);
    $category    = clean($conn, $_POST['category']    ?? 'Other');
    $description = clean($conn, $_POST['description'] ?? '');
    $on_flipkart = isset($_POST['on_flipkart']) ? 1 : 0;

    if (empty($name))  $errors[] = 'Enter Product name.';
    if ($price <= 0)   $errors[] = 'Enter valid price.';
    if ($stock < 0)    $errors[] = 'Stock should be 0 or more.';

    if (!empty($sku)) {
        $chk = mysqli_query($conn, "SELECT id FROM products WHERE sku='$sku' AND id!=$edit_id");
        if (mysqli_num_rows($chk) > 0) $errors[] = "SKU '$sku' is already used.";
    }

    if (empty($errors)) {
        if ($edit_id > 0) {
            mysqli_query($conn, "UPDATE products SET
                name='$name', sku='$sku', price=$price, mrp=$mrp, stock=$stock,
                category='$category', description='$description',
                on_flipkart=$on_flipkart
                WHERE id=$edit_id");
            header('Location: products.php?msg=updated'); exit;
        } else {
            mysqli_query($conn, "INSERT INTO products
                (name, sku, price, mrp, stock, category, description, on_flipkart)
                VALUES
                ('$name','$sku',$price,$mrp,$stock,'$category','$description',$on_flipkart)");
            header('Location: products.php?msg=added'); exit;
        }
    }
    $product = compact('name','sku','price','mrp','stock','category','description','on_flipkart');
}
$cats = ['Clothing','Electronics','Home','Books','Sports','Beauty','Toys','Food','Other'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $edit_id?'Edit':'Add' ?> Product</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="wrapper">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main">
        <div class="topbar">
            <h1><?= $edit_id?'✏️ Product Edit':'➕ Product Add' ?></h1>
            <a href="products.php" class="btn btn-outline btn-sm">← Back</a>
        </div>
        <div class="content">
            <?php foreach($errors as $e): ?>
                <div class="alert alert-error">❌ <?= htmlspecialchars($e) ?></div>
            <?php endforeach; ?>

            <div class="form-card">
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Product Name *</label>
                        <input type="text" name="name" class="form-control"
                               placeholder="e.g. Blue Cotton Kurti"
                               value="<?= htmlspecialchars($product['name']) ?>" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Selling Price (₹) *</label>
                            <input type="number" name="price" class="form-control"
                                   placeholder="499" step="0.01" min="1"
                                   value="<?= $product['price'] ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">MRP (₹)</label>
                            <input type="number" name="mrp" class="form-control"
                                   placeholder="699" step="0.01"
                                   value="<?= $product['mrp'] ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Stock *</label>
                            <input type="number" name="stock" class="form-control"
                                   placeholder="100" min="0"
                                   value="<?= $product['stock'] ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">SKU Code</label>
                            <input type="text" name="sku" class="form-control"
                                   placeholder="KRT-001"
                                   value="<?= htmlspecialchars($product['sku']) ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-control">
                            <?php foreach($cats as $c): ?>
                            <option value="<?= $c ?>" <?= $product['category']===$c?'selected':''?>>
                                <?= $c ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"
                                  placeholder="Write a short description about the product..."><?= htmlspecialchars($product['description']) ?></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Platform</label>
                        <div style="margin-top:8px">
                            <label class="form-check">
                                <input type="checkbox" name="on_flipkart" value="1"
                                       <?= $product['on_flipkart']?'checked':''?>>
                                🔵 List on <strong style="color:#6baef9">Flipkart</strong>
                            </label>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <?= $edit_id?'💾 Update':'➕ Add' ?>
                        </button>
                        <a href="products.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>
