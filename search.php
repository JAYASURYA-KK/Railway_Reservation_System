<?php
session_start();
// Simple search page reusing user session
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'user') {
    header('Location: index.php');
    exit();
}

require_once 'db.php';

$stations_result = mysqli_query($conn, "SELECT DISTINCT StationName FROM STATION ORDER BY StationName");

$filters = array();
if (!empty($_GET['source'])) {
    $src = mysqli_real_escape_string($conn, $_GET['source']);
    $filters[] = "FromStation = '$src'";
}
if (!empty($_GET['destination'])) {
    $dst = mysqli_real_escape_string($conn, $_GET['destination']);
    $filters[] = "ToStation = '$dst'";
}

$trains_query = "SELECT * FROM TRAIN";
if (count($filters) > 0) $trains_query .= ' WHERE ' . implode(' AND ', $filters);
$trains_query .= ' ORDER BY TrainNumber';
$trains_result = mysqli_query($conn, $trains_query);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Search Trains</title>
    <link rel="stylesheet" href="style.css">
    <style> .container { padding:20px; } </style>
</head>
<body>
    <div class="container">
        <h2>Search Trains</h2>
        <div class="section">
            <form method="GET" action="search.php">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="source">From</label>
                        <select id="source" name="source">
                            <option value="">Any</option>
                            <?php mysqli_data_seek($stations_result, 0); while ($st = mysqli_fetch_assoc($stations_result)) { $sel = (isset($_GET['source']) && $_GET['source']===$st['StationName'])? ' selected' : ''; echo '<option value="'.htmlspecialchars($st['StationName']).'"'.$sel.'>'.htmlspecialchars($st['StationName']).'</option>'; } ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="destination">To</label>
                        <select id="destination" name="destination">
                            <option value="">Any</option>
                            <?php mysqli_data_seek($stations_result, 0); while ($st = mysqli_fetch_assoc($stations_result)) { $sel = (isset($_GET['destination']) && $_GET['destination']===$st['StationName'])? ' selected' : ''; echo '<option value="'.htmlspecialchars($st['StationName']).'"'.$sel.'>'.htmlspecialchars($st['StationName']).'</option>'; } ?>
                        </select>
                    </div>

                    <div style="display:flex; align-items:flex-end; gap:8px;">
                        <div style="width:100%;"></div>
                        <div>
                            <button class="btn btn-primary" type="submit">Search</button>
                        </div>
                        <div>
                            <a href="search.php" class="btn" style="background:#eee;">Reset</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <h3 style="margin-top:20px;">Results</h3>
        <table class="table">
            <thead><tr><th>Train No</th><th>Name</th><th>From</th><th>To</th><th>Dep</th><th>Arr</th><th>Fare</th><th>Book</th></tr></thead>
            <tbody>
                <?php if (mysqli_num_rows($trains_result) > 0) { while ($train = mysqli_fetch_assoc($trains_result)) {
                    echo '<tr>';
                    echo '<td>'.htmlspecialchars($train['TrainNumber']).'</td>';
                    echo '<td>'.htmlspecialchars($train['TrainName']).'</td>';
                    echo '<td>'.htmlspecialchars($train['FromStation']).'</td>';
                    echo '<td>'.htmlspecialchars($train['ToStation']).'</td>';
                    echo '<td>'.(!empty($train['DepartureTime'])?htmlspecialchars(substr($train['DepartureTime'],0,5)):'-').'</td>';
                    echo '<td>'.(!empty($train['ArrivalTime'])?htmlspecialchars(substr($train['ArrivalTime'],0,5)):'-').'</td>';
                    echo '<td>₹'.htmlspecialchars($train['Fare']).'</td>';
                    echo '<td><a class="btn btn-primary" href="booking.php?train_id='.$train['TrainID'].'">Book</a></td>';
                    echo '</tr>';
                } } else { echo '<tr><td colspan="8">No trains found</td></tr>'; } ?>
            </tbody>
        </table>
        <p style="margin-top:12px;"><a href="user_dashboard.php">Back to Dashboard</a></p>
    </div>
</body>
</html>
