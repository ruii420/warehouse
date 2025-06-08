<?php
session_start();
include 'db.php';

$filter = isset($_GET['category']) ? $_GET['category'] : null;


$cat_stmt = $conn->query("SELECT DISTINCT category FROM products");
$categories = $cat_stmt->fetch_all(MYSQLI_ASSOC);


if ($filter) {
    $stmt = $conn->prepare("
        SELECT p.*, u.username
        FROM products p
        LEFT JOIN users u ON p.user_id = u.id
        WHERE p.category = ?
    ");
    $stmt->bind_param("s", $filter);
    $stmt->execute();
    $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $result = $conn->query("
        SELECT p.*, u.username
        FROM products p
        LEFT JOIN users u ON p.user_id = u.id
    ");
    $products = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Warehouse Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="wrapper">
    <aside class="sidebar">
        <h1 class="logo">Stash</h1>
        <nav class="menu">
            <a href="index.php" class="menu-item active">🏠 Sākums</a>
            <?php if (isset($_SESSION['permissions']['can_manage_inventory']) && $_SESSION['permissions']['can_manage_inventory']): ?>
                <a href="manage_inventory.php" class="menu-item">📦 Izvietot preces</a>
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
            <h2>Produkti</h2>
        </header>

        <section class="table-section">
            <table class="product-table">
                <thead>
                    <tr>
                        <th>Produkts</th>
                        <th>Katagorija</th>
                        <th>Cena</th>
                        <th>Firma Id</th>
                        <th>Daudzumus</th>
                        <th>Darbības</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($products)): ?>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?= htmlspecialchars($product['name']) ?></td>
                                <td><?= htmlspecialchars($product['category']) ?></td>
                                <td>&euro;<?= htmlspecialchars($product['price']) ?></td>
                                <td><?= htmlspecialchars($product['company_id']) ?></td>
                                <td><?= htmlspecialchars($product['quantity']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="no-products">Produkts nav atrasts</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>
</div>

</body>
</html>

