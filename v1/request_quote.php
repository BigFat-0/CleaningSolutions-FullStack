<?php
// v1/request_quote.php
require('auth_session.php');
require('db_connect.php');
require('n8n_helper.php');

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $job_description = $_POST['job_description'];
    $service_address = $_POST['service_address'];
    $scheduled_date = $_POST['scheduled_date'];

    if (empty($job_description) || empty($service_address) || empty($scheduled_date)) {
        $message = "All fields are required.";
    } else {
        $user_id = $_SESSION['user_id'];
        // Status defaults to 'awaiting_quote'
        $sql = "INSERT INTO bookings (user_id, job_description, service_address, scheduled_date) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);

        if ($stmt->execute([$user_id, $job_description, $service_address, $scheduled_date])) {
            $booking_id = $pdo->lastInsertId();
            
            // Fetch User Email
            $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user_email = $stmt->fetchColumn();

            // Webhook: New Booking
            sendWebhook('new-booking', [
                'id' => $booking_id,
                'description' => $job_description,
                'date' => $scheduled_date,
                'user_email' => $user_email
            ]);

            header("Location: dashboard.php");
            exit();
        } else {
            $message = "Failed to submit request. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    <meta charset="UTF-8">
    <title>Request Quote - Cleaning Platform</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="auth-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="margin: 0;">Request a Quote</h2>
            <a href="dashboard.php" class="btn btn-sm btn-secondary">Back</a>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-error" style="color: red; margin-bottom: 15px;"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <form method="post" action="">
            <div class="form-group">
                <label>Job Description</label>
                <textarea name="job_description" rows="4" placeholder="Describe your needs..." required></textarea>
            </div>
            <div class="form-group">
                <label>Service Address</label>
                <input type="text" name="service_address" required>
            </div>
            <div class="form-group">
                <label>Date/Time</label>
                <input type="datetime-local" name="scheduled_date" required>
            </div>
            <button type="submit" class="btn btn-primary">Submit Request</button>
        </form>
    </div>
</body>
</html>