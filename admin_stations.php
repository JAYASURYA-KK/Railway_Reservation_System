<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: index.php');
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "train_booking";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die('Connection failed: ' . $conn->connect_error);

$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_station'])) {
        $name = mysqli_real_escape_string($conn, trim($_POST['station_name']));
        $city = mysqli_real_escape_string($conn, trim($_POST['city']));
        $state = mysqli_real_escape_string($conn, trim($_POST['state']));
        if ($name === '') $error_msg = 'Station name required.';
        else { $q = "INSERT INTO STATION (StationName, City, State) VALUES ('$name','$city','$state')"; if (mysqli_query($conn,$q)) $success_msg='Station added.'; else $error_msg='Error: '.mysqli_error($conn); }
    }
    if (isset($_POST['edit_station'])) {
        $id = (int)$_POST['station_id']; $name = mysqli_real_escape_string($conn, trim($_POST['station_name'])); $city = mysqli_real_escape_string($conn, trim($_POST['city'])); $state = mysqli_real_escape_string($conn, trim($_POST['state']));
        if ($id>0 && $name!=='') { $q = "UPDATE STATION SET StationName='$name', City='$city', State='$state' WHERE StationID=$id"; if (mysqli_query($conn,$q)) $success_msg='Station updated.'; else $error_msg='Error: '.mysqli_error($conn); }
    }
    if (isset($_POST['delete_station'])) {
        $id = (int)$_POST['station_id']; if ($id>0) { $q = "DELETE FROM STATION WHERE StationID=$id"; if (mysqli_query($conn,$q)) $success_msg='Station deleted.'; else $error_msg='Error: '.mysqli_error($conn); }
    }
}

$stations = mysqli_query($conn, "SELECT * FROM STATION ORDER BY StationName");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Admin - Stations</title>
    <link rel="stylesheet" href="style.css">
    <style>.container{padding:20px;} .form-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;}</style>
</head>
<body>
    <div class="container">
        <div class="section">
            <h2>Manage Stations</h2>
            <?php if ($success_msg): ?><div class="alert alert-success"><?php echo htmlspecialchars($success_msg); ?></div><?php endif; ?>
            <?php if ($error_msg): ?><div class="alert alert-error"><?php echo htmlspecialchars($error_msg); ?></div><?php endif; ?>

            <h3>Add Station</h3>
            <form method="POST" action="">
                <div class="form-grid">
                    <div class="form-group"><label>Name</label><input name="station_name" required></div>
                    <div class="form-group"><label>City</label><input name="city"></div>
                    <div class="form-group"><label>State</label><input name="state"></div>
                </div>
                <div style="margin-top:8px;"><button class="btn btn-primary" name="add_station" type="submit">Add</button></div>
            </form>

            <h3 style="margin-top:16px;">Stations</h3>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>ID</th><th>Name</th><th>City</th><th>State</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php if ($stations && mysqli_num_rows($stations)>0) { while ($s = mysqli_fetch_assoc($stations)) { echo '<tr>'; echo '<td>'.$s['StationID'].'</td>'; echo '<td>'.htmlspecialchars($s['StationName']).'</td>'; echo '<td>'.htmlspecialchars($s['City']).'</td>'; echo '<td>'.htmlspecialchars($s['State']).'</td>'; echo '<td><div class="action-buttons">'; echo '<button class="btn btn-small btn-primary" onclick="openEdit('.$s['StationID'].',\''.addslashes($s['StationName']).'\',\''.addslashes($s['City']).'\',\''.addslashes($s['State']).'\')">Edit</button>'; echo '<form method="POST" style="display:inline;margin-left:8px;"><input type="hidden" name="station_id" value="'.$s['StationID'].'"><button class="btn btn-small btn-danger" name="delete_station" onclick="return confirm(\'Delete station?\')">Delete</button></form>'; echo '</div></td>'; echo '</tr>'; } } else { echo '<tr><td colspan="5">No stations</td></tr>'; } ?>
                    </tbody>
                </table>
            </div>

            <p style="margin-top:12px;"><a href="admin_dashboard.php">Back to Admin Dashboard</a></p>
        </div>
    </div>

    <div id="editModal" class="modal" style="display:none;">
        <div class="modal-content">
            <h2>Edit Station</h2>
            <form method="POST" action="">
                <input type="hidden" id="edit_sid" name="station_id">
                <div class="form-grid">
                    <div class="form-group"><label>Name</label><input id="edit_sname" name="station_name"></div>
                    <div class="form-group"><label>City</label><input id="edit_scity" name="city"></div>
                    <div class="form-group"><label>State</label><input id="edit_sstate" name="state"></div>
                </div>
                <div style="margin-top:8px;"><button class="btn btn-primary" name="edit_station" type="submit">Save</button> <button type="button" class="btn" onclick="closeEdit()">Cancel</button></div>
            </form>
        </div>
    </div>

    <script>
    function openEdit(id,name,city,state){document.getElementById('edit_sid').value=id;document.getElementById('edit_sname').value=name;document.getElementById('edit_scity').value=city;document.getElementById('edit_sstate').value=state;document.getElementById('editModal').style.display='flex';}
    function closeEdit(){document.getElementById('editModal').style.display='none';}
    </script>
</body>
</html>
