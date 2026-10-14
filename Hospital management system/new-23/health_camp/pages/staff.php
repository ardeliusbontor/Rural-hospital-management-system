<?php

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

include('../includes/db.php');

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$role_filter = isset($_GET['role']) ? trim($_GET['role']) : '';

$where = "WHERE role IN ('doctor','staff')";
if ($search !== '') {
    $search_safe = mysqli_real_escape_string($conn, $search);
    $where .= " AND (name LIKE '%$search_safe%' OR username LIKE '%$search_safe%')";
}
if ($role_filter !== '') {
    $role_filter_safe = mysqli_real_escape_string($conn, $role_filter);
    $where .= " AND role = '$role_filter_safe'";
}

$users = mysqli_query($conn, "SELECT * FROM users $where ORDER BY created_at DESC");
$doctor_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM users WHERE role='doctor'"));
$staff_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM users WHERE role='staff'"));
$total_users = mysqli_num_rows($users);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor & Staff Records - Health Camp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
</head>
<body>

<?php include('../includes/sidebar.php'); ?>

<div class="main-content">
    <div class="topbar">
        <h4><i class="fas fa-users"></i> Doctor & Staff Records</h4>
        <div class="user-info">
            <div class="user-avatar"><?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?></div>
            <span><?= $_SESSION['user_name'] ?></span>
        </div>
    </div>

    <div class="page-content">
        <div class="row mb-4">
            <div class="col-md-4"><div class="stat-card"><div class="icon-box bg-green"><i class="fas fa-user-md"></i></div><div><h3><?= (int)$doctor_count['cnt'] ?></h3><p>Doctors</p></div></div></div>
            <div class="col-md-4"><div class="stat-card"><div class="icon-box bg-blue"><i class="fas fa-user-tie"></i></div><div><h3><?= (int)$staff_count['cnt'] ?></h3><p>Staff</p></div></div></div>
            <div class="col-md-4"><div class="stat-card"><div class="icon-box bg-orange"><i class="fas fa-users"></i></div><div><h3><?= $total_users ?></h3><p>Total Accounts</p></div></div></div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" class="form-control" placeholder="Search by name or username" value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select">
                            <option value="">All Roles</option>
                            <option value="doctor" <?= $role_filter === 'doctor' ? 'selected' : '' ?>>Doctor</option>
                            <option value="staff" <?= $role_filter === 'staff' ? 'selected' : '' ?>>Staff</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i> Filter</button>
                    </div>
                    <div class="col-md-2">
                        <a href="staff.php" class="btn btn-secondary w-100"><i class="fas fa-times"></i> Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-id-card"></i> All Doctor / Staff Accounts</span>
                <span class="badge bg-success"><?= mysqli_num_rows($users) ?> found</span>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Photo</th>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Created</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($users) > 0): $i = 1; while ($u = mysqli_fetch_assoc($users)): ?>
                        <tr class="align-middle">
                            <td><?= (int)$u['id'] ?></td>
                            <td>
                                <?php if (!empty($u['photo'])): ?>
                                    <img src="../<?= htmlspecialchars($u['photo']) ?>" alt="Profile" class="rounded-circle" style="width:46px; height:46px; object-fit:cover; border:2px solid #d5e8d4; box-shadow:0 4px 12px rgba(0,0,0,0.08);">
                                <?php else: ?>
                                    <span class="badge bg-secondary"><i class="fas fa-user"></i></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($u['name']) ?></strong>
                                <br><small class="text-muted">Active account</small>
                            </td>
                            <td><span class="badge bg-light text-dark">@<?= htmlspecialchars($u['username']) ?></span></td>
                            <td><span class="badge bg-success text-capitalize"><?= htmlspecialchars($u['role']) ?></span></td>
                            <td><?= htmlspecialchars($u['email'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($u['phone'] ?? '-') ?></td>
                            <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                            <td>
                                <a href="doctor_profile.php?id=<?= (int)$u['id'] ?>" class="btn btn-sm btn-primary"><i class="fas fa-user-edit"></i> View/Edit</a>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="9" class="text-center text-muted py-4">No doctor or staff accounts found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
