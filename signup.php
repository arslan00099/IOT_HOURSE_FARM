<?php
include('config.php');

$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$confirm = $_POST['confirm'] ?? '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if ($password !== $confirm) {
        echo "❌ Passwords do not match.";
        exit;
    }

    $hashed = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $email, $hashed);

if ($stmt->execute()) {
    header("Location: index.html");
    exit();
} else {
    echo "❌ Error: " . $stmt->error;
}

    $stmt->close();
}
$conn->close();
?>
