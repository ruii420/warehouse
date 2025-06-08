<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['permissions']['can_create_report']) || !$_SESSION['permissions']['can_create_report']) {
    header("Location: login.php");
    exit;
}

$msg = '';
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sagatavot Atskaiti</title>
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
            <h2>Sagatavot Atskaiti</h2>
        </header>

        <section class="report-section">
            <p>Select a report to generate:</p>
            <div class="report-buttons">
                <?php if (isset($_SESSION['permissions']['can_create_report'])): ?>
                    <?php if (isset($_SESSION['permissions']['can_make_order']) && $_SESSION['permissions']['can_make_order']):  ?>
                        <a href="report_warehouse_orders.php" class="action-button">Pasūtījumu pārskats</a>
                    <?php endif; ?>
                    <?php 

                        $is_shelf_organizer_or_admin = false;
                        if (isset($_SESSION['user_role'])) {
                            if ($_SESSION['user_role'] == 'Shelf Organizer' || $_SESSION['user_role'] == 'Admin') {
                                $is_shelf_organizer_or_admin = true;
                            }
                        }
                    ?>
                    <?php if ($is_shelf_organizer_or_admin): ?>
                        <a href="report_worker_actions.php" class="action-button">Produktu atskaite</a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <?php if ($msg): ?><p class="message"><?= $msg ?></p><?php endif; ?>
        </section>
    </main>
</div>

</body>
</html> 