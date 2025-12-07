<?php
// v1/admin_booking_edit.php
require_once 'db_connect.php';
require_once 'admin_header.php';
require_once 'n8n_helper.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: admin_bookings.php");
    exit;
}

// Fetch Booking
$stmt = $pdo->prepare("SELECT b.*, u.email, u.phone_number FROM bookings b JOIN users u ON b.user_id = u.id WHERE b.id = ?");
$stmt->execute([$id]);
$booking = $stmt->fetch();

if (!$booking) {
    die("Booking not found.");
}

$message = '';

// Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $job_desc = $_POST['job_description'];
    $address = $_POST['service_address'];
    $date = $_POST['scheduled_date'];
    $status = $_POST['status'];
    $quoted = $_POST['quoted_price'];
    $bill = $_POST['actual_bill'];

    $sql = "UPDATE bookings SET job_description=?, service_address=?, scheduled_date=?, status=?, quoted_price=?, actual_bill=? WHERE id=?";
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute([$job_desc, $address, $date, $status, $quoted ?: null, $bill ?: null, $id])) {
        // Webhook: Status Update
        // Only fire if status changed? Prompt implies just "Trigger 'Status Update'".
        // I will trigger it on every update for simplicity, or I could check if status changed. 
        // "Find the logic where the Admin updates the status (e.g., UPDATE bookings SET status...)."
        // It implies whenever that query runs.
        sendWebhook('status-update', [
            'id' => $id,
            'status' => $status
        ]);

        $message = "Booking updated successfully.";
        // Refresh data
        $stmt = $pdo->prepare("SELECT b.*, u.email FROM bookings b JOIN users u ON b.user_id = u.id WHERE b.id = ?");
        $stmt->execute([$id]);
        $booking = $stmt->fetch();
    } else {
        $message = "Error updating booking.";
    }
}
?>

<div class="admin-container">
    <div class="page-header">
        <h1>Edit Booking #<?php echo $booking['id']; ?></h1>
        <a href="admin_bookings.php" class="btn btn-primary">Back to List</a>
    </div>

    <?php if ($message): ?>
        <div style="background: #d4edda; color: #155724; padding: 10px; margin-bottom: 20px; border-radius: 4px;">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="form-card">
        <div style="margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #eee;">
            <strong>Client:</strong> <?php echo htmlspecialchars($booking['email']); ?> <br>
            <strong>Phone:</strong> <?php echo htmlspecialchars($booking['phone_number']); ?>
        </div>

        <form method="post">
            <div class="form-group">
                <label>Job Description</label>
                <textarea name="job_description" class="form-control" rows="3" required><?php echo htmlspecialchars($booking['job_description']); ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Service Address</label>
                <input type="text" name="service_address" class="form-control" value><?php echo htmlspecialchars($booking['service_address']); ?>
            </div>

            <div class="form-group">
                <label>Scheduled Date & Time</label>
                <input type="datetime-local" name="scheduled_date" class="form-control" value="<?php echo date('Y-m-d\TH:i', strtotime($booking['scheduled_date'])); ?>" required>
            </div>

            <div class="form-group">
                <label>Status</label>
                <select name="status" class="form-control">
                    <?php 
                    $statuses = ['awaiting_quote', 'quoted', 'confirmed', 'completed', 'cancelled', 'rejected'];
                    foreach ($statuses as $s) {
                        $selected = ($booking['status'] == $s) ? 'selected' : '';
                        echo "<option value='$s' $selected>" . ucfirst($s) . "</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-group" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div>
                    <label>Quoted Price ($)</label>
                    <input type="number" step="0.01" name="quoted_price" class="form-control" value="<?php echo htmlspecialchars($booking['quoted_price']); ?>">
                </div>
                <div>
                    <label>Actual Bill ($)</label>
                    <input type="number" step="0.01" name="actual_bill" class="form-control" value="<?php echo htmlspecialchars($booking['actual_bill']); ?>">
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">Update Booking</button>
        </form>
    </div>
</div>
</body>
</html>
