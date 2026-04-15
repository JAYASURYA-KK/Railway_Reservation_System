<?php
$host = getenv("DB_HOST");
$user = getenv("DB_USER");
$pass = getenv("DB_PASS");
$db   = getenv("DB_NAME");
$port = getenv("DB_PORT");

// Initialize connection
$conn = mysqli_init();

// REQUIRED for Aiven SSL
mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);

// Connect securely
if (!mysqli_real_connect($conn, $host, $user, $pass, $db, $port, NULL, MYSQLI_CLIENT_SSL)) {
    die("Connection failed: " . mysqli_connect_error());
}

// optional cleanup
unset($host, $user, $pass, $db, $port);
?>