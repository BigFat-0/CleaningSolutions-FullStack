<?php
// v1/admin_bookings.php
require_once 'db_connect.php';
require_once 'admin_header.php';

$message = '';

// Handle Create Booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_booking'])) {
    $user_id = $_POST['user_id'];
    $job_desc = $_POST['job_description'];
    $address = $_POST['service_address'];
    $date = $_POST['scheduled_date'];

    if ($user_id && $job_desc && $address && $date) {
        $stmt = $pdo->prepare("INSERT INTO bookings (user_id, job_description, service_address, scheduled_date, status) VALUES (?, ?, ?, ?, 'awaiting_quote')");
        if ($stmt->execute([$user_id, $job_desc, $address, $date])) {
            $message = "Booking created successfully.";
        } else {
            $message = "Error creating booking.";
        }
    }
}

// Handle Delete
if (isset($_GET['delete_id'])) {
    $stmt = $pdo->prepare("DELETE FROM bookings WHERE id = ?");
    if ($stmt->execute([$_GET['delete_id']])) {
        $message = "Booking deleted.";
    }
}

// Fetch All Bookings (Sorted by Status Priority)
// Priority: awaiting_quote > quoted > confirmed > completed > rejected > cancelled
$sql = "SELECT b.*, u.first_name, u.last_name, u.email, u.phone_number 
        FROM bookings b 
        JOIN users u ON b.user_id = u.id 
        ORDER BY FIELD(b.status, 'awaiting_quote', 'quoted', 'confirmed', 'completed', 'rejected', 'cancelled'), b.created_at DESC";
$bookings = $pdo->query($sql)->fetchAll();

// Fetch Users for Dropdown
$users = $pdo->query("SELECT id, first_name, last_name, email FROM users ORDER BY first_name ASC")->fetchAll();

$show_create = isset($_GET['action']) && $_GET['action'] == 'create';
?>

<div class="admin-container page-padding">
    <div class="page-header">
        <h1><i class="fas fa-list"></i> All Bookings</h1>
        <?php if (!$show_create): ?>
        <a href="?action=create" class="btn btn-primary">Create New Booking</a>
        <?php endif; ?>
    </div>

    <?php if ($message): ?>
        <div style="background: #d4edda; color: #155724; padding: 10px; margin-bottom: 20px; border-radius: 4px;">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <?php if ($show_create): ?>
    <div class="auth-card" style="margin-bottom: 30px;">
        <h3>Create Booking</h3>
        <form method="post">
            <div class="form-group">
                <label>Client</label>
                <select name="user_id" class="form-control" required>
                    <option value="">-- Select Client --</option>
                    <?php foreach ($users as $u): ?>
                    <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['first_name'] . ' ' . $u['last_name'] . ' (' . $u['email'] . ')'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Job Description</label>
                <textarea name="job_description" class="form-control" required></textarea>
            </div>
            <div class="form-group">
                <label>Service Address</label>
                <input type="text" name="service_address" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Scheduled Date</label>
                <input type="datetime-local" name="scheduled_date" class="form-control" required>
            </div>
            <div class="form-actions">
                <a href="admin_bookings.php" class="btn btn-danger">Cancel</a>
                <button type="submit" name="create_booking" class="btn btn-primary">Create</button>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th class="d-none-mobile">ID</th>
                    <th>Customer Name</th>
                    <th>Date</th>
                    <th class="d-none-mobile">Address</th>
                    <th>Status</th>
                    <th class="d-none-mobile">Quoted</th>
                    <th class="d-none-mobile">Bill</th>
                    <th class="d-none-mobile">Created At</th>
                    <th class="d-none-mobile">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bookings as $b): ?>
                <tr class="clickable-row" onclick="window.location='admin_booking_edit.php?id=<?php echo $b['id']; ?>'">
                    <td class="d-none-mobile">#<?php echo $b['id']; ?></td>
                    <td>
                        <?php echo htmlspecialchars($b['first_name'] . ' ' . $b['last_name']); ?><br>
                        <small><?php echo htmlspecialchars($b['phone_number']); ?></small>
                    </td>
                    <td><?php echo date('M d, H:i', strtotime($b['scheduled_date'])); ?></td>
                    <td class="d-none-mobile"><?php echo htmlspecialchars($b['service_address']); ?></td>
                    <td>
                        <span class="badge badge-<?php echo $b['status']; ?>">
                            <?php echo ucwords(str_replace('_', ' ', $b['status'])); ?>
                        </span>
                    </td>
                    <td class="d-none-mobile"><?php echo $b['quoted_price'] ? '$'.$b['quoted_price'] : '-'; ?></td>
                    <td class="d-none-mobile"><?php echo $b['actual_bill'] ? '<strong>$'.$b['actual_bill'].'</strong>' : '-'; ?></td>
                    <td class="d-none-mobile"><?php echo date('d M Y', strtotime($b['created_at'])); ?></td>
                    <td class="d-none-mobile">
                        <a href="admin_booking_edit.php?id=<?php echo $b['id']; ?>" class="btn btn-sm btn-primary" onclick="event.stopPropagation();"><i class="fas fa-edit"></i></a>
                        <a href="?delete_id=<?php echo $b['id']; ?>" class="btn btn-sm btn-danger" onclick="event.stopPropagation(); return confirm('Delete?');"><i class="fas fa-trash"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>