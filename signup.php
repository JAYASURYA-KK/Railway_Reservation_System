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

$signup_error = '';
$signup_success = '';

// Handle signup form
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['signup_btn'])) {
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($full_name) || empty($email) || empty($phone) || empty($password)) {
        $signup_error = "All fields are required!";
    } elseif (strlen($password) < 6) {
        $signup_error = "Password must be at least 6 characters!";
    } elseif ($password !== $confirm_password) {
        $signup_error = "Passwords do not match!";
    } else {
        // Check if email already exists
        $check_query = "SELECT Email FROM USERS WHERE Email = '$email'";
        $check_result = mysqli_query($conn, $check_query);
        
        if (mysqli_num_rows($check_result) > 0) {
            $signup_error = "Email already registered!";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            
            // Insert user
            $insert_query = "INSERT INTO USERS (Name, Email, Phone, Password, Role) 
                           VALUES ('$full_name', '$email', '$phone', '$hashed_password', 'user')";
            
            if (mysqli_query($conn, $insert_query)) {
                $signup_success = "Account created successfully! Redirecting to login...";
                header("Refresh: 2; url=index.php");
            } else {
                $signup_error = "Error creating account: " . mysqli_error($conn);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Train Booking System</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('public/images/rail.jpg');
            background-size: cover;
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Arial', sans-serif;
            padding: 20px;
        }
        .signup-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            padding: 40px;
            width: 100%;
            max-width: 500px;
            animation: slideUp 0.5s ease-out;
        }
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .signup-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .signup-header h1 {
            color: #0066cc;
            font-size: 28px;
            margin: 0;
        }
        .signup-header p {
            color: #666;
            margin: 5px 0 0 0;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .form-row.full {
            grid-template-columns: 1fr;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"],
        textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }
        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus,
        textarea:focus {
            outline: none;
            border-color: #0066cc;
            box-shadow: 0 0 5px rgba(0, 102, 204, 0.3);
        }
        textarea {
            resize: vertical;
            min-height: 80px;
        }
        .signup-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #0066cc, #004999);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .signup-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 102, 204, 0.4);
        }
        .error-message {
            background: #fee;
            color: #c00;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #c00;
            display: none;
        }
        .error-message.show {
            display: block;
        }
        .success-message {
            background: #efe;
            color: #060;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #060;
            display: none;
        }
        .success-message.show {
            display: block;
        }
        .login-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
        .login-link p {
            margin: 0;
            color: #666;
        }
        .login-link a {
            color: #0066cc;
            text-decoration: none;
            font-weight: 600;
        }
        .login-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="signup-container">
        <div class="signup-header">
            <h1>Create Account</h1>
            <p>Join our train booking platform</p>
        </div>

        <?php if (!empty($signup_error)): ?>
        <div class="error-message show">
            <?php echo htmlspecialchars($signup_error); ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($signup_success)): ?>
        <div class="success-message show">
            <?php echo htmlspecialchars($signup_success); ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-row">
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" required placeholder="John Doe">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required placeholder="john@example.com">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="text" id="phone" name="phone" required placeholder="(555) 123-4567">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required placeholder="Min 6 characters" minlength="6">
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required placeholder="Confirm password" minlength="6">
                </div>
            </div>

            <button type="submit" name="signup_btn" class="signup-btn">Create Account</button>
        </form>

        <div class="login-link">
            <p>Already have an account? <a href="index.php">Login Here</a></p>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>
