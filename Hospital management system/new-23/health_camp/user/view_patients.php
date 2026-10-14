<?php

session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['doctor', 'staff'])) {
    header("Location: /health_camp/login.php");
    exit();
}

include(__DIR__ . '/../includes/db.php');

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_date = isset($_GET['filter_date']) ? $_GET['filter_date'] : '';

$where = "WHERE 1=1";
if ($search) {
    $where .= " AND (p.name LIKE '%$search%' OR p.village LIKE '%$search%' OR p.disease LIKE '%$search%')";
}
if ($filter_date) {
    $where .= " AND p.visit_date = '$filter_date'";
}

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="doctor_patient_records.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID', 'Patient Name', 'Age', 'Gender', 'Phone', 'Village', 'Disease', 'Diagnosis', 'Doctor', 'Visit Date']);

    $export_query = mysqli_query($conn,
        "SELECT p.*, d.name as doctor_name FROM patients p
         LEFT JOIN doctors d ON p.doctor_id = d.id
         $where
         ORDER BY p.visit_date DESC, p.created_at DESC");

    while ($row = mysqli_fetch_assoc($export_query)) {
        fputcsv($out, [
            $row['id'],
            $row['name'],
            $row['age'],
            $row['gender'],
            $row['phone'],
            $row['village'],
            $row['disease'],
            $row['diagnosis'],
            $row['doctor_name'] ?: 'Not Assigned',
            $row['visit_date'],
        ]);
    }

    fclose($out);
    exit;
}

$patients = mysqli_query($conn,
    "SELECT p.*, d.name as doctor_name FROM patients p
     LEFT JOIN doctors d ON p.doctor_id = d.id
     $where
     ORDER BY p.visit_date DESC, p.created_at DESC");

$total = mysqli_num_rows($patients);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Patients</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
</head>
<body>

<?php include('sidebar_user.php'); ?>

<div class="main-content">

    <div class="topbar">
        <h4><i class="fas fa-list-alt"></i> Patient Records</h4>
        <div class="user-info">
            <div class="user-avatar"><?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?></div>
            <span><?= $_SESSION['user_name'] ?></span>
        </div>
    </div>

    <div class="page-content">

        <div class="card mb-3">
            <div class="card-body py-3">
                <form method="GET" action="" class="row align-items-end gap-2">
                    <div class="col-md-4">
                        <label class="form-label">Search Patient</label>
                        <input type="text" name="search" class="form-control"
                               placeholder="Name, village, disease..."
                               value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Filter by Date</label>
                        <input type="date" name="filter_date" class="form-control"
                               value="<?= $filter_date ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                    <div class="col-md-2">
                        <a href="view_patients.php" class="btn btn-secondary w-100">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    </div>
                    <div class="col-md-2">
                        <a href="view_patients.php?search=<?= urlencode($search) ?>&filter_date=<?= urlencode($filter_date) ?>&export=csv" class="btn btn-success w-100">
                            <i class="fas fa-file-excel"></i> Export Excel
                        </a>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-outline-secondary w-100" onclick="window.print()">
                            <i class="fas fa-file-pdf"></i> Print / PDF
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <span>
                    <i class="fas fa-users"></i> Patient List
                    <span class="badge bg-success ms-2"><?= $total ?> found</span>
                </span>
                <a href="register_patient.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Register New
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>#ID</th>
                                <th>Patient Name</th>
                                <th>Age</th>
                                <th>Gender</th>
                                <th>Phone</th>
                                <th>Village</th>
                                <th>Disease / Complaint</th>
                                <th>Diagnosis</th>
                                <th>Doctor</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($total > 0):
                                while ($p = mysqli_fetch_assoc($patients)): ?>
                            <tr>
                                <td><span class="badge bg-secondary">#<?= $p['id'] ?></span></td>
                                <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
                                <td><?= $p['age'] ?></td>
                                <td>
                                    <span class="badge <?= $p['gender']=='Male'?'bg-primary':($p['gender']=='Female'?'bg-danger':'bg-secondary') ?>">
                                        <?= $p['gender'] ?>
                                    </span>
                                </td>
                                <td><?= $p['phone'] ?: '-' ?></td>
                                <td><?= htmlspecialchars($p['village']) ?: '-' ?></td>
                                <td><?= htmlspecialchars($p['disease']) ?: '-' ?></td>
                                <td>
                                    <?php if (!empty($p['diagnosis'])): ?>
                                        <span class="badge bg-success">Done</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= $p['doctor_name'] ? htmlspecialchars($p['doctor_name']) : '<span class="text-muted">Not Assigned</span>' ?></td>
                                <td><?= date('d M Y', strtotime($p['visit_date'])) ?></td>
                                <td>
                                    <a href="enter_diagnosis.php?patient_id=<?= $p['id'] ?>"
                                       class="btn btn-warning btn-sm me-1" title="Enter Diagnosis">
                                        <i class="fas fa-stethoscope"></i>
                                    </a>
                                    <a href="../patient_report.php?id=<?= $p['id'] ?>"
                                       class="btn btn-info btn-sm" title="Download Patient Report">
                                        <i class="fas fa-download"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; else: ?>
                            <tr>
                                <td colspan="11" class="text-center text-muted py-4">
                                    <?= $search || $filter_date ? 'No patients found for your search.' : 'No patients registered yet.' ?>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
