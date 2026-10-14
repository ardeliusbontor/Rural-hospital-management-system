<?php

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'doctor') {
    header('Location: ../login.php');
    exit();
}

include(__DIR__ . '/../includes/db.php');

$user_id = intval($_SESSION['user_id']);
$doctor = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT d.id, d.name FROM doctors d
     JOIN users u ON u.name = d.name
     WHERE u.id = '$user_id'"
));

if (!$doctor) {
    die('Doctor record not found.');
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';
$export = isset($_GET['export']) ? $_GET['export'] : '';

$where = "WHERE p.doctor_id = '{$doctor['id']}'";
if ($search) {
    $search_safe = mysqli_real_escape_string($conn, $search);
    $where .= " AND (p.name LIKE '%$search_safe%' OR p.village LIKE '%$search_safe%' OR p.disease LIKE '%$search_safe%')";
}
if ($from_date) {
    $where .= " AND p.visit_date >= '$from_date'";
}
if ($to_date) {
    $where .= " AND p.visit_date <= '$to_date'";
}

if ($export === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="doctor_assigned_patients.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Doctor Report for', $doctor['name']]);
    fputcsv($out, ['Generated On', date('Y-m-d H:i:s')]);
    fputcsv($out, []);
    fputcsv($out, ['ID', 'Patient Name', 'Age', 'Gender', 'Phone', 'Village', 'Disease', 'Diagnosis', 'Visit Date']);
    $export_result = mysqli_query($conn,
        "SELECT p.* FROM patients p $where ORDER BY p.visit_date DESC, p.created_at DESC");
    while ($row = mysqli_fetch_assoc($export_result)) {
        fputcsv($out, [
            $row['id'],
            $row['name'],
            $row['age'],
            $row['gender'],
            $row['phone'],
            $row['village'],
            $row['disease'],
            $row['diagnosis'],
            $row['visit_date'],
        ]);
    }
    fclose($out);
    exit;
}

$patients = mysqli_query($conn,
    "SELECT p.* FROM patients p $where ORDER BY p.visit_date DESC, p.created_at DESC");
$total = mysqli_num_rows($patients);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Reports - Health Camp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
</head>
<body>

<?php include('sidebar_user.php'); ?>

<div class="main-content">

    <div class="topbar">
        <h4><i class="fas fa-file-medical-alt"></i> Doctor Reports</h4>
        <div class="user-info">
            <div class="user-avatar"><?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?></div>
            <span><?= $_SESSION['user_name'] ?></span>
        </div>
    </div>

    <div class="page-content">

        <div class="card mb-4">
            <div class="card-body py-3">
                <form method="GET" action="" class="row align-items-end gap-2">
                    <div class="col-md-4">
                        <label class="form-label">Search Assigned Patients</label>
                        <input type="text" name="search" class="form-control" placeholder="Name, village, disease..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">From Date</label>
                        <input type="date" name="from_date" class="form-control" value="<?= htmlspecialchars($from_date) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">To Date</label>
                        <input type="date" name="to_date" class="form-control" value="<?= htmlspecialchars($to_date) ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i> Filter</button>
                    </div>
                    <div class="col-md-2">
                        <a href="doctor_reports.php" class="btn btn-secondary w-100"><i class="fas fa-times"></i> Reset</a>
                    </div>
                    <div class="col-md-2">
                        <a href="doctor_reports.php?search=<?= urlencode($search) ?>&from_date=<?= urlencode($from_date) ?>&to_date=<?= urlencode($to_date) ?>&export=csv" class="btn btn-success w-100">
                            <i class="fas fa-file-csv"></i> Download Assigned CSV
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="alert alert-info mb-4">
            <i class="fas fa-user-md"></i>
            Showing <strong><?= $total ?></strong> patient(s) assigned to you.
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-users"></i> Assigned Patients</span>
                <span class="badge bg-success"><?= $total ?> assigned</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Patient Name</th>
                                <th>Age</th>
                                <th>Gender</th>
                                <th>Village</th>
                                <th>Disease</th>
                                <th>Diagnosis</th>
                                <th>Visit Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($total > 0): $i = 1; while ($row = mysqli_fetch_assoc($patients)): ?>
                            <tr>
                                <td><?= $i++ ?></td>
                                <td><strong><?= htmlspecialchars($row['name']) ?></strong></td>
                                <td><?= htmlspecialchars($row['age']) ?></td>
                                <td><?= htmlspecialchars($row['gender']) ?></td>
                                <td><?= htmlspecialchars($row['village']) ?></td>
                                <td><?= htmlspecialchars($row['disease']) ?></td>
                                <td><?= $row['diagnosis'] ? htmlspecialchars($row['diagnosis']) : '<span class="text-muted">Pending</span>' ?></td>
                                <td><?= htmlspecialchars($row['visit_date']) ?></td>
                                <td>
                                    <a href="../patient_report.php?id=<?= $row['id'] ?>" class="btn btn-info btn-sm" title="Download Report">
                                        <i class="fas fa-file-download"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; else: ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">No assigned patients found.</td>
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
