<?php

session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['doctor', 'staff'])) {
    header("Location: /health_camp/login.php");
    exit();
}

include(__DIR__ . '/../includes/db.php');

$today = date('Y-m-d');
$user_id = $_SESSION['user_id'];

$total_patients = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM patients"));

$today_patients = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM patients WHERE visit_date = '$today'"));

$my_patients = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as cnt FROM patients p
     JOIN doctors d ON p.doctor_id = d.id
     JOIN users u ON u.name = d.name
     WHERE u.id = '$user_id'"));

$low_stock_items = mysqli_query($conn, "SELECT id, name, quantity, unit FROM medicines WHERE quantity < 50 ORDER BY quantity ASC");

$recent = mysqli_query($conn,
    "SELECT p.*, d.name as doctor_name FROM patients p
     LEFT JOIN doctors d ON p.doctor_id = d.id
     ORDER BY p.created_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= ucfirst($_SESSION['user_role']) ?> Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
</head>
<body>

<?php include('sidebar_user.php'); ?>

<div class="main-content">

    <div class="topbar">
        <h4><i class="fas fa-tachometer-alt"></i> <?= ucfirst($_SESSION['user_role']) ?> Dashboard</h4>
        <div class="user-info">
            <div class="user-avatar"><?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?></div>
            <span><?= $_SESSION['user_name'] ?> (<?= $_SESSION['user_role'] ?>)</span>
        </div>
    </div>

    <div class="page-content">

        <div class="alert" style="background:#eafaf1; color:#1a7a4a; border-left:4px solid #1a7a4a;">
            <i class="fas fa-hand-wave"></i>
            Welcome, <strong><?= $_SESSION['user_name'] ?></strong>!
            You are logged in as <strong><?= ucfirst($_SESSION['user_role']) ?></strong>.
            Today is <strong><?= date('d M Y') ?></strong>.
        </div>

        <div class="row mb-4">
            <div class="col-md-4">
                <a href="register_patient.php" class="text-decoration-none">
                    <div class="stat-card" style="cursor:pointer; transition:0.2s;" onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'">
                        <div class="icon-box bg-green"><i class="fas fa-user-plus"></i></div>
                        <div>
                            <h3 style="font-size:16px; margin-bottom:2px;">Register Patient</h3>
                            <p>Add a new patient</p>
                        </div>
                    </div>
                </a>
            </div>
            <?php if ($_SESSION['user_role'] === 'doctor'): ?>
            <div class="col-md-4">
                <a href="doctor_reports.php" class="text-decoration-none">
                    <div class="stat-card" style="cursor:pointer; transition:0.2s;" onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'">
                        <div class="icon-box bg-blue"><i class="fas fa-file-medical-alt"></i></div>
                        <div>
                            <h3 style="font-size:16px; margin-bottom:2px;">My Reports</h3>
                            <p>View assigned patients</p>
                        </div>
                    </div>
                </a>
            </div>
            <?php endif; ?>
            <div class="col-md-4">
                <a href="view_patients.php" class="text-decoration-none">
                    <div class="stat-card" style="cursor:pointer; transition:0.2s;" onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'">
                        <div class="icon-box bg-blue"><i class="fas fa-list-alt"></i></div>
                        <div>
                            <h3 style="font-size:16px; margin-bottom:2px;">View Records</h3>
                            <p><?= $total_patients['cnt'] ?> total patients</p>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-4">
                <a href="enter_diagnosis.php" class="text-decoration-none">
                    <div class="stat-card" style="cursor:pointer; transition:0.2s;" onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'">
                        <div class="icon-box bg-orange"><i class="fas fa-stethoscope"></i></div>
                        <div>
                            <h3 style="font-size:16px; margin-bottom:2px;">Enter Diagnosis</h3>
                            <p>Update patient diagnosis</p>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-6">
                <div class="stat-card">
                    <div class="icon-box bg-green"><i class="fas fa-users"></i></div>
                    <div>
                        <h3><?= $total_patients['cnt'] ?></h3>
                        <p>Total Patients in Camp</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="stat-card">
                    <div class="icon-box bg-blue"><i class="fas fa-calendar-day"></i></div>
                    <div>
                        <h3><?= $today_patients['cnt'] ?></h3>
                        <p>Patients Today</p>
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
                        <i class="fas fa-pills"></i> Restock these items soon:
                        <ul class="mb-0 mt-2">
                            <?php while ($item = mysqli_fetch_assoc($low_stock_items)): ?>
                                <li><strong><?= htmlspecialchars($item['name']) ?></strong> — <?= (int)$item['quantity'] ?> <?= htmlspecialchars($item['unit'] ?: 'units') ?> left.</li>
                            <?php endwhile; ?>
                        </ul>
                    </div>
                <?php else: ?>
                    <div class="alert alert-success mb-0">
                        <i class="fas fa-check-circle"></i> Medicine stock is healthy right now.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <span><i class="fas fa-clock"></i> Recently Registered Patients</span>
                <a href="view_patients.php" class="btn btn-primary btn-sm">View All</a>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Age</th>
                            <th>Gender</th>
                            <th>Village</th>
                            <th>Disease</th>
                            <th>Doctor</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($recent) > 0):
                            $i = 1; while ($r = mysqli_fetch_assoc($recent)): ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><strong><?= htmlspecialchars($r['name']) ?></strong></td>
                            <td><?= $r['age'] ?></td>
                            <td><span class="badge <?= $r['gender']=='Male'?'bg-primary':'bg-danger' ?>"><?= $r['gender'] ?></span></td>
                            <td><?= htmlspecialchars($r['village']) ?></td>
                            <td><?= htmlspecialchars($r['disease']) ?></td>
                            <td><?= $r['doctor_name'] ?? '-' ?></td>
                            <td><?= date('d M Y', strtotime($r['visit_date'])) ?></td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="8" class="text-center text-muted py-3">No patients yet.</td></tr>
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