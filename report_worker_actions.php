<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['permissions']['can_create_report']) || !$_SESSION['permissions']['can_create_report']) {
    header("Location: login.php");
    exit;
}

$msg = '';
$report_data = [];

$current_user_id = $_SESSION['user_id'];
$admin_role_id = 1; 

$stmt = $conn->prepare("
    SELECT pl.id, p.name AS product_name, u.username AS worker_name, pl.action_type, pl.action_description, pl.action_time
    FROM product_log pl
    JOIN products p ON pl.product_id = p.id
    JOIN users u ON pl.user_id = u.id
    JOIN roles r ON u.role_id = r.id
    WHERE r.role_name = 'Shelf Organizer' OR r.role_name = 'Admin'
    ORDER BY pl.action_time DESC
");
$stmt->bind_param("ii", $current_user_id, $admin_role_id);
$stmt->execute();
$result = $stmt->get_result();
$report_data = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Produktu Atskaite</title>
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
            <h2>Produktu Atskaite</h2>
        </header>

        <section class="table-section">
            <?php if ($msg): ?><p class="message"><?= $msg ?></p><?php endif; ?>
            <table class="product-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Produkts</th>
                        <th>Darbinieks</th>
                        <th>Darbība Tips</th>
                        <th>Apraksts</th>
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
                                <td><?= htmlspecialchars($row['action_type']) ?></td>
                                <td><?= htmlspecialchars($row['action_description']) ?></td>
                                <td><?= htmlspecialchars($row['action_time']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="no-products">Nav atrasta neviena darbinieka darbība.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>
</div>

</body>
</html> 