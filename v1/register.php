<?php
// v1/register.php
require('db_connect.php');
session_start();

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone_number']);
    $billing_address = trim($_POST['billing_address']);
    $security_question = $_POST['security_question'];
    $security_answer = $_POST['security_answer'];
    $password = $_POST['password'];
    
    // Validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($phone) || empty($billing_address) || empty($security_question) || empty($security_answer) || empty($password)) {
        $message = "All fields are required.";
    } else {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            $message = "Email already registered.";
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $security_answer_hash = password_hash($security_answer, PASSWORD_DEFAULT);
            $role = 'customer'; 

            $sql = "INSERT INTO users (first_name, last_name, email, phone_number, billing_address, password_hash, security_question, security_answer_hash, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            
            if ($stmt->execute([$first_name, $last_name, $email, $phone, $billing_address, $password_hash, $security_question, $security_answer_hash, $role])) {
                 header("Location: login.php");
                 exit();
            } else {
                $message = "Registration failed. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - Cleaning Platform</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="auth-card">
        <h2 style="text-align: center; margin-bottom: 20px;">Customer Registration</h2>
        <?php if ($message): ?>
            <div class="alert alert-error" style="color: red; margin-bottom: 15px;"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <form method="post" action="">
            <div class="form-group">
                <label>First Name</label>
                <input type="text" name="first_name" required>
            </div>
            <div class="form-group">
                <label>Last Name</label>
                <input type="text" name="last_name" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>Phone Number</label>
                <input type="text" name="phone_number" required>
            </div>
            <div class="form-group">
                <label>Billing Address</label>
                <textarea name="billing_address" required></textarea>
            </div>
            <div class="form-group">
                <label>Security Question</label>
                <select name="security_question" required>
                    <option value="">Select a question...</option>
                    <option value="Mother's Maiden Name">Mother's Maiden Name</option>
                    <option value="First Pet">First Pet</option>
                    <option value="Primary School">Primary School</option>
                </select>
            </div>
            <div class="form-group">
                <label>Security Answer</label>
                <input type="password" name="security_answer" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary">Register</button>
            <a href="login.php" class="btn btn-secondary">Already have an account? Login</a>
        </form>
    </div>
</body>
</html>