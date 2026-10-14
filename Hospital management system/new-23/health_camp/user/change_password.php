<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

include('../includes/db.php');

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = trim($_POST['current_password'] ?? '');
    $new_password = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    $user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = " . (int)$_SESSION['user_id'] . " LIMIT 1"));

    if (!$user) {
        $error = 'Your account could not be found.';
    } elseif (md5($current_password) !== $user['password']) {
        $error = 'Current password is incorrect.';
    } elseif ($new_password === '') {
        $error = 'Please enter a new password.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New passwords do not match.';
    } else {
        mysqli_query($conn, "UPDATE users SET password = '" . md5($new_password) . "' WHERE id = " . (int)$_SESSION['user_id'] . " LIMIT 1");
        $message = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Password changed successfully.</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - Health Camp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
</head>
<body>
<?php include('sidebar_user.php'); ?>
<div class="main-content">
    <div class="topbar">
        <h4><i class="fas fa-key"></i> Change Password</h4>
        <div class="user-info">
            <div class="user-avatar"><?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?></div>
            <span><?= $_SESSION['user_name'] ?></span>
        </div>
    </div>
    <div class="page-content">
        <div class="card" style="max-width:700px;">
            <div class="card-header"><span><i class="fas fa-lock"></i> Update Your Password</span></div>
            <div class="card-body">
                <?php if ($message) echo $message; ?>
                <?php if ($error) echo '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> ' . htmlspecialchars($error) . '</div>'; ?>
                <form method="POST">
                    <div class="mb-3"><label class="form-label">Current Password</label><input type="password" name="current_password" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">New Password</label><input type="password" name="new_password" class="form-control" required></div>
                    <div class="mb-4"><label class="form-label">Confirm New Password</label><input type="password" name="confirm_password" class="form-control" required></div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Change Password</button>
                </form>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
