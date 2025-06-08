<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['permissions']['can_manage_users']) || !$_SESSION['permissions']['can_manage_users']) {
    header("Location: login.php");
    exit;
}

$msg = '';
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_data = null;
$roles = [];

$roles_result = $conn->query("SELECT id, role_name FROM roles");
if ($roles_result) {
    $roles = $roles_result->fetch_all(MYSQLI_ASSOC);
}

if ($user_id > 0) {
    $stmt = $conn->prepare("SELECT id, username, role_id FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_data = $result->fetch_assoc();
    $stmt->close();

    if (!$user_data) {
        $msg = "User not found.";
    }
} else {
    $msg = "Invalid user ID.";
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user_data) {
    $new_username = trim($_POST['username']);
    $new_password = $_POST['password'];
    $new_role_id = (int)$_POST['role_id'];

    if ($user_id == $_SESSION['user_id']) {

        $current_role_stmt = $conn->prepare("SELECT role_id FROM users WHERE id = ?");
        $current_role_stmt->bind_param("i", $user_id);
        $current_role_stmt->execute();
        $current_role_result = $current_role_stmt->get_result();
        $current_role = $current_role_result->fetch_assoc();
        $current_role_stmt->close();

        $admin_role_id = 1; 
        if ($current_role['role_id'] == $admin_role_id && $new_role_id != $admin_role_id) {
            $admin_count_stmt = $conn->query("SELECT COUNT(*) FROM users WHERE role_id = " . $admin_role_id);
            $admin_count = $admin_count_stmt->fetch_row()[0];
            if ($admin_count <= 1) {
                $msg = "Cannot change your own role from Admin if you are the only administrator.";
            }
        }
    }

    if (empty($new_username)) {
        $msg = "Username cannot be empty.";
    } elseif (strlen($new_username) < 3) {
        $msg = "Username must be at least 3 characters long.";
    } elseif (!preg_match('/^[a-zA-Z0-9]+$/', $new_username)) {
        $msg = "Username can only contain letters and numbers.";
    } else {

        $check = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $check->bind_param("si", $new_username, $user_id);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $msg = "Username already taken by another user.";
        } else {
            $update_sql = "UPDATE users SET username = ?, role_id = ?";
            $params = [$new_username, $new_role_id];
            $types = "si";

            if (!empty($new_password)) {
                if (strlen($new_password) < 6) {
                    $msg = "Password must be at least 6 characters long.";
                } elseif (!preg_match('/[A-Z]/', $new_password)) {
                    $msg = "Password must contain at least one uppercase letter.";
                } else {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $update_sql .= ", password = ?";
                    $params[] = $hashed_password;
                    $types .= "s";
                }
            }

            if (empty($msg)) { 
                $update_sql .= " WHERE id = ?";
                $params[] = $user_id;
                $types .= "i";

                $stmt = $conn->prepare($update_sql);
                $stmt->bind_param($types, ...$params);

                if ($stmt->execute()) {
                    $msg = "User updated successfully!";
                    $user_data['username'] = $new_username;
                    $user_data['role_id'] = $new_role_id;
                } else {
                    $msg = "Error updating user: " . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit User</title>
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
                <a href="manage_users.php" class="menu-item active">👥 Lietotāji</a>
            <?php endif; ?>
            <a href="logout.php" class="menu-item">➡️ Iziet</a>
        </nav>
    </aside>

    <main class="content">
        <header class="page-header">
            <h2>Edit User</h2>
        </header>

        <section class="form-section">
            <?php if ($msg): ?><p class="message"><?= $msg ?></p><?php endif; ?>

            <?php if ($user_data): ?>
                <form method="POST" class="edit-user-form">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" value="<?= htmlspecialchars($user_data['username']) ?>" required>

                    <label for="password">New Password (leave blank to keep current):</label>
                    <input type="password" id="password" name="password">

                    <label for="role_id">Role:</label>
                    <select id="role_id" name="role_id" required>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= $role['id'] ?>" <?= ($role['id'] == $user_data['role_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($role['role_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select><br>

                    <button type="submit">Update User</button>
                </form>
            <?php else: ?>
                <p>User details could not be loaded.</p>
            <?php endif; ?>
        </section>
    </main>
</div>

</body>
</html> 