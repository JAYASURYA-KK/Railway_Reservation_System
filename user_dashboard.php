<?php
session_start();

require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'user') {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['email'];
$success_msg = '';
$error_msg = '';

// Get stations for search dropdowns
$stations_result = mysqli_query($conn, "SELECT DISTINCT StationName FROM STATION ORDER BY StationName");

// Get user info
$user_query = "SELECT * FROM USERS WHERE UserID = $user_id";
$user_result = mysqli_query($conn, $user_query);
$user_info = mysqli_fetch_assoc($user_result);

// Handle passenger CRUD
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_passenger'])) {
        $passenger_name = mysqli_real_escape_string($conn, $_POST['passenger_name']);
        $age = (int)$_POST['age'];
        $gender = mysqli_real_escape_string($conn, $_POST['gender']);
        
        $query = "INSERT INTO PASSENGER (UserID, PassengerName, Age, Gender) 
                  VALUES ($user_id, '$passenger_name', $age, '$gender')";
        
        if (mysqli_query($conn, $query)) {
            $success_msg = "Passenger added successfully!";
        } else {
            $error_msg = "Error adding passenger: " . mysqli_error($conn);
        }
    }
    
    if (isset($_POST['edit_passenger'])) {
        $passenger_id = (int)$_POST['passenger_id'];
        $passenger_name = mysqli_real_escape_string($conn, $_POST['passenger_name']);
        $age = (int)$_POST['age'];
        $gender = mysqli_real_escape_string($conn, $_POST['gender']);
        
        $query = "UPDATE PASSENGER SET PassengerName='$passenger_name', Age=$age, Gender='$gender' WHERE PassengerID=$passenger_id";
        
        if (mysqli_query($conn, $query)) {
            $success_msg = "Passenger updated successfully!";
        } else {
            $error_msg = "Error updating passenger: " . mysqli_error($conn);
        }
    }
    
    if (isset($_POST['delete_passenger'])) {
        $passenger_id = (int)$_POST['passenger_id'];
        $query = "DELETE FROM PASSENGER WHERE PassengerID=$passenger_id";
        
        if (mysqli_query($conn, $query)) {
            $success_msg = "Passenger deleted successfully!";
        } else {
            $error_msg = "Error deleting passenger: " . mysqli_error($conn);
        }
    }
    
    if (isset($_POST['book_ticket'])) {
        $train_id = (int)$_POST['train_id'];
        $selected = $_POST['passenger_id'];
        $passenger_ids = is_array($selected) ? $selected : array($selected);

        // Check train availability
        $train_query = "SELECT * FROM TRAIN WHERE TrainID = $train_id";
        $train_result = mysqli_query($conn, $train_query);
        $train = mysqli_fetch_assoc($train_result);

        // Count already confirmed tickets
        $booked_query = "SELECT COUNT(*) as booked FROM TICKET WHERE TrainID = $train_id AND Status = 'CNF'";
        $booked_result = mysqli_query($conn, $booked_query);
        $booked = mysqli_fetch_assoc($booked_result);
        $booked_count = (int)$booked['booked'];

        $booking_date = date('Y-m-d');
        $results = array();

        foreach ($passenger_ids as $pid) {
            $pid = (int)$pid;
            if ($booked_count < (int)$train['TotalSeats']) {
                $status = 'CNF';
                $fare = $train['Fare'];
                $booked_count++; // reserve seat for this passenger
            } else {
                $status = 'WTL';
                $fare = 0;
            }

            $ins = "INSERT INTO TICKET (UserID, TrainID, PassengerID, Status, BookingDate, Fare) 
                    VALUES ($user_id, $train_id, $pid, '$status', '$booking_date', $fare)";

            if (mysqli_query($conn, $ins)) {
                $results[] = array('pid' => $pid, 'status' => $status);
            } else {
                $results[] = array('pid' => $pid, 'status' => 'ERROR', 'error' => mysqli_error($conn));
            }
        }

        // Build summary message
        $success_list = array();
        $error_list = array();
        foreach ($results as $r) {
            if (isset($r['status']) && $r['status'] === 'ERROR') {
                $error_list[] = 'PID ' . $r['pid'] . ': ' . $r['error'];
            } else {
                $success_list[] = 'PID ' . $r['pid'] . ' (' . $r['status'] . ')';
            }
        }

        if (count($success_list) > 0) {
            $success_msg = 'Tickets created: ' . implode(', ', $success_list);
        }
        if (count($error_list) > 0) {
            $error_msg = 'Errors: ' . implode('; ', $error_list);
        }
    }
}

// Get user's passengers
$passengers_query = "SELECT * FROM PASSENGER WHERE UserID = $user_id ORDER BY PassengerID DESC";
$passengers_result = mysqli_query($conn, $passengers_query);

// Build trains query with optional filters from GET params (source/destination)
$trains_query = "SELECT * FROM TRAIN";
$filters = array();
if (!empty($_GET['source'])) {
    $src = mysqli_real_escape_string($conn, $_GET['source']);
    $filters[] = "FromStation = '$src'";
}
if (!empty($_GET['destination'])) {
    $dst = mysqli_real_escape_string($conn, $_GET['destination']);
    $filters[] = "ToStation = '$dst'";
}
if (count($filters) > 0) {
    $trains_query .= ' WHERE ' . implode(' AND ', $filters);
}
$trains_query .= ' ORDER BY TrainNumber';
$trains_result = mysqli_query($conn, $trains_query);

// Get user's bookings
$bookings_query = "SELECT t.TicketID, p.PassengerName, tr.TrainNumber, tr.TrainName, tr.FromStation, 
                          tr.ToStation, t.Status, t.BookingDate, t.Fare
                   FROM TICKET t 
                   JOIN PASSENGER p ON t.PassengerID = p.PassengerID
                   JOIN TRAIN tr ON t.TrainID = tr.TrainID
                   WHERE t.UserID = $user_id
                   ORDER BY t.BookingDate DESC";
$bookings_result = mysqli_query($conn, $bookings_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Train Booking System</title>
    <link rel="stylesheet" href="style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: #f5f5f5;
            font-family: 'Arial', sans-serif;
        }
        
        .user-container {
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
            margin: 0 0 30px 0;
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
            cursor: pointer;
        }
        
        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(255,255,255,0.2);
        }
        
        .user-info {
            background: rgba(255,255,255,0.1);
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .user-info p {
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
            display: none;
        }
        
        .content-section.active {
            display: block;
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
        input[type="number"],
        input[type="date"],
        select,
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            font-family: 'Arial', sans-serif;
        }
        
        input:focus,
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
        
        .btn-delete {
            background: #f44336;
            color: white;
            padding: 6px 12px;
            font-size: 13px;
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
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 3px;
            font-weight: 600;
            font-size: 12px;
        }
        
        .status-cnf {
            background: #d4edda;
            color: #155724;
        }
        
        .status-wtl {
            background: #fff3cd;
            color: #856404;
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
    <div class="user-container">
        <div class="sidebar">
            <h2>User Panel</h2>
            <div class="user-info">
                <p><strong>Email:</strong></p>
                <p><?php echo htmlspecialchars($user_email); ?></p>
                <p style="color: #4CAF50; margin-top: 8px;">Regular User</p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="user_dashboard.php" class="active">My Passengers</a></li>
                <li><a href="search.php">Search Trains</a></li>
                <li><a href="bookings.php">My Bookings</a></li>
                <li><a href="profile.php">My Profile</a></li>
            </ul>
            <form method="POST" action="logout.php" style="margin: 0;">
                <button type="submit" class="logout-btn">Logout</button>
            </form>
        </div>

        <div class="main-content">
            <div class="dashboard-header">
                <h1>User Dashboard</h1>
                <p>Manage your passengers and book trains</p>
            </div>

            <?php if (!empty($success_msg)): ?>
            <div class="message success show"><?php echo htmlspecialchars($success_msg); ?></div>
            <?php endif; ?>

            <?php if (!empty($error_msg)): ?>
            <div class="message error show"><?php echo htmlspecialchars($error_msg); ?></div>
            <?php endif; ?>

            <!-- Passengers Section -->
            <div class="content-section active" id="passengers">
                <h2 class="section-title">My Passengers</h2>

                <div class="form-section">
                    <h3 style="margin-top: 0; color: #0066cc;">Add New Passenger</h3>
                    <form method="POST" action="">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="passenger_name">Passenger Name</label>
                                <input type="text" id="passenger_name" name="passenger_name" required placeholder="Enter passenger name">
                            </div>
                            <div class="form-group">
                                <label for="age">Age</label>
                                <input type="number" id="age" name="age" required placeholder="Enter age" min="1" max="120">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="gender">Gender</label>
                            <select id="gender" name="gender" required>
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <button type="submit" name="add_passenger" class="btn btn-primary">Add Passenger</button>
                    </form>
                </div>

                <h3 style="color: #0066cc; margin-top: 30px;">Passengers List</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Passenger Name</th>
                            <th>Age</th>
                            <th>Gender</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $passengers_result = mysqli_query($conn, "SELECT * FROM PASSENGER WHERE UserID = $user_id ORDER BY PassengerID DESC");
                        if (mysqli_num_rows($passengers_result) > 0) {
                            while ($passenger = mysqli_fetch_assoc($passengers_result)) {
                                echo '<tr>';
                                echo '<td>' . htmlspecialchars($passenger['PassengerName']) . '</td>';
                                echo '<td>' . htmlspecialchars($passenger['Age']) . '</td>';
                                echo '<td>' . htmlspecialchars($passenger['Gender']) . '</td>';
                                echo '<td>';
                                echo '<div class="action-buttons">';
                                echo '<button class="btn btn-edit" onclick="editPassenger(' . $passenger['PassengerID'] . ', \'' . addslashes($passenger['PassengerName']) . '\', ' . $passenger['Age'] . ', \'' . $passenger['Gender'] . '\')">Edit</button>';
                                echo '<form method="POST" action="" style="display: inline;">';
                                echo '<input type="hidden" name="passenger_id" value="' . $passenger['PassengerID'] . '">';
                                echo '<button type="submit" name="delete_passenger" class="btn btn-delete" onclick="return confirm(\'Are you sure?\')">Delete</button>';
                                echo '</form>';
                                echo '</div>';
                                echo '</td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="4" style="text-align: center; color: #999;">No passengers added yet</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <!-- Search Trains Section -->
            <div class="content-section" id="search">
                <h2 class="section-title">Search & Book Trains</h2>

                <div class="form-section">
                        <h3 style="margin-top: 0; color: #0066cc;">Available Trains</h3>
                        <form method="GET" action="" style="margin-bottom:15px; display:flex; gap:10px; flex-wrap:wrap; align-items:end;">
                            <div style="min-width:200px;">
                                <label for="source">From</label>
                                <select name="source" id="source">
                                    <option value="">Any</option>
                                    <?php
                                    mysqli_data_seek($stations_result, 0);
                                    while ($st = mysqli_fetch_assoc($stations_result)) {
                                        $sel = (isset($_GET['source']) && $_GET['source'] === $st['StationName']) ? ' selected' : '';
                                        echo '<option value="' . htmlspecialchars($st['StationName']) . '"' . $sel . '>' . htmlspecialchars($st['StationName']) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div style="min-width:200px;">
                                <label for="destination">To</label>
                                <select name="destination" id="destination">
                                    <option value="">Any</option>
                                    <?php
                                    mysqli_data_seek($stations_result, 0);
                                    while ($st = mysqli_fetch_assoc($stations_result)) {
                                        $sel = (isset($_GET['destination']) && $_GET['destination'] === $st['StationName']) ? ' selected' : '';
                                        echo '<option value="' . htmlspecialchars($st['StationName']) . '"' . $sel . '>' . htmlspecialchars($st['StationName']) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div>
                                <button type="submit" class="btn btn-primary">Search</button>
                                <a href="user_dashboard.php" class="btn" style="background:#eee; margin-left:8px;">Reset</a>
                            </div>
                        </form>
                        <table>
                        <thead>
                            <tr>
                                <th>Train Number</th>
                                <th>Train Name</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Seats</th>
                                <th>Fare (₹)</th>
                                <th>Book</th>
                            </tr>
                        </thead>
                        <tbody>
                                <?php
                                if (mysqli_num_rows($trains_result) > 0) {
                                    while ($train = mysqli_fetch_assoc($trains_result)) {
                                        echo '<tr>';
                                        echo '<td>' . htmlspecialchars($train['TrainNumber']) . '</td>';
                                        echo '<td>' . htmlspecialchars($train['TrainName']) . '</td>';
                                        echo '<td>' . htmlspecialchars($train['FromStation']) . '</td>';
                                        echo '<td>' . htmlspecialchars($train['ToStation']) . '</td>';
                                        echo '<td>' . htmlspecialchars($train['TotalSeats']) . '</td>';
                                        echo '<td>₹' . htmlspecialchars($train['Fare']) . '</td>';
                                        echo '<td><a href="booking.php?train_id=' . $train['TrainID'] . '" class="btn btn-primary">Book</a></td>';
                                        echo '</tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="7" style="text-align: center; color: #999;">No trains available</td></tr>';
                                }
                                ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Bookings Section -->
            <div class="content-section" id="bookings">
                <h2 class="section-title">My Bookings</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Ticket ID</th>
                            <th>Passenger Name</th>
                            <th>Train Number</th>
                            <th>Train Name</th>
                            <th>Route</th>
                            <th>Status</th>
                            <th>Fare (₹)</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $bookings_result = mysqli_query($conn, $bookings_query);
                        if (mysqli_num_rows($bookings_result) > 0) {
                            while ($booking = mysqli_fetch_assoc($bookings_result)) {
                                $status_class = $booking['Status'] == 'CNF' ? 'status-cnf' : 'status-wtl';
                                echo '<tr>';
                                echo '<td>' . htmlspecialchars($booking['TicketID']) . '</td>';
                                echo '<td>' . htmlspecialchars($booking['PassengerName']) . '</td>';
                                echo '<td>' . htmlspecialchars($booking['TrainNumber']) . '</td>';
                                echo '<td>' . htmlspecialchars($booking['TrainName']) . '</td>';
                                echo '<td>' . htmlspecialchars($booking['FromStation']) . ' → ' . htmlspecialchars($booking['ToStation']) . '</td>';
                                echo '<td><span class="status-badge ' . $status_class . '">' . htmlspecialchars($booking['Status']) . '</span></td>';
                                echo '<td>₹' . htmlspecialchars($booking['Fare']) . '</td>';
                                echo '<td>' . date('M d, Y', strtotime($booking['BookingDate'])) . '</td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="8" style="text-align: center; color: #999;">No bookings yet</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <!-- Profile Section -->
            <div class="content-section" id="profile">
                <h2 class="section-title">My Profile</h2>
                <table>
                    <tr>
                        <th style="width: 150px;">Name</th>
                        <td><?php echo htmlspecialchars($user_info['Name']); ?></td>
                    </tr>
                    <tr>
                        <th>Email</th>
                        <td><?php echo htmlspecialchars($user_info['Email']); ?></td>
                    </tr>
                    <tr>
                        <th>Phone</th>
                        <td><?php echo htmlspecialchars($user_info['Phone']); ?></td>
                    </tr>
                    <tr>
                        <th>Account Type</th>
                        <td><span style="background: #e8f5e9; padding: 5px 10px; border-radius: 3px;">Regular User</span></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Edit Passenger Modal -->
    <div id="editModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div style="background: white; padding: 30px; border-radius: 8px; width: 90%; max-width: 500px;">
            <h3 style="margin-top: 0; color: #0066cc;">Edit Passenger</h3>
            <form method="POST" action="">
                <input type="hidden" id="edit_passenger_id" name="passenger_id">
                <div class="form-group">
                    <label for="edit_passenger_name">Passenger Name</label>
                    <input type="text" id="edit_passenger_name" name="passenger_name" required>
                </div>
                <div class="form-group">
                    <label for="edit_age">Age</label>
                    <input type="number" id="edit_age" name="age" required min="1" max="120">
                </div>
                <div class="form-group">
                    <label for="edit_gender">Gender</label>
                    <select id="edit_gender" name="gender" required>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" name="edit_passenger" class="btn btn-primary">Update</button>
                    <button type="button" class="btn" style="background: #ccc;" onclick="closeEditModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Book Train Modal -->
    <div id="bookModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div style="background: white; padding: 30px; border-radius: 8px; width: 90%; max-width: 500px;">
            <h3 style="margin-top: 0; color: #0066cc;">Book Ticket</h3>
            <p id="train-info" style="color: #666; margin-bottom: 20px;"></p>
                <form method="POST" action="">
                <input type="hidden" id="book_train_id" name="train_id">
                <div class="form-group">
                    <label for="book_passenger_id">Select Passenger(s)</label>
                    <select id="book_passenger_id" name="passenger_id[]" multiple size="4" required>
                        <?php
                        $passengers_result = mysqli_query($conn, "SELECT * FROM PASSENGER WHERE UserID = $user_id");
                        while ($passenger = mysqli_fetch_assoc($passengers_result)) {
                            echo '<option value="' . $passenger['PassengerID'] . '">' . htmlspecialchars($passenger['PassengerName']) . '</option>';
                        }
                        ?>
                    </select>
                    <p style="font-size:12px; color:#666; margin-top:8px;">Hold Ctrl (Windows) / Cmd (Mac) to select multiple.</p>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" name="book_ticket" class="btn btn-primary">Book Ticket</button>
                    <button type="button" class="btn" style="background: #ccc;" onclick="closeBookModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showSection(sectionId) {
            // Hide all sections
            document.querySelectorAll('.content-section').forEach(section => {
                section.classList.remove('active');
            });
            
            // Show selected section
            document.getElementById(sectionId).classList.add('active');
            
            // Update sidebar active link
            document.querySelectorAll('.sidebar-menu a').forEach(link => {
                link.classList.remove('active');
            });
            event.target.classList.add('active');
        }

        function editPassenger(id, name, age, gender) {
            document.getElementById('edit_passenger_id').value = id;
            document.getElementById('edit_passenger_name').value = name;
            document.getElementById('edit_age').value = age;
            document.getElementById('edit_gender').value = gender;
            document.getElementById('editModal').style.display = 'flex';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        function bookTrain(trainId, trainNumber) {
            document.getElementById('book_train_id').value = trainId;
            document.getElementById('train-info').textContent = 'Booking Train: ' + trainNumber;
            document.getElementById('bookModal').style.display = 'flex';
        }

        function closeBookModal() {
            document.getElementById('bookModal').style.display = 'none';
        }

        // Auto-hide messages
        setTimeout(function() {
            document.querySelectorAll('.message').forEach(function(msg) {
                msg.style.transition = 'opacity 0.5s';
                msg.style.opacity = '0';
                setTimeout(function() {
                    msg.style.display = 'none';
                }, 500);
            });
        }, 3000);
    </script>
</body>
</html>
