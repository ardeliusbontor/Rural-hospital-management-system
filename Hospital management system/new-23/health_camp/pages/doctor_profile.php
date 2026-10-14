<?php

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

include('../includes/db.php');

$message = '';
$error = '';

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id LIMIT 1"));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = (int)($_POST['user_id'] ?? 0);

    if (isset($_POST['reset_password'])) {
        $new_password = trim($_POST['new_password'] ?? '');
        $confirm_password = trim($_POST['confirm_password'] ?? '');

        if ($user_id <= 0) {
            $error = 'Invalid user selected.';
        } elseif ($new_password === '') {
            $error = 'Please enter a new password.';
        } elseif ($new_password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } else {
            mysqli_query($conn, "UPDATE users SET password = '" . md5($new_password) . "' WHERE id = $user_id LIMIT 1");
            $message = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Password reset successfully for this account.</div>';
        }
    } else {
        $name = trim($_POST['name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $role = trim($_POST['role'] ?? 'doctor');

        if ($user_id <= 0 || $name === '' || $username === '') {
            $error = 'Please provide the doctor name and username.';
        } else {
        $old_name = $user['name'] ?? '';
        $name_safe = mysqli_real_escape_string($conn, $name);
        $username_safe = mysqli_real_escape_string($conn, $username);
        $email_safe = mysqli_real_escape_string($conn, $email);
        $phone_safe = mysqli_real_escape_string($conn, $phone);
        $role_safe = in_array($role, ['doctor', 'staff'], true) ? $role : 'doctor';

            mysqli_query($conn, "UPDATE users SET name = '$name_safe', username = '$username_safe', email = '$email_safe', phone = '$phone_safe', role = '$role_safe' WHERE id = $user_id LIMIT 1");
            if ($old_name !== '') {
                mysqli_query($conn, "UPDATE doctors SET name = '$name_safe' WHERE name = '" . mysqli_real_escape_string($conn, $old_name) . "'");
            }

            $message = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Doctor details updated successfully.</div>';
        }
    }

    $user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id LIMIT 1"));
}

if (!$user) {
    $error = 'Doctor account not found.';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Profile - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
</head>
<body>
<?php include('../includes/sidebar.php'); ?>
<div class="main-content">
    <div class="topbar">
        <h4><i class="fas fa-user-md"></i> Doctor Profile</h4>
        <div class="user-info">
            <div class="user-avatar"><?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?></div>
            <span><?= $_SESSION['user_name'] ?></span>
        </div>
    </div>
    <div class="page-content">
        <?php if ($message) echo $message; ?>
        <?php if ($error) echo '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> ' . htmlspecialchars($error) . '</div>'; ?>

        <?php if ($user): ?>
        <div class="row">
            <div class="col-lg-4 mb-4">
                <div class="card">
                    <div class="card-body text-center">
                        <?php if (!empty($user['photo'])): ?>
                            <img src="../<?= htmlspecialchars($user['photo']) ?>" class="rounded-circle mb-3" style="width:110px;height:110px;object-fit:cover;border:4px solid #d5e8d4;">
                        <?php else: ?>
                            <div class="rounded-circle bg-success text-white d-inline-flex align-items-center justify-content-center mb-3" style="width:110px;height:110px;font-size:36px;"><?= strtoupper(substr($user['name'], 0, 1)) ?></div>
                        <?php endif; ?>
                        <h4 class="mb-1"><?= htmlspecialchars($user['name']) ?></h4>
                        <p class="text-muted mb-2"><?= htmlspecialchars($user['role']) ?> account</p>
                        <span class="badge bg-primary">ID: <?= (int)$user['id'] ?></span>
                    </div>
                </div>
                <div class="card mt-3">
                    <div class="card-header"><span><i class="fas fa-id-card"></i> Credentials</span></div>
                    <div class="card-body">
                        <p class="mb-2"><strong>Username:</strong> <?= htmlspecialchars($user['username']) ?></p>
                        <p class="mb-2"><strong>Email:</strong> <?= htmlspecialchars($user['email'] ?? '-') ?></p>
                        <p class="mb-2"><strong>Phone:</strong> <?= htmlspecialchars($user['phone'] ?? '-') ?></p>
                        <p class="mb-0"><strong>Created:</strong> <?= date('d M Y H:i', strtotime($user['created_at'])) ?></p>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header"><span><i class="fas fa-user-edit"></i> Edit Doctor Details</span></div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>">
                            <div class="row">
                                <div class="col-md-6 mb-3"><label class="form-label">Full Name</label><input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required></div>
                                <div class="col-md-6 mb-3"><label class="form-label">Username</label><input type="text" name="username" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" required></div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>"></div>
                                <div class="col-md-6 mb-3"><label class="form-label">Phone Number</label><input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>"></div>
                            </div>
                            <div class="mb-4"><label class="form-label">Role</label><select name="role" class="form-select"><option value="doctor" <?= $user['role'] === 'doctor' ? 'selected' : '' ?>>Doctor</option><option value="staff" <?= $user['role'] === 'staff' ? 'selected' : '' ?>>Staff</option></select></div>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
                            <a href="staff.php" class="btn btn-secondary ms-2"><i class="fas fa-arrow-left"></i> Back to Records</a>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><span><i class="fas fa-key"></i> Reset Password</span></div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>">
                            <input type="hidden" name="reset_password" value="1">
                            <div class="mb-3"><label class="form-label">New Password</label><input type="password" name="new_password" class="form-control" required></div>
                            <div class="mb-4"><label class="form-label">Confirm New Password</label><input type="password" name="confirm_password" class="form-control" required></div>
                            <button type="submit" class="btn btn-warning"><i class="fas fa-unlock-alt"></i> Reset Password</button>
                            <small class="text-muted d-block mt-2">This lets the admin set a new password directly for this doctor/staff account.</small>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
