<?php
session_start();

require 'db.php';

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username)) {
        $msg = "Lūdzu ievadiet lietotājvārdu.";
    } elseif (empty($password)) {
        $msg = "Lūdzu ievadiet paroli.";
    } else {
        $stmt = $conn->prepare("SELECT u.id, u.password, r.* FROM users u JOIN roles r ON u.role_id = r.id WHERE u.username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            
            $_SESSION['role'] = strtolower($user['role_name']);
            
            $_SESSION['permissions'] = [
                'can_add_product' => (bool)$user['can_add_product'],
                'can_add_user' => (bool)$user['can_add_user'],
                'can_manage_users' => (bool)$user['can_manage_users'],
                'can_create_report' => (bool)$user['can_create_report'],
                'can_make_order' => (bool)$user['can_make_order'],
                'can_manage_inventory' => (bool)$user['can_manage_inventory'],
                'can_delete_product' => (bool)$user['can_delete_product'],
                'can_edit_product' => (bool)$user['can_edit_product']
            ];
            
            header("Location: index.php");
            exit;
        } else {
            $msg = "Nepareizs lietotājvārds vai parole.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <title>Pieslēgties</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container2">
        <h2>Pieslēgties</h2>
        <form method="POST">
            <input type="text" name="username" placeholder="Lietotājvārds" value="<?= isset($username) ? htmlspecialchars($username) : '' ?>">
            <input type="password" name="password" placeholder="Parole">
            <button type="submit">Pieslēgties</button>
            <?php if ($msg): ?><p class="error"><?= $msg ?></p><?php endif; ?>
        </form>
        <p>Nav konta? <a href="register.php" class="logout-link">Reģistrēties</a></p>
    </div>
</body>
</html>
