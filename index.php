<?php
// index.php
require_once 'config/db.php';
$page    = 'dashboard';
$total   = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM products"))['c'];
$amazon  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM products WHERE on_flipkart=1"))['c'];
$flipk   = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM products WHERE on_flipkart=1"))['c'];
$low     = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM products WHERE stock<=10"))['c'];
$recent  = mysqli_query($conn,"SELECT * FROM products ORDER BY created_at DESC LIMIT 5");

$fk_ok  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT is_active FROM api_settings WHERE platform='flipkart'"))['is_active']??0;
?>
<!DOCTYPE html>
<html lang="mr">
<head>
    <meta charset="UTF-8">
    <title>Dashboard | विक्रेता App</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="wrapper">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main">
        <div class="topbar"><h1>📊 Dashboard</h1></div>
        <div class="content">

            <!-- API Status Banner -->
            <div class="alert alert-warning">
                ⚙️ API Keys अजून add केल्या नाहीत —
                <a href="settings.php" style="color:#e3b341"><strong>Settings मध्ये Sandbox Keys add करा</strong></a>
            </div>
            <?php endif; ?>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Total Products</div>
                    <div class="stat-value"><?= $total ?></div>
                </div>
                <div class="stat-card s-amazon">
                    <div class="stat-label">🟡 Amazon Listed</div>
                    <div class="stat-value" style="color:var(--amazon)"><?= $amazon ?></div>
                </div>
                <div class="stat-card s-flipkart">
                    <div class="stat-label">🔵 Flipkart Listed</div>
                    <div class="stat-value" style="color:#6baef9"><?= $flipk ?></div>
                    <div class="stat-sub"><?= $fk_ok ? '✅ API Active' : '⚠️ No API Key' ?></div>
                </div>
                <div class="stat-card s-danger">
                    <div class="stat-label">⚠️ Low Stock</div>
                    <div class="stat-value" style="color:var(--warning)"><?= $low ?></div>
                </div>
            </div>

            <div class="table-card">
                <div class="table-header">
                    <div class="table-title">नवीन Products</div>
                    <a href="products.php" class="btn btn-outline btn-sm">सगळे पहा →</a>
                </div>
                <table>
                    <thead><tr><th>Product</th><th>किंमत</th><th>Stock</th><th>Platforms</th></tr></thead>
                    <tbody>
                    <?php if (mysqli_num_rows($recent)>0): while($p=mysqli_fetch_assoc($recent)): ?>
                    <tr>
                        <td>
                            <div class="product-name"><?= htmlspecialchars($p['name']) ?></div>
                            <div class="product-sku"><?= htmlspecialchars($p['sku']) ?></div>
                        </td>
                        <td class="price">₹<?= number_format($p['price'],2) ?></td>
                        <td><span class="<?= $p['stock']>10?'stock-ok':($p['stock']>0?'stock-low':'stock-out') ?>"><?= $p['stock']>0?$p['stock']:'❌ 0' ?></span></td>
                        <td class="gap-8">
                            <?php if($p['on_amazon']): ?><span class="badge badge-amazon">🟡 Amazon</span><?php endif; ?>
                            <?php if($p['on_flipkart']): ?><span class="badge badge-flipkart">🔵 Flipkart</span><?php endif; ?>
                            <?php if(!$p['on_amazon']&&!$p['on_flipkart']): ?><span class="badge badge-muted">Listed नाही</span><?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr><td colspan="4" class="text-center" style="color:var(--muted);padding:30px">
                        <a href="add_product.php" style="color:var(--accent)">+ पहिला product add करा</a>
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
