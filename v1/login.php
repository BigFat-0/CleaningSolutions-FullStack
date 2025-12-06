<?php
// v1/login.php
require('db_connect.php');
session_start();

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $message = "Both fields are required.";
    } else {
        $stmt = $pdo->prepare("SELECT id, password_hash, role FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];

            if ($user['role'] === 'staff') {
                header("Location: admin_panel.php");
            } else {
                header("Location: dashboard.php");
            }
            exit();
        } else {
            $message = "Invalid email or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Cleaning Platform</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="auth-card">
        <h2 style="text-align: center; margin-bottom: 20px;">Login</h2>
        <?php if ($message): ?>
            <div class="alert alert-error" style="color: red; margin-bottom: 15px;"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <form method="post" action="">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
                <div style="text-align: right; margin-top: -10px; margin-bottom: 15px;">
                    <a href="reset_password.php" style="font-size: 0.9em;">Forgot Password?</a>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Login</button>
            <a href="register.php" class="btn btn-secondary">Register</a>
        </form>
    </div>
</body>
</html>