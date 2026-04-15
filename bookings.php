<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'user') {
    header('Location: index.php');
    exit();
}

require_once 'db.php';

$user_id = (int)$_SESSION['user_id'];

$bookings_query = "SELECT t.TicketID, p.PassengerName, tr.TrainNumber, tr.TrainName, tr.FromStation, 
                 tr.ToStation, tr.DepartureTime, tr.ArrivalTime, t.Status, t.BookingDate, t.Fare
                   FROM TICKET t
                   JOIN PASSENGER p ON t.PassengerID = p.PassengerID
                   JOIN TRAIN tr ON t.TrainID = tr.TrainID
                   WHERE t.UserID = $user_id
                   ORDER BY t.BookingDate DESC";

$bookings_result = mysqli_query($conn, $bookings_query);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>My Bookings</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .container { padding: 20px; }
        .status-badge { padding: 5px 10px; border-radius: 4px; font-weight: 600; }
        .status-cnf { background:#d1fae5; color:#065f46; }
        .status-wtl { background:#fef3c7; color:#92400e; }
        .status-rjd { background:#fee2e2; color:#7f1d1d; }
    </style>
</head>
<body>
    <div class="container">
        <h2>My Bookings</h2>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Ticket ID</th>
                        <th>Passenger</th>
                        <th>Train</th>
                        <th>Route</th>
                        <th>Dep</th>
                        <th>Arr</th>
                        <th>Status</th>
                        <th>Fare (₹)</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($bookings_result && mysqli_num_rows($bookings_result) > 0) {
                        while ($b = mysqli_fetch_assoc($bookings_result)) {
                            $cls = ($b['Status'] == 'CNF') ? 'status-cnf' : (($b['Status']=='WTL') ? 'status-wtl' : 'status-rjd');
                            echo '<tr>';
                            echo '<td>' . htmlspecialchars($b['TicketID']) . '</td>';
                            echo '<td>' . htmlspecialchars($b['PassengerName']) . '</td>';
                            echo '<td>' . htmlspecialchars($b['TrainNumber']) . ' - ' . htmlspecialchars($b['TrainName']) . '</td>';
                            echo '<td>' . htmlspecialchars($b['FromStation']) . ' → ' . htmlspecialchars($b['ToStation']) . '</td>';
                            echo '<td>' . (!empty($b['DepartureTime'])?htmlspecialchars(substr($b['DepartureTime'],0,5)):'-') . '</td>';
                            echo '<td>' . (!empty($b['ArrivalTime'])?htmlspecialchars(substr($b['ArrivalTime'],0,5)):'-') . '</td>';
                            echo '<td><span class="status-badge ' . $cls . '">' . htmlspecialchars($b['Status']) . '</span></td>';
                            echo '<td>₹' . htmlspecialchars($b['Fare']) . '</td>';
                            echo '<td>' . date('M d, Y', strtotime($b['BookingDate'])) . '</td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="9" style="text-align:center; color:#666;">No bookings found</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <p style="margin-top:12px;"><a href="user_dashboard.php">Back to Dashboard</a></p>
    </div>
</body>
</html>
