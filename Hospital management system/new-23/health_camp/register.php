<?php

session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include('includes/db.php');

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS users (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','doctor','staff') NOT NULL DEFAULT 'staff',
    photo VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
mysqli_query($conn, "ALTER TABLE users ADD COLUMN IF NOT EXISTS photo VARCHAR(255) DEFAULT NULL");

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $role = trim($_POST['role']);
    $allowed_roles = ['doctor', 'staff'];
    $photo_path = null;

    if (empty($name) || empty($username) || empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all fields.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (!in_array($role, $allowed_roles, true)) {
        $error = 'Please choose a valid account type.';
    } else {
        $name_safe = mysqli_real_escape_string($conn, $name);
        $username_safe = mysqli_real_escape_string($conn, $username);

        $check_sql = "SELECT id FROM users WHERE username = '$username_safe'";
        $result = mysqli_query($conn, $check_sql);

        if ($result && mysqli_num_rows($result) > 0) {
            $error = 'That username is already taken. Please choose another.';
        } else {
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = __DIR__ . '/uploads/profiles';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                $photo_name = 'user_' . time() . '_' . rand(1000, 9999) . '.' . strtolower($ext);
                $photo_target = $upload_dir . '/' . $photo_name;
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $photo_target)) {
                    $photo_path = 'uploads/profiles/' . $photo_name;
                }
            }

            $email_safe = mysqli_real_escape_string($conn, $email);
            $phone_safe = mysqli_real_escape_string($conn, $phone);
            $password_hash = md5($password);
            $photo_value = $photo_path ? "'$photo_path'" : 'NULL';
            $insert_sql = "INSERT INTO users (name, username, email, phone, password, role, photo) VALUES ('$name_safe', '$username_safe', '$email_safe', '$phone_safe', '$password_hash', '$role', $photo_value)";

            if (mysqli_query($conn, $insert_sql)) {
                $message = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Account created successfully. You can now <a href="login.php">login</a>.</div>';
            } else {
                $error = 'Unable to create account. Please try again later.';
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
    <title>Create Account - Health Camp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body class="login-page">

    <div class="login-box">
        <div class="logo">
            <i class="fas fa-user-plus"></i>
            <h2>New Account</h2>
            <p>Create a Doctor or Staff login for the system</p>
        </div>

        <?php if ($message): ?>
            <?= $message ?>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" name="name" class="form-control" placeholder="Enter full name" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" placeholder="Choose a username" required>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" placeholder="doctor@example.com">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Phone Number</label>
                    <input type="text" name="phone" class="form-control" placeholder="Mobile number">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Enter password" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control" placeholder="Repeat password" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Profile Photo (optional)</label>
                <input type="file" name="photo" class="form-control" accept="image/*">
                <small class="text-muted">PNG, JPG, GIF up to 2 MB.</small>
            </div>
            <div class="mb-4">
                <label class="form-label">Account Type</label>
                <select name="role" class="form-select" required>
                    <option value="doctor">Doctor</option>
                    <option value="staff">Staff</option>
                </select>
            </div>
            <button type="submit" class="btn-login"><i class="fas fa-user-check"></i> Create Account</button>
        </form>

        <div class="register-link">
            <a href="login.php" class="btn btn-secondary btn-action"><i class="fas fa-arrow-left"></i> Back to Login</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
