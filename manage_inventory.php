<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['permissions']['can_manage_inventory']) || !$_SESSION['permissions']['can_manage_inventory']) {
    header("Location: login.php");
    exit;
}

$msg = '';
$products_result = [];

$sort_columns = ['id', 'name', 'category', 'quantity'];
$sort_by = isset($_GET['sort_by']) && in_array($_GET['sort_by'], $sort_columns) ? $_GET['sort_by'] : 'id';
$sort_order = isset($_GET['sort_order']) && in_array(strtoupper($_GET['sort_order']), ['ASC', 'DESC']) ? strtoupper($_GET['sort_order']) : 'ASC';

$sql = "SELECT id, name, category, quantity FROM products ORDER BY " . $sort_by . " " . $sort_order;
$products_result = $conn->query($sql);

if ($products_result) {
    $products = $products_result->fetch_all(MYSQLI_ASSOC);
} else {
    $products = [];
    $msg = "Error fetching products: " . $conn->error;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Izvietot Preces</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="wrapper">
    <aside class="sidebar">
        <h1 class="logo">Stash</h1>
        <nav class="menu">
            <a href="index.php" class="menu-item">🏠 Sākums</a>
            <?php if (isset($_SESSION['permissions']['can_manage_inventory']) && $_SESSION['permissions']['can_manage_inventory']): ?>
                <a href="manage_inventory.php" class="menu-item active">📦 Izvietot preces</a>
            <?php endif; ?>
            <?php if (isset($_SESSION['permissions']['can_make_order']) && $_SESSION['permissions']['can_make_order']): ?>
                <a href="make_order.php" class="menu-item">🚚 Veikt pasūtījumu</a>
            <?php endif; ?>
            <?php if (isset($_SESSION['permissions']['can_create_report']) && $_SESSION['permissions']['can_create_report']): ?>
                <a href="create_report.php" class="menu-item">📄 Sagatavot atskaiti</a>
            <?php endif; ?>
            <?php if (isset($_SESSION['permissions']['can_add_product']) && $_SESSION['permissions']['can_add_product']): ?>
                <a href="add_product.php" class="menu-item">➕ Pievienot produktu</a>
            <?php endif; ?>
            <?php if (isset($_SESSION['permissions']['can_add_user']) && $_SESSION['permissions']['can_add_user']): ?>
                <a href="add_user.php" class="menu-item">👤 Pievienot lietotāju</a>
            <?php endif; ?>
            <?php if (isset($_SESSION['permissions']['can_manage_users']) && $_SESSION['permissions']['can_manage_users']): ?>
                <a href="manage_users.php" class="menu-item">👥 Lietotāji</a>
            <?php endif; ?>
            <a href="logout.php" class="menu-item">➡️ Iziet</a>
        </nav>
    </aside>

    <main class="content">
        <header class="page-header">
            <h2>Izvietot Preces (Inventory Management)</h2>
            <div class="sort-controls">
                <form action="manage_inventory.php" method="GET">
                    <label for="sort_by">Sort by:</label>
                    <select name="sort_by" id="sort_by" onchange="this.form.submit()">
                        <option value="id" <?= $sort_by == 'id' ? 'selected' : '' ?>>ID</option>
                        <option value="name" <?= $sort_by == 'name' ? 'selected' : '' ?>>Produkts</option>
                        <option value="category" <?= $sort_by == 'category' ? 'selected' : '' ?>>Kategorija</option>
                        <option value="quantity" <?= $sort_by == 'quantity' ? 'selected' : '' ?>>Daudzumus</option>
                    </select>
                    <label for="sort_order">Order:</label>
                    <select name="sort_order" id="sort_order" onchange="this.form.submit()">
                        <option value="ASC" <?= $sort_order == 'ASC' ? 'selected' : '' ?>>ASC</option>
                        <option value="DESC" <?= $sort_order == 'DESC' ? 'selected' : '' ?>>DESC</option>
                    </select>
                </form>
            </div>
        </header>

        <section class="table-section">
            <?php if ($msg): ?><p class="message"><?= $msg ?></p><?php endif; ?>
            <table class="product-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Produkts</th>
                        <th>Kategorija</th>
                        <th>Daudzumus</th>
                        <th>Darbības</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($products)): ?>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?= htmlspecialchars($product['id']) ?></td>
                                <td><?= htmlspecialchars($product['name']) ?></td>
                                <td><?= htmlspecialchars($product['category']) ?></td>
                                <td><?= htmlspecialchars($product['quantity']) ?></td>
                                <td>
                                    <?php if (isset($_SESSION['permissions']['can_edit_product']) && $_SESSION['permissions']['can_edit_product']): ?>
                                        <a href="edit_product.php?id=<?= $product['id'] ?>" class="action-button edit">Rediģēt</a>
                                    <?php endif; ?>
                                    <?php if (isset($_SESSION['permissions']['can_delete_product']) && $_SESSION['permissions']['can_delete_product']): ?>
                                        <a href="delete_product.php?id=<?= $product['id'] ?>" class="action-button delete" onclick="return confirm('Are you sure you want to delete this product?');">Dzēst</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="no-products">No products found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>
</div>

</body>
</html> 