<?php

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

include('../includes/db.php');

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS password_resets (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    token VARCHAR(4) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
    INDEX (user_id),
    INDEX (expires_at),
    INDEX (used)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
mysqli_query($conn, "ALTER TABLE password_resets MODIFY token VARCHAR(4) NOT NULL");

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_token'])) {
    $id = (int)$_POST['delete_token'];
    mysqli_query($conn, "DELETE FROM password_resets WHERE id = $id LIMIT 1");
    $message = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Reset token removed.</div>';
}

$token_result = mysqli_query($conn, "
    SELECT pr.*, u.username, u.role,
           CASE WHEN pr.used = 1 THEN 'Used' WHEN pr.expires_at <= NOW() THEN 'Expired' ELSE 'Active' END AS status
    FROM password_resets pr
    JOIN users u ON u.id = pr.user_id
    ORDER BY pr.created_at DESC
");

$token_rows = [];
while ($row = mysqli_fetch_assoc($token_result)) {
    $token_rows[] = $row;
}

$total_tokens = count($token_rows);
$active_tokens = 0;
$expired_tokens = 0;
$used_tokens = 0;

foreach ($token_rows as $row) {
    if ($row['used'] == 1) {
        $used_tokens++;
    } elseif ($row['expires_at'] <= date('Y-m-d H:i:s')) {
        $expired_tokens++;
    } else {
        $active_tokens++;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Tokens - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
</head>
<body>

<?php include('../includes/sidebar.php'); ?>

<div class="main-content">
    <div class="topbar">
        <h4><i class="fas fa-key"></i> Reset Tokens</h4>
        <div class="user-info">
            <div class="user-avatar"><?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?></div>
            <span><?= $_SESSION['user_name'] ?> (admin)</span>
        </div>
    </div>

    <div class="page-content">
        <?= $message ?>

        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> These are the password reset tokens currently stored in the system. Share the token only with the correct user and make sure it expires after 1 hour.
        </div>

        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stat-card">
                    <div class="icon-box bg-blue"><i class="fas fa-key"></i></div>
                    <div><h3><?= $total_tokens ?></h3><p>Total Requests</p></div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card">
                    <div class="icon-box bg-green"><i class="fas fa-check-circle"></i></div>
                    <div><h3><?= $active_tokens ?></h3><p>Active Tokens</p></div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card">
                    <div class="icon-box bg-orange"><i class="fas fa-hourglass-half"></i></div>
                    <div><h3><?= $expired_tokens ?></h3><p>Expired</p></div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card">
                    <div class="icon-box bg-purple"><i class="fas fa-user-check"></i></div>
                    <div><h3><?= $used_tokens ?></h3><p>Used</p></div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <span><i class="fas fa-list"></i> Password Reset Requests</span>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>User</th>
                            <th>Role</th>
                            <th>Token</th>
                            <th>Expires At</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($token_rows)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No reset requests found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($token_rows as $index => $row): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($row['username']) ?></strong>
                                    </td>
                                    <td><?= htmlspecialchars($row['role'] ?? 'user') ?></td>
                                    <td>
                                        <code style="word-break: break-all;"><?= htmlspecialchars($row['token']) ?></code>
                                        <button type="button" class="btn btn-sm btn-outline-primary ms-2 copy-token-btn" data-token="<?= htmlspecialchars($row['token']) ?>" title="Copy token"><i class="fas fa-copy"></i> Copy</button>
                                    </td>
                                    <td><?= date('d M Y H:i', strtotime($row['expires_at'])) ?></td>
                                    <td>
                                        <?php
                                            $badge = 'secondary';
                                            if ($row['used'] == 1) $badge = 'success';
                                            elseif ($row['expires_at'] <= date('Y-m-d H:i:s')) $badge = 'danger';
                                            else $badge = 'primary';
                                        ?>
                                        <span class="badge bg-<?= $badge ?>"><?= htmlspecialchars($row['status'] ?? 'Unknown') ?></span>
                                    </td>
                                    <td>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete this reset token?');">
                                            <input type="hidden" name="delete_token" value="<?= (int)$row['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i> Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('click', function (e) {
    const btn = e.target.closest('.copy-token-btn');
    if (!btn) return;

    const token = btn.getAttribute('data-token');
    if (!token) return;

    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(token).then(function () {
            btn.innerHTML = '<i class="fas fa-check"></i> Copied';
            setTimeout(function () { btn.innerHTML = '<i class="fas fa-copy"></i> Copy'; }, 1500);
        });
    } else {
        const temp = document.createElement('textarea');
        temp.value = token;
        document.body.appendChild(temp);
        temp.select();
        document.execCommand('copy');
        document.body.removeChild(temp);
        btn.innerHTML = '<i class="fas fa-check"></i> Copied';
        setTimeout(function () { btn.innerHTML = '<i class="fas fa-copy"></i> Copy'; }, 1500);
    }
});
</script>
</body>
</html>
