<?php

session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include('includes/db.php');

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS settings (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$site_title = 'Ardeliusnet.inc';
$site_tagline = 'Hospital Management System';
$welcome_text = 'Welcome to Ardeliusnet.inc portal';
$result = mysqli_query($conn, "SELECT setting_key, setting_value FROM settings");
while ($row = mysqli_fetch_assoc($result)) {
    if ($row['setting_key'] === 'login_welcome_text') {
        $welcome_text = $row['setting_value'];
    }
}

$error = ""; 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $error = "Please enter username and password!";
    } else {
        $sql = "SELECT * FROM users WHERE username = '$username' AND password = MD5('$password')";
        $result = mysqli_query($conn, $sql);

        if (mysqli_num_rows($result) == 1) {
            $user = mysqli_fetch_assoc($result);
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];

            if ($user['role'] == 'admin') {
                header("Location: index.php");         
            } else {
                header("Location: user/dashboard.php"); 
            }
            exit();
        } else {
            $error = "Wrong username or password!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= htmlspecialchars($site_title) ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css?v=2" rel="stylesheet">
</head>
<body class="login-page">

    <div class="login-box">

        <div class="logo">
            <i class="fas fa-hospital-alt"></i>
            <h2><?= htmlspecialchars($site_title) ?></h2>
            <p><?= htmlspecialchars($site_tagline) ?></p>
            <p class="small text-muted"><?= htmlspecialchars($welcome_text) ?></p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">

            <div class="mb-3">
                <label class="form-label">Username</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input type="text" name="username" class="form-control" placeholder="Enter username" required>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" name="password" class="form-control" placeholder="Enter password" required>
                </div>
            </div>

            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>

        </form>

        <div class="login-links">
            <div class="register-link">
                <a href="forgot_password.php" class="btn btn-secondary btn-action"><i class="fas fa-key"></i> Forgot Password?</a>
            </div>

            <div class="register-link">
                <a href="reset_password.php" class="btn btn-secondary btn-action"><i class="fas fa-unlock-alt"></i> Reset Password</a>
            </div>

            <div class="register-link">
                <a href="register.php" class="btn btn-secondary btn-action"><i class="fas fa-user-plus"></i> Create Doctor / Staff Account</a>
            </div>
        </div>

        <p class="text-center mt-3" style="font-size: 12px; color: #aaa;">
            Default: admin / admin123 <br>
            Default: doctor1 / doctor123 
        </p>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
