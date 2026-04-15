<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: index.php');
    exit();
}

require_once 'db.php';

$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['set_status'])) {
        $train_id = (int)$_POST['train_id'];
        $status = mysqli_real_escape_string($conn, $_POST['status']);
        if ($train_id>0 && in_array($status, ['CNF','WTL','RJD'])) {
            // Update all tickets for this train to the new status
            $up = "UPDATE TICKET SET Status='$status' WHERE TrainID=$train_id";
            if (mysqli_query($conn,$up)) {
                $success_msg = 'Status set and bookings updated.';
            } else {
                $error_msg = 'Failed to update tickets: '.mysqli_error($conn);
            }
        } else {
            $error_msg = 'Invalid train or status.';
        }
    }
}

$trains = mysqli_query($conn, "SELECT TrainID, TrainNumber, TrainName FROM TRAIN ORDER BY TrainNumber");
// Derive recent status updates from ticket history (most recent ticket update per train/status)
$statuses = mysqli_query($conn, "SELECT tr.TrainNumber, t.Status, MAX(t.CreatedAt) AS UpdatedAt 
                               FROM TICKET t 
                               JOIN TRAIN tr ON t.TrainID = tr.TrainID 
                               GROUP BY tr.TrainNumber, t.Status 
                               ORDER BY UpdatedAt DESC LIMIT 50");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Admin - Train Status</title>
    <link rel="stylesheet" href="style.css">
    <style>.container{padding:20px;} .status-select{width:200px;}</style>
</head>
<body>
    <div class="container">
        <h2>Update Train Status</h2>
        <?php if ($success_msg): ?><div class="alert alert-success"><?php echo htmlspecialchars($success_msg); ?></div><?php endif; ?>
        <?php if ($error_msg): ?><div class="alert alert-error"><?php echo htmlspecialchars($error_msg); ?></div><?php endif; ?>

        <div class="form-section">
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="train_id">Select Train</label>
                        <select id="train_id" name="train_id" required>
                            <option value="">Choose</option>
                            <?php while ($t = mysqli_fetch_assoc($trains)) { echo '<option value="'.$t['TrainID'].'">'.htmlspecialchars($t['TrainNumber'].' - '.$t['TrainName']).'</option>'; } ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" class="status-select">
                            <option value="CNF">CNF</option>
                            <option value="WTL">WTL</option>
                            <option value="RJD">RJD</option>
                        </select>
                    </div>
                </div>
                <div style="margin-top:12px;"><button class="btn btn-primary" name="set_status" type="submit">Set Status & Update Bookings</button></div>
            </form>
        </div>

        

        <p style="margin-top:12px;"><a href="admin_dashboard.php">Back to Admin Dashboard</a></p>
    </div>
</body>
</html>
