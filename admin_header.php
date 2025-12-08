<?php
// v1/admin_header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Strict Staff Role Check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Cleaning Services</title>
    
    <!-- Styles -->
    <link rel="stylesheet" href="style.css">
    
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- FullCalendar -->
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>
</head>
<body>

<header class="admin-header">
    <div class="admin-brand">
        <i class="fas fa-broom"></i> CleanAdmin
    </div>
    <div class="nav-toggle" onclick="document.querySelector('.admin-nav').classList.toggle('active')">
        <i class="fas fa-bars"></i>
    </div>
    <nav class="admin-nav">
        <a href="admin_panel.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="admin_calendar.php"><i class="fas fa-calendar-alt"></i> Calendar</a>
        <a href="admin_revenue.php"><i class="fas fa-chart-line"></i> Revenue</a>
        <a href="admin_users.php"><i class="fas fa-users"></i> Users</a>
        <a href="admin_bookings.php"><i class="fas fa-list"></i> Bookings</a>
        <a href="admin_bookings.php?action=create" class="btn-new-booking"><i class="fas fa-plus"></i> New</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </nav>
</header>