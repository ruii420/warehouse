<?php
session_start();

require 'db.php';

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $pass = $_POST['password'];

    $stmt = $conn->prepare("SELECT u.id, u.password, r.can_add_product, r.can_add_user, r.can_manage_users, r.can_create_report, r.can_make_order, r.can_manage_inventory FROM users u JOIN roles r ON u.role_id = r.id WHERE u.username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($user = $res->fetch_assoc()) {
        if (password_verify($pass, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['permissions'] = [
                'can_add_product' => (bool)$user['can_add_product'],
                'can_add_user' => (bool)$user['can_add_user'],
                'can_manage_users' => (bool)$user['can_manage_users'],
                'can_create_report' => (bool)$user['can_create_report'],
                'can_make_order' => (bool)$user['can_make_order'],
                'can_manage_inventory' => (bool)$user['can_manage_inventory']
            ];
            header("Location: index.php");
            exit;
        } else {
            $msg = "Wrong password.";
        }
    } else {
        $msg = "User not found.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container2">
    <h2>Login</h2>
    <form method="POST">
        <input name="username" placeholder="username" required>
        <input name="password" type="password" placeholder="Password" required>
        <button>Login</button>
        <?php if ($msg): ?><p class="error"><?= $msg ?></p><?php endif; ?>
    </form>
    <p>Don't have an account? <a href="register.php" class="logout-link">Register here</a></p>
</div>
</body>
</html>
