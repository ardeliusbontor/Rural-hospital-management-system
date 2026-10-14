<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include('includes/db.php');

$total_patients = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM patients"));

$total_doctors = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM doctors"));

$today = date('Y-m-d');
$today_patients = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM patients WHERE visit_date = '$today'"));

$total_medicines = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM medicines"));

$low_stock_items = mysqli_query($conn, "SELECT id, name, quantity, unit FROM medicines WHERE quantity < 50 ORDER BY quantity ASC");

$recent_patients = mysqli_query($conn, "SELECT p.*, d.name as doctor_name FROM patients p LEFT JOIN doctors d ON p.doctor_id = d.id ORDER BY p.created_at DESC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Health Camp</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>

<?php include('includes/sidebar.php'); ?>

<div class="main-content">

    <div class="topbar">
        <h4><i class="fas fa-tachometer-alt"></i> Dashboard</h4>
        <div class="user-info">
            <div class="user-avatar"><?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?></div>
            <span><?= $_SESSION['user_name'] ?> (<?= $_SESSION['user_role'] ?>)</span>
        </div>
    </div>

    <div class="page-content">

        <div class="alert" style="background:#eafaf1; color:#1a7a4a; border-left: 4px solid #1a7a4a;">
            <i class="fas fa-sun"></i>
            Welcome back, <strong><?= $_SESSION['user_name'] ?></strong>! Today is <strong><?= date('d M Y') ?></strong>.
        </div>

        <div class="row">

            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <div class="icon-box bg-green">
                        <i class="fas fa-user-injured"></i>
                    </div>
                    <div>
                        <h3><?= $total_patients ?></h3>
                        <p>Total Patients</p>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <div class="icon-box bg-blue">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div>
                        <h3><?= $today_patients ?></h3>
                        <p>Today's Patients</p>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <div class="icon-box bg-orange">
                        <i class="fas fa-user-md"></i>
                    </div>
                    <div>
                        <h3><?= $total_doctors ?></h3>
                        <p>Doctors Available</p>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <div class="icon-box bg-purple">
                        <i class="fas fa-pills"></i>
                    </div>
                    <div>
                        <h3><?= $total_medicines ?></h3>
                        <p>Medicine Types</p>
                    </div>
                </div>
            </div>

        </div>

        <div class="card mb-4">
            <div class="card-header">
                <span><i class="fas fa-exclamation-triangle"></i> Low Stock Alerts</span>
            </div>
            <div class="card-body">
                <?php if (mysqli_num_rows($low_stock_items) > 0): ?>
                    <div class="alert alert-warning mb-0">
                        <i class="fas fa-pills"></i> The following medicines are running low and should be restocked soon:
                        <ul class="mb-0 mt-2">
                            <?php while ($item = mysqli_fetch_assoc($low_stock_items)): ?>
                                <li><strong><?= htmlspecialchars($item['name']) ?></strong> — only <?= (int)$item['quantity'] ?> <?= htmlspecialchars($item['unit'] ?: 'units') ?> left.</li>
                            <?php endwhile; ?>
                        </ul>
                    </div>
                <?php else: ?>
                    <div class="alert alert-success mb-0">
                        <i class="fas fa-check-circle"></i> All medicines are above the low-stock threshold.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <span><i class="fas fa-clock"></i> Recently Registered Patients</span>
                <a href="pages/patients.php" class="btn btn-primary btn-sm">View All</a>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Patient Name</th>
                            <th>Age</th>
                            <th>Gender</th>
                            <th>Village</th>
                            <th>Disease</th>
                            <th>Doctor</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($recent_patients) > 0): ?>
                            <?php $i = 1; while ($row = mysqli_fetch_assoc($recent_patients)): ?>
                            <tr>
                                <td><?= $i++ ?></td>
                                <td><strong><?= htmlspecialchars($row['name']) ?></strong></td>
                                <td><?= $row['age'] ?></td>
                                <td>
                                    <span class="badge <?= $row['gender'] == 'Male' ? 'bg-primary' : 'bg-danger' ?>">
                                        <?= $row['gender'] ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($row['village']) ?></td>
                                <td><?= htmlspecialchars($row['disease']) ?></td>
                                <td><?= $row['doctor_name'] ? htmlspecialchars($row['doctor_name']) : '-' ?></td>
                                <td><?= date('d M Y', strtotime($row['visit_date'])) ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    No patients registered yet.
                                </td>
                            </tr>
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
