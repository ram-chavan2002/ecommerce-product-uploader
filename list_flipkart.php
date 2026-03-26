<?php
// list_flipkart.php — Real Sandbox API Integration
require_once 'config/db.php';
require_once 'api/FlipkartAPI.php';
$page = 'flipkart';

$result_msg  = '';
$result_type = 'info';

// Get Flipkart API settings from DB
$fk_settings = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM api_settings WHERE platform='flipkart'"));
$api_active  = $fk_settings['is_active'] ?? 0;
$app_id      = $fk_settings['app_id']    ?? '';
$app_secret  = $fk_settings['app_secret']?? '';

// ── List single product ──
if (isset($_POST['action']) && $_POST['action'] === 'list_single') {
    $product_id = (int)$_POST['product_id'];
    $product    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM products WHERE id=$product_id"));

    if ($product && $api_active && $app_id) {
        $api    = new FlipkartAPI($app_id, $app_secret, $conn);
        $result = $api->listProduct($product);

        if ($result['success']) {
            mysqli_query($conn, "UPDATE products SET on_flipkart=1, flipkart_status='listed' WHERE id=$product_id");
            $result_msg  = "✅ '{$product['name']}' successfully listed on Flipkart Sandbox!";
            $result_type = 'success';
        } else {
            $result_msg  = "❌ Error: " . $result['message'];
            $result_type = 'error';
        }
    } elseif (!$api_active || !$app_id) {
        // If no API, just mark in DB
        mysqli_query($conn, "UPDATE products SET on_flipkart=1, flipkart_status='pending' WHERE id=$product_id");
        $result_msg  = "⚠️ API keys missing — marked 'pending' in DB. Add keys in Settings.";
        $result_type = 'warning';
    }
}

// ── Bulk listing ──
if (isset($_POST['action']) && $_POST['action'] === 'bulk_list' && !empty($_POST['product_ids'])) {
    $ids      = array_map('intval', $_POST['product_ids']);
    $success  = 0; $failed = 0;
    $api      = ($api_active && $app_id) ? new FlipkartAPI($app_id, $app_secret, $conn) : null;

    foreach ($ids as $pid) {
        $product = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM products WHERE id=$pid"));
        if (!$product) continue;

        if ($api) {
            $res = $api->listProduct($product);
            if ($res['success']) {
                mysqli_query($conn, "UPDATE products SET on_flipkart=1, flipkart_status='listed' WHERE id=$pid");
                $success++;
            } else {
                $failed++;
            }
        } else {
            mysqli_query($conn, "UPDATE products SET on_flipkart=1, flipkart_status='pending' WHERE id=$pid");
            $success++;
        }
    }

    $result_msg  = "✅ $success products listed" . ($failed ? ", ❌ $failed failed" : "") . "!";
    $result_type = $failed ? 'warning' : 'success';
}

$listed     = mysqli_query($conn, "SELECT * FROM products WHERE on_flipkart=1 ORDER BY name");
$not_listed = mysqli_query($conn, "SELECT * FROM products WHERE on_flipkart=0 ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Flipkart Listing</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="wrapper">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main">
        <div class="topbar">
            <h1>🔵 Flipkart <?= $fk_settings['sandbox_mode']??1 ? '<span style="font-size:13px;background:rgba(210,153,34,.2);color:#e3b341;padding:3px 10px;border-radius:20px;margin-left:8px">🧪 SANDBOX</span>' : '' ?></h1>
            <a href="settings.php" class="btn btn-outline btn-sm">⚙️ API Settings</a>
        </div>
        <div class="content">

            <?php if ($result_msg): ?>
                <div class="alert alert-<?= $result_type ?>"><?= htmlspecialchars($result_msg) ?></div>
            <?php endif; ?>

            <!-- API Status -->
            <?php if (!$api_active || !$app_id): ?>
            <div class="alert alert-warning">
                ⚠️ <strong>Flipkart API Keys missing</strong> —
                <a href="settings.php" style="color:#e3b341">Add in Settings</a>.
                It will now save as "pending" in DB.
            </div>
            <?php else: ?>
            <div class="alert alert-success">
                ✅ <strong>Flipkart Sandbox API Active</strong> — Real API calls will be executed!
            </div>
            <?php endif; ?>

            <!-- Not Listed -->
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
                                <th><input type="checkbox" id="chkAll" onclick="toggleAll(this,'row-chk')"></th>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Single Action</th>
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
                                    <button type="submit" class="btn btn-flipkart btn-sm"
                                            onclick="this.innerHTML='<span class=spinner></span> Listing...'">
                                        🔵 List
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                    <div style="padding:14px 18px; border-top:1px solid var(--border); display:flex; gap:10px; align-items:center">
                        <button type="submit" class="btn btn-flipkart">🔵 List all Selected → on Flipkart</button>
                        <span style="color:var(--muted); font-size:13px">Select from above</span>
                    </div>
                </form>
                <?php else: ?>
                    <div class="empty-state" style="padding:30px">🎉 All Product Link On Flipkart!</div>
                <?php endif; ?>
            </div>

            <!-- Listed -->
            <div class="table-card">
                <div class="table-header">
                    <div class="table-title">✅ F Listed On Flipkart (<?= mysqli_num_rows($listed) ?>)</div>
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
                                $st = $p['flipkart_status'] ?? 'listed';
                                $bc = $st === 'listed' ? 'badge-success' : ($st === 'pending' ? 'badge-warning' : 'badge-muted');
                                ?>
                                <span class="badge <?= $bc ?>"><?= strtoupper($st) ?></span>
                            </td>
                            <td><a href="add_product.php?id=<?= $p['id'] ?>" class="btn btn-secondary btn-sm">✏️ Edit</a></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center" style="color:var(--muted);padding:20px">Not listed yet</td></tr>
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
