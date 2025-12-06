<?php
// v1/dashboard.php
require('auth_session.php');
require('db_connect.php');

$user_id = $_SESSION['user_id'];
$message = '';

// 1. Handle Profile Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $phone = trim($_POST['phone_number']);
    $billing = trim($_POST['billing_address']);

    if ($first_name && $last_name && $phone && $billing) {
        $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, phone_number = ?, billing_address = ? WHERE id = ?");
        if ($stmt->execute([$first_name, $last_name, $phone, $billing, $user_id])) {
            $message = "Profile updated successfully.";
        } else {
            $message = "Error updating profile.";
        }
    } else {
        $message = "All profile fields are required.";
    }
}

// 2. Handle Booking Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'], $_POST['action'])) {
    $booking_id = $_POST['booking_id'];
    $action = $_POST['action'];
    
    $new_status = '';
    $required_current_status = [];

    if ($action === 'accept') {
        $new_status = 'confirmed';
        $required_current_status = ['quoted'];
    } elseif ($action === 'reject') {
        $new_status = 'rejected';
        $required_current_status = ['quoted'];
    } elseif ($action === 'cancel') {
        $new_status = 'cancelled';
        $required_current_status = ['awaiting_quote', 'confirmed'];
    } elseif ($action === 'undo_reject') {
        $new_status = 'quoted'; 
        $required_current_status = ['rejected'];
    } elseif ($action === 'resubmit') {
        $new_status = 'awaiting_quote'; 
        $required_current_status = ['cancelled'];
    }

    if ($new_status) {
        $placeholders = implode(',', array_fill(0, count($required_current_status), '?'));
        $sql = "UPDATE bookings SET status = ? WHERE id = ? AND user_id = ? AND status IN ($placeholders)";
        $params = array_merge([$new_status, $booking_id, $user_id], $required_current_status);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        header("Location: dashboard.php"); 
        exit();
    }
}

// Fetch User
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Fetch Bookings
$stmt = $pdo->prepare("SELECT * FROM bookings WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$bookings = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Cleaning Platform</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>
</head>
<body>
    <header class="admin-header">
        <div class="admin-brand"><i class="fas fa-home"></i> MyDashboard</div>
        <div class="nav-toggle" onclick="document.querySelector('.admin-nav').classList.toggle('active')">
            <i class="fas fa-bars"></i>
        </div>
        <nav class="admin-nav">
            <a href="request_quote.php" class="btn-new-booking"><i class="fas fa-plus"></i> Request Quote</a>
            <a href="#" onclick="openProfileModal()"><i class="fas fa-user-edit"></i> Profile</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </header>

    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
            <h2>Welcome, <?php echo htmlspecialchars($user['first_name']); ?></h2>
        </div>

        <!-- Stats / Profile Summary Card -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3><i class="fas fa-user-circle"></i> My Profile</h3>
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($user['phone_number']); ?></p>
                <p><strong>Address:</strong> <?php echo htmlspecialchars($user['billing_address']); ?></p>
                <button onclick="openProfileModal()" class="btn btn-sm btn-secondary" style="margin-top:10px;">Edit Details</button>
            </div>
        </div>

        <!-- Tabs -->
        <div class="nav-tabs">
            <button class="active" onclick="switchTab('list')">My Bookings</button>
            <button onclick="switchTab('calendar')">Calendar</button>
        </div>

        <!-- List View -->
        <div id="tab-list" class="tab-content active">
            <div class="table-responsive">
                <?php if (count($bookings) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th class="mobile-hide">Description</th>
                                <th>Status</th>
                                <th>Amount</th>
                                <th class="mobile-hide">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $b): ?>
                                <tr class="clickable-row" onclick='openBookingModal(<?php echo json_encode($b); ?>)'>
                                    <td>
                                        <?php echo date('M d', strtotime($b['scheduled_date'])); ?><br>
                                        <small style="color:#777;"><?php echo date('H:i', strtotime($b['scheduled_date'])); ?></small>
                                    </td>
                                    <td class="mobile-hide"><?php echo htmlspecialchars(substr($b['job_description'], 0, 30)) . (strlen($b['job_description'])>30 ? '...' : ''); ?></td>
                                    <td><span class="status-badge status-<?php echo $b['status']; ?>"><?php echo ucfirst(str_replace('_', ' ', $b['status'])); ?></span></td>
                                    <td>
                                        <?php 
                                            if ($b['status'] == 'quoted' || $b['status'] == 'confirmed') echo '$'.$b['quoted_price'];
                                            elseif ($b['status'] == 'completed') echo '<strong>$'.$b['actual_bill'].'</strong>';
                                            else echo '-';
                                        ?>
                                    </td>
                                    <td class="mobile-hide" onclick="event.stopPropagation()">
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                            
                                            <?php if ($b['status'] == 'quoted'): ?>
                                                <button type="submit" name="action" value="accept" class="btn btn-sm btn-success"><i class="fas fa-check"></i></button>
                                                <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger"><i class="fas fa-times"></i></button>
                                            
                                            <?php elseif ($b['status'] == 'awaiting_quote' || $b['status'] == 'confirmed'): ?>
                                                <button type="submit" name="action" value="cancel" class="btn btn-sm btn-danger" onclick="return confirm('Cancel this booking?')">Cancel</button>
                                            
                                            <?php elseif ($b['status'] == 'rejected'): ?>
                                                <button type="submit" name="action" value="undo_reject" class="btn btn-sm btn-secondary">Undo</button>
                                            
                                            <?php elseif ($b['status'] == 'cancelled'): ?>
                                                <button type="submit" name="action" value="resubmit" class="btn btn-sm btn-success">Resubmit</button>
                                            
                                            <?php elseif ($b['status'] == 'completed'): ?>
                                                <span class="text-success"><i class="fas fa-check-circle"></i> Done</span>
                                            <?php endif; ?>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No bookings found.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Calendar View -->
        <div id="tab-calendar" class="tab-content">
            <div id="calendar" style="background:white; padding:10px;"></div>
        </div>
    </div>

    <!-- Profile Modal -->
    <div id="profileModal" class="modal">
        <div class="modal-content">
            <h3>Update Profile</h3>
            <form method="post">
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone_number" value="<?php echo htmlspecialchars($user['phone_number']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Billing Address</label>
                    <textarea name="billing_address" required rows="3"><?php echo htmlspecialchars($user['billing_address']); ?></textarea>
                </div>
                <div style="text-align: right; margin-top: 15px;">
                    <button type="button" onclick="closeProfileModal()" class="btn btn-danger">Cancel</button>
                    <button type="submit" name="update_profile" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Booking Details Modal -->
    <div id="bookingModal" class="modal">
        <div class="modal-content">
            <h3 id="modal-title">Booking Details</h3>
            <div id="modal-body">
                <p><strong>Date:</strong> <span id="modal-date"></span></p>
                <p><strong>Status:</strong> <span id="modal-status"></span></p>
                <p><strong>Address:</strong> <span id="modal-address"></span></p>
                <p><strong>Description:</strong></p>
                <p id="modal-desc" style="background:#f9f9f9; padding:10px; border-radius:4px;"></p>
                <p><strong>Price:</strong> <span id="modal-price"></span></p>
                
                <div id="modal-actions" style="margin-top: 20px; border-top: 1px solid #eee; padding-top: 10px;">
                    <!-- Actions will be injected here -->
                </div>
            </div>
            <button onclick="document.getElementById('bookingModal').style.display='none'" class="btn btn-secondary" style="width:100%; margin-top:10px;">Close</button>
        </div>
    </div>

    <script>
        function openProfileModal() { document.getElementById('profileModal').style.display = 'flex'; }
        function closeProfileModal() { document.getElementById('profileModal').style.display = 'none'; }

        function openBookingModal(b) {
            document.getElementById('bookingModal').style.display = 'flex';
            document.getElementById('modal-title').innerText = 'Booking #' + b.id;
            document.getElementById('modal-date').innerText = b.scheduled_date;
            document.getElementById('modal-status').innerText = b.status.replace('_', ' ').toUpperCase();
            document.getElementById('modal-address').innerText = b.service_address;
            document.getElementById('modal-desc').innerText = b.job_description;
            
            let price = '-';
            if (b.status == 'quoted' || b.status == 'confirmed') price = '$' + b.quoted_price;
            else if (b.status == 'completed') price = '$' + b.actual_bill;
            document.getElementById('modal-price').innerText = price;

            // Generate Actions
            let actionsHtml = '';
            if (b.status == 'quoted') {
                actionsHtml = `
                    <form method="post" style="display:flex; gap:10px;">
                        <input type="hidden" name="booking_id" value="${b.id}">
                        <button type="submit" name="action" value="accept" class="btn btn-success">Accept Quote</button>
                        <button type="submit" name="action" value="reject" class="btn btn-danger">Reject Quote</button>
                    </form>`;
            } else if (b.status == 'awaiting_quote' || b.status == 'confirmed') {
                actionsHtml = `
                    <form method="post">
                        <input type="hidden" name="booking_id" value="${b.id}">
                        <button type="submit" name="action" value="cancel" class="btn btn-danger" onclick="return confirm('Cancel this booking?')">Cancel Booking</button>
                    </form>`;
            } else if (b.status == 'rejected') {
                actionsHtml = `
                    <form method="post">
                        <input type="hidden" name="booking_id" value="${b.id}">
                        <button type="submit" name="action" value="undo_reject" class="btn btn-secondary">Undo Rejection</button>
                    </form>`;
            } else if (b.status == 'cancelled') {
                actionsHtml = `
                    <form method="post">
                        <input type="hidden" name="booking_id" value="${b.id}">
                        <button type="submit" name="action" value="resubmit" class="btn btn-success">Resubmit Request</button>
                    </form>`;
            }
            document.getElementById('modal-actions').innerHTML = actionsHtml;
        }

        function switchTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.nav-tabs button').forEach(el => el.classList.remove('active'));
            document.getElementById('tab-' + tabName).classList.add('active');
            
            if(tabName === 'list') document.querySelector('.nav-tabs button:nth-child(1)').classList.add('active');
            if(tabName === 'calendar') {
                document.querySelector('.nav-tabs button:nth-child(2)').classList.add('active');
                setTimeout(() => { calendar.render(); }, 100);
            }
        }

        var calendar;
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var isMobile = window.innerWidth < 768;
            
            calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: isMobile ? 'listWeek' : 'dayGridMonth',
                events: 'api_customer_calendar.php',
                headerToolbar: isMobile ? {
                    left: 'prev,next',
                    center: 'title',
                    right: 'listWeek'
                } : {
                    left: 'prev,next',
                    center: 'title',
                    right: 'dayGridMonth,listMonth'
                },
                height: 'auto'
            });
            calendar.render();
        });
    </script>
</body>
</html>