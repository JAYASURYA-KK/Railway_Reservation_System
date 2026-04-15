<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'user') {
    header('Location: index.php');
    exit();
}

$servername = "mysql-3475dc67-jayasurya272007-0f36.i.aivencloud.com"; 
$username = "avnadmin"; 
$password = "avnadmin";
$dbname = "train_booking";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die('Connection failed: ' . $conn->connect_error);

$user_id = $_SESSION['user_id'];
$train_id = isset($_GET['train_id']) ? (int)$_GET['train_id'] : 0;
$success_msg = '';
$error_msg = '';

// Get train info
$train = null;
if ($train_id) {
    $res = mysqli_query($conn, "SELECT * FROM TRAIN WHERE TrainID = $train_id");
    if ($res && mysqli_num_rows($res) > 0) $train = mysqli_fetch_assoc($res);
}

// Handle booking POST (multiple passengers)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_ticket'])) {
    $train_id = (int)$_POST['train_id'];
    $selected = $_POST['passenger_id'];
    $passenger_ids = is_array($selected) ? $selected : array($selected);

    $train_q = mysqli_query($conn, "SELECT * FROM TRAIN WHERE TrainID = $train_id");
    $train = mysqli_fetch_assoc($train_q);
    $booked_q = mysqli_query($conn, "SELECT COUNT(*) as booked FROM TICKET WHERE TrainID = $train_id AND Status = 'CNF'");
    $booked = mysqli_fetch_assoc($booked_q);
    $booked_count = (int)$booked['booked'];

    $booking_date = date('Y-m-d');
    $results = array();
    foreach ($passenger_ids as $pid) {
        $pid = (int)$pid;
        if ($booked_count < (int)$train['TotalSeats']) { $status = 'CNF'; $fare = $train['Fare']; $booked_count++; }
        else { $status = 'WTL'; $fare = 0; }

        $ins = "INSERT INTO TICKET (UserID, TrainID, PassengerID, Status, BookingDate, Fare) VALUES ($user_id, $train_id, $pid, '$status', '$booking_date', $fare)";
        if (mysqli_query($conn, $ins)) $results[] = array('pid'=>$pid,'status'=>$status);
        else $results[] = array('pid'=>$pid,'status'=>'ERROR','error'=>mysqli_error($conn));
    }

    $success = array(); $errors = array();
    foreach ($results as $r) { if ($r['status']==='ERROR') $errors[] = 'PID '.$r['pid'].': '.$r['error']; else $success[] = 'PID '.$r['pid'].'('.$r['status'].')'; }
    if (count($success)) $success_msg = 'Tickets: '.implode(', ',$success);
    if (count($errors)) $error_msg = 'Errors: '.implode('; ',$errors);
}

// Get user's passengers
$passengers_result = mysqli_query($conn, "SELECT * FROM PASSENGER WHERE UserID = $user_id");

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Book Ticket</title>
    <link rel="stylesheet" href="style.css">
    <style>.container{padding:20px;} select[multiple]{min-height:120px;}</style>
</head>
<body>
    <div class="container">
        <h2>Book Ticket</h2>
        <?php if ($train): ?>
            <p><strong><?php echo htmlspecialchars($train['TrainNumber'].' - '.$train['TrainName']); ?></strong></p>
            <p style="margin:6px 0 12px 0; color:#444; font-size:14px;">Departure: <?php echo !empty($train['DepartureTime'])?htmlspecialchars(substr($train['DepartureTime'],0,5)):'-'; ?> &nbsp; | &nbsp; Arrival: <?php echo !empty($train['ArrivalTime'])?htmlspecialchars(substr($train['ArrivalTime'],0,5)):'-'; ?></p>
            <?php if ($success_msg): ?><div class="alert alert-success"><?php echo htmlspecialchars($success_msg); ?></div><?php endif; ?>
            <?php if ($error_msg): ?><div class="alert alert-error"><?php echo htmlspecialchars($error_msg); ?></div><?php endif; ?>

            <form method="POST" action="booking.php?train_id=<?php echo $train_id; ?>">
                <input type="hidden" name="train_id" value="<?php echo $train_id; ?>">
                <div class="form-section">
                    <div class="form-group">
                        <label for="passenger_id">Select Passenger(s)</label>
                        <select id="passenger_id" name="passenger_id[]" multiple required>
                            <?php while ($p = mysqli_fetch_assoc($passengers_result)) { echo '<option value="'.$p['PassengerID'].'">'.htmlspecialchars($p['PassengerName']).'</option>'; } ?>
                        </select>
                        <p style="font-size:12px; color:#666; margin-top:8px;">Hold Ctrl (Windows) / Cmd (Mac) to select multiple.</p>
                    </div>
                </div>
                <div style="margin-top:12px;">
                    <button class="btn btn-primary" type="submit" name="book_ticket">Confirm Booking</button>
                    <a href="search.php" class="btn" style="background:#eee; margin-left:8px;">Back</a>
                </div>
            </form>
        <?php else: ?>
            <p>Train not found. <a href="search.php">Search trains</a>.</p>
        <?php endif; ?>
    </div>
</body>
</html>
