<?php

require 'db.php';

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $pass = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($user = $res->fetch_assoc()) {
        if (password_verify($pass, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
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
    <p>Don’t have an account? <a href="register.php" class="logout-link">Register here</a></p>
</div>
</body>
</html>
