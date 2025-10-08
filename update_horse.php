<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo "Unauthorized";
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$horseId = $data['horse_id'];
$name = $data['horse_name'];
$feedingAmount = $data['feeding_amount'];
$feederId = $data['feeder_id'];
$userId = $_SESSION['user_id'];

$stmt = $conn->prepare("UPDATE horses SET name = ?, feeding_amount = ?, feeder_id = ? WHERE id = ? AND user_id = ?");
$stmt->bind_param("ssiii", $name, $feedingAmount, $feederId, $horseId, $userId);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => $stmt->error]);
}

$stmt->close();
$conn->close();
