<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || (!isset($_SESSION['permissions']['can_add_product']) || !$_SESSION['permissions']['can_add_product']) && (!isset($_SESSION['permissions']['can_add_user']) || !$_SESSION['permissions']['can_add_user']) && (!isset($_SESSION['permissions']['can_manage_users']) || !$_SESSION['permissions']['can_manage_users'])) {
    header("Location: login.php");
    exit;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Datu ievade</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="wrapper">
    <aside class="sidebar">
        <h1 class="logo">Stash</h1>
        <nav class="menu">
            <a href="index.php" class="menu-item">Sākums</a>
            <?php if (isset($_SESSION['permissions']['can_manage_inventory']) && $_SESSION['permissions']['can_manage_inventory']): ?>
                <a href="manage_inventory.php" class="menu-item">Izvietot preces</a>
            <?php endif; ?>
            <?php if (isset($_SESSION['permissions']['can_create_report']) && $_SESSION['permissions']['can_create_report']): ?>
                <a href="create_report.php" class="menu-item">Sagatavot atskaiti</a>
            <?php endif; ?>
            <?php if ((isset($_SESSION['permissions']['can_add_product']) && $_SESSION['permissions']['can_add_product']) || (isset($_SESSION['permissions']['can_add_user']) && $_SESSION['permissions']['can_add_user']) || (isset($_SESSION['permissions']['can_manage_users']) && $_SESSION['permissions']['can_manage_users'])): ?>
                <a href="data_entry.php" class="menu-item active">Datu ievade</a>
            <?php endif; ?>
            <a href="logout.php" class="menu-item">Iziet</a>
        </nav>
    </aside>

    <main class="content">
        <header class="page-header">
            <h2>Datu ievade</h2>
        </header>

        <section class="data-entry-links">
            <?php if (isset($_SESSION['permissions']['can_add_product']) && $_SESSION['permissions']['can_add_product']): ?>
                <a href="add_product.php" class="action-button">Pievienot produktu</a>
            <?php endif; ?>

            <?php if (isset($_SESSION['permissions']['can_add_user']) && $_SESSION['permissions']['can_add_user']): ?>
                <a href="add_user.php" class="action-button">Pievienot lietotāju</a>
            <?php endif; ?>

            <?php if (isset($_SESSION['permissions']['can_manage_users']) && $_SESSION['permissions']['can_manage_users']): ?>
                <a href="manage_users.php" class="action-button">Lietotāji</a>
            <?php endif; ?>
        </section>
    </main>
</div>

</body>
</html> 