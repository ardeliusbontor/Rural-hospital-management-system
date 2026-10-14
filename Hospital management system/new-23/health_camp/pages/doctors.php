<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include('../includes/db.php');

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_doctor'])) {
    $name           = trim($_POST['name']);
    $specialization = trim($_POST['specialization']);
    $phone          = trim($_POST['phone']);

    if (empty($name)) {
        $message = '<div class="alert alert-danger">Doctor name is required!</div>';
    } else {
        $sql = "INSERT INTO doctors (name, specialization, phone) VALUES ('$name', '$specialization', '$phone')";
        if (mysqli_query($conn, $sql)) {
            $message = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Doctor added successfully!</div>';
        } else {
            $message = '<div class="alert alert-danger">Error adding doctor. Try again.</div>';
        }
    }
}

if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM doctors WHERE id = '$delete_id'");
    $message = '<div class="alert alert-warning"><i class="fas fa-trash"></i> Doctor removed.</div>';
}

$doctors = mysqli_query($conn, "SELECT d.*, u.id AS user_id, u.username, u.photo, u.email, u.phone AS user_phone FROM doctors d LEFT JOIN users u ON u.name = d.name AND u.role = 'doctor' ORDER BY d.created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctors - Health Camp</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
</head>
<body>

<?php include('../includes/sidebar.php'); ?>

<div class="main-content">

    <div class="topbar">
        <h4><i class="fas fa-user-md"></i> Doctors</h4>
        <div class="user-info">
            <div class="user-avatar"><?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?></div>
            <span><?= $_SESSION['user_name'] ?></span>
        </div>
    </div>

    <div class="page-content">

        <?= $message ?>

        <div class="row">

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <span><i class="fas fa-plus-circle"></i> Add New Doctor</span>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">

                            <div class="mb-3">
                                <label class="form-label">Doctor Name *</label>
                                <input type="text" name="name" class="form-control" placeholder="e.g. Dr. Rahul Sharma" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Specialization</label>
                                <input type="text" name="specialization" class="form-control" placeholder="e.g. General Medicine">
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Phone Number</label>
                                <input type="text" name="phone" class="form-control" placeholder="Mobile number">
                            </div>

                            <button type="submit" name="add_doctor" class="btn btn-primary w-100">
                                <i class="fas fa-save"></i> Add Doctor
                            </button>

                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <span><i class="fas fa-list"></i> All Doctors (<?= mysqli_num_rows($doctors) ?>)</span>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Doctor Name</th>
                                    <th>Specialization</th>
                                    <th>Phone</th>
                                    <th>Added On</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($doctors) > 0): ?>
                                    <?php $i = 1; while ($doc = mysqli_fetch_assoc($doctors)): ?>
                                    <tr>
                                        <td><?= $i++ ?></td>
                                        <td>
                                            <?php if (!empty($doc['photo'])): ?>
                                                <img src="../<?= htmlspecialchars($doc['photo']) ?>" alt="Photo" class="rounded-circle me-2" style="width:38px; height:38px; object-fit:cover; border:2px solid #d5e8d4;">
                                            <?php else: ?>
                                                <span class="me-2"><i class="fas fa-user-md text-success"></i></span>
                                            <?php endif; ?>
                                            <strong><?= htmlspecialchars($doc['name']) ?></strong>
                                            <?php if (!empty($doc['username'])): ?><br><small class="text-muted">@<?= htmlspecialchars($doc['username']) ?></small><?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($doc['specialization']): ?>
                                                <span class="badge bg-success"><?= htmlspecialchars($doc['specialization']) ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= $doc['phone'] ? $doc['phone'] : '-' ?></td>
                                        <td><?= date('d M Y', strtotime($doc['created_at'])) ?></td>
                                        <td>
                                            <a href="doctor_profile.php?id=<?= (int)($doc['user_id'] ?? 0) ?>" class="btn btn-primary btn-sm me-1"><i class="fas fa-user-edit"></i> Edit</a>
                                            <a href="?delete=<?= $doc['id'] ?>"
                                               class="btn btn-danger btn-sm"
                                               onclick="return confirm('Remove this doctor?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            No doctors added yet.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
