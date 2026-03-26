<?php
require_once 'config/db.php';
require_once 'api/AmazonAPI.php';
$page = 'amazon';

$result_msg  = '';
$result_type = 'info';

$amz_settings = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM api_settings WHERE platform='amazon'"));
$api_active   = $amz_settings['is_active']   ?? 0;
$app_id       = $amz_settings['app_id']      ?? '';
$app_secret   = $amz_settings['app_secret']  ?? '';
$sandbox_mode = $amz_settings['sandbox_mode'] ?? 1;

if (isset($_POST['action']) && $_POST['action'] === 'list_single') {
    $product_id = (int)$_POST['product_id'];
    $product    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM products WHERE id=$product_id"));
    if ($product && $api_active && $app_id) {
        $api    = new AmazonAPI($app_id, $app_secret, $conn, $sandbox_mode);
        $result = $api->listProduct($product);
        if ($result['success']) {
            mysqli_query($conn, "UPDATE products SET on_amazon=1, amazon_status='listed' WHERE id=$product_id");
            $result_msg  = "✅ '{$product['name']}' listed on Amazon!";
            $result_type = 'success';
        } else {
            $result_msg  = "❌ Error: " . $result['message'];
            $result_type = 'error';
        }
    } else {
        mysqli_query($conn, "UPDATE products SET on_amazon=1, amazon_status='pending' WHERE id=$product_id");
        $result_msg  = "⚠️ API keys missing — marked pending.";
        $result_type = 'warning';
    }
}

if (isset($_POST['action']) && $_POST['action'] === 'bulk_list' && !empty($_POST['product_ids'])) {
    $ids     = array_map('intval', $_POST['product_ids']);
    $success = 0; $failed = 0;
    $api     = ($api_active && $app_id) ? new AmazonAPI($app_id, $app_secret, $conn, $sandbox_mode) : null;
    foreach ($ids as $pid) {
        $product = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM products WHERE id=$pid"));
        if (!$product) continue;
        if ($api) {
            $res = $api->listProduct($product);
            if ($res['success']) {
                mysqli_query($conn, "UPDATE products SET on_amazon=1, amazon_status='listed' WHERE id=$pid");
                $success++;
            } else { $failed++; }
        } else {
            mysqli_query($conn, "UPDATE products SET on_amazon=1, amazon_status='pending' WHERE id=$pid");
            $success++;
        }
    }
    $result_msg  = "✅ $success products listed" . ($failed ? ", ❌ $failed failed" : "") . "!";
    $result_type = $failed ? 'warning' : 'success';
}

$listed     = mysqli_query($conn, "SELECT * FROM products WHERE on_amazon=1 ORDER BY name");
$not_listed = mysqli_query($conn, "SELECT * FROM products WHERE on_amazon=0 ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Amazon Listing</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="wrapper">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main">
        <div class="topbar">
            <h1>🟡 Amazon
                <?= $sandbox_mode
                    ? '<span style="font-size:13px;background:rgba(210,153,34,.2);color:#e3b341;padding:3px 10px;border-radius:20px;margin-left:8px">🧪 SANDBOX</span>'
                    : '<span style="font-size:13px;background:rgba(86,211,100,.2);color:#56d364;padding:3px 10px;border-radius:20px;margin-left:8px">🟢 LIVE</span>'
                ?>
            </h1>
            <a href="settings.php" class="btn btn-outline btn-sm">⚙️ API Settings</a>
        </div>
        <div class="content">
            <?php if ($result_msg): ?>
                <div class="alert alert-<?= $result_type ?>"><?= htmlspecialchars($result_msg) ?></div>
            <?php endif; ?>

            <?php if (!$api_active || !$app_id): ?>
            <div class="alert alert-warning">
                ⚠️ <strong>Amazon API Keys missing</strong> —
                <a href="settings.php" style="color:#e3b341">Add in Settings</a>.
            </div>
            <?php else: ?>
            <div class="alert alert-success">
                ✅ <strong>Amazon <?= $sandbox_mode ? 'Sandbox' : 'Live' ?> API Active!</strong>
            </div>
            <?php endif; ?>

            <div class="table-card" style="margin-bottom:20px">
                <div class="table-header">
                    <div class="table-title">⏳ Not Listed (<?= mysqli_num_rows($not_listed) ?>)</div>
                </div>
                <?php if (mysqli_num_rows($not_listed) > 0): ?>
                <form method="POST">
                    <input type="hidden" name="action" value="bulk_list">
                    <table>
                        <thead>
                            <tr>
                                <th><input type="checkbox" onclick="toggleAll(this,'row-chk')"></th>
                                <th>Product</th><th>Price</th><th>Stock</th><th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php while ($p = mysqli_fetch_assoc($not_listed)): ?>
                        <tr>
                            <td><input type="checkbox" name="product_ids[]" value="<?= $p['id'] ?>" class="row-chk"></td>
                            <td>
                                <div class="product-name"><?= htmlspecialchars($p['name']) ?></div>
                                <div class="product-sku"><?= htmlspecialchars($p['sku']) ?></div>
                            </td>
                            <td class="price">₹<?= number_format($p['price'],2) ?></td>
                            <td><span class="<?= $p['stock']>0?'stock-ok':'stock-out' ?>"><?= $p['stock']>0?$p['stock']:'❌ 0' ?></span></td>
                            <td>
                                <form method="POST" style="display:inline">
                                    <input type="hidden" name="action" value="list_single">
                                    <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                    <button type="submit" class="btn btn-amazon btn-sm">🟡 List</button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                    <div style="padding:14px 18px; border-top:1px solid var(--border)">
                        <button type="submit" class="btn btn-amazon">🟡 List all Selected → on Amazon</button>
                    </div>
                </form>
                <?php else: ?>
                    <div class="empty-state" style="padding:30px">🎉 All products are listed on Amazon!</div>
                <?php endif; ?>
            </div>

            <div class="table-card">
                <div class="table-header">
                    <div class="table-title">✅ Listed on Amazon (<?= mysqli_num_rows($listed) ?>)</div>
                </div>
                <table>
                    <thead>
                        <tr><th>Product</th><th>Price</th><th>Stock</th><th>Status</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                    <?php if (mysqli_num_rows($listed) > 0): ?>
                        <?php while ($p = mysqli_fetch_assoc($listed)): ?>
                        <tr>
                            <td>
                                <div class="product-name"><?= htmlspecialchars($p['name']) ?></div>
                                <div class="product-sku"><?= htmlspecialchars($p['sku']) ?></div>
                            </td>
                            <td class="price">₹<?= number_format($p['price'],2) ?></td>
                            <td><span class="<?= $p['stock']>0?'stock-ok':'stock-out' ?>"><?= $p['stock']>0?$p['stock']:'❌ 0' ?></span></td>
                            <td>
                                <?php
                                $st = $p['amazon_status'] ?? 'listed';
                                $bc = $st==='listed'?'badge-success':($st==='pending'?'badge-warning':'badge-muted');
                                ?>
                                <span class="badge <?= $bc ?>"><?= strtoupper($st) ?></span>
                            </td>
                            <td><a href="add_product.php?id=<?= $p['id'] ?>" class="btn btn-secondary btn-sm">✏️ Edit</a></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align:center;color:var(--muted);padding:20px">Not listed yet</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script>
function toggleAll(master, cls) {
    document.querySelectorAll('.' + cls).forEach(c => c.checked = master.checked);
}
</script>
</body>
</html>
