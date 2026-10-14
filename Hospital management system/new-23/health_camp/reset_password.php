<?php

session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

include('includes/db.php');

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = trim($_POST['token']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    if (empty($token) || empty($new_password) || empty($confirm_password)) {
        $error = 'Please fill in all fields.';
    } elseif (!preg_match('/^\d{4}$/', $token)) {
        $error = 'Reset token must be the 4-digit code provided by the admin.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        $reset = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM password_resets WHERE token = '" . mysqli_real_escape_string($conn, $token) . "' AND used = 0 AND expires_at > NOW() LIMIT 1"));
        if (!$reset) {
            $error = 'Invalid or expired reset token.';
        } else {
            mysqli_query($conn, "UPDATE users SET password = MD5('" . mysqli_real_escape_string($conn, $new_password) . "') WHERE id = '" . (int)$reset['user_id'] . "'");
            mysqli_query($conn, "UPDATE password_resets SET used = 1 WHERE id = '" . (int)$reset['id'] . "'");
            $message = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Password reset successfully. You can now <a href="login.php">login</a>.</div>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Health Camp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body class="login-page">
    <div class="login-box">
        <div class="logo">
            <i class="fas fa-unlock"></i>
            <h2>Enter Reset Token</h2>
            <p>Use the 4-digit, 1-hour token provided by the admin.</p>
        </div>

        <?php if ($message): echo $message; endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label">Reset Token</label>
                <input type="text" name="token" class="form-control" inputmode="numeric" pattern="\d{4}" maxlength="4" placeholder="Enter the 4-digit token from admin" required>
            </div>
            <div class="mb-3">
                <label class="form-label">New Password</label>
                <input type="password" name="new_password" class="form-control" placeholder="Enter new password" required>
            </div>
            <div class="mb-4">
                <label class="form-label">Confirm Password</label>
                <input type="password" name="confirm_password" class="form-control" placeholder="Repeat new password" required>
            </div>
            <button type="submit" class="btn-login"><i class="fas fa-key"></i> Reset Password</button>
        </form>

        <div class="register-link">
            <a href="login.php" class="btn btn-secondary btn-action"><i class="fas fa-arrow-left"></i> Back to Login</a>
        </div>
    </div>
</body>
</html>
