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
    <title>Warehouse Inventory</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">
    <aside class="sidebar">
        <h2 class="sidebar-title">Stash</h2>
        <ul class="sidebar-menu">
            <li><a href="index.php" class="sidebar-link active">Sākums</a></li>
            <li><a href="#" class="sidebar-link">Veikt Pasūtijumu</a></li>
            <li><a href="#" class="sidebar-link">Izveidot atskaiti</a></li>
            <li><a href="logout.php" class="sidebar-link">Iziet</a></li>
        </ul>
    </aside>

    <main class="main-content">
       
        <h2 class="title">Produkti</h2>
        <table class="product-table">
            <thead>
                <tr>
                    <th>Produkts</th>
                    <th>Katagorija</th>
                    <th>Cena</th>
                    <th>Firmas Id</th>
                    <th>Daudzums</th>
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
                    <tr><td colspan="6" style="text-align:center;">Produkts nav atrasts</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </main>
</div>

</body>
</html>
