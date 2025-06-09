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

$shelf_filter = isset($_GET['shelf_id']) ? (int)$_GET['shelf_id'] : null;

$shelves_result = $conn->query("SELECT id, shelf_code, section FROM shelves ORDER BY section, shelf_code");
$shelves = $shelves_result->fetch_all(MYSQLI_ASSOC);


if ($valid_dates) {
    $query = "
        SELECT 
            sal.id,
            sal.action_time,
            p.name as product_name,
            s1.shelf_code as from_shelf,
            s2.shelf_code as to_shelf,
            sal.quantity,
            sal.action_type,
            u.username as user_name,
            sal.notes
        FROM shelf_activity_log sal
        LEFT JOIN products p ON sal.product_id = p.id
        LEFT JOIN shelves s1 ON sal.from_shelf_id = s1.id
        LEFT JOIN shelves s2 ON sal.to_shelf_id = s2.id
        LEFT JOIN users u ON sal.user_id = u.id
        WHERE DATE(sal.action_time) BETWEEN ? AND ?
    ";

    if ($shelf_filter) {
        $query .= " AND (sal.from_shelf_id = ? OR sal.to_shelf_id = ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssii", $start_date, $end_date, $shelf_filter, $shelf_filter);
    } else {
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $start_date, $end_date);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $report_data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}


$total_movements = count($report_data);
$total_quantity = 0;
$movements_by_type = [];

foreach ($report_data as $movement) {
    $total_quantity += $movement['quantity'];
    $movements_by_type[$movement['action_type']] = ($movements_by_type[$movement['action_type']] ?? 0) + 1;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <title>Plauktu Aktivitātes Atskaite</title>
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
                <h2>Plauktu Aktivitātes Atskaite</h2>
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
                        <label for="shelf_id">Plaukts:</label>
                        <select name="shelf_id" id="shelf_id">
                            <option value="">Visi plaukti</option>
                            <?php foreach ($shelves as $shelf): ?>
                                <option value="<?= $shelf['id'] ?>" 
                                    <?= $shelf_filter === $shelf['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($shelf['shelf_code']) ?> - Sekcija <?= substr($shelf['section'], -1) ?>
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
                        <h3>Kopējās Kustības</h3>
                        <p class="stat-number"><?= $total_movements ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Kopējais Pārvietotais Daudzums</h3>
                        <p class="stat-number"><?= $total_quantity ?></p>
                    </div>
                </div>
            </section>

            <section class="table-section">
                <table class="product-table">
                    <thead>
                        <tr>
                            <th>Datums</th>
                            <th>Produkts</th>
                            <th>No Plaukta</th>
                            <th>Uz Plauktu</th>
                            <th>Daudzums</th>
                            <th>Darbība</th>
                            <th>Darbinieks</th>
                            <th>Piezīmes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($report_data)): ?>
                            <?php foreach ($report_data as $row): ?>
                                <?php 
                                
                                $action_type_lv = [
                                    'place' => 'Novietošana',
                                    'remove' => 'Izņemšana',
                                    'transfer' => 'Pārvietošana'
                                ][$row['action_type']] ?? $row['action_type'];
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['action_time']) ?></td>
                                    <td><?= htmlspecialchars($row['product_name'] ?? '') ?></td>
                                    <td><?= $row['from_shelf'] ? htmlspecialchars($row['from_shelf']) : '-' ?></td>
                                    <td><?= $row['to_shelf'] ? htmlspecialchars($row['to_shelf']) : '-' ?></td>
                                    <td><?= htmlspecialchars($row['quantity'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($action_type_lv ?? '') ?></td>
                                    <td><?= htmlspecialchars($row['user_name'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($row['notes'] ?? '') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="8" class="no-data">Nav atrasta neviena plauktu aktivitāte.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>
</body>
</html> 