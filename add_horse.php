<?php
session_start();


require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo "User not logged in.";
    exit;
}

$userId = $_SESSION['user_id'];

$name = $_POST['horse_name'];
$age = $_POST['horse_age'];
$breed = $_POST['horse_breed'];
$location = $_POST['stable_location'];
$amount = $_POST['feeding_schedule'];
$feederId = $_POST['feeder_id'];

$targetDir = "uploads/";
$imageName = uniqid() . "_" . basename($_FILES["horse_image"]["name"]);
$targetFile = $targetDir . $imageName;

if (move_uploaded_file($_FILES["horse_image"]["tmp_name"], $targetFile)) {
    $stmt = $conn->prepare("INSERT INTO horses (user_id, feeder_id, name, age, breed, stable_location, feeding_amount, image_path)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iissssss", $userId, $feederId, $name, $age, $breed, $location, $amount, $targetFile);

    if ($stmt->execute()) {
         header("Location: dashboard.php");
        exit();
    } else {
        echo "❌ Error: " . $stmt->error;
    }

    $stmt->close();
} else {
    echo "❌ Failed to upload image.";
}
?>
