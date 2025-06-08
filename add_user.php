<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['permissions']['can_add_user']) || !$_SESSION['permissions']['can_add_user']) {
    header("Location: login.php");
    exit;
}

$msg = '';
$roles = [];

$role_display_names = [
    'Admin' => 'Administrators',
    'Warehouse Worker' => 'Noliktavas Darbinieks',
    'Shelf Organizer' => 'Plauktu Krāvējs',
    'Regular User' => 'Parasts Lietotājs'
];

$roles_result = $conn->query("SELECT * FROM roles");
if ($roles_result) {
    $roles = $roles_result->fetch_all(MYSQLI_ASSOC);
}

$current_user_stmt = $conn->prepare("SELECT role_id FROM users WHERE id = ?");
$current_user_stmt->bind_param("i", $_SESSION['user_id']);
$current_user_stmt->execute();
$current_user_result = $current_user_stmt->get_result();
$current_user = $current_user_result->fetch_assoc();
$current_user_stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role_id = filter_var($_POST['role_id'], FILTER_VALIDATE_INT);
    $valid = true;
    $errors = [];


    if (empty($username)) {
        $valid = false;
        $errors[] = "Lietotājvārds ir obligāts.";
    } elseif (strlen($username) < 3) {
        $valid = false;
        $errors[] = "Lietotājvārdam jābūt vismaz 3 rakstzīmes garam.";
    } elseif (!preg_match('/^[a-zA-Z0-9]+$/', $username)) {
        $valid = false;
        $errors[] = "Lietotājvārds var saturēt tikai burtus un ciparus.";
    }


    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $valid = false;
        $errors[] = "Lietotājvārds jau eksistē.";
    }
    $stmt->close();

    
    if (empty($password)) {
        $valid = false;
        $errors[] = "Parole ir obligāta.";
    } elseif (strlen($password) < 6) {
        $valid = false;
        $errors[] = "Parolei jābūt vismaz 6 rakstzīmes garai.";
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $valid = false;
        $errors[] = "Parolei jāsatur vismaz viens lielais burts.";
    } elseif (!preg_match('/^[a-zA-Z0-9]+$/', $password)) {
        $valid = false;
        $errors[] = "Parole var saturēt tikai burtus un ciparus.";
    }

  
    if ($password !== $confirm_password) {
        $valid = false;
        $errors[] = "Paroles nesakrīt.";
    }

  
    if (!$role_id) {
        $valid = false;
        $errors[] = "Lūdzu izvēlieties derīgu lomu.";
    }

   
   

   if ($valid) {
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (username, password, role_id) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $username, $hashed_password, $role_id);

    if ($stmt->execute()) {
        $msg = "Lietotājs veiksmīgi pievienots!";
        $username = '';
    } else {
        $msg = "Kļūda pievienojot lietotāju: " . $stmt->error;
    }
    $stmt->close();
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
    <title>Pievienot Lietotāju</title>
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
                <a href="create_report.php" class="menu-item">📄 Sagatavot atskaiti</a>
            <?php endif; ?>
            <?php if (isset($_SESSION['permissions']['can_add_product']) && $_SESSION['permissions']['can_add_product']): ?>
                <a href="add_product.php" class="menu-item">➕ Pievienot produktu</a>
            <?php endif; ?>
            <?php if (isset($_SESSION['permissions']['can_add_user']) && $_SESSION['permissions']['can_add_user']): ?>
                <a href="add_user.php" class="menu-item active">👤 Pievienot lietotāju</a>
            <?php endif; ?>
            <?php if (isset($_SESSION['permissions']['can_manage_users']) && $_SESSION['permissions']['can_manage_users']): ?>
                <a href="manage_users.php" class="menu-item">👥 Lietotāji</a>
            <?php endif; ?>
            <a href="logout.php" class="menu-item">➡️ Iziet</a>
        </nav>
    </aside>

    <main class="content">
        <header class="page-header">
            <h2>Pievienot Lietotāju</h2>
        </header>

        <section class="form-section">
            <?php if ($msg): ?>
                <p class="message <?= strpos($msg, 'veiksmīgi') !== false ? 'success' : 'error' ?>">
                    <?= $msg ?>
                </p>
            <?php endif; ?>

            <form method="POST" class="user-form">
                <div class="form-group">
                    <label for="username">Lietotājvārds:</label>
                    <input type="text" id="username" name="username" 
                           value="<?= isset($username) ? htmlspecialchars($username) : '' ?>">
                </div>
                <div class="form-group">
                    <label for="password">Parole:</label>
                    <input type="password" id="password" name="password">
                </div>
                <div class="form-group">
                    <label for="confirm_password">Apstiprināt Paroli:</label>
                    <input type="password" id="confirm_password" name="confirm_password">
                </div>
                <div class="form-group">
                    <label for="role_id">Loma:</label>
                    <select id="role_id" name="role_id">
                        <option value="">Izvēlieties lomu</option>
                        <?php foreach ($roles as $role): ?>
                            <?php 
                            
                            if ($role['id'] != 1 || $current_user['role_id'] == 1): 
                               
                                $display_name = $role_display_names[$role['role_name']] ?? $role['role_name'];
                            ?>
                                <option value="<?= htmlspecialchars($role['id']) ?>">
                                    <?= htmlspecialchars($display_name) ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="button">Pievienot Lietotāju</button>
            </form>
        </section>
    </main>
</div>

</body>
</html> 