<?php
// api_logs.php — For debugging API calls
require_once 'config/db.php';
$page = 'logs';

// Filter
$platform = clean($conn, $_GET['platform'] ?? '');
$status   = clean($conn, $_GET['status']   ?? '');
$where    = 'WHERE 1';
if ($platform) $where .= " AND platform='$platform'";
if ($status)   $where .= " AND status='$status'";

$logs  = mysqli_query($conn, "SELECT * FROM api_logs $where ORDER BY created_at DESC LIMIT 50");
$count = mysqli_num_rows($logs);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>API Logs</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="wrapper">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main">
        <div class="topbar">
            <h1>📋 API Logs (<?= $count ?>)</h1>
            <a href="?clear=1" class="btn btn-danger btn-sm"
               onclick="return confirm('Delete all logs?')">🗑️ Clear Logs</a>
        </div>
        <?php
        if (isset($_GET['clear'])) {
            mysqli_query($conn, "DELETE FROM api_logs");
            header('Location: api_logs.php');
            exit;
        }
        ?>
        <div class="content">

            <div class="alert alert-info">
                📖 All API calls appear here — success, error, request/response everything.
                Very useful for debugging!
            </div>

            <!-- Filters -->
            <form method="GET" style="display:flex; gap:10px; margin-bottom:16px">
                <select name="platform" class="form-control" style="width:auto">
                    <option value="">All Platforms</option>
                    <option value="flipkart" <?= $platform==='flipkart'?'selected':''?>>🔵 Flipkart</option>
                    <option value="amazon"   <?= $platform==='amazon'?'selected':''?>>🟡 Amazon</option>
                </select>
                <select name="status" class="form-control" style="width:auto">
                    <option value="">All Status</option>
                    <option value="success" <?= $status==='success'?'selected':''?>>✅ Success</option>
                    <option value="error"   <?= $status==='error'?'selected':''?>>❌ Error</option>
                </select>
                <button type="submit" class="btn btn-secondary">Filter</button>
                <a href="api_logs.php" class="btn btn-outline">Reset</a>
            </form>

            <?php if ($count > 0): ?>
            <?php while ($log = mysqli_fetch_assoc($logs)): ?>
            <div class="log-entry">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px">
                    <div style="display:flex; gap:10px; align-items:center">
                        <?php if ($log['platform'] === 'amazon'): ?>
                            <span class="badge badge-amazon">🟡 Amazon</span>
                        <?php else: ?>
                            <span class="badge badge-flipkart">🔵 Flipkart</span>
                        <?php endif; ?>
                        <strong><?= htmlspecialchars($log['action']) ?></strong>
                        <?php if ($log['status'] === 'success'): ?>
                            <span class="badge badge-success">✅ Success</span>
                        <?php else: ?>
                            <span class="badge badge-danger">❌ Error</span>
                        <?php endif; ?>
                    </div>
                    <span style="color:var(--muted); font-size:12px"><?= $log['created_at'] ?></span>
                </div>

                <details>
                    <summary style="cursor:pointer; color:var(--muted); font-size:13px">📤 View Request</summary>
                    <pre><?= htmlspecialchars($log['request']) ?></pre>
                </details>
                <details style="margin-top:6px">
                    <summary style="cursor:pointer; color:var(--muted); font-size:13px">📥 View Response</summary>
                    <pre><?= htmlspecialchars($log['response']) ?></pre>
                </details>
            </div>
            <?php endwhile; ?>
            <?php else: ?>
            <div class="empty-state">
                <div class="icon">📭</div>
                <div>No API logs yet</div>
                <div style="margin-top:8px; font-size:13px">Logs will appear here after listing products</div>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>
</body>
</html>
