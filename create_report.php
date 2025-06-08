<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['permissions']['can_create_report']) || !$_SESSION['permissions']['can_create_report']) {
    header("Location: login.php");
    exit;
}

$msg = '';
$user_role = $_SESSION['role'] ?? '';


$report_types = [];

$report_types['warehouse_orders'] = [
    'title' => 'Pasūtījumu Atskaites',
    'description' => 'Pārskats par noliktavas pasūtījumiem',
    'url' => 'report_warehouse_orders.php'
];

if ($user_role === 'shelf_organizer' || $user_role === 'admin') {
    $report_types['shelf_activity'] = [
        'title' => 'Plauktu Atskaites',
        'description' => 'Pārskats par preču kustību plauktos',
        'url' => 'report_shelf_activity.php'
    ];
}

if ($user_role === 'admin') {
    $report_types['worker_actions'] = [
        'title' => 'Darbinieku Atskaites',
        'description' => 'Pārskats par darbinieku veiktajām darbībām',
        'url' => 'report_worker_actions.php'
    ];
}
?>

<!DOCTYPE html>
<html lang="lv">
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
                    <a href="create_report.php" class="menu-item active">📄 Atskaites</a>
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
                <h2>Atskaites</h2>
            </header>

            <section class="report-section">
                <div class="report-grid">
                    <?php foreach ($report_types as $type => $report): ?>
                        <div class="report-card">
                            <h3><?= htmlspecialchars($report['title']) ?></h3>
                            <p><?= htmlspecialchars($report['description']) ?></p>
                            <a href="<?= htmlspecialchars($report['url']) ?>" class="action-button">Skatīt atskaiti</a>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if ($msg): ?><p class="message"><?= $msg ?></p><?php endif; ?>
            </section>
        </main>
    </div>
</body>
</html> 