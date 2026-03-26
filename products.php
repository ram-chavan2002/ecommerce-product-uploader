<?php
require_once 'config/db.php';
$page   = 'products';
$search = '';
$where  = '';
if (!empty($_GET['search'])) {
    $search = clean($conn, $_GET['search']);
    $where  = "WHERE name LIKE '%$search%' OR sku LIKE '%$search%'";
}
$result = mysqli_query($conn, "SELECT * FROM products $where ORDER BY created_at DESC");
$count  = mysqli_num_rows($result);
$msg    = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Products</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="wrapper">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main">
        <div class="topbar">
            <h1>📦 Products (<?= $count ?>)</h1>
            <a href="add_product.php" class="btn btn-primary">+ New Product</a>
        </div>
        <div class="content">

            <?php if($msg==='added'): ?><div class="alert alert-success">✅ Product added!</div>
            <?php elseif($msg==='updated'): ?><div class="alert alert-success">✅ Product updated!</div>
            <?php elseif($msg==='deleted'): ?><div class="alert alert-error">🗑️ Product deleted.</div>
            <?php endif; ?>

            <form method="GET" style="margin-bottom:16px">
                <input type="text" name="search" class="search-input" placeholder="🔍 Search..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn btn-secondary" style="margin-left:8px">Search</button>
                <?php if($search): ?><a href="products.php" class="btn btn-outline" style="margin-left:8px">Reset</a><?php endif; ?>
            </form>

            <div class="table-card">
                <table>
                    <thead>
                        <tr><th>#</th><th>Product</th><th>Price</th><th>Stock</th><th>Platforms</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                    <?php if($count>0): $i=1; while($p=mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td style="color:var(--muted)"><?= $i++ ?></td>
                        <td>
                            <div class="product-name"><?= htmlspecialchars($p['name']) ?></div>
                            <div class="product-sku">SKU: <?= htmlspecialchars($p['sku']) ?> | <?= $p['category'] ?></div>
                        </td>
                        <td>
                            <div class="price">₹<?= number_format($p['price'],2) ?></div>
                            <?php if($p['mrp']>$p['price']): ?><div class="price-mrp">₹<?= number_format($p['mrp'],2) ?></div><?php endif; ?>
                        </td>
                        <td><span class="<?= $p['stock']>10?'stock-ok':($p['stock']>0?'stock-low':'stock-out') ?>"><?= $p['stock']>0?$p['stock']:'❌ 0' ?></span></td>
                        <td class="gap-8">
                            <?php if($p['on_amazon']): ?><span class="badge badge-amazon">🟡</span><?php endif; ?>
                            <?php if($p['on_flipkart']): ?><span class="badge badge-flipkart">🔵</span><?php endif; ?>
                            <?php if(!$p['on_amazon']&&!$p['on_flipkart']): ?><span class="badge badge-muted">None</span><?php endif; ?>
                        </td>
                        <td>
                            <div class="actions">
                                <a href="add_product.php?id=<?= $p['id'] ?>" class="btn btn-secondary btn-sm">✏️</a>
                                <a href="delete_product.php?id=<?= $p['id'] ?>" class="btn btn-danger btn-sm"
                                   onclick="return confirm('Do you want to delete?')">🗑️</a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr><td colspan="6">
                        <div class="empty-state">
                            <div class="icon">📭</div>
                            <div>No products available</div>
                            <a href="add_product.php" class="btn btn-primary mt-16">+ Add</a>
                        </div>
                    </td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>
