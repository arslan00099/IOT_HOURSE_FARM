<?php
include('config.php');

session_start();

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $stmt = $conn->prepare("SELECT id, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($id, $hashedPassword);
        $stmt->fetch();

        if (password_verify($password, $hashedPassword)) {
            // ✅ Save both user ID and email in session
            $_SESSION['user_id'] = $id;
            $_SESSION['email'] = $email;

            header("Location: dashboard.php");
            exit();
        } else {
            echo "<script>alert('❌ Incorrect password!'); window.location.href='index.html';</script>";
        }
    } else {
        echo "<script>alert('❌ Email not found!'); window.location.href='index.html';</script>";
    }

    $stmt->close();
}
$conn->close();
?>
