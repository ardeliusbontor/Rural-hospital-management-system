<?php

session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

include('includes/db.php');

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS password_resets (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    token VARCHAR(4) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
    INDEX (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
mysqli_query($conn, "ALTER TABLE password_resets MODIFY token VARCHAR(4) NOT NULL");

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    if (empty($username)) {
        $error = 'Please enter your username.';
    } else {
        $user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE username = '" . mysqli_real_escape_string($conn, $username) . "' LIMIT 1"));
        if (!$user) {
            $error = 'No account found with that username.';
        } else {
            $token = '';
            $attempts = 0;
            do {
                $token = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
                $existing = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM password_resets WHERE token = '" . mysqli_real_escape_string($conn, $token) . "' LIMIT 1"));
                $attempts++;
            } while ($existing && $attempts < 10);

            if ($existing) {
                $error = 'Unable to generate a unique reset token. Please try again.';
            } else {
                $expires_at = (new DateTimeImmutable('now', new DateTimeZone('Asia/Kolkata')))->modify('+1 hour')->format('Y-m-d H:i:s');
                mysqli_query($conn, "INSERT INTO password_resets (user_id, token, expires_at) VALUES ('" . (int)$user['id'] . "', '$token', '$expires_at')");

                $message = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Your 4-digit reset token has been created. Please contact the admin to receive it.</div>';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Health Camp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body class="login-page">
    <div class="login-box">
        <div class="logo">
            <i class="fas fa-key"></i>
            <h2>Reset Password</h2>
            <p>Request a reset token. The admin will provide the code.</p>
        </div>

        <?php if ($message): echo $message; endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" placeholder="Enter your username" required>
            </div>
            <button type="submit" class="btn-login"><i class="fas fa-paper-plane"></i> Request Reset Token</button>
        </form>

        <div class="register-link">
            <a href="login.php" class="btn btn-secondary btn-action"><i class="fas fa-arrow-left"></i> Back to Login</a>
        </div>
    </div>
</body>
</html>
