<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['permissions']['can_manage_inventory']) || !$_SESSION['permissions']['can_manage_inventory']) {
    header("Location: login.php");
    exit;
}

$msg = '';
$success_msg = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['action']) || !isset($_POST['product_id']) || !isset($_POST['quantity'])) {
        $msg = "Kļūda: Trūkst nepieciešamo lauku.";
    } else {
        $product_id = filter_var($_POST['product_id'], FILTER_VALIDATE_INT);
        $quantity = filter_var($_POST['quantity'], FILTER_VALIDATE_INT);
        $to_shelf_id = isset($_POST['to_shelf_id']) ? filter_var($_POST['to_shelf_id'], FILTER_VALIDATE_INT) : null;
        $from_shelf_id = isset($_POST['from_shelf_id']) ? filter_var($_POST['from_shelf_id'], FILTER_VALIDATE_INT) : null;
        $action = $_POST['action'];

        if ($product_id === false || $quantity === false || $quantity <= 0) {
            $msg = "Kļūda: Nederīgs produkta ID vai daudzums.";
        } else if ($action === 'place' && ($to_shelf_id === false || $to_shelf_id === null)) {
            $msg = "Kļūda: Jānorāda mērķa plaukts.";
        } else if ($action === 'transfer' && ($from_shelf_id === false || $from_shelf_id === null || $to_shelf_id === false || $to_shelf_id === null)) {
            $msg = "Kļūda: Jānorāda abi plaukti.";
        } else {
            $product_stmt = $conn->prepare("SELECT quantity, name FROM products WHERE id = ?");
            $product_stmt->bind_param("i", $product_id);
            $product_stmt->execute();
            $product = $product_stmt->get_result()->fetch_assoc();

            if (!$product) {
                $msg = "Kļūda: Produkts nav atrasts.";
            } else if ($action === 'place') {
                if ($product['quantity'] < $quantity) {
                    $msg = "Kļūda: Nav pietiekams produkta daudzums noliktavā.";
                } else {
                    $shelf_stmt = $conn->prepare("SELECT capacity, shelf_code,
                        (SELECT COALESCE(SUM(quantity), 0) FROM product_locations WHERE shelf_id = shelves.id) as used_capacity 
                        FROM shelves WHERE id = ?");
                    $shelf_stmt->bind_param("i", $to_shelf_id);
                    $shelf_stmt->execute();
                    $shelf = $shelf_stmt->get_result()->fetch_assoc();

                    if (!$shelf) {
                        $msg = "Kļūda: Plaukts nav atrasts.";
                    } else if ($shelf['capacity'] < ($shelf['used_capacity'] + $quantity)) {
                        $msg = "Kļūda: Nav pietiekamas vietas plauktā. (Pieejams: " . ($shelf['capacity'] - $shelf['used_capacity']) . ")";
                    } else {
                        $update_product = $conn->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");
                        $update_product->bind_param("ii", $quantity, $product_id);
                        $update_product->execute();

                        $place_stmt = $conn->prepare("INSERT INTO product_locations (product_id, shelf_id, quantity, updated_by) VALUES (?, ?, ?, ?)");
                        $place_stmt->bind_param("iiii", $product_id, $to_shelf_id, $quantity, $_SESSION['user_id']);
                        $place_stmt->execute();

                        $log_stmt = $conn->prepare("INSERT INTO shelf_activity_log (product_id, to_shelf_id, quantity, action_type, user_id, notes) VALUES (?, ?, ?, 'Novietošana', ?, ?)");
                        $notes = "Prece novietota plauktā";
                        $log_stmt->bind_param("iiiis", $product_id, $to_shelf_id, $quantity, $_SESSION['user_id'], $notes);
                        $log_stmt->execute();

                        $action_type = 'Novietošana';
                        $new_quantity = $product['quantity'] - $quantity;
                        $action_description = "Novietoti " . $quantity . " " . $product['name'] . " plauktā " . $shelf['shelf_code'];
                        
                        $inventory_log_stmt = $conn->prepare("INSERT INTO inventory_log (product_id, user_id, action_type, quantity_change, new_quantity, action_description) VALUES (?, ?, ?, ?, ?, ?)");
                        $inventory_log_stmt->bind_param("iisiss", $product_id, $_SESSION['user_id'], $action_type, $quantity, $new_quantity, $action_description);
                        $inventory_log_stmt->execute();

                        $success_msg = "Produkts veiksmīgi novietots plauktā!";
                    }
                }
            } else if ($action === 'transfer') {
                $source_stmt = $conn->prepare("SELECT quantity FROM product_locations WHERE product_id = ? AND shelf_id = ?");
                $source_stmt->bind_param("ii", $product_id, $from_shelf_id);
                $source_stmt->execute();
                $source = $source_stmt->get_result()->fetch_assoc();

                if (!$source || $source['quantity'] < $quantity) {
                    $msg = "Kļūda: Nav pietiekama daudzuma avota plauktā.";
                } else {
                    $shelf_stmt = $conn->prepare("SELECT capacity, 
                        (SELECT COALESCE(SUM(quantity), 0) FROM product_locations WHERE shelf_id = shelves.id) as used_capacity 
                        FROM shelves WHERE id = ?");
                    $shelf_stmt->bind_param("i", $to_shelf_id);
                    $shelf_stmt->execute();
                    $shelf = $shelf_stmt->get_result()->fetch_assoc();

                    if ($shelf['capacity'] < ($shelf['used_capacity'] + $quantity)) {
                        $msg = "Kļūda: Nav pietiekamas vietas galamērķa plauktā.";
                    } else {
                        $update_source = $conn->prepare("UPDATE product_locations SET quantity = quantity - ? WHERE product_id = ? AND shelf_id = ?");
                        $update_source->bind_param("iii", $quantity, $product_id, $from_shelf_id);
                        $update_source->execute();

                        $update_dest = $conn->prepare("INSERT INTO product_locations (product_id, shelf_id, quantity, updated_by) 
                            VALUES (?, ?, ?, ?) 
                            ON DUPLICATE KEY UPDATE quantity = quantity + ?");
                        $update_dest->bind_param("iiiii", $product_id, $to_shelf_id, $quantity, $_SESSION['user_id'], $quantity);
                        $update_dest->execute();

                        $log_stmt = $conn->prepare("INSERT INTO shelf_activity_log (product_id, from_shelf_id, to_shelf_id, quantity, action_type, user_id, notes) VALUES (?, ?, ?, ?, 'Pārvietošana', ?, ?)");
                        $notes = "Prece pārvietota starp plauktiem";
                        $log_stmt->bind_param("iiiiss", $product_id, $from_shelf_id, $to_shelf_id, $quantity, $_SESSION['user_id'], $notes);
                        $log_stmt->execute();

                        $success_msg = "Produkts veiksmīgi pārvietots!";
                    }
                }
            }
        }
    }
}

$products_result = $conn->query("SELECT id, name, category, quantity FROM products ORDER BY name");
$products = $products_result->fetch_all(MYSQLI_ASSOC);


$shelves_result = $conn->query("
    SELECT s.*, 
        COALESCE(SUM(pl.quantity), 0) as used_capacity
    FROM shelves s
    LEFT JOIN product_locations pl ON s.id = pl.shelf_id
    GROUP BY s.id
    ORDER BY s.section, s.shelf_code
");
$shelves = $shelves_result->fetch_all(MYSQLI_ASSOC);


$locations_result = $conn->query("
    SELECT 
        p.id as product_id,
        p.name as product_name,
        s.shelf_code,
        CONCAT('Sekcija ', SUBSTRING(s.section, -1)) as section,
        pl.quantity
    FROM product_locations pl
    JOIN shelves s ON pl.shelf_id = s.id
    JOIN products p ON pl.product_id = p.id
    ORDER BY p.name, s.shelf_code
");
$locations = $locations_result->fetch_all(MYSQLI_ASSOC);


$grouped_locations = [];
foreach ($locations as $location) {
    $product_id = $location['product_id'];
    if (!isset($grouped_locations[$product_id])) {
        $grouped_locations[$product_id] = [
            'product_id' => $product_id,
            'product_name' => $location['product_name'],
            'total_quantity' => 0,
            'locations' => []
        ];
    }
    $grouped_locations[$product_id]['total_quantity'] += $location['quantity'];
    $grouped_locations[$product_id]['locations'][] = [
        'shelf_code' => $location['shelf_code'],
        'quantity' => $location['quantity']
    ];
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <title>Izvietot Preces</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="wrapper">
    <aside class="sidebar">
        <h1 class="logo">Stash</h1>
        <nav class="menu">
            <a href="index.php" class="menu-item">🏠 Sākums</a>
            <?php if (isset($_SESSION['permissions']['can_manage_inventory']) && $_SESSION['permissions']['can_manage_inventory']): ?>
                <a href="manage_inventory.php" class="menu-item active">📦 Izvietot preces</a>
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
            <h2>Izvietot Preces</h2>
        </header>

        <?php if ($msg): ?>
            <p class="message error"><?= htmlspecialchars($msg) ?></p>
        <?php endif; ?>
        <?php if ($success_msg): ?>
            <p class="message success"><?= htmlspecialchars($success_msg) ?></p>
        <?php endif; ?>

        
        <section class="action-form">
            <h3>Novietot Produktu</h3>
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="product_id">Produkts:</label>
                        <select name="product_id" id="product_id" required>
                            <option value="">Izvēlieties produktu</option>
                            <?php foreach ($products as $product): ?>
                                <option value="<?= $product['id'] ?>">
                                    <?= htmlspecialchars($product['name']) ?> (Pieejams: <?= $product['quantity'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="quantity">Daudzums:</label>
                        <input type="number" name="quantity" id="quantity" min="1" required>
                    </div>
                    <div class="form-group">
                        <label for="to_shelf_id">Plaukts:</label>
                        <select name="to_shelf_id" id="to_shelf_id" required>
                            <option value="">Izvēlieties plauktu</option>
                            <?php foreach ($shelves as $shelf): ?>
                                <option value="<?= $shelf['id'] ?>">
                                    <?= htmlspecialchars($shelf['shelf_code']) ?> 
                                    (<?= $shelf['used_capacity'] ?>/<?= $shelf['capacity'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <input type="hidden" name="action" value="place">
                <button type="submit" class="button">Novietot Produktu</button>
            </form>
        </section>

      
        <section class="action-form">
            <h3>Pārvietot Produktu</h3>
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="transfer_product_id">Produkts un Avota Plaukts:</label>
                        <select name="product_id" id="transfer_product_id">
                            <option value="">Izvēlieties produktu un plauktu</option>
                            <?php foreach ($grouped_locations as $group): ?>
                                <?php foreach ($group['locations'] as $loc): ?>
                                    <option value="<?= $group['product_id'] ?>" data-shelf="<?= $loc['shelf_code'] ?>">
                                        <?= htmlspecialchars($group['product_name']) ?> - 
                                        <?= htmlspecialchars($loc['shelf_code']) ?> 
                                        (Daudzums: <?= $loc['quantity'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="transfer_quantity">Daudzums:</label>
                        <input type="number" name="quantity" id="transfer_quantity" min="1">
                    </div>
                    <div class="form-group">
                        <label for="transfer_to_shelf_id">Uz Plauktu:</label>
                        <select name="to_shelf_id" id="transfer_to_shelf_id">
                            <option value="">Izvēlieties galamērķa plauktu</option>
                            <?php foreach ($shelves as $shelf): ?>
                                <option value="<?= $shelf['id'] ?>">
                                    <?= htmlspecialchars($shelf['shelf_code']) ?> 
                                    (<?= $shelf['used_capacity'] ?>/<?= $shelf['capacity'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <input type="hidden" name="action" value="transfer">
                <input type="hidden" name="from_shelf_id" id="from_shelf_id">
                <button type="submit" class="button">Pārvietot Produktu</button>
            </form>
        </section>

   
        <section>
            <h3>Plauktu Pārskats</h3>
            <div class="shelf-grid">
                <?php foreach ($shelves as $shelf): ?>
                    <div class="shelf-card">
                        <h3><?= htmlspecialchars($shelf['shelf_code']) ?> - Sekcija <?= substr($shelf['section'], -1) ?></h3>
                        <p>Kapacitāte: <?= $shelf['used_capacity'] ?>/<?= $shelf['capacity'] ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

    
        <section class="table-section">
            <h3>Pašreizējās Produktu Atrašanās Vietas</h3>
            <table class="product-table">
                <thead>
                    <tr>
                        <th>Produkts</th>
                        <th>Plaukts</th>
                        <th>Sekcija</th>
                        <th>Daudzums</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($locations)): ?>
                        <?php 
                        $current_product = '';
                        foreach ($locations as $location): 
                            $is_new_product = $current_product !== $location['product_name'];
                            if ($is_new_product) {
                                $current_product = $location['product_name'];
                            }
                        ?>
                            <tr <?= $is_new_product ? '' : 'class="same-product"' ?>>
                                <td>
                                    <?php if ($is_new_product): ?>
                                        <?= htmlspecialchars($location['product_name']) ?>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($location['shelf_code']) ?></td>
                                <td><?= htmlspecialchars($location['section']) ?></td>
                                <td><?= htmlspecialchars($location['quantity']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="no-data">Nav atrasta neviena produkta atrašanās vieta.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>
</div>

<script>
document.getElementById('transfer_product_id').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const shelfId = selectedOption.getAttribute('data-shelf');
    document.getElementById('from_shelf_id').value = shelfId;
});
</script>

</body>
</html> 