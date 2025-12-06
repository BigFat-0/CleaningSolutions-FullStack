<?php
// v1/reset_password.php
require('db_connect.php');
session_start();

$step = 1;
$error = '';
$question_text = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['step1'])) {
        $email = trim($_POST['email']);
        $stmt = $pdo->prepare("SELECT id, security_question FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && !empty($user['security_question'])) {
            $_SESSION['reset_user_id'] = $user['id'];
            $_SESSION['security_question'] = $user['security_question'];
            $question_text = $user['security_question'];
            $step = 2;
        } else {
            $error = "Cannot verify identity. Please contact support: admin@cleaning.com";
        }
    } elseif (isset($_POST['step2'])) {
        $answer = $_POST['security_answer'];
        $user_id = $_SESSION['reset_user_id'];
        $question_text = $_SESSION['security_question'];

        $stmt = $pdo->prepare("SELECT security_answer_hash FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $hash = $stmt->fetchColumn();

        if (password_verify($answer, $hash)) {
            $step = 3;
        } else {
            $error = "Cannot verify identity. Please contact support: admin@cleaning.com";
            $step = 2;
        }
    } elseif (isset($_POST['step3'])) {
        $new_password = $_POST['new_password'];
        $user_id = $_SESSION['reset_user_id'];
        
        if (!empty($new_password)) {
            $hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            if ($stmt->execute([$hash, $user_id])) {
                session_destroy();
                header("Location: login.php?msg=reset_success");
                exit();
            } else {
                $error = "Database error.";
                $step = 3;
            }
        } else {
            $error = "Password required.";
            $step = 3;
        }
    }
} else {
    // If returning to page (refresh), check session state
    if (isset($_SESSION['reset_user_id']) && isset($_SESSION['security_question'])) {
        $step = 2;
        $question_text = $_SESSION['security_question'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="auth-card">
        <h2 style="text-align: center; margin-bottom: 20px;">Reset Password</h2>
        <?php if ($error): ?>
            <div class="alert alert-error" style="color: red; margin-bottom: 15px;"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($step == 1): ?>
        <form method="post">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" required>
            </div>
            <button type="submit" name="step1" class="btn-auth btn-primary">Next</button>
            <a href="login.php" class="btn-auth btn-secondary">Back to Login</a>
        </form>
        <?php elseif ($step == 2): ?>
        <form method="post">
            <div class="form-group">
                <label>Security Question:</label>
                <p><strong><?php echo htmlspecialchars($question_text); ?></strong></p>
                <input type="password" name="security_answer" placeholder="Your Answer" required>
            </div>
            <button type="submit" name="step2" class="btn-auth btn-primary">Verify</button>
        </form>
        <?php elseif ($step == 3): ?>
        <form method="post">
            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="new_password" required>
            </div>
            <button type="submit" name="step3" class="btn-auth btn-primary">Reset Password</button>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>