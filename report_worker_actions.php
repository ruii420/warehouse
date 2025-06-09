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
    $msg = "Nederīgs beigu datuma formātu. Lūdzu izmantojiet GGGG-MM-DD formātu.";
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

$worker_filter = isset($_GET['worker_id']) ? $_GET['worker_id'] : null;

$workers_result = $conn->query("SELECT id, username FROM users ORDER BY username");
$workers = $workers_result->fetch_all(MYSQLI_ASSOC);

if ($valid_dates) {
    $query = "
        SELECT 
            il.id,
            p.name AS product_name,
            u.username AS worker_name,
            il.action_type,
            il.quantity_change as quantity,
            il.action_description as notes,
            il.log_time as created_at
        FROM inventory_log il
        LEFT JOIN products p ON il.product_id = p.id
        LEFT JOIN users u ON il.user_id = u.id
        WHERE DATE(il.log_time) BETWEEN ? AND ?
    ";

    if ($worker_filter) {
        $query .= " AND il.user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssi", $start_date, $end_date, $worker_filter);
    } else {
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $start_date, $end_date);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $report_data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$total_actions = count($report_data);
$total_quantity = 0;
$actions_by_worker = [];

foreach ($report_data as $action) {
    $total_quantity += abs($action['quantity']);
    $worker = $action['worker_name'];
    $actions_by_worker[$worker] = ($actions_by_worker[$worker] ?? 0) + 1;
}

$most_active_worker = !empty($actions_by_worker) ? array_search(max($actions_by_worker), $actions_by_worker) : '-';

$conn->close();
?>

<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <title>Darbinieku Darbību Atskaite</title>
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
                <h2>Darbinieku Darbību Atskaite</h2>
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
                    <div class="form-group">
                        <label for="worker_id">Darbinieks:</label>
                        <select name="worker_id" id="worker_id">
                            <option value="">Visi darbinieki</option>
                            <?php foreach ($workers as $worker): ?>
                                <option value="<?= $worker['id'] ?>" 
                                    <?= $worker_filter === $worker['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($worker['username']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="button">Filtrēt</button>
                </form>
            </section>

            <section class="statistics-section">
                <div class="stat-cards">
                    <div class="stat-card">
                        <h3>Kopējās Darbības</h3>
                        <p class="stat-number"><?= $total_actions ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Aktīvākais Darbinieks</h3>
                        <p class="stat-number"><?= htmlspecialchars($most_active_worker) ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Kopējais Daudzums</h3>
                        <p class="stat-number"><?= $total_quantity ?></p>
                    </div>
                </div>
            </section>

            <section class="table-section">
                <table class="product-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Datums</th>
                            <th>Produkts</th>
                            <th>Darbinieks</th>
                            <th>Darbība</th>
                            <th>Daudzums</th>
                            <th>Piezīmes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($report_data)): ?>
                            <?php foreach ($report_data as $row): ?>
                                <?php
                                $action_type_lv = [
                                    'Order' => 'Pasūtījums',
                                    'Pievienošana' => 'Pievienošana',
                                    'Rediģēšana' => 'Rediģēšana',
                                    'Daudzuma_maiņa' => 'Daudzuma maiņa',
                                    'Novietošana' => 'Novietošana',
                                    'Pārvietošana' => 'Pārvietošana',
                                    'Delete' => 'Dzēšana'
                                ][$row['action_type']] ?? $row['action_type'];
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['id']) ?></td>
                                    <td><?= htmlspecialchars($row['created_at']) ?></td>
                                    <td><?= htmlspecialchars($row['product_name'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($row['worker_name'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($action_type_lv ?? '') ?></td>
                                    <td><?= htmlspecialchars($row['quantity'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($row['notes'] ?? '') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="no-data">Nav atrasta neviena darbinieka darbība.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>

</body>
</html> 