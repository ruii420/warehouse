<?php
session_start();
include 'db.php';
if (!isset($_SESSION['user_id']) || !isset($_SESSION['permissions']['can_make_order']) || !$_SESSION['permissions']['can_make_order']) {
    header("Location: login.php");
    exit;
}

$msg = '';
$products = [];

// Dabū visus produktus
$products_result = $conn->query("SELECT id, name, quantity FROM products ORDER BY name");
if ($products_result) {
    $products = $products_result->fetch_all(MYSQLI_ASSOC);
} else {
    $msg = "Kļūda ielādējot produktus: " . $conn->error;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = filter_var($_POST['product_id'], FILTER_VALIDATE_INT);
    $order_quantity = filter_var($_POST['quantity'], FILTER_VALIDATE_INT);
    $valid = true;
    $errors = [];

    if (!$product_id || $product_id <= 0) {
        $valid = false;
        $errors[] = "Lūdzu izvēlieties produktu.";
    }

    if ($order_quantity === false || $order_quantity <= 0) {
        $valid = false;
        $errors[] = "Daudzumam jābūt pozitīvam veselam skaitlim.";
    } elseif (!preg_match("/^[0-9]+$/", $_POST['quantity'])) {
        $valid = false;
        $errors[] = "Daudzums var saturēt tikai ciparus.";
    }

    if ($valid) {
        $stmt = $conn->prepare("SELECT quantity, name FROM products WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
        $stmt->close();

        if (!$product) {
            $msg = "Produkts nav atrasts.";
        } else {
            $old_quantity = $product['quantity'];
            $new_quantity = $old_quantity + $order_quantity;

            // Sāk pasūtījuma apstrādi
            $conn->begin_transaction();

            try {
                $update_stmt = $conn->prepare("UPDATE products SET quantity = ? WHERE id = ?");
                $update_stmt->bind_param("ii", $new_quantity, $product_id);
                $update_stmt->execute();

                // Saglabā pasūtījuma info
                $order_stmt = $conn->prepare("INSERT INTO orders (product_id, user_id, order_quantity, old_quantity, new_quantity) VALUES (?, ?, ?, ?, ?)");
                $order_stmt->bind_param("iiiii", $product_id, $_SESSION['user_id'], $order_quantity, $old_quantity, $new_quantity);
                $order_stmt->execute();

                $action_type = 'Pasūtījums';
                $action_description = "Pasūtīti " . $order_quantity . " " . $product['name'] . " (Papildināts noliktavas krājums)";
                $log_stmt = $conn->prepare("INSERT INTO inventory_log (product_id, user_id, action_type, quantity_change, new_quantity, action_description) VALUES (?, ?, ?, ?, ?, ?)");
                $quantity_change = $order_quantity;
                $log_stmt->bind_param("iisiss", $product_id, $_SESSION['user_id'], $action_type, $quantity_change, $new_quantity, $action_description);
                $log_stmt->execute();

                $conn->commit();
                $msg = "Pasūtījums veiksmīgi izveidots! Noliktavas krājums papildināts.";

                $products_result = $conn->query("SELECT id, name, quantity FROM products ORDER BY name");
                if ($products_result) {
                    $products = $products_result->fetch_all(MYSQLI_ASSOC);
                }
            } catch (Exception $e) {
                $conn->rollback();
                $msg = "Kļūda veidojot pasūtījumu: " . $e->getMessage();
            }
        }
    } else {
        $msg = implode("<br>", $errors);
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="lv">
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
            <h2>Veikt Pasūtījumu (Papildināt Krājumus)</h2>
        </header>

        <section class="form-section">
            <?php if ($msg): ?>
                <p class="message <?= strpos($msg, 'veiksmīgi') !== false ? 'success' : 'error' ?>">
                    <?= $msg ?>
                </p>
            <?php endif; ?>

            <form method="POST" class="order-form">
                <div class="form-group">
                    <label for="product_id">Produkts:</label>
                    <select id="product_id" name="product_id">
                        <option value="">Izvēlieties produktu</option>
                        <?php foreach ($products as $product): ?>
                            <option value="<?= htmlspecialchars($product['id']) ?>">
                                <?= htmlspecialchars($product['name']) ?> (Pašreizējais daudzums: <?= htmlspecialchars($product['quantity']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="quantity">Pasūtāmais Daudzums:</label>
                    <input type="text" id="quantity" name="quantity" value="<?= isset($_POST['quantity']) ? htmlspecialchars($_POST['quantity']) : '' ?>" placeholder="Ievadiet pasūtāmo daudzumu">
                </div>
                <button type="submit" class="button">Veikt Pasūtījumu</button>
            </form>
        </section>
    </main>
</div>

</body>
</html> 