<?php
// v1/admin_panel.php (Dashboard)
require_once 'db_connect.php';
require_once 'admin_header.php';

// Today's Bookings
$today = date('Y-m-d');
$stmt = $pdo->prepare("SELECT b.*, u.email, u.last_name FROM bookings b JOIN users u ON b.user_id = u.id WHERE DATE(scheduled_date) = ? ORDER BY scheduled_date ASC");
$stmt->execute([$today]);
$todays_bookings = $stmt->fetchAll();

// Recent Requests (Awaiting Quote)
$stmt = $pdo->query("SELECT b.*, u.email FROM bookings b JOIN users u ON b.user_id = u.id WHERE status = 'awaiting_quote' ORDER BY created_at DESC LIMIT 5");
$recent_requests = $stmt->fetchAll();
?>

<div class="admin-main">
    <div class="page-header" style="margin-bottom: var(--spacing-lg);">
        <h1>Dashboard Overview</h1>
    </div>

    <div class="stats-grid">
        <!-- Today's Schedule -->
        <div class="stat-card" style="grid-column: span 2; border-left-color: var(--accent-color);">
            <h3><i class="fas fa-calendar-day"></i> Today's Schedule (<?php echo date('M d'); ?>)</h3>
            <?php if (count($todays_bookings) > 0): ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th class="time-col">Time</th>
                                <th class="name-col">Client</th>
                                <th class="d-none-mobile">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($todays_bookings as $b): ?>
                            <tr class="clickable-row" onclick="window.location='admin_booking_edit.php?id=<?php echo $b['id']; ?>'">
                                <td class="time-col"><strong><?php echo date('H:i', strtotime($b['scheduled_date'])); ?></strong></td>
                                <td class="name-col">
                                    <?php echo htmlspecialchars($b['last_name']); ?>
                                    <small class="d-none-mobile" style="display:block; color:var(--text-light);"><?php echo htmlspecialchars($b['email']); ?></small>
                                </td>
                                <td class="d-none-mobile"><span class="status-badge status-<?php echo $b['status']; ?>"><?php echo $b['status']; ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="color: var(--text-light); padding: 10px 0;">No bookings scheduled for today.</p>
            <?php endif; ?>
            <a href="admin_calendar.php" class="btn btn-sm btn-primary" style="margin-top: 10px;"><i class="fas fa-calendar-alt"></i> View Calendar</a>
        </div>

        <!-- Recent Requests -->
        <div class="stat-card" style="border-left-color: var(--secondary-color);">
            <h3><i class="fas fa-clock"></i> Recent Requests</h3>
            <?php if (count($recent_requests) > 0): ?>
                <ul style="list-style: none; padding: 0;">
                    <?php foreach ($recent_requests as $r): ?>
                        <li style="margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #eee;">
                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <span>#<?php echo $r['id']; ?> - <small><?php echo htmlspecialchars($r['email']); ?></small></span>
                                <a href="admin_booking_edit.php?id=<?php echo $r['id']; ?>" class="btn btn-sm btn-primary">Review</a>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p style="color: var(--text-light);">No pending requests.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Quick Links -->
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <a href="admin_bookings.php?action=create" class="btn btn-primary"><i class="fas fa-plus"></i> Create Booking</a>
        <a href="admin_users.php" class="btn btn-secondary"><i class="fas fa-users"></i> Manage Users</a>
    </div>

</div>
</body>
</html>