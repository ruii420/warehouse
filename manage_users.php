<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['permissions']['can_manage_users']) || !$_SESSION['permissions']['can_manage_users']) {
    header("Location: login.php");
    exit;
}

$msg = '';

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $user_id = (int)$_GET['id'];

    if ($user_id == $_SESSION['user_id']) {
        $msg = "You cannot delete your own account.";
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            $msg = "User deleted successfully!";
        } else {
            $msg = "Error deleting user: " . $stmt->error;
        }
        $stmt->close();
    }
}

$users_result = $conn->query("SELECT u.id, u.username, r.role_name, u.created_at FROM users u JOIN roles r ON u.role_id = r.id");
$users = $users_result->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users</title>
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
                <a href="manage_users.php" class="menu-item active">👥 Lietotāji</a>
            <?php endif; ?>
            <a href="logout.php" class="menu-item">➡️ Iziet</a>
        </nav>
    </aside>

    <main class="content">
        <header class="page-header">
            <h2>Manage Users</h2>
        </header>

        <section class="table-section">
            <?php if ($msg): ?><p class="message"><?= $msg ?></p><?php endif; ?>
            <table class="user-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($users)): ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['id']) ?></td>
                                <td><?= htmlspecialchars($user['username']) ?></td>
                                <td><?= htmlspecialchars($user['role_name']) ?></td>
                                <td><?= htmlspecialchars($user['created_at']) ?></td>
                                <td>
                                    <a href="edit_user.php?id=<?= $user['id'] ?>" class="action-button edit">Edit</a>
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <a href="manage_users.php?action=delete&id=<?= $user['id'] ?>" class="action-button delete" onclick="return confirm('Are you sure you want to delete this user?');">Delete</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="no-users">No users found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>
</div>

</body>
</html> 