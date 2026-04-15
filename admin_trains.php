<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: index.php');
    exit();
}

$servername = "mysql-3475dc67-jayasurya272007-0f36.i.aivencloud.com"; 
$username = "avnadmin"; 
$password = "avnadmin";
$dbname = "train_booking";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die('Connection failed: ' . $conn->connect_error);

// Ensure arrival/departure columns exist (safe to run on MySQL 8+)
mysqli_query($conn, "ALTER TABLE TRAIN ADD COLUMN IF NOT EXISTS ArrivalTime TIME NULL, ADD COLUMN IF NOT EXISTS DepartureTime TIME NULL");

$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_train'])) {
        $train_number = mysqli_real_escape_string($conn, $_POST['train_number']);
        $train_name = mysqli_real_escape_string($conn, $_POST['train_name']);
        $from_station = mysqli_real_escape_string($conn, $_POST['from_station']);
        $to_station = mysqli_real_escape_string($conn, $_POST['to_station']);
        $departure_time = isset($_POST['departure_time']) ? mysqli_real_escape_string($conn, $_POST['departure_time']) : null;
        $arrival_time = isset($_POST['arrival_time']) ? mysqli_real_escape_string($conn, $_POST['arrival_time']) : null;
        $total_seats = (int)$_POST['total_seats'];
        $fare = (float)$_POST['fare'];
        $q = "INSERT INTO TRAIN (TrainNumber, TrainName, FromStation, ToStation, TotalSeats, Fare, DepartureTime, ArrivalTime) VALUES ('$train_number','$train_name','$from_station','$to_station',$total_seats,$fare," . ($departure_time!==null?"'".$departure_time."'":'NULL') . "," . ($arrival_time!==null?"'".$arrival_time."'":'NULL') . ")";
        if (mysqli_query($conn, $q)) $success_msg = 'Train added.'; else $error_msg = 'Error: '.mysqli_error($conn);
    }
    if (isset($_POST['edit_train'])) {
        $tid = (int)$_POST['train_id'];
        $tname = mysqli_real_escape_string($conn, $_POST['train_name']);
        $departure_time = isset($_POST['departure_time']) ? mysqli_real_escape_string($conn, $_POST['departure_time']) : null;
        $arrival_time = isset($_POST['arrival_time']) ? mysqli_real_escape_string($conn, $_POST['arrival_time']) : null;
        $seats = (int)$_POST['total_seats'];
        $fare = (float)$_POST['fare'];
        $q = "UPDATE TRAIN SET TrainName='$tname', TotalSeats=$seats, Fare=$fare, DepartureTime=" . ($departure_time!==null?"'".$departure_time."'":'NULL') . ", ArrivalTime=" . ($arrival_time!==null?"'".$arrival_time."'":'NULL') . " WHERE TrainID=$tid";
        if (mysqli_query($conn, $q)) $success_msg = 'Train updated.'; else $error_msg = 'Error: '.mysqli_error($conn);
    }
    if (isset($_POST['delete_train'])) {
        $tid = (int)$_POST['train_id'];
        $q = "DELETE FROM TRAIN WHERE TrainID=$tid";
        if (mysqli_query($conn, $q)) $success_msg = 'Train deleted.'; else $error_msg = 'Error: '.mysqli_error($conn);
    }
}

$stations = mysqli_query($conn, "SELECT DISTINCT StationName FROM STATION ORDER BY StationName");
$trains = mysqli_query($conn, "SELECT * FROM TRAIN ORDER BY TrainNumber");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Manage Trains - railway reservation system</title>
    <link rel="stylesheet" href="style.css">
    <style>.container{padding:20px;}.form-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;}</style>
</head>
<body>
    <div class="container">
        <div class="section">
            <h1 class="section-title">Manage Trains</h1>
            <?php if ($success_msg): ?><div class="alert alert-success"><?php echo htmlspecialchars($success_msg); ?></div><?php endif; ?>
            <?php if ($error_msg): ?><div class="alert alert-error"><?php echo htmlspecialchars($error_msg); ?></div><?php endif; ?>

            <h3>Add Train</h3>
            <form method="POST" action="">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Train Number</label>
                        <input name="train_number" required>
                    </div>
                    <div class="form-group">
                        <label>Train Name</label>
                        <input name="train_name" required>
                    </div>
                    <div class="form-group">
                        <label>From Station</label>
                        <select name="from_station">
                            <option value="">Select</option>
                            <?php mysqli_data_seek($stations,0); while ($s = mysqli_fetch_assoc($stations)) { echo '<option>'.htmlspecialchars($s['StationName']).'</option>'; } ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>To Station</label>
                        <select name="to_station">
                            <option value="">Select</option>
                            <?php mysqli_data_seek($stations,0); while ($s = mysqli_fetch_assoc($stations)) { echo '<option>'.htmlspecialchars($s['StationName']).'</option>'; } ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Total Seats</label>
                        <input name="total_seats" type="number" min="1" required>
                    </div>
                    <div class="form-group">
                        <label>Departure Time</label>
                        <input name="departure_time" type="time">
                    </div>
                    <div class="form-group">
                        <label>Arrival Time</label>
                        <input name="arrival_time" type="time">
                    </div>
                    <div class="form-group">
                        <label>Fare</label>
                        <input name="fare" type="number" step="0.01" min="0" required>
                    </div>
                </div>
                <div style="margin-top:12px;"><button class="btn btn-primary" name="add_train" type="submit">Add Train</button></div>
            </form>

            <h3 style="margin-top:20px;">Trains List</h3>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>No</th><th>Name</th><th>From</th><th>To</th><th>Seats</th><th>Fare</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php mysqli_data_seek($trains,0); if (mysqli_num_rows($trains)>0) { while ($t = mysqli_fetch_assoc($trains)) { echo '<tr>';
                            echo '<td>'.htmlspecialchars($t['TrainNumber']).'</td>';
                            echo '<td>'.htmlspecialchars($t['TrainName']).'</td>';
                            echo '<td>'.htmlspecialchars($t['FromStation']).'</td>';
                            echo '<td>'.htmlspecialchars($t['ToStation']).'</td>';
                            echo '<td>'.htmlspecialchars($t['TotalSeats']).'</td>';
                            echo '<td>₹'.htmlspecialchars($t['Fare']).'</td>';
                            echo '<td><div class="action-buttons">';
                            echo '<button class="btn btn-small btn-primary" onclick="openEdit('.$t['TrainID'].',\''.addslashes($t['TrainName']).'\','.$t['TotalSeats'].','.$t['Fare'].')">Edit</button>';
                            echo '<form method="POST" style="display:inline;margin-left:8px;"><input type="hidden" name="train_id" value="'.$t['TrainID'].'"><button class="btn btn-small btn-danger" name="delete_train" onclick="return confirm(\'Delete train?\')">Delete</button></form>';
                            echo '</div></td>';
                            echo '</tr>'; } } else { echo '<tr><td colspan="7">No trains</td></tr>'; } ?>
                    </tbody>
                </table>
            </div>

            <p style="margin-top:12px;"><a href="admin_dashboard.php">Back to Admin Dashboard</a></p>
        </div>
    </div>

    <div id="editTrainModal" class="modal" style="display:none;">
        <div class="modal-content">
            <h2>Edit Train</h2>
            <form method="POST" action="">
                <input type="hidden" id="edit_tid" name="train_id">
                <div class="form-grid">
                    <div class="form-group"><label>Name</label><input id="edit_tname" name="train_name"></div>
                    <div class="form-group"><label>Seats</label><input id="edit_seats" name="total_seats" type="number" min="1"></div>
                    <div class="form-group"><label>Departure Time</label><input id="edit_departure" name="departure_time" type="time"></div>
                    <div class="form-group"><label>Arrival Time</label><input id="edit_arrival" name="arrival_time" type="time"></div>
                    <div class="form-group"><label>Fare</label><input id="edit_fare" name="fare" type="number" step="0.01" min="0"></div>
                </div>
                <div style="margin-top:8px;"><button class="btn btn-primary" name="edit_train" type="submit">Save</button> <button type="button" class="btn" onclick="closeEdit()">Cancel</button></div>
            </form>
        </div>
    </div>

    <script>
    function openEdit(id,name,seats,fare){document.getElementById('edit_tid').value=id;document.getElementById('edit_tname').value=name;document.getElementById('edit_seats').value=seats;document.getElementById('edit_fare').value=fare;document.getElementById('editTrainModal').style.display='flex';}
    function closeEdit(){document.getElementById('editTrainModal').style.display='none';}
    </script>
</body>
</html>
