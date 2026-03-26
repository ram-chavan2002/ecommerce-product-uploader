<?php
require_once 'config/db.php';
$page = 'settings';
$msg  = '';

$chk = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM api_settings WHERE platform='amazon'"));
if (!$chk) {
    mysqli_query($conn, "INSERT INTO api_settings (platform, sandbox_mode) VALUES ('amazon', 1)");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_flipkart'])) {
        $fk_app_id     = clean($conn, $_POST['fk_app_id']     ?? '');
        $fk_app_secret = clean($conn, $_POST['fk_app_secret'] ?? '');
        $fk_active     = isset($_POST['fk_active'])  ? 1 : 0;
        $fk_sandbox    = isset($_POST['fk_sandbox']) ? 1 : 0;
        mysqli_query($conn, "UPDATE api_settings SET app_id='$fk_app_id', app_secret='$fk_app_secret', is_active=$fk_active, sandbox_mode=$fk_sandbox, access_token=NULL, token_expiry=NULL WHERE platform='flipkart'");
        $msg = 'flipkart';
    }
    if (isset($_POST['save_amazon'])) {
        $amz_app_id     = clean($conn, $_POST['amz_app_id']     ?? '');
        $amz_app_secret = clean($conn, $_POST['amz_app_secret'] ?? '');
        $amz_active     = isset($_POST['amz_active'])  ? 1 : 0;
        $amz_sandbox    = isset($_POST['amz_sandbox']) ? 1 : 0;
        mysqli_query($conn, "UPDATE api_settings SET app_id='$amz_app_id', app_secret='$amz_app_secret', is_active=$amz_active, sandbox_mode=$amz_sandbox, access_token=NULL, token_expiry=NULL WHERE platform='amazon'");
        $msg = 'amazon';
    }
}

$fk  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM api_settings WHERE platform='flipkart'"));
$amz = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM api_settings WHERE platform='amazon'"));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>API Settings</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="wrapper">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main">
        <div class="topbar"><h1>⚙️ API Settings</h1></div>
        <div class="content">

            <?php if ($msg === 'flipkart'): ?>
                <div class="alert alert-success">✅ Flipkart Settings saved!</div>
            <?php elseif ($msg === 'amazon'): ?>
                <div class="alert alert-success">✅ Amazon Settings saved!</div>
            <?php endif; ?>

            <div class="form-card" style="margin-bottom:20px; border-top:3px solid #388bfd">
                <div class="form-title">📋 Where to get API Keys?</div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px">
                    <div style="background:var(--surface2); border-radius:8px; padding:16px; font-size:13px; color:var(--muted); line-height:1.9">
                        <strong style="color:#6baef9">🔵 Flipkart</strong><br>
                        seller.flipkart.com → Login<br>
                        → Account → Settings<br>
                        → Developer Access<br>
                        → "Create New App" → Sandbox
                    </div>
                    <div style="background:var(--surface2); border-radius:8px; padding:16px; font-size:13px; color:var(--muted); line-height:1.9">
                        <strong style="color:#e3b341">🟡 Amazon</strong><br>
                        sellercentral.amazon.in → Login<br>
                        → Apps & Services<br>
                        → Develop Apps<br>
                        → "Add New App" → Sandbox
                    </div>
                </div>
            </div>

            <!-- FLIPKART -->
            <div class="form-card" style="margin-bottom:20px; border-top:3px solid #388bfd">
                <div class="form-title" style="color:#6baef9">🔵 Flipkart Credentials</div>
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">App ID (Client ID)</label>
                            <input type="text" name="fk_app_id" class="form-control"
                                   placeholder="fk_merchant_XXXXXXXXXXXXXXXX"
                                   value="<?= htmlspecialchars($fk['app_id'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">App Secret</label>
                            <input type="password" name="fk_app_secret" class="form-control"
                                   placeholder="••••••••••••••••"
                                   value="<?= htmlspecialchars($fk['app_secret'] ?? '') ?>">
                        </div>
                    </div>
                    <div style="display:flex; gap:24px; margin-bottom:20px">
                        <label class="form-check">
                            <input type="checkbox" name="fk_sandbox" value="1" <?= ($fk['sandbox_mode']??1)?'checked':'' ?>>
                            🧪 Sandbox Mode (Testing)
                        </label>
                        <label class="form-check">
                            <input type="checkbox" name="fk_active" value="1" <?= ($fk['is_active']??0)?'checked':'' ?>>
                            ✅ Activate API
                        </label>
                    </div>
                    <div style="background:var(--surface2); border-radius:8px; padding:14px; margin-bottom:20px; font-size:13px">
                        <strong style="color:var(--muted)">🔑 Token: </strong>
                        <?php if (!empty($fk['access_token'])): ?>
                            <span style="color:#56d364">✅ Active — <?= $fk['token_expiry'] ?></span>
                        <?php elseif (!empty($fk['app_id'])): ?>
                            <span style="color:#e3b341">⏳ Will get on first call</span>
                        <?php else: ?>
                            <span style="color:var(--muted)">— After adding App ID</span>
                        <?php endif; ?>
                    </div>
                    <div style="display:flex; gap:10px">
                        <button type="submit" name="save_flipkart" class="btn btn-primary">💾 Save Flipkart</button>
                        <?php if (!empty($fk['app_id'])): ?>
                        <a href="list_flipkart.php" class="btn btn-flipkart">🔵 Test →</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- AMAZON -->
            <div class="form-card" style="border-top:3px solid #e3b341">
                <div class="form-title" style="color:#e3b341">🟡 Amazon Credentials</div>
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Client ID</label>
                            <input type="text" name="amz_app_id" class="form-control"
                                   placeholder="amzn1.application-oa2-client.XXXXX"
                                   value="<?= htmlspecialchars($amz['app_id'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Client Secret</label>
                            <input type="password" name="amz_app_secret" class="form-control"
                                   placeholder="••••••••••••••••"
                                   value="<?= htmlspecialchars($amz['app_secret'] ?? '') ?>">
                        </div>
                    </div>
                    <div style="display:flex; gap:24px; margin-bottom:20px">
                        <label class="form-check">
                            <input type="checkbox" name="amz_sandbox" value="1" <?= ($amz['sandbox_mode']??1)?'checked':'' ?>>
                            🧪 Sandbox Mode (Testing)
                        </label>
                        <label class="form-check">
                            <input type="checkbox" name="amz_active" value="1" <?= ($amz['is_active']??0)?'checked':'' ?>>
                            ✅ Activate API
                        </label>
                    </div>
                    <div style="background:var(--surface2); border-radius:8px; padding:14px; margin-bottom:20px; font-size:13px">
                        <strong style="color:var(--muted)">🔑 Token: </strong>
                        <?php if (!empty($amz['access_token'])): ?>
                            <span style="color:#56d364">✅ Active — <?= $amz['token_expiry'] ?></span>
                        <?php elseif (!empty($amz['app_id'])): ?>
                            <span style="color:#e3b341">⏳ Will get on first call</span>
                        <?php else: ?>
                            <span style="color:var(--muted)">— After adding Client ID</span>
                        <?php endif; ?>
                    </div>
                    <div style="display:flex; gap:10px">
                        <button type="submit" name="save_amazon" class="btn btn-primary" style="background:#e3b341;color:#000">💾 Save Amazon</button>
                        <?php if (!empty($amz['app_id'])): ?>
                        <a href="list_amazon.php" class="btn btn-amazon">🟡 Test →</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>
</body>
</html>
