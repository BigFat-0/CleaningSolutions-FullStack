<?php
require('db_connect.php');
require('n8n_helper.php');
session_start();

$message = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Collect Inputs
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone_number']);
    $password = $_POST['password'];
    $security_question = $_POST['security_question'];
    $security_answer = $_POST['security_answer'];
    
    $job_description = $_POST['job_description'];
    $billing_address = trim($_POST['billing_address']);
    $service_address = trim($_POST['service_address']);
    $scheduled_date = $_POST['scheduled_date'];

    // Basic Validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($billing_address)) {
        $error = "Please fill in all required fields.";
    } else {
        try {
            $pdo->beginTransaction();

            // Step A: Check if email exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->rowCount() > 0) {
                throw new Exception("Email already registered. Please <a href='login.php'>Login</a> first.");
            }

            // Step B: Insert User
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $security_answer_hash = password_hash($security_answer, PASSWORD_DEFAULT);
            $role = 'customer';

            $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, phone_number, billing_address, password_hash, security_question, security_answer_hash, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$first_name, $last_name, $email, $phone, $billing_address, $password_hash, $security_question, $security_answer_hash, $role]);
            $user_id = $pdo->lastInsertId();

            // Webhook: New User
            sendWebhook('new-user', [
                'name' => $first_name . ' ' . $last_name,
                'email' => $email,
                'phone' => $phone
            ]);

            // Step C: Insert Booking
            $stmt = $pdo->prepare("INSERT INTO bookings (user_id, job_description, service_address, scheduled_date, status) VALUES (?, ?, ?, ?, 'awaiting_quote')");
            $stmt->execute([$user_id, $job_description, $service_address, $scheduled_date]);
            $booking_id = $pdo->lastInsertId();

            // Webhook: New Booking
            sendWebhook('new-booking', [
                'id' => $booking_id,
                'description' => $job_description,
                'date' => $scheduled_date,
                'user_email' => $email // Added user_email
            ]);

            // Commit
            $pdo->commit();

            // Step D: Auto Login
            $_SESSION['user_id'] = $user_id;
            $_SESSION['role'] = $role;

            // Step E: Redirect
            header("Location: dashboard.php");
            exit();

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Get a Quote - Cleaning Platform</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="auth-card" style="max-width: 600px;">
        <div style="text-align: right; margin-bottom: 10px;">
            <a href="login.php" style="color: var(--accent-color); text-decoration: none; font-size: 0.9em;">Already have an account? Login</a>
        </div>
        <h2 style="text-align: center; margin-bottom: 20px;">Get a Free Quote</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-error" style="color: #721c24; margin-bottom: 15px; background: #f8d7da; padding: 10px; border-radius: 4px;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="post" action="">
            <!-- Contact Details -->
            <h4 style="border-bottom: 1px solid #eee; padding-bottom: 5px; margin-top: 0;">1. Contact Details</h4>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <div class="form-group" style="flex: 1; min-width: 200px;">
                    <label>First Name</label>
                    <input type="text" name="first_name" required>
                </div>
                <div class="form-group" style="flex: 1; min-width: 200px;">
                    <label>Last Name</label>
                    <input type="text" name="last_name" required>
                </div>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="phone_number" required>
            </div>

            <!-- Account Setup -->
            <h4 style="border-bottom: 1px solid #eee; padding-bottom: 5px; margin-top: 20px;">2. Secure Your Account</h4>
            <div class="form-group">
                <label>Create Password</label>
                <input type="password" name="password" required>
            </div>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <div class="form-group" style="flex: 1; min-width: 200px;">
                    <label>Security Question</label>
                    <select name="security_question" required>
                        <option value="Mother's Maiden Name">Mother's Maiden Name</option>
                        <option value="First Pet">First Pet</option>
                    </select>
                </div>
                <div class="form-group" style="flex: 1; min-width: 200px;">
                    <label>Answer</label>
                    <input type="text" name="security_answer" required>
                </div>
            </div>

            <!-- Job Details -->
            <h4 style="border-bottom: 1px solid #eee; padding-bottom: 5px; margin-top: 20px;">3. Job Details</h4>
            <div class="form-group">
                <label>Job Description</label>
                <textarea name="job_description" rows="3" placeholder="What do you need done?" required></textarea>
            </div>
            <div class="form-group">
                <label>Billing Address</label>
                <textarea name="billing_address" id="billing_address" rows="2" required></textarea>
            </div>
            
            <div class="form-group" style="display: flex; align-items: center; margin-bottom: 15px;">
                <input type="checkbox" id="same_address" style="width: auto; margin-bottom: 0; margin-right: 10px;">
                <label for="same_address" style="margin-bottom: 0; font-weight: normal;">Service Address is same as Billing Address</label>
            </div>

            <div class="form-group">
                <label>Service Address</label>
                <input type="text" name="service_address" id="service_address" required>
            </div>
            <div class="form-group">
                <label>Preferred Date/Time</label>
                <input type="datetime-local" name="scheduled_date" required>
            </div>

            <button type="submit" class="btn-auth btn-primary" style="margin-top: 20px;">Submit Request</button>
            <a href="login.php" class="btn-auth btn-secondary">Already have an account? Login</a>
        </form>
    </div>

    <script>
        document.getElementById('same_address').addEventListener('change', function() {
            if(this.checked) {
                document.getElementById('service_address').value = document.getElementById('billing_address').value;
            } else {
                document.getElementById('service_address').value = '';
            }
        });
    </script>
</body>
</html>