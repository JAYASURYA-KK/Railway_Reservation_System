<?php
session_start();
// Admin-only page to manage users
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: index.php');
    exit();
}

require_once 'db.php';

// Handle role update (promote/demote) and delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_role'])) {
        $uid = (int)$_POST['user_id'];
        $role = ($_POST['role'] === 'admin') ? 'admin' : 'user';
        mysqli_query($conn, "UPDATE USERS SET Role='".mysqli_real_escape_string($conn,$role)."' WHERE UserID=$uid");
    }
    if (isset($_POST['delete_user'])) {
        $uid = (int)$_POST['user_id'];
        mysqli_query($conn, "DELETE FROM USERS WHERE UserID=$uid");
    }
}

$users = mysqli_query($conn, "SELECT * FROM USERS ORDER BY UserID DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Admin - Manage Users</title>
    <link rel="stylesheet" href="style.css">
    <style>.container{padding:20px;} .action-form{display:inline-block;margin:0;}</style>
</head>
<body>
    <div class="container">
        <h2>Manage Users</h2>
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Actions</th></tr></thead>
                <tbody>
                <?php if ($users && mysqli_num_rows($users) > 0) {
                    while ($u = mysqli_fetch_assoc($users)) {
                        echo '<tr>';
                        echo '<td>'.htmlspecialchars($u['UserID']).'</td>';
                        echo '<td>'.htmlspecialchars($u['Name']).'</td>';
                        echo '<td>'.htmlspecialchars($u['Email']).'</td>';
                        echo '<td>'.htmlspecialchars($u['Phone']).'</td>';
                        echo '<td>'.htmlspecialchars($u['Role']).'</td>';
                        echo '<td>';
                        echo '<form method="POST" class="action-form" style="margin-right:8px;">';
                        echo '<input type="hidden" name="user_id" value="'.(int)$u['UserID'].'">';
                        echo '<select name="role"><option value="user">user</option><option value="admin">admin</option></select>';
                        echo '<button class="btn btn-primary" name="update_role" type="submit">Update Role</button>';
                        echo '</form>';
                        echo '<form method="POST" class="action-form">';
                        echo '<input type="hidden" name="user_id" value="'.(int)$u['UserID'].'">';
                        echo '<button class="btn btn-delete" name="delete_user" onclick="return confirm(\'Delete user?\')">Delete</button>';
                        echo '</form>';
                        echo '</td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="6" style="text-align:center;color:#666;">No users found</td></tr>';
                } ?>
                </tbody>
            </table>
        </div>
        <p style="margin-top:12px;"><a href="admin_dashboard.php">Back to Admin Dashboard</a></p>
    </div>
</body>
</html>
