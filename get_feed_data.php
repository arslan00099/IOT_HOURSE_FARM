<?php
// get_feed_data.php
require_once 'config.php'; // Include your DB connection here

header('Content-Type: application/json');

// Get horse feed info (manual/scheduled feeders)
$sql = "
    SELECT 
        horses.id AS horse_id,
        horses.name AS horse_name,
        horses.image_path AS horse_image,
        feeders.type AS feeder_type,
        IFNULL(feed_data.feed_count, 0) AS feed_count,
        IFNULL(feed_data.last_feed_time, NULL) AS last_feed_time
    FROM horses
    JOIN feeders ON horses.feeder_id = feeders.id
    LEFT JOIN (
        SELECT 
            horse_id,
            COUNT(*) AS feed_count,
            MAX(feed_time) AS last_feed_time
        FROM feed_logs
        GROUP BY horse_id
    ) AS feed_data ON horses.id = feed_data.horse_id
    WHERE feeders.type IN ('manual', 'scheduled')
";

$result = $conn->query($sql);
$horses = [];

while ($row = $result->fetch_assoc()) {
    $horses[] = [
        'id' => $row['horse_id'],
        'name' => $row['horse_name'],
        'image' => $row['horse_image'] ?: 'default-horse.jpg',
        'feeder_type' => $row['feeder_type'],
        'feed_count' => $row['feed_count'],
        'last_feed_time' => $row['last_feed_time']
    ];
}

echo json_encode(['success' => true, 'horses' => $horses]);
