<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['permissions']['can_manage_users']) || !$_SESSION['permissions']['can_manage_users']) {
    header("Location: login.php");
    exit;
}

$msg = '';
$user = null;
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

if (isset($_GET['id'])) {
    $user_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if (!$user_id) {
        $msg = "Nederīgs lietotāja ID.";
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$user) {
            $msg = "Lietotājs nav atrasts.";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    $username = trim($_POST['username']);
    $password = $_POST['password']; 
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

    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
    $stmt->bind_param("si", $username, $user['id']);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $valid = false;
        $errors[] = "Lietotājvārds jau eksistē.";
    }
    $stmt->close();

    if (!$role_id) {
        $valid = false;
        $errors[] = "Lūdzu izvēlieties derīgu lomu.";
    }

    if ($user['id'] == $_SESSION['user_id'] && $role_id != $user['role_id']) {
        $admin_role_id = 1; 
        $admin_count_stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE role_id = ?");
        $admin_count_stmt->bind_param("i", $admin_role_id);
        $admin_count_stmt->execute();
        $admin_count_result = $admin_count_stmt->get_result();
        $admin_count = $admin_count_result->fetch_assoc()['count'];
        $admin_count_stmt->close();

        if ($user['role_id'] == $admin_role_id && $admin_count <= 1) {
            $valid = false;
            $errors[] = "Nevar mainīt savu lomu, jo jūs esat vienīgais administrators.";
        }
    }

    if (!empty($password)) {
        if (strlen($password) < 6) {
            $valid = false;
            $errors[] = "Parolei jābūt vismaz 6 rakstzīmes garai.";
        } elseif (!preg_match('/[A-Z]/', $password)) {
            $valid = false;
            $errors[] = "Parolei jāsatur vismaz viens lielais burts.";
        } elseif (!preg_match('/^[a-zA-Z0-9]+$/', $password)) {
            $valid = false;
            $errors[] = "Parole var saturēt tikai burtus un ciparus.";
        }
    }

    if ($role_id != $user['role_id']) {
        if ($current_user['role_id'] != 1) { 
            $valid = false;
            $errors[] = "Tikai administrators var mainīt lietotāju lomas.";
        }
    }

    if ($valid) {
        try {
            $conn->begin_transaction();

            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET username = ?, password = ?, role_id = ? WHERE id = ?");
                $stmt->bind_param("ssii", $username, $hashed_password, $role_id, $user['id']);
            } else {
                $stmt = $conn->prepare("UPDATE users SET username = ?, role_id = ? WHERE id = ?");
                $stmt->bind_param("sii", $username, $role_id, $user['id']);
            }

            if ($stmt->execute()) {
                $conn->commit();
                $msg = "Lietotājs veiksmīgi atjaunināts!";
                $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->bind_param("i", $user['id']);
                $stmt->execute();
                $user = $stmt->get_result()->fetch_assoc();
            } else {
                throw new Exception($stmt->error);
            }
            $stmt->close();
        } catch (Exception $e) {
            $conn->rollback();
            $msg = "Kļūda atjauninot lietotāju: " . $e->getMessage();
        }
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
    <title>Rediģēt Lietotāju</title>
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
            <h2>Rediģēt Lietotāju</h2>
        </header>

        <section class="form-section">
            <?php if ($msg): ?>
                <p class="message <?= strpos($msg, 'veiksmīgi') !== false ? 'success' : 'error' ?>">
                    <?= $msg ?>
                </p>
            <?php endif; ?>

            <?php if ($user): ?>
                <form method="POST" class="user-form">
                    <div class="form-group">
                        <label for="username">Lietotājvārds:</label>
                        <input type="text" id="username" name="username" 
                               value="<?= htmlspecialchars($user['username']) ?>">
                    </div>
                    <div class="form-group">
                        <label for="password">Parole: (atstājiet tukšu, lai nemainītu)</label>
                        <input type="password" id="password" name="password">
                    </div>
                    <div class="form-group">
                        <label for="role_id">Loma:</label>
                        <select id="role_id" name="role_id">
                            <?php 
                            $current_user_role_id = isset($current_user['role_id']) ? $current_user['role_id'] : null;

                            foreach ($roles as $role):
                                if ($role['id'] != 1 || ($current_user_role_id !== null && $current_user_role_id == 1)): 
                                   
                                    $display_name = $role_display_names[$role['role_name']] ?? $role['role_name'];
                                ?>
                                    <option value="<?= htmlspecialchars($role['id']) ?>" 
                                        <?= $role['id'] == $user['role_id'] ? 'selected' : '' ?>
                                        <?= (isset($current_user['role_id']) && $current_user['role_id'] != 1 && $role['id'] != $user['role_id']) ? 'disabled' : '' ?>>
                                        <?= htmlspecialchars($display_name) ?>
                                    </option>
                                <?php 
                                endif; 
                            endforeach; 
                            ?>
                        </select>
                    </div>
                    <button type="submit" class="button">Saglabāt Izmaiņas</button>
                </form>
            <?php else: ?>
                <p class="error">Lietotājs nav atrasts.</p>
            <?php endif; ?>
        </section>
    </main>
</div>

</body>
</html> 