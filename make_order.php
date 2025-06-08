<?php
session_start();
include 'db.php';
if (!isset($_SESSION['user_id']) || !isset($_SESSION['permissions']['can_make_order']) || !$_SESSION['permissions']['can_make_order']) {
    header("Location: login.php");
    exit;
}

$msg = '';
$products = [];

$products_result = $conn->query("SELECT id, name, quantity FROM products");
if ($products_result) {
    $products = $products_result->fetch_all(MYSQLI_ASSOC);
} else {
    $msg = "Error fetching products: " . $conn->error;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $order_quantity = isset($_POST['order_quantity']) ? (int)$_POST['order_quantity'] : 0;
    $user_id = $_SESSION['user_id'];

    if ($product_id <= 0) {
        $msg = "Please select a product.";
    } elseif ($order_quantity <= 0) {
        $msg = "Order quantity must be a positive number.";
    } else {
        $current_quantity_stmt = $conn->prepare("SELECT quantity FROM products WHERE id = ?");
        $current_quantity_stmt->bind_param("i", $product_id);
        $current_quantity_stmt->execute();
        $current_quantity_result = $current_quantity_stmt->get_result();
        $current_product = $current_quantity_result->fetch_assoc();
        $current_quantity_stmt->close();

        if ($current_product) {
            $new_quantity = $current_product['quantity'] + $order_quantity;

            $update_stmt = $conn->prepare("UPDATE products SET quantity = ? WHERE id = ?");
            $update_stmt->bind_param("ii", $new_quantity, $product_id);

            if ($update_stmt->execute()) {
                $msg = "Product quantity updated successfully (ordered: " . $order_quantity . ")!";

                // Log the order in the new orders table
                $order_stmt = $conn->prepare("INSERT INTO orders (product_id, user_id, order_quantity, old_quantity, new_quantity) VALUES (?, ?, ?, ?, ?)");
                $order_stmt->bind_param("iiiii", $product_id, $user_id, $order_quantity, $current_product['quantity'], $new_quantity);
                $order_stmt->execute();
                $order_stmt->close();

                // Refresh product list to show updated quantities if needed
                $products_result = $conn->query("SELECT id, name, quantity FROM products");
                if ($products_result) {
                    $products = $products_result->fetch_all(MYSQLI_ASSOC);
                }

            } else {
                $msg = "Error updating product quantity: " . $conn->error;
            }
            $update_stmt->close();
        } else {
            $msg = "Product not found.";
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Veikt Pasūtījumu</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="wrapper">
    <aside class="sidebar">
        <h1 class="logo">Stash</h1>
        <nav class="menu">
            <a href="index.php" class="menu-item">🏠 Sākums</a>
            <?php if (isset($_SESSION['permissions']['can_manage_inventory']) && $_SESSION['permissions']['can_manage_inventory']): ?>
                <a href="manage_inventory.php" class="menu-item">📦 Izvietot preces</a>
            <?php endif; ?>
            <?php if (isset($_SESSION['permissions']['can_make_order']) && $_SESSION['permissions']['can_make_order']): ?>
                <a href="make_order.php" class="menu-item active">🚚 Veikt pasūtījumu</a>
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
            <h2>Veikt Pasūtījumu</h2>
        </header>

        <section class="form-section">
            <?php if ($msg): ?><p class="message"><?= $msg ?></p><?php endif; ?>

            <form method="POST" class="order-form">
                <div class="form-group">
                    <label for="product_id">Produkts:</label>
                    <select id="product_id" name="product_id" required>
                        <option value="">Select a product</option>
                        <?php foreach ($products as $product): ?>
                            <option value="<?= htmlspecialchars($product['id']) ?>">
                                <?= htmlspecialchars($product['name']) ?> (Current Quantity: <?= htmlspecialchars($product['quantity']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="order_quantity">Pasūtījuma Daudzums:</label>
                    <input type="number" id="order_quantity" name="order_quantity" required min="1">
                </div>
                <button type="submit" class="button">Pasūtīt</button>
            </form>
        </section>
    </main>
</div>

</body>
</html> 