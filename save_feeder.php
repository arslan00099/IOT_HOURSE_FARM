<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

include('config.php');

$name = $_POST['name'];
$location = $_POST['location'];
$type = $_POST['type'];
$morning = $_POST['morning'] ?? null;
$day = $_POST['day'] ?? null;
$night = $_POST['night'] ?? null;

$sql = "INSERT INTO feeders (user_id, name, location, type, morning_time, day_time, night_time) VALUES (?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("issssss", $user_id, $name, $location, $type, $morning, $day, $night);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => $stmt->error]);
}
?>
