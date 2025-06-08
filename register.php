<?php
session_start();
require 'db.php';

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

   
    if (empty($username)) {
        $msg = "Lietotājvārds ir obligāts.";
    } elseif (strlen($username) < 3) {
        $msg = "Lietotājvārdam jābūt vismaz 3 rakstzīmes garam.";
    } elseif (!preg_match('/^[a-zA-Z0-9]+$/', $username)) {
        $msg = "Lietotājvārds var saturēt tikai burtus un ciparus.";
    } elseif (empty($password)) {
        $msg = "Parole ir obligāta.";
    } elseif (strlen($password) < 6) {
        $msg = "Parolei jābūt vismaz 6 rakstzīmes garai.";
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $msg = "Parolei jāsatur vismaz viens lielais burts.";
    } elseif (!preg_match('/^[a-zA-Z0-9]+$/', $password)) {
        $msg = "Parole var saturēt tikai burtus un ciparus.";
    } elseif ($password !== $confirm_password) {
        $msg = "Paroles nesakrīt.";
    } else {
      
        $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $msg = "Lietotājvārds jau eksistē.";
        } else {
            $role_id = 3; 
            $count_users_stmt = $conn->query("SELECT COUNT(*) FROM users");
            $user_count = $count_users_stmt->fetch_row()[0];
            if ($user_count == 0) {
                $role_id = 1;
            }

            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, password, role_id) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $username, $hashed, $role_id);
            if ($stmt->execute()) {
                header("Location: login.php");
                exit;
            } else {
                $msg = "Reģistrācija neizdevās. Lūdzu mēģiniet vēlreiz.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <title>Reģistrēties</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container2">
    <h2>Reģistrēties</h2>
    <form method="POST">
        <input type="text" name="username" placeholder="Lietotājvārds" value="<?= isset($username) ? htmlspecialchars($username) : '' ?>">
        <input type="password" name="password" placeholder="Parole">
        <input type="password" name="confirm_password" placeholder="Apstiprināt Paroli">
        <button type="submit">Reģistrēties</button>
        <?php if ($msg): ?><p class="error"><?= $msg ?></p><?php endif; ?>
    </form>
    <p>Jau ir konts? <a href="login.php" class="logout-link">Pieslēgties</a></p>
</div>
</body>
</html>
