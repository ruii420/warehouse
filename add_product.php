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
    $quantity = filter_var($_POST['quantity'], FILTER_VALIDATE_INT);
    $price = filter_var($_POST['price'], FILTER_VALIDATE_FLOAT);
    $user_id = $_SESSION['user_id'];

    if (empty($name)) {
        $msg = "Produkta nosaukums nevar būt tukšs.";
    } elseif (empty($category)) {
        $msg = "Kategorija nevar būt tukša.";
    } elseif (empty($company_id)) {
        $msg = "Uzņēmuma ID nevar būt tukšs.";
    } elseif (!preg_match("/^[a-zA-Z0-9_]+$/", $name)) {
        $msg = "Produkta nosaukums var saturēt tikai burtus, ciparus un pasvītrojumus.";
    } elseif (!preg_match("/^[a-zA-Z0-9_]+$/", $category)) {
        $msg = "Kategorija var saturēt tikai burtus, ciparus un pasvītrojumus.";
    } elseif (!preg_match("/^[a-zA-Z0-9_]+$/", $company_id)) {
        $msg = "Uzņēmuma ID var saturēt tikai burtus, ciparus un pasvītrojums.";
    } elseif ($quantity === false || $quantity < 0 || !preg_match("/^[0-9]+$/", $_POST['quantity'])) {
        $msg = "Daudzumam jābūt pozitīvam veselam skaitlim.";
    } elseif ($price === false || $price <= 0 || !preg_match("/^[0-9]+\.?[0-9]*$/", $_POST['price'])) {
        $msg = "Cenai jābūt pozitīvam skaitlim ar minimums 1 punktu.";
    } else {
        $stmt = $conn->prepare("INSERT INTO products (name, description, category, company_id, quantity, price, user_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssidi", $name, $description, $category, $company_id, $quantity, $price, $user_id);

        if ($stmt->execute()) {
            $product_id = $conn->insert_id;

          
            if ($quantity > 0) {
                $action_type = 'Pievienošana';
                $action_description = "Pievienoja jaunu produktu";
                $inventory_log_stmt = $conn->prepare("INSERT INTO inventory_log (product_id, user_id, action_type, quantity_change, new_quantity, action_description) VALUES (?, ?, ?, ?, ?, ?)");
                $inventory_log_stmt->bind_param("iisiss", $product_id, $user_id, $action_type, $quantity, $quantity, $action_description);
                $inventory_log_stmt->execute();
            }

            $msg = "Produkts veiksmīgi pievienots!";
            // Clear form
            $name = '';
            $description = '';
            $category = '';
            $company_id = '';
            $quantity = '';
            $price = '';
        } else {
            $msg = "Kļūda pievienojot produktu: " . $stmt->error;
        }
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <title>Pievienot Produktu</title>
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
            <h2>Pievienot Jaunu Produktu</h2>
        </header>

        <section class="form-section">
            <form method="POST" class="add-product-form">
                <label for="name">Produkta Nosaukums:</label>
                <input type="text" id="name" name="name" value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>">

                <label for="description">Apraksts:</label>
                <textarea id="description" name="description"><?= isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '' ?></textarea>

                <label for="category">Kategorija:</label>
                <input type="text" id="category" name="category" value="<?= isset($_POST['category']) ? htmlspecialchars($_POST['category']) : '' ?>">

                <label for="company_id">Uzņēmuma ID:</label>
                <input type="text" id="company_id" name="company_id" value="<?= isset($_POST['company_id']) ? htmlspecialchars($_POST['company_id']) : '' ?>">

                <label for="quantity">Daudzums:</label>
                <input type="text" id="quantity" name="quantity" value="<?= isset($_POST['quantity']) ? htmlspecialchars($_POST['quantity']) : '' ?>">

                <label for="price">Cena:</label>
                <input type="text" id="price" name="price" value="<?= isset($_POST['price']) ? htmlspecialchars($_POST['price']) : '' ?>">

                <button type="submit">Pievienot</button>
                <?php if ($msg): ?>
                    <p class="message <?= strpos($msg, 'veiksmīgi') !== false ? 'success' : 'error' ?>">
                        <?= $msg ?>
                    </p>
                <?php endif; ?>
            </form>
        </section>
    </main>
</div>

</body>
</html> 