<?php
// v1/api_customer_calendar.php
require('auth_session.php');
require('db_connect.php');

$user_id = $_SESSION['user_id'];

// Fetch active bookings (exclude cancelled/rejected)
$stmt = $pdo->prepare("SELECT id, job_description, scheduled_date, status FROM bookings WHERE user_id = ? AND status NOT IN ('cancelled', 'rejected')");
$stmt->execute([$user_id]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

$events = [];
foreach ($bookings as $row) {
    $color = '#3788d8'; // default
    switch ($row['status']) {
        case 'confirmed':
            $color = '#28a745'; // Green
            break;
        case 'awaiting_quote':
            $color = '#fd7e14'; // Orange
            break;
        case 'quoted':
            $color = '#17a2b8'; // Blue
            break;
        case 'completed':
            $color = '#6c757d'; // Grey
            break;
    }

    $events[] = [
        'title' => 'Service: ' . substr($row['job_description'], 0, 15) . '...',
        'start' => $row['scheduled_date'],
        'color' => $color,
        'url'   => '#' // could link to specific details if implemented
    ];
}

header('Content-Type: application/json');
echo json_encode($events);
?>