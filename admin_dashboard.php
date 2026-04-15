<?php
session_start();

// Database connection
$servername = "mysql-3475dc67-jayasurya272007-0f36.i.aivencloud.com"; 
$username = "avnadmin"; 
$password = "avnadmin";
$dbname = "train_booking";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}

$admin_email = $_SESSION['email'];
$success_msg = '';
$error_msg = '';

// Handle Train CRUD operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_train'])) {
        $train_number = mysqli_real_escape_string($conn, $_POST['train_number']);
        $train_name = mysqli_real_escape_string($conn, $_POST['train_name']);
        $from_station = mysqli_real_escape_string($conn, $_POST['from_station']);
        $to_station = mysqli_real_escape_string($conn, $_POST['to_station']);
        $departure_time = isset($_POST['departure_time']) ? mysqli_real_escape_string($conn, $_POST['departure_time']) : null;
        $arrival_time = isset($_POST['arrival_time']) ? mysqli_real_escape_string($conn, $_POST['arrival_time']) : null;
        $total_seats = (int)$_POST['total_seats'];
        $fare = (float)$_POST['fare'];
        
        $query = "INSERT INTO TRAIN (TrainNumber, TrainName, FromStation, ToStation, TotalSeats, Fare, DepartureTime, ArrivalTime) 
                  VALUES ('$train_number', '$train_name', '$from_station', '$to_station', $total_seats, $fare, " . ($departure_time!==null?"'".$departure_time."'":'NULL') . ", " . ($arrival_time!==null?"'".$arrival_time."'":'NULL') . ")";
        
        if (mysqli_query($conn, $query)) {
            $success_msg = "Train added successfully!";
        } else {
            $error_msg = "Error adding train: " . mysqli_error($conn);
        }
    }
    
    if (isset($_POST['edit_train'])) {
        $train_id = (int)$_POST['train_id'];
        $train_name = mysqli_real_escape_string($conn, $_POST['train_name']);
        $departure_time = isset($_POST['departure_time']) ? mysqli_real_escape_string($conn, $_POST['departure_time']) : null;
        $arrival_time = isset($_POST['arrival_time']) ? mysqli_real_escape_string($conn, $_POST['arrival_time']) : null;
        $total_seats = (int)$_POST['total_seats'];
        $fare = (float)$_POST['fare'];
        
        $query = "UPDATE TRAIN SET TrainName='$train_name', TotalSeats=$total_seats, Fare=$fare, DepartureTime=" . ($departure_time!==null?"'".$departure_time."'":'NULL') . ", ArrivalTime=" . ($arrival_time!==null?"'".$arrival_time."'":'NULL') . " WHERE TrainID=$train_id";
        
        if (mysqli_query($conn, $query)) {
            $success_msg = "Train updated successfully!";
        } else {
            $error_msg = "Error updating train: " . mysqli_error($conn);
        }
    }
    
    if (isset($_POST['delete_train'])) {
        $train_id = (int)$_POST['train_id'];
        $query = "DELETE FROM TRAIN WHERE TrainID=$train_id";
        
        if (mysqli_query($conn, $query)) {
            $success_msg = "Train deleted successfully!";
        } else {
            $error_msg = "Error deleting train: " . mysqli_error($conn);
        }
    }

    // Add new station
    if (isset($_POST['add_station'])) {
        $station_name = mysqli_real_escape_string($conn, trim($_POST['station_name']));
        $city = mysqli_real_escape_string($conn, trim($_POST['city']));
        $state = mysqli_real_escape_string($conn, trim($_POST['state']));
        if ($station_name === '') {
            $error_msg = 'Station name is required.';
        } else {
            $q = "INSERT INTO STATION (StationName, City, State) VALUES ('$station_name', '$city', '$state')";
            if (mysqli_query($conn, $q)) {
                $success_msg = 'Station added successfully.';
            } else {
                $error_msg = 'Error adding station: ' . mysqli_error($conn);
            }
        }
    }

    // Train status is managed via admin_status.php (TSTATUS/TICKET-driven)

    // Edit station
    if (isset($_POST['edit_station'])) {
        $sid = (int)$_POST['station_id'];
        $sname = mysqli_real_escape_string($conn, trim($_POST['station_name']));
        $scity = mysqli_real_escape_string($conn, trim($_POST['city']));
        $sstate = mysqli_real_escape_string($conn, trim($_POST['state']));
        if ($sid > 0 && $sname !== '') {
            $q = "UPDATE STATION SET StationName='$sname', City='$scity', State='$sstate' WHERE StationID=$sid";
            if (mysqli_query($conn, $q)) $success_msg = 'Station updated.'; else $error_msg = 'Error updating station: ' . mysqli_error($conn);
        } else {
            $error_msg = 'Invalid station data.';
        }
    }

    // Delete station
    if (isset($_POST['delete_station'])) {
        $sid = (int)$_POST['station_id'];
        if ($sid > 0) {
            $q = "DELETE FROM STATION WHERE StationID=$sid";
            if (mysqli_query($conn, $q)) $success_msg = 'Station deleted.'; else $error_msg = 'Error deleting station: ' . mysqli_error($conn);
        }
    }

    // Status editing/deletion handled in admin_status.php (no TRAIN_STATUS table)
}

// Get all trains
$trains_result = mysqli_query($conn, "SELECT * FROM TRAIN");

// Get all stations for dropdown
$stations_result = mysqli_query($conn, "SELECT DISTINCT StationName FROM STATION ORDER BY StationName");
// Get station list for management
$all_stations = mysqli_query($conn, "SELECT * FROM STATION ORDER BY StationName");
// (removed train status query — status table shown on separate admin page)
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Train Booking System</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            background: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.3)), url('rail.gif');
            background-size: cover;
            background-attachment: fixed;
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
        }
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 250px;
            background: linear-gradient(135deg, #0066cc, #004999);
            color: white;
            padding: 20px;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        .sidebar h2 {
            margin: 0 0 30px 0;
            font-size: 20px;
            text-align: center;
            border-bottom: 2px solid rgba(255,255,255,0.3);
            padding-bottom: 15px;
        }
        .sidebar-menu {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        .sidebar-menu li {
            margin-bottom: 10px;
        }
        .sidebar-menu a {
            color: white;
            text-decoration: none;
            display: block;
            padding: 10px 15px;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(255,255,255,0.2);
        }
        .admin-user-info {
            background: rgba(255,255,255,0.1);
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .admin-user-info p {
            margin: 5px 0;
            font-size: 13px;
        }
        .logout-btn {
            width: 100%;
            padding: 10px;
            background: #ff4444;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 20px;
            transition: background 0.3s;
        }
        .logout-btn:hover {
            background: #cc0000;
        }
        .main-content {
            margin-left: 250px;
            flex: 1;
            padding: 20px;
        }
        .dashboard-header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .dashboard-header h1 {
            margin: 0;
            color: #0066cc;
        }
        .content-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .section-title {
            color: #0066cc;
            font-size: 20px;
            margin: 0 0 20px 0;
            border-bottom: 2px solid #0066cc;
            padding-bottom: 10px;
        }
        .form-section {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #0066cc;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="number"],
        input[type="date"],
        input[type="time"],
        select,
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            font-family: 'Arial', sans-serif;
            box-sizing: border-box;
        }
        input[type="text"]:focus,
        input[type="number"]:focus,
        input[type="date"]:focus,
        input[type="time"]:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #0066cc;
            box-shadow: 0 0 5px rgba(0, 102, 204, 0.3);
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-primary {
            background: #0066cc;
            color: white;
        }
        .btn-primary:hover {
            background: #004999;
        }
        .btn-edit {
            background: #4CAF50;
            color: white;
            padding: 6px 12px;
            font-size: 13px;
        }
        .btn-edit:hover {
            background: #388E3C;
        }
        .btn-delete {
            background: #f44336;
            color: white;
            padding: 6px 12px;
            font-size: 13px;
        }
        .btn-delete:hover {
            background: #d32f2f;
        }
        .message {
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            display: none;
        }
        .message.show {
            display: block;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            border-left: 4px solid #28a745;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            border-left: 4px solid #f44336;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table thead {
            background: #f5f5f5;
        }
        table th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #0066cc;
        }
        table td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
        }
        table tbody tr:hover {
            background: #f9f9f9;
        }
        table tbody tr:nth-child(even) {
            background: #fafafa;
        }
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        .success-message {
            display: none;
        }
        .success-message.show {
            display: block;
        }
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="sidebar">
            <h2>Admin Panel</h2>
            <div class="admin-user-info">
                <p><strong>Email:</strong></p>
                <p><?php echo htmlspecialchars($admin_email); ?></p>
                <p style="color: #4CAF50; margin-top: 8px;">Admin User</p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="admin_stations.php">Manage Stations</a></li>
                <li><a href="admin_status.php">Update Status</a></li>
                <li><a href="admin_bookings.php">View Bookings</a></li>
                <li><a href="admin_users.php">Manage Users</a></li>
            </ul>
            <form method="POST" action="logout.php" style="margin: 0;">
                <button type="submit" class="logout-btn">Logout</button>
            </form>
        </div>

    <div class="main-content">
            <div class="dashboard-header">
                <h1>Admin Dashboard</h1>
            </div>

            <?php if ($success_msg): ?>
                <div class="message show success"><?php echo htmlspecialchars($success_msg); ?></div>
            <?php endif; ?>
            <?php if ($error_msg): ?>
                <div class="message show error"><?php echo htmlspecialchars($error_msg); ?></div>
            <?php endif; ?>

            <div class="content-section">
                <h2 class="section-title">Manage Trains</h2>

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
                                <?php mysqli_data_seek($stations_result,0); while ($s = mysqli_fetch_assoc($stations_result)) { echo '<option>'.htmlspecialchars($s['StationName']).'</option>'; } ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>To Station</label>
                            <select name="to_station">
                                <option value="">Select</option>
                                <?php mysqli_data_seek($stations_result,0); while ($s = mysqli_fetch_assoc($stations_result)) { echo '<option>'.htmlspecialchars($s['StationName']).'</option>'; } ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Total Seats</label>
                            <input name="total_seats" type="number" min="1" required>
                        </div>
                        <div class="form-group">
                            <label>Fare</label>
                            <input name="fare" type="number" step="0.01" min="0" required>
                        </div>
                        <div class="form-group">
                            <label>Departure Time</label>
                            <input name="departure_time" type="time">
                        </div>
                        <div class="form-group">
                            <label>Arrival Time</label>
                            <input name="arrival_time" type="time">
                        </div>
                    </div>
                    <div style="margin-top:12px;"><button class="btn btn-primary" name="add_train" type="submit">Add Train</button></div>
                </form>

                <h3 style="margin-top:20px;">Trains List</h3>
                <div class="table-responsive">
                    <table class="table">
                        <thead><tr><th>No</th><th>Name</th><th>From</th><th>To</th><th>Dep</th><th>Arr</th><th>Seats</th><th>Fare</th><th>Actions</th></tr></thead>
                        <tbody>
                            <?php mysqli_data_seek($trains_result,0); if (mysqli_num_rows($trains_result)>0) { while ($t = mysqli_fetch_assoc($trains_result)) { echo '<tr>';
                                echo '<td>'.htmlspecialchars($t['TrainNumber']).'</td>';
                                echo '<td>'.htmlspecialchars($t['TrainName']).'</td>';
                                echo '<td>'.htmlspecialchars($t['FromStation']).'</td>';
                                echo '<td>'.htmlspecialchars($t['ToStation']).'</td>';
                                echo '<td>'.(!empty($t['DepartureTime'])?htmlspecialchars(substr($t['DepartureTime'],0,5)):'-').'</td>';
                                echo '<td>'.(!empty($t['ArrivalTime'])?htmlspecialchars(substr($t['ArrivalTime'],0,5)):'-').'</td>';
                                echo '<td>'.htmlspecialchars($t['TotalSeats']).'</td>';
                                echo '<td>₹'.htmlspecialchars($t['Fare']).'</td>';
                                echo '<td><div class="action-buttons">';
                                echo '<button class="btn btn-small btn-primary" onclick="openEdit('.
    $t['TrainID'].',\''.addslashes($t['TrainName']).'\','.
    $t['TotalSeats'].','.
    $t['Fare'].',\''.
    (!empty($t['DepartureTime']) ? addslashes($t['DepartureTime']) : '').'\',\''.
    (!empty($t['ArrivalTime']) ? addslashes($t['ArrivalTime']) : '').'\')">Edit</button>';
                                echo '<form method="POST" style="display:inline;margin-left:8px;"><input type="hidden" name="train_id" value="'.$t['TrainID'].'"><button class="btn btn-small btn-danger" name="delete_train" onclick="return confirm(\'Delete train?\')">Delete</button></form>';
                                echo '</div></td>';
                                echo '</tr>'; } } else { echo '<tr><td colspan="9">No trains</td></tr>'; } ?>
                        </tbody>
                    </table>
                </div>

                <p style="margin-top:12px;"><a href="admin_dashboard.php">Back to Admin Dashboard</a></p>

                <div id="editTrainModal" class="modal" style="display:none;">
                    <div class="modal-content">
                        <h2>Edit Train</h2>
                        <form method="POST" action="">
                            <input type="hidden" id="edit_tid" name="train_id">
                            <div class="form-grid">
                                <div class="form-group"><label>Name</label><input id="edit_tname" name="train_name"></div>
                                <div class="form-group"><label>Seats</label><input id="edit_seats" name="total_seats" type="number" min="1"></div>
                                <div class="form-group"><label>Fare</label><input id="edit_fare" name="fare" type="number" step="0.01" min="0"></div>
                                <div class="form-group"><label>Departure Time</label><input id="edit_deptime" name="departure_time" type="time"></div>
                                <div class="form-group"><label>Arrival Time</label><input id="edit_arrtime" name="arrival_time" type="time"></div>
                            </div>
                            <div style="margin-top:8px;"><button class="btn btn-primary" name="edit_train" type="submit">Save</button> <button type="button" class="btn" onclick="closeEdit()">Cancel</button></div>
                        </form>
                    </div>
                </div>

                <script>
                function openEdit(id,name,seats,fare,deptime,arrtime){document.getElementById('edit_tid').value=id;document.getElementById('edit_tname').value=name;document.getElementById('edit_seats').value=seats;document.getElementById('edit_fare').value=fare;document.getElementById('edit_deptime').value=deptime;document.getElementById('edit_arrtime').value=arrtime;document.getElementById('editTrainModal').style.display='flex';}
                function closeEdit(){document.getElementById('editTrainModal').style.display='none';}
                </script>
            </div>

        </div>
    </div>

</body>
</html>
