<?php
// v1/api_calendar.php
require_once 'db_connect.php';
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header('HTTP/1.1 403 Forbidden');
    exit;
}

$stmt = $pdo->query("SELECT b.id, b.scheduled_date, b.status, b.job_description, u.email 
                     FROM bookings b 
                     JOIN users u ON b.user_id = u.id");
$bookings = $stmt->fetchAll();

$events = [];
foreach ($bookings as $b) {
    $color = '#3788d8'; // default
    switch ($b['status']) {
        case 'confirmed': $color = '#27ae60'; break;
        case 'pending': 
        case 'awaiting_quote': $color = '#f39c12'; break;
        case 'completed': $color = '#7f8c8d'; break;
        case 'cancelled': $color = '#c0392b'; break;
    }

    $events[] = [
        'id' => $b['id'],
        'title' => ucfirst($b['status']) . ': ' . $b['email'],
        'start' => $b['scheduled_date'],
        'color' => $color,
        'url' => 'admin_booking_edit.php?id=' . $b['id']
    ];
}

header('Content-Type: application/json');
echo json_encode($events);
?>
