<?php // includes/sidebar.php ?>
<div class="sidebar">
    <div class="logo">Seller<span>App</span></div>
    <nav class="nav">
        <a href="index.php"         class="<?= ($page??'')==='dashboard'?'active':''?>">📊 Dashboard</a>
        <a href="products.php"      class="<?= ($page??'')==='products' ?'active':''?>">📦 Products</a>
        <a href="add_product.php"   class="<?= ($page??'')==='add'      ?'active':''?>">➕ Product Add</a>
        <a href="list_flipkart.php" class="<?= ($page??'')==='flipkart' ?'active':''?>">🔵 Flipkart List</a>
        <a href="api_logs.php"      class="<?= ($page??'')==='logs'     ?'active':''?>">📋 API Logs</a>
        <a href="settings.php"      class="<?= ($page??'')==='settings' ?'active':''?>">⚙️ API Settings</a>
        <a href="list_amazon.php" class="<?= ($page??'')==='amazon' ?'active':''?>">🟡 Amazon List</a>
    </nav>
</div>
