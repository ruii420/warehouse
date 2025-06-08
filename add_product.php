<?php
session_start();
include 'db.php';
if (!isset($_SESSION['user_id']) || !isset($_SESSION['permissions']['can_add_product']) || !$_SESSION['permissions']['can_add_product']) {
    header("Location: login.php");
    exit;
}

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $category = trim($_POST['category']);
    $company_id = trim($_POST['company_id']);
    $quantity = (int)$_POST['quantity'];
    $price = (float)$_POST['price'];
    $user_id = $_SESSION['user_id'];

    if (empty($name)) {
        $msg = "Product Name cannot be empty.";
    } elseif (empty($category)) {
        $msg = "Category cannot be empty.";
    } elseif (empty($company_id)) {
        $msg = "Company ID cannot be empty.";
    } elseif (!preg_match("/^[a-zA-Z0-9 ]*$/", $name)) {
        $msg = "Product Name can only contain letters, numbers, and spaces.";
    } elseif (!preg_match("/^[a-zA-Z0-9 ]*$/", $category)) {
        $msg = "Category can only contain letters, numbers, and spaces.";
    } elseif (!preg_match("/^[a-zA-Z0-9]*$/", $company_id)) {
        $msg = "Company ID can only contain letters and numbers.";
    } elseif (!is_numeric($quantity) || $quantity < 0) {
        $msg = "Quantity must be a non-negative number.";
    } elseif (!is_numeric($price) || $price < 0) {
        $msg = "Price must be a non-negative number.";
    } else {
        $stmt = $conn->prepare("INSERT INTO products (name, description, category, company_id, quantity, price, user_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssidi", $name, $description, $category, $company_id, $quantity, $price, $user_id);

        if ($stmt->execute()) {
            $msg = "Product added successfully!";
        } else {
            $msg = "Error: " . $stmt->error;
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
    <title>Add Product</title>
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
                <a href="make_order.php" class="menu-item">🚚 Veikt pasūtījumu</a>
            <?php endif; ?>
            <?php if (isset($_SESSION['permissions']['can_create_report']) && $_SESSION['permissions']['can_create_report']): ?>
                <a href="create_report.php" class="menu-item">📄 Sagatavot atskaiti</a>
            <?php endif; ?>
            <?php if (isset($_SESSION['permissions']['can_add_product']) && $_SESSION['permissions']['can_add_product']): ?>
                <a href="add_product.php" class="menu-item active">➕ Pievienot produktu</a>
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
            <h2>Add New Product</h2>
        </header>

        <section class="form-section">
            <form method="POST" class="add-product-form">
                <label for="name">Product Name:</label>
                <input type="text" id="name" name="name">

                <label for="description">Description:</label>
                <textarea id="description" name="description"></textarea>

                <label for="category">Category:</label>
                <input type="text" id="category" name="category">

                <label for="company_id">Company ID:</label>
                <input type="text" id="company_id" name="company_id">

                <label for="quantity">Quantity:</label>
                <input type="number" id="quantity" name="quantity">

                <label for="price">Price:</label>
                <input type="number" id="price" name="price" step="0.01">

                <button type="submit">Add Product</button>
                <?php if ($msg): ?><p class="message"><?= $msg ?></p><?php endif; ?>
            </form>
        </section>
    </main>
</div>

</body>
</html> 