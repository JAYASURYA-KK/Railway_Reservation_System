<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "train_booking";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die('Connection failed: ' . $conn->connect_error);

$user_id = (int)$_SESSION['user_id'];

$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $phone = mysqli_real_escape_string($conn, trim($_POST['phone']));
    $password = trim($_POST['password']);

    if ($name === '' || $phone === '') {
        $error_msg = 'Name and phone are required.';
    } else {
        if ($password !== '') {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $q = "UPDATE USERS SET Name='$name', Phone='$phone', Password='$hash' WHERE UserID=$user_id";
        } else {
            $q = "UPDATE USERS SET Name='$name', Phone='$phone' WHERE UserID=$user_id";
        }
        if (mysqli_query($conn, $q)) {
            $success_msg = 'Profile updated successfully.';
        } else {
            $error_msg = 'Error updating profile: ' . mysqli_error($conn);
        }
    }
}

$res = mysqli_query($conn, "SELECT * FROM USERS WHERE UserID = $user_id");
$user = mysqli_fetch_assoc($res);

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>My Profile</title>
    <link rel="stylesheet" href="style.css">
    <style>.container{padding:20px;} table{width:auto;}</style>
</head>
<body>
    <div class="container">
        <h2>My Profile</h2>
        <?php if ($success_msg): ?><div class="alert alert-success"><?php echo htmlspecialchars($success_msg); ?></div><?php endif; ?>
        <?php if ($error_msg): ?><div class="alert alert-error"><?php echo htmlspecialchars($error_msg); ?></div><?php endif; ?>
        <?php if ($user): ?>
            <form method="POST" action="profile.php">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="name">Name</label>
                        <input id="name" name="name" type="text" value="<?php echo htmlspecialchars($user['Name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email (readonly)</label>
                        <input id="email" type="email" value="<?php echo htmlspecialchars($user['Email']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input id="phone" name="phone" type="text" value="<?php echo htmlspecialchars($user['Phone']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="password">New Password (leave blank to keep)</label>
                        <input id="password" name="password" type="password" placeholder="Enter new password">
                    </div>
                </div>
                <div style="margin-top:12px;">
                    <button class="btn btn-primary" type="submit" name="update_profile">Update Profile</button>
                    <a href="user_dashboard.php" class="btn" style="background:#eee; margin-left:8px;">Back</a>
                </div>
            </form>
        <?php else: ?>
            <p>User not found.</p>
        <?php endif; ?>
    </div>
</body>
</html>
