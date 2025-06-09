<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['permissions']['can_edit_product']) || !$_SESSION['permissions']['can_edit_product']) {
    header("Location: login.php");
    exit;
}

$msg = '';
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product_data = null;

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $product_data) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $category = trim($_POST['category']);
    $company_id = trim($_POST['company_id']);
    $quantity = filter_var($_POST['quantity'], FILTER_VALIDATE_INT);
    $price = filter_var($_POST['price'], FILTER_VALIDATE_FLOAT);

   
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
    } elseif (strlen($description) > 500) {
        $msg = "Apraksts nevar būt garāks par 500 simboliem.";
    } elseif (!preg_match("/^[a-zA-Z0-9\s\.,\-]+$/u", $description)) {
        $msg = "Apraksts var saturēt tikai burtus, ciparus, atstarpes, punktus, komatus un domuzīmes.";
    } elseif ($quantity === false || $quantity < 0 || !preg_match("/^[0-9]+$/", $_POST['quantity'])) {
        $msg = "Daudzumam jābūt pozitīvam veselam skaitlim.";
    } elseif ($price === false || $price <= 0 || !preg_match("/^[0-9]+\.?[0-9]*$/", $_POST['price'])) {
        $msg = "Cenai jābūt pozitīvam skaitlim ar minimums 1 punktu.";
    } else {
        $action_description = isset($_POST['action_description']) ? trim($_POST['action_description']) : '';
        
        if (strlen($action_description) > 200) {
            $msg = "Darbības apraksts nevar būt garāks par 200 simboliem.";
        } elseif (!empty($action_description) && !preg_match("/^[a-zA-Z0-9\s\.,\-]+$/u", $action_description)) {
            $msg = "Darbības apraksts var saturēt tikai burtus, ciparus, atstarpes, punktus, komatus un domuzīmes.";
        } else {
            $stmt = $conn->prepare("UPDATE products SET name = ?, description = ?, category = ?, company_id = ?, quantity = ?, price = ? WHERE id = ?");
            $stmt->bind_param("ssssidi", $name, $description, $category, $company_id, $quantity, $price, $product_id);

            if ($stmt->execute()) {
                $msg = "Produkts veiksmīgi atjaunināts!";
                $product_data['name'] = $name;
                $product_data['description'] = $description;
                $product_data['category'] = $category;
                $product_data['company_id'] = $company_id;
                $product_data['quantity'] = $quantity;
                $product_data['price'] = $price;

                $user_id = $_SESSION['user_id'];
                $action_type = 'Rediģēšana';
                $action_description = "Preces informācija atjaunināta";

                $log_mod_stmt = $conn->prepare("INSERT INTO product_log (product_id, user_id, action_type, action_description) VALUES (?, ?, ?, ?)");
                $log_mod_stmt->bind_param("iiss", $product_id, $user_id, $action_type, $action_description);
                $log_mod_stmt->execute();
                $log_mod_stmt->close();

              
                if ($quantity != $product_data['quantity']) {
                    $quantity_change = $quantity - $product_data['quantity'];
                    $action_type = 'Daudzuma_maiņa';
                    $action_description = "Preces daudzums mainīts no " . $product_data['quantity'] . " uz " . $quantity;
                    $inventory_log_stmt = $conn->prepare("INSERT INTO inventory_log (product_id, user_id, action_type, quantity_change, new_quantity, action_description) VALUES (?, ?, ?, ?, ?, ?)");
                    $inventory_log_stmt->bind_param("iisiss", $product_id, $user_id, $action_type, $quantity_change, $quantity, $action_description);
                    $inventory_log_stmt->execute();
                    $inventory_log_stmt->close();
                }

            } else {
                $msg = "Kļūda atjauninot produktu: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <title>Rediģēt Produktu</title>
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
            <h2>Rediģēt Produktu</h2>
        </header>

        <section class="form-section">
            <?php if ($msg): ?>
                <p class="message <?= strpos($msg, 'veiksmīgi') !== false ? 'success' : 'error' ?>">
                    <?= $msg ?>
                </p>
            <?php endif; ?>

            <?php if ($product_data): ?>
                <form method="POST" class="edit-product-form">
                    <label for="name">Produkta Nosaukums:</label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($product_data['name']) ?>">

                    <label for="description">Apraksts:</label>
                    <textarea id="description" name="description"><?= htmlspecialchars($product_data['description']) ?></textarea>

                    <label for="category">Kategorija:</label>
                    <input type="text" id="category" name="category" value="<?= htmlspecialchars($product_data['category']) ?>">

                    <label for="company_id">Uzņēmuma ID:</label>
                    <input type="text" id="company_id" name="company_id" value="<?= htmlspecialchars($product_data['company_id']) ?>">

                    <label for="quantity">Daudzums:</label>
                    <input type="text" id="quantity" name="quantity" value="<?= htmlspecialchars($product_data['quantity']) ?>">

                    <label for="price">Cena:</label>
                    <input type="text" id="price" name="price" value="<?= htmlspecialchars($product_data['price']) ?>">

                    <label for="action_description">Darbības Apraksts:</label>
                    <textarea id="action_description" name="action_description" rows="3"></textarea>

                    <button type="submit">Saglabāt</button>
                </form>
            <?php else: ?>
                <p>Produkta dati nav atrasti.</p>
            <?php endif; ?>
        </section>
    </main>
</div>

</body>
</html> 