<?php
require 'db.php';


$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (strlen($username) < 3) {
        $msg = "Username must be at least 3 characters long.";
    } elseif (!preg_match('/^[a-zA-Z0-9]+$/', $username)) {
        $msg = "Username can only contain letters and numbers.";
    }
   
    elseif (strlen($password) < 6) {
        $msg = "Password must be at least 6 characters long.";
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $msg = "Password must contain at least one uppercase letter.";
    } elseif (!preg_match('/^[a-zA-Z0-9]+$/', $password)) {
        $msg = "Password can only contain letters and numbers.";
    } else {
      
        $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $msg = "Username already taken.";
        } else {
        
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $stmt->bind_param("ss", $username, $hashed);
            if ($stmt->execute()) {
                $_SESSION['user_id'] = $stmt->insert_id;
                header("Location: index.php");
                exit;
            } else {
                $msg = "Registration failed. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container2">
    <h2>Register</h2>
    <form method="POST">
        <input name="username" placeholder="Username" required>
        <input name="password" type="password" placeholder="Password" required>
        <button>Register</button>
        <?php if ($msg): ?><p class="error"><?= $msg ?></p><?php endif; ?>
    </form>
    <p>Already have an account? <a href="login.php" class="logout-link">Login here</a></p>
</div>
</body>
</html>
