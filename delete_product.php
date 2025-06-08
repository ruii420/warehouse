<?php
session_start();
include 'db.php';
if (!isset($_SESSION['user_id']) || !isset($_SESSION['permissions']['can_delete_product']) || !$_SESSION['permissions']['can_delete_product']) {
    header("Location: login.php");
    exit;
}

$msg = '';

if (isset($_GET['id'])) {
    $product_id = (int)$_GET['id'];

    
    $product_name_stmt = $conn->prepare("SELECT name, quantity FROM products WHERE id = ?");
    $product_name_stmt->bind_param("i", $product_id);
    $product_name_stmt->execute();
    $product_result = $product_name_stmt->get_result();
    $product = $product_result->fetch_assoc();
    $product_name = $product['name'] ?? 'Nezināms Produkts';
    $current_quantity = $product['quantity'] ?? 0;
    $product_name_stmt->close();

    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);

    if ($stmt->execute()) {
        $msg = "Produkts veiksmīgi dzēsts!";

        $user_id = $_SESSION['user_id'];
        $action_type = 'Dzēšana';
        $action_description = "Dzēsts produkts: " . $product_name . " (ID: " . $product_id . ")";

        
        $log_mod_stmt = $conn->prepare("INSERT INTO product_log (product_id, user_id, action_type, action_description) VALUES (?, ?, ?, ?)");
        $log_mod_stmt->bind_param("iiss", $product_id, $user_id, $action_type, $action_description);
        $log_mod_stmt->execute();
        $log_mod_stmt->close();

      
        $quantity_change = -$current_quantity;
        $new_quantity = 0;
        $inventory_log_stmt = $conn->prepare("INSERT INTO inventory_log (product_id, user_id, action_type, quantity_change, new_quantity, action_description) VALUES (?, ?, ?, ?, ?, ?)");
        $inventory_log_stmt->bind_param("iisiss", $product_id, $user_id, $action_type, $quantity_change, $new_quantity, $action_description);
        $inventory_log_stmt->execute();
        $inventory_log_stmt->close();

        header("Location: index.php?msg=" . urlencode($msg));
        exit;
    } else {
        $msg = "Kļūda dzēšot produktu: " . $stmt->error;
    }
    $stmt->close();
} else {
    $msg = "Nederīgs produkta ID.";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delete Product</title>
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
            <h2>Delete Product</h2>
        </header>

        <section class="message-section">
            <?php if ($msg): ?><p class="message"><?= $msg ?></p><?php endif; ?>
            <p>Redirecting back to the dashboard...</p>
        </section>
    </main>
</div>

</body>
</html> 