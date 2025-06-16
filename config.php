<?php
$host = '127.0.0.1';          // MySQL host (localhost if using host port 3306)
$port = 3306;                 // MySQL port
$dbname = 'app_db';           // Your database name
$username = 'app_user';       // MySQL username
$password = 'app_pass123';    // MySQL user password

// Create connection
$conn = new mysqli($host, $username, $password, $dbname, $port);

// Check connection
if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}

//echo "✅ Successfully connected to MySQL database '$dbname' as user '$username'";
?>
