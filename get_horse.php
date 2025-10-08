<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (!isset($_GET['horse_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing horse ID']);
    exit;
}

$horseId = $_GET['horse_id'];
$userId = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT id, name, feeding_amount, feeder_id FROM horses WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $horseId, $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode($row);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Horse not found']);
}

$stmt->close();
$conn->close();
