<?php
session_start();
include 'db.php';

// Redirect if not logged in or not authorized
if (!isset($_SESSION['user_id']) || !isset($_SESSION['permissions']['can_delete_product']) || !$_SESSION['permissions']['can_delete_product']) {
    header("Location: login.php");
    exit;
}

$msg = '';

if (isset($_GET['id'])) {
    $product_id = (int)$_GET['id'];

    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);

    if ($stmt->execute()) {
        $msg = "Product deleted successfully!";
        header("Location: index.php?msg=" . urlencode($msg));
        exit;
    } else {
        $msg = "Error deleting product: " . $stmt->error;
    }
    $stmt->close();
} else {
    $msg = "Invalid product ID.";
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