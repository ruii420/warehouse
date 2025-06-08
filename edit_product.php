<?php
session_start();
include 'db.php';

// Redirect if not logged in or not authorized
if (!isset($_SESSION['user_id']) || !isset($_SESSION['permissions']['can_edit_product']) || !$_SESSION['permissions']['can_edit_product']) {
    header("Location: login.php");
    exit;
}

$msg = '';
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product_data = null;

// Fetch product data for editing
if ($product_id > 0) {
    $stmt = $conn->prepare("SELECT id, name, description, category, company_id, quantity, price FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product_data = $result->fetch_assoc();
    $stmt->close();

    if (!$product_data) {
        $msg = "Product not found.";
    }
} else {
    $msg = "Invalid product ID.";
}

// Handle form submission for updating product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $product_data) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $category = trim($_POST['category']);
    $company_id = trim($_POST['company_id']);
    $quantity = (int)$_POST['quantity'];
    $price = (float)$_POST['price'];

    if (empty($name) || empty($category) || empty($company_id) || $quantity < 0 || $price < 0) {
        $msg = "Please fill in all required fields and ensure quantity and price are not negative.";
    } else {
        $stmt = $conn->prepare("UPDATE products SET name = ?, description = ?, category = ?, company_id = ?, quantity = ?, price = ? WHERE id = ?");
        $stmt->bind_param("ssssidi", $name, $description, $category, $company_id, $quantity, $price, $product_id);

        if ($stmt->execute()) {
            $msg = "Product updated successfully!";
            // Update product_data for immediate display of changes
            $product_data['name'] = $name;
            $product_data['description'] = $description;
            $product_data['category'] = $category;
            $product_data['company_id'] = $company_id;
            $product_data['quantity'] = $quantity;
            $product_data['price'] = $price;
        } else {
            $msg = "Error updating product: " . $stmt->error;
        }
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Product</title>
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
            <h2>Edit Product</h2>
        </header>

        <section class="form-section">
            <?php if ($msg): ?><p class="message"><?= $msg ?></p><?php endif; ?>

            <?php if ($product_data): ?>
                <form method="POST" class="edit-product-form">
                    <label for="name">Product Name:</label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($product_data['name']) ?>" required>

                    <label for="description">Description:</label>
                    <textarea id="description" name="description"><?= htmlspecialchars($product_data['description']) ?></textarea>

                    <label for="category">Category:</label>
                    <input type="text" id="category" name="category" value="<?= htmlspecialchars($product_data['category']) ?>" required>

                    <label for="company_id">Company ID:</label>
                    <input type="text" id="company_id" name="company_id" value="<?= htmlspecialchars($product_data['company_id']) ?>" required>

                    <label for="quantity">Quantity:</label>
                    <input type="number" id="quantity" name="quantity" value="<?= htmlspecialchars($product_data['quantity']) ?>" required min="0">

                    <label for="price">Price:</label>
                    <input type="number" id="price" name="price" value="<?= htmlspecialchars($product_data['price']) ?>" step="0.01" required min="0">

                    <button type="submit">Update Product</button>
                </form>
            <?php else: ?>
                <p>Product details could not be loaded.</p>
            <?php endif; ?>
        </section>
    </main>
</div>

</body>
</html> 