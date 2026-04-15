<?php
session_start();
// Admin-only page to view all bookings
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

$query = "SELECT t.TicketID, p.PassengerName, tr.TrainNumber, tr.TrainName, tr.FromStation, tr.ToStation, tr.DepartureTime, tr.ArrivalTime, t.Status, t.BookingDate, u.Email as UserEmail
          FROM TICKET t
          JOIN PASSENGER p ON t.PassengerID = p.PassengerID
          JOIN TRAIN tr ON t.TrainID = tr.TrainID
          JOIN USERS u ON t.UserID = u.UserID
          ORDER BY t.BookingDate DESC";

$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Admin - View Bookings</title>
    <link rel="stylesheet" href="style.css">
    <style>.container{padding:20px;}.status-badge{padding:5px 8px;border-radius:4px;font-weight:600;}</style>
</head>
<body>
    <div class="container">
        <h2>All Bookings</h2>
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Ticket ID</th><th>Passenger</th><th>User Email</th><th>Train</th><th>Route</th><th>Dep</th><th>Arr</th><th>Status</th><th>Date</th></tr></thead>
                <tbody>
                <?php if ($result && mysqli_num_rows($result) > 0) {
                    while ($r = mysqli_fetch_assoc($result)) {
                        $cls = $r['Status']=='CNF' ? 'status-cnf' : ($r['Status']=='WTL' ? 'status-wtl' : 'status-rjd');
                        echo '<tr>';
                        echo '<td>'.htmlspecialchars($r['TicketID']).'</td>';
                        echo '<td>'.htmlspecialchars($r['PassengerName']).'</td>';
                        echo '<td>'.htmlspecialchars($r['UserEmail']).'</td>';
                        echo '<td>'.htmlspecialchars($r['TrainNumber']).' - '.htmlspecialchars($r['TrainName']).'</td>';
                        echo '<td>'.htmlspecialchars($r['FromStation']).' → '.htmlspecialchars($r['ToStation']).'</td>';
                        echo '<td>'.(!empty($r['DepartureTime'])?htmlspecialchars(substr($r['DepartureTime'],0,5)):'-').'</td>';
                        echo '<td>'.(!empty($r['ArrivalTime'])?htmlspecialchars(substr($r['ArrivalTime'],0,5)):'-').'</td>';
                        echo '<td><span class="status-badge '.$cls.'">'.htmlspecialchars($r['Status']).'</span></td>';
                        echo '<td>'.date('M d, Y', strtotime($r['BookingDate'])).'</td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="9" style="text-align:center;color:#666;">No bookings found</td></tr>';
                } ?>
                </tbody>
            </table>
        </div>
        <p style="margin-top:12px;"><a href="admin_dashboard.php">Back to Admin Dashboard</a></p>
    </div>
</body>
</html>
