<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['permissions']['can_create_report']) || !$_SESSION['permissions']['can_create_report']) {
    header("Location: login.php");
    exit;
}

$msg = '';
$report_data = [];

$warehouse_worker_role_id = null;
$role_stmt = $conn->prepare("SELECT id FROM roles WHERE role_name = ?");
$role_name = 'Warehouse Worker';
$role_stmt->bind_param("s", $role_name);
$role_stmt->execute();
$role_result = $role_stmt->get_result();
if ($row = $role_result->fetch_assoc()) {
    $warehouse_worker_role_id = $row['id'];
}
$role_stmt->close();

if ($warehouse_worker_role_id !== null) {
    $stmt = $conn->prepare("
        SELECT o.id, p.name AS product_name, u.username AS worker_name, o.order_quantity, o.old_quantity, o.new_quantity, o.order_time
        FROM orders o
        JOIN products p ON o.product_id = p.id
        JOIN users u ON o.user_id = u.id
        WHERE u.role_id = ? -- Filter by Warehouse Worker role
        ORDER BY o.order_time DESC
    ");
    $stmt->bind_param("i", $warehouse_worker_role_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $report_data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $msg = "Warehouse Worker role not found.";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pasūtījumu Pārskats</title>
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
                <a href="create_report.php" class="menu-item active">📄 Sagatavot atskaiti</a>
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
            <h2>Pasūtījumu Pārskats</h2>
        </header>

        <section class="table-section">
            <?php if ($msg): ?><p class="message"><?= $msg ?></p><?php endif; ?>
            <table class="product-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Produkts</th>
                        <th>Darbinieks</th>
                        <th>Pasūtījuma Daudzums</th>
                        <th>Vecais Daudzums</th>
                        <th>Jauns Daudzums</th>
                        <th>Datums</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($report_data)): ?>
                        <?php foreach ($report_data as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['id']) ?></td>
                                <td><?= htmlspecialchars($row['product_name']) ?></td>
                                <td><?= htmlspecialchars($row['worker_name']) ?></td>
                                <td><?= htmlspecialchars($row['order_quantity']) ?></td>
                                <td><?= htmlspecialchars($row['old_quantity']) ?></td>
                                <td><?= htmlspecialchars($row['new_quantity']) ?></td>
                                <td><?= htmlspecialchars($row['order_time']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="no-products">Nav atrasts neviens pasūtījums no noliktavas darbinieka.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>
</div>

</body>
</html> 