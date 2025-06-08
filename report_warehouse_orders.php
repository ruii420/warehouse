<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['permissions']['can_create_report']) || !$_SESSION['permissions']['can_create_report']) {
    header("Location: login.php");
    exit;
}

$msg = '';
$report_data = [];
$valid_dates = true;

$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $start_date) || !strtotime($start_date)) {
    $msg = "Nederīgs sākuma datuma formāts. Lūdzu izmantojiet GGGG-MM-DD formātu.";
    $start_date = date('Y-m-d', strtotime('-30 days'));
    $valid_dates = false;
}

if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $end_date) || !strtotime($end_date)) {
    $msg = "Nederīgs beigu datuma formāts. Lūdzu izmantojiet GGGG-MM-DD formātu.";
    $end_date = date('Y-m-d');
    $valid_dates = false;
}

if (strtotime($end_date) < strtotime($start_date)) {
    $msg = "Beigu datums nevar būt agrāks par sākuma datumu";
    $end_date = date('Y-m-d');
    $start_date = date('Y-m-d', strtotime('-30 days'));
    $valid_dates = false;
}

if (strtotime($start_date) > strtotime('today')) {
    $msg = "Sākuma datums nevar būt nākotnē";
    $start_date = date('Y-m-d');
    $valid_dates = false;
}

if (strtotime($end_date) > strtotime('today')) {
    $msg = "Beigu datums nevar būt pēc šodienas datuma";
    $end_date = date('Y-m-d');
    $valid_dates = false;
}

if ($valid_dates) {
    $query = "
        SELECT 
            o.id,
            o.order_time as order_date,
            o.order_quantity,
            o.old_quantity,
            o.new_quantity,
            p.name as product_name,
            p.price,
            u.username as ordered_by
        FROM orders o
        LEFT JOIN products p ON o.product_id = p.id
        LEFT JOIN users u ON o.user_id = u.id
        WHERE DATE(o.order_time) BETWEEN ? AND ?
        ORDER BY o.order_time DESC
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    $report_data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$total_orders = count($report_data);
$total_quantity = 0;
$total_amount = 0;

foreach ($report_data as &$order) {
    $total_quantity += $order['order_quantity'];
    $order['total_amount'] = $order['order_quantity'] * $order['price'];
    $total_amount += $order['total_amount'];
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <title>Noliktavas Pasūtījumu Atskaite</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="wrapper">
        <aside class="sidebar">
            <h1 class="logo">Stash</h1>
            <nav class="menu">
                <a href="index.php" class="menu-item">🏠 Sākums</a>
                <a href="create_report.php" class="menu-item">📄 Atskaites</a>
                <a href="logout.php" class="menu-item">➡️ Iziet</a>
            </nav>
        </aside>

        <main class="content">
            <header class="page-header">
                <h2>Noliktavas Pasūtījumu Atskaite</h2>
            </header>

            <section class="filters">
                <?php if ($msg): ?>
                    <p class="message error"><?= $msg ?></p>
                <?php endif; ?>
                <form method="GET" class="form-inline">
                    <div class="form-group">
                        <label for="start_date">No:</label>
                        <input type="date" id="start_date" name="start_date" 
                               value="<?= htmlspecialchars($start_date) ?>"
                               max="<?= date('Y-m-d') ?>"
                               required>
                    </div>
                    <div class="form-group">
                        <label for="end_date">Līdz:</label>
                        <input type="date" id="end_date" name="end_date" 
                               value="<?= htmlspecialchars($end_date) ?>"
                               max="<?= date('Y-m-d') ?>"
                               required>
                    </div>
                    <button type="submit" class="button">Filtrēt</button>
                </form>
            </section>

            <section class="statistics-section">
                <div class="stat-cards">
                    <div class="stat-card">
                        <h3>Kopējie Pasūtījumi</h3>
                        <p class="stat-number"><?= $total_orders ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Kopējais Daudzums</h3>
                        <p class="stat-number"><?= $total_quantity ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Kopējā Summa</h3>
                        <p class="stat-number"><?= number_format($total_amount, 2) ?> €</p>
                    </div>
                </div>
            </section>

            <section class="table-section">
                <table class="product-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Datums</th>
                            <th>Pasūtītājs</th>
                            <th>Produkts</th>
                            <th>Daudzums</th>
                            <th>Vecais Daudzums</th>
                            <th>Jaunais Daudzums</th>
                            <th>Summa</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($report_data)): ?>
                            <?php foreach ($report_data as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['id']) ?></td>
                                    <td><?= htmlspecialchars($row['order_date']) ?></td>
                                    <td><?= htmlspecialchars($row['ordered_by'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($row['product_name'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($row['order_quantity']) ?></td>
                                    <td><?= htmlspecialchars($row['old_quantity']) ?></td>
                                    <td><?= htmlspecialchars($row['new_quantity']) ?></td>
                                    <td><?= number_format($row['total_amount'], 2) ?> €</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="8" class="no-data">Nav atrasts neviens pasūtījums.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>

</body>
</html> 