<?php

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

include('../includes/db.php');

$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-01'); // First day of month
$to_date   = isset($_GET['to_date'])   ? $_GET['to_date']   : date('Y-m-d');   // Today
$export    = isset($_GET['export']) ? $_GET['export'] : '';
$export_all = isset($_GET['all']) && $_GET['all'] === '1';

$total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM patients WHERE visit_date BETWEEN '$from_date' AND '$to_date'"));

$males = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM patients WHERE gender='Male' AND visit_date BETWEEN '$from_date' AND '$to_date'"));

$females = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM patients WHERE gender='Female' AND visit_date BETWEEN '$from_date' AND '$to_date'"));

$by_village = mysqli_query($conn, "SELECT village, COUNT(*) as cnt FROM patients WHERE visit_date BETWEEN '$from_date' AND '$to_date' AND village != '' GROUP BY village ORDER BY cnt DESC LIMIT 10");

$by_disease = mysqli_query($conn, "SELECT disease, COUNT(*) as cnt FROM patients WHERE visit_date BETWEEN '$from_date' AND '$to_date' AND disease != '' GROUP BY disease ORDER BY cnt DESC LIMIT 10");

$by_doctor = mysqli_query($conn, "SELECT d.name, COUNT(p.id) as cnt FROM patients p LEFT JOIN doctors d ON p.doctor_id = d.id WHERE p.visit_date BETWEEN '$from_date' AND '$to_date' GROUP BY p.doctor_id ORDER BY cnt DESC");

$daily = mysqli_query($conn, "SELECT visit_date, COUNT(*) as cnt FROM patients WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY visit_date ORDER BY visit_date ASC");

if ($export === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="health_camp_reports.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Report Period', $from_date . ' to ' . $to_date]);
    fputcsv($out, ['Metric', 'Value']);
    fputcsv($out, ['Total Patients', $total['cnt']]);
    fputcsv($out, ['Male Patients', $males['cnt']]);
    fputcsv($out, ['Female Patients', $females['cnt']]);
    fputcsv($out, []);
    fputcsv($out, ['Village', 'Patients', '%']);
    $village_data = mysqli_query($conn, "SELECT village, COUNT(*) as cnt FROM patients WHERE visit_date BETWEEN '$from_date' AND '$to_date' AND village != '' GROUP BY village ORDER BY cnt DESC LIMIT 10");
    while ($v = mysqli_fetch_assoc($village_data)) {
        $pct = $total['cnt'] > 0 ? round(($v['cnt'] / $total['cnt']) * 100) : 0;
        fputcsv($out, [$v['village'], $v['cnt'], $pct . '%']);
    }
    fputcsv($out, []);
    fputcsv($out, ['Disease', 'Count']);
    $disease_data = mysqli_query($conn, "SELECT disease, COUNT(*) as cnt FROM patients WHERE visit_date BETWEEN '$from_date' AND '$to_date' AND disease != '' GROUP BY disease ORDER BY cnt DESC LIMIT 10");
    while ($d = mysqli_fetch_assoc($disease_data)) {
        fputcsv($out, [$d['disease'], $d['cnt']]);
    }
    fclose($out);
    exit;
} elseif ($export === 'patients') {
    header('Content-Type: text/csv; charset=utf-8');
    $filename = $export_all ? 'health_camp_all_patients.csv' : 'health_camp_patients_' . $from_date . '_to_' . $to_date . '.csv';
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID', 'Patient Name', 'Age', 'Gender', 'Phone', 'Village', 'Disease', 'Diagnosis', 'Doctor', 'Visit Date']);
    $query = "SELECT p.*, d.name as doctor_name FROM patients p LEFT JOIN doctors d ON p.doctor_id = d.id";
    if (!$export_all) {
        $query .= " WHERE p.visit_date BETWEEN '$from_date' AND '$to_date'";
    }
    $query .= " ORDER BY p.created_at DESC";
    $export_result = mysqli_query($conn, $query);
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
            $row['doctor_name'] ?: 'Not Assigned',
            $row['visit_date'],
        ]);
    }
    fclose($out);
    exit;
} elseif ($export === 'camp') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="health_camp_full_report.csv"');
    $out = fopen('php://output', 'w');
    $all_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM patients"));
    $all_males = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM patients WHERE gender='Male'"));
    $all_females = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM patients WHERE gender='Female'"));
    fputcsv($out, ['Health Camp Full Report']);
    fputcsv($out, ['Generated On', date('Y-m-d H:i:s')]);
    fputcsv($out, []);
    fputcsv($out, ['Metric', 'Value']);
    fputcsv($out, ['Total Patients', $all_count['cnt']]);
    fputcsv($out, ['Male Patients', $all_males['cnt']]);
    fputcsv($out, ['Female Patients', $all_females['cnt']]);
    fputcsv($out, []);
    fputcsv($out, ['Patients by Village', 'Count']);
    $village_data = mysqli_query($conn, "SELECT village, COUNT(*) as cnt FROM patients WHERE village != '' GROUP BY village ORDER BY cnt DESC LIMIT 20");
    while ($v = mysqli_fetch_assoc($village_data)) {
        fputcsv($out, [$v['village'], $v['cnt']]);
    }
    fputcsv($out, []);
    fputcsv($out, ['Patients by Disease', 'Count']);
    $disease_data = mysqli_query($conn, "SELECT disease, COUNT(*) as cnt FROM patients WHERE disease != '' GROUP BY disease ORDER BY cnt DESC LIMIT 20");
    while ($d = mysqli_fetch_assoc($disease_data)) {
        fputcsv($out, [$d['disease'], $d['cnt']]);
    }
    fputcsv($out, []);
    fputcsv($out, ['Patients by Doctor', 'Count']);
    $doctor_data = mysqli_query($conn, "SELECT d.name, COUNT(p.id) as cnt FROM patients p LEFT JOIN doctors d ON p.doctor_id = d.id GROUP BY p.doctor_id ORDER BY cnt DESC");
    while ($dr = mysqli_fetch_assoc($doctor_data)) {
        fputcsv($out, [$dr['name'] ?: 'Not Assigned', $dr['cnt']]);
    }
    fputcsv($out, []);
    fputcsv($out, ['','']);
    fputcsv($out, ['All Patient Records']);
    fputcsv($out, ['ID', 'Patient Name', 'Age', 'Gender', 'Phone', 'Village', 'Disease', 'Diagnosis', 'Doctor', 'Visit Date']);
    $patient_rows = mysqli_query($conn, "SELECT p.*, d.name as doctor_name FROM patients p LEFT JOIN doctors d ON p.doctor_id = d.id ORDER BY p.created_at DESC");
    while ($row = mysqli_fetch_assoc($patient_rows)) {
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Health Camp</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
</head>
<body>

<?php include('../includes/sidebar.php'); ?>

<div class="main-content">

    <div class="topbar">
        <h4><i class="fas fa-chart-bar"></i> Reports & Statistics</h4>
        <div class="user-info">
            <div class="user-avatar"><?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?></div>
            <span><?= $_SESSION['user_name'] ?></span>
        </div>
    </div>

    <div class="page-content">

        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="" class="row align-items-end">
                    <div class="col-md-3 mb-2">
                        <label class="form-label">From Date</label>
                        <input type="date" name="from_date" class="form-control" value="<?= $from_date ?>">
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label">To Date</label>
                        <input type="date" name="to_date" class="form-control" value="<?= $to_date ?>">
                    </div>
                    <div class="col-md-2 mb-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                    </div>
                    <div class="col-md-2 mb-2">
                        <a href="reports.php" class="btn btn-secondary w-100">Reset</a>
                    </div>
                    <div class="col-md-2 mb-2">
                        <a href="reports.php?from_date=<?= urlencode($from_date) ?>&to_date=<?= urlencode($to_date) ?>&export=csv" class="btn btn-success w-100">
                            <i class="fas fa-file-excel"></i> Summary Export
                        </a>
                    </div>
                    <div class="col-md-2 mb-2">
                        <a href="reports.php?export=patients&all=1" class="btn btn-info w-100">
                            <i class="fas fa-file-csv"></i> Download All Patients
                        </a>
                    </div>
                    <div class="col-md-2 mb-2">
                        <a href="reports.php?export=camp" class="btn btn-dark w-100">
                            <i class="fas fa-hospital"></i> Download Health Camp
                        </a>
                    </div>
                    <div class="col-md-2 mb-2">
                        <button type="button" class="btn btn-outline-secondary w-100" onclick="window.print()">
                            <i class="fas fa-file-pdf"></i> Print / PDF
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <p class="text-muted mb-3">
            <i class="fas fa-calendar"></i>
            Showing data from <strong><?= date('d M Y', strtotime($from_date)) ?></strong>
            to <strong><?= date('d M Y', strtotime($to_date)) ?></strong>
        </p>

        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="icon-box bg-green"><i class="fas fa-users"></i></div>
                    <div>
                        <h3><?= $total['cnt'] ?></h3>
                        <p>Total Patients</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="icon-box bg-blue"><i class="fas fa-male"></i></div>
                    <div>
                        <h3><?= $males['cnt'] ?></h3>
                        <p>Male Patients</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="icon-box" style="background:#e91e8c;"><i class="fas fa-female"></i></div>
                    <div>
                        <h3><?= $females['cnt'] ?></h3>
                        <p>Female Patients</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <span><i class="fas fa-map-marker-alt"></i> Patients by Village</span>
                    </div>
                    <div class="card-body p-0">
                        <table class="table mb-0">
                            <thead>
                                <tr><th>Village</th><th>Patients</th><th>%</th></tr>
                            </thead>
                            <tbody>
                                <?php while ($v = mysqli_fetch_assoc($by_village)):
                                    $pct = $total['cnt'] > 0 ? round(($v['cnt']/$total['cnt'])*100) : 0;
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($v['village']) ?></td>
                                    <td><strong><?= $v['cnt'] ?></strong></td>
                                    <td>
                                        <div style="background:#eee; border-radius:4px; height:8px; width:100px;">
                                            <div style="background:var(--primary); width:<?= $pct ?>%; height:100%; border-radius:4px;"></div>
                                        </div>
                                        <small><?= $pct ?>%</small>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <span><i class="fas fa-stethoscope"></i> Common Diseases</span>
                    </div>
                    <div class="card-body p-0">
                        <table class="table mb-0">
                            <thead>
                                <tr><th>Disease / Complaint</th><th>Count</th></tr>
                            </thead>
                            <tbody>
                                <?php while ($d = mysqli_fetch_assoc($by_disease)): ?>
                                <tr>
                                    <td><?= htmlspecialchars($d['disease']) ?></td>
                                    <td><span class="badge bg-success"><?= $d['cnt'] ?></span></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mt-3">
                <div class="card">
                    <div class="card-header">
                        <span><i class="fas fa-user-md"></i> Patients per Doctor</span>
                    </div>
                    <div class="card-body p-0">
                        <table class="table mb-0">
                            <thead>
                                <tr><th>Doctor</th><th>Patients Seen</th></tr>
                            </thead>
                            <tbody>
                                <?php while ($dr = mysqli_fetch_assoc($by_doctor)): ?>
                                <tr>
                                    <td><?= $dr['name'] ? htmlspecialchars($dr['name']) : 'Not Assigned' ?></td>
                                    <td><strong><?= $dr['cnt'] ?></strong></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mt-3">
                <div class="card">
                    <div class="card-header">
                        <span><i class="fas fa-calendar-week"></i> Last 7 Days</span>
                    </div>
                    <div class="card-body p-0">
                        <table class="table mb-0">
                            <thead>
                                <tr><th>Date</th><th>Patients</th></tr>
                            </thead>
                            <tbody>
                                <?php while ($day = mysqli_fetch_assoc($daily)): ?>
                                <tr>
                                    <td><?= date('D, d M', strtotime($day['visit_date'])) ?></td>
                                    <td><strong><?= $day['cnt'] ?></strong></td>
                                </tr>
                                <?php endwhile; ?>
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
