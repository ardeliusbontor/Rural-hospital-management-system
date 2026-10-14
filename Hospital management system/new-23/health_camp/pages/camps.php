<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include('../includes/db.php');

$message = "";


mysqli_query($conn, "CREATE TABLE IF NOT EXISTS camps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    camp_name VARCHAR(150) NOT NULL,
    location VARCHAR(200) NOT NULL,
    village VARCHAR(100),
    camp_date DATE NOT NULL,
    start_time TIME,
    end_time TIME,
    organizer VARCHAR(100),
    status ENUM('Upcoming','Ongoing','Completed','Cancelled') DEFAULT 'Upcoming',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS camp_doctors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    camp_id INT NOT NULL,
    doctor_id INT NOT NULL
)");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS patient_camps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    camp_id INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_assignment (patient_id, camp_id),
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (camp_id) REFERENCES camps(id) ON DELETE CASCADE
)");

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_camp'])) {
    $camp_name  = trim($_POST['camp_name']);
    $location   = trim($_POST['location']);
    $village    = trim($_POST['village']);
    $camp_date  = $_POST['camp_date'];
    $start_time = $_POST['start_time'];
    $end_time   = $_POST['end_time'];
    $organizer  = trim($_POST['organizer']);
    $status     = $_POST['status'];
    $notes      = trim($_POST['notes']);

    if (empty($camp_name) || empty($location) || empty($camp_date)) {
        $message = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Camp name, location and date are required!</div>';
    } else {
        $sql = "INSERT INTO camps (camp_name, location, village, camp_date, start_time, end_time, organizer, status, notes)
                VALUES ('$camp_name','$location','$village','$camp_date','$start_time','$end_time','$organizer','$status','$notes')";
        if (mysqli_query($conn, $sql)) {
            $new_camp_id = mysqli_insert_id($conn);

            if (!empty($_POST['doctors'])) {
                foreach ($_POST['doctors'] as $doc_id) {
                    $doc_id = intval($doc_id);
                    mysqli_query($conn, "INSERT INTO camp_doctors (camp_id, doctor_id) VALUES ('$new_camp_id','$doc_id')");
                }
            }
            $message = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Camp <strong>' . htmlspecialchars($camp_name) . '</strong> created successfully!</div>';
        } else {
            $message = '<div class="alert alert-danger">Error creating camp. Try again.</div>';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $camp_id    = intval($_POST['camp_id']);
    $new_status = $_POST['new_status'];
    mysqli_query($conn, "UPDATE camps SET status='$new_status' WHERE id='$camp_id'");
    $message = '<div class="alert alert-success">Camp status updated!</div>';
}

if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM camps WHERE id='$del_id'");
    mysqli_query($conn, "DELETE FROM camp_doctors WHERE camp_id='$del_id'");
    $message = '<div class="alert alert-warning"><i class="fas fa-trash"></i> Camp deleted.</div>';
}

$view_camp = null;
$camp_doctors_list = [];
$camp_patients = null;

if (isset($_GET['view'])) {
    $view_id = intval($_GET['view']);
    $view_camp = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM camps WHERE id='$view_id'"));

    $cd_result = mysqli_query($conn,
        "SELECT d.* FROM camp_doctors cd
         JOIN doctors d ON cd.doctor_id = d.id
         WHERE cd.camp_id = '$view_id'");
    while ($cd = mysqli_fetch_assoc($cd_result)) {
        $camp_doctors_list[] = $cd;
    }

    $camp_patients = mysqli_query($conn,
        "SELECT p.*, d.name as doctor_name
         FROM patients p
         LEFT JOIN doctors d ON p.doctor_id = d.id
         INNER JOIN patient_camps pc ON pc.patient_id = p.id
         WHERE pc.camp_id = '$view_id'
         ORDER BY p.created_at DESC");
}

$filter_status = isset($_GET['filter']) ? $_GET['filter'] : '';
$where = $filter_status ? "WHERE status='$filter_status'" : "";
$all_camps = mysqli_query($conn, "SELECT * FROM camps $where ORDER BY camp_date DESC");

$total_camps     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM camps"))['cnt'];
$upcoming_camps  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM camps WHERE status='Upcoming'"))['cnt'];
$ongoing_camps   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM camps WHERE status='Ongoing'"))['cnt'];
$completed_camps = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM camps WHERE status='Completed'"))['cnt'];

$all_doctors = mysqli_query($conn, "SELECT * FROM doctors ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Camps - Health Camp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <style>
        .status-badge { font-size: 12px; padding: 5px 12px; border-radius: 20px; font-weight: 600; }
        .camp-detail-box { background:#f8fffe; border:2px solid #d5e8d4; border-radius:10px; padding:20px; margin-bottom:20px; }
        .camp-detail-box h5 { color:#1a7a4a; font-weight:700; }
        .info-row { display:flex; gap:8px; align-items:center; margin-bottom:8px; font-size:14px; }
        .info-row i { color:#1a7a4a; width:18px; }
        .doctor-chip { display:inline-flex; align-items:center; gap:6px; background:#eafaf1;
                       color:#1a7a4a; border-radius:20px; padding:4px 12px; font-size:13px;
                       font-weight:600; margin:3px; border:1px solid #a9dfbf; }
    </style>
</head>
<body>

<?php include('../includes/sidebar.php'); ?>

<div class="main-content">

    <div class="topbar">
        <h4><i class="fas fa-campground"></i> Manage Camps</h4>
        <div class="user-info">
            <div class="user-avatar"><?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?></div>
            <span><?= $_SESSION['user_name'] ?> (<?= $_SESSION['user_role'] ?>)</span>
        </div>
    </div>

    <div class="page-content">

        <?= $message ?>

        <?php if ($view_camp): ?>
      
        <div class="mb-3">
            <a href="camps.php" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Back to All Camps
            </a>
        </div>

        <div class="camp-detail-box">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div>
                    <h5><i class="fas fa-campground me-2"></i><?= htmlspecialchars($view_camp['camp_name']) ?></h5>
                    <div class="info-row">
                        <i class="fas fa-map-marker-alt"></i>
                        <?= htmlspecialchars($view_camp['location']) ?>
                        <?= $view_camp['village'] ? ' — ' . htmlspecialchars($view_camp['village']) : '' ?>
                    </div>
                    <div class="info-row">
                        <i class="fas fa-calendar"></i>
                        <strong><?= date('d M Y', strtotime($view_camp['camp_date'])) ?></strong>
                        <?= $view_camp['start_time']
                            ? '&nbsp;&nbsp;<i class="fas fa-clock" style="color:#1a7a4a;"></i>&nbsp;'
                              . date('h:i A', strtotime($view_camp['start_time']))
                              . ' – ' . date('h:i A', strtotime($view_camp['end_time']))
                            : '' ?>
                    </div>
                    <?php if ($view_camp['organizer']): ?>
                    <div class="info-row">
                        <i class="fas fa-user-tie"></i>
                        Organized by: <strong><?= htmlspecialchars($view_camp['organizer']) ?></strong>
                    </div>
                    <?php endif; ?>
                    <?php if ($view_camp['notes']): ?>
                    <div class="info-row">
                        <i class="fas fa-sticky-note"></i>
                        <?= htmlspecialchars($view_camp['notes']) ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div>
                    <form method="POST" action="" class="d-flex gap-2 align-items-center">
                        <input type="hidden" name="camp_id" value="<?= $view_camp['id'] ?>">
                        <select name="new_status" class="form-select form-select-sm" style="width:140px;">
                            <?php foreach (['Upcoming','Ongoing','Completed','Cancelled'] as $s): ?>
                                <option value="<?= $s ?>" <?= $view_camp['status']==$s?'selected':'' ?>><?= $s ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" name="update_status" class="btn btn-primary btn-sm">Update</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="row">

            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <span><i class="fas fa-user-md"></i> Assigned Doctors (<?= count($camp_doctors_list) ?>)</span>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($camp_doctors_list)): ?>
                            <?php foreach ($camp_doctors_list as $cd): ?>
                                <div class="doctor-chip mb-1">
                                    <i class="fas fa-user-md"></i>
                                    <?= htmlspecialchars($cd['name']) ?>
                                    <?php if ($cd['specialization']): ?>
                                        <small style="opacity:0.7;">(<?= $cd['specialization'] ?>)</small>
                                    <?php endif; ?>
                                </div><br>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted mb-0">No doctors assigned to this camp.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <span>
                            <i class="fas fa-user-injured"></i>
                            Patients in this Camp
                            <span class="badge bg-success ms-2"><?= mysqli_num_rows($camp_patients) ?></span>
                        </span>
                        <a href="../pages/patients.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Add Patient
                        </a>
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
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($camp_patients) > 0):
                                    $i = 1;
                                    while ($p = mysqli_fetch_assoc($camp_patients)): ?>
                                <tr>
                                    <td><?= $i++ ?></td>
                                    <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
                                    <td><?= $p['age'] ?></td>
                                    <td>
                                        <span class="badge <?= $p['gender']=='Male' ? 'bg-primary' : 'bg-danger' ?>">
                                            <?= $p['gender'] ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($p['village']) ?: '-' ?></td>
                                    <td><?= htmlspecialchars($p['disease']) ?: '-' ?></td>
                                    <td><?= $p['doctor_name'] ?? '<span class="text-muted">-</span>' ?></td>
                                </tr>
                                <?php endwhile; else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        <i class="fas fa-user-injured fa-2x mb-2 d-block"></i>
                                        No patients assigned to this camp yet.<br>
                                        <small>Go to <a href="../pages/patients.php">Patients page</a> and use the <strong>+ Camp</strong> button or assign during registration.</small>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>

        <?php else: ?>
      
        <div class="row mb-3">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="icon-box bg-green"><i class="fas fa-campground"></i></div>
                    <div><h3><?= $total_camps ?></h3><p>Total Camps</p></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="icon-box bg-blue"><i class="fas fa-calendar-alt"></i></div>
                    <div><h3><?= $upcoming_camps ?></h3><p>Upcoming</p></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="icon-box bg-orange"><i class="fas fa-spinner"></i></div>
                    <div><h3><?= $ongoing_camps ?></h3><p>Ongoing</p></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="icon-box" style="background:#27ae60;"><i class="fas fa-check-double"></i></div>
                    <div><h3><?= $completed_camps ?></h3><p>Completed</p></div>
                </div>
            </div>
        </div>

        <div class="row">

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <span><i class="fas fa-plus-circle"></i> Create New Camp</span>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">

                            <div class="mb-3">
                                <label class="form-label">Camp Name <span class="text-danger">*</span></label>
                                <input type="text" name="camp_name" class="form-control"
                                       placeholder="e.g. Village Health Camp 2026" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Location / Address <span class="text-danger">*</span></label>
                                <input type="text" name="location" class="form-control"
                                       placeholder="e.g. Community Hall, Sector 12" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Village / Area</label>
                                <input type="text" name="village" class="form-control"
                                       placeholder="e.g. Raipur Village">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Camp Date <span class="text-danger">*</span></label>
                                <input type="date" name="camp_date" class="form-control"
                                       value="<?= date('Y-m-d') ?>" required>
                            </div>

                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label class="form-label">Start Time</label>
                                    <input type="time" name="start_time" class="form-control" value="09:00">
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label">End Time</label>
                                    <input type="time" name="end_time" class="form-control" value="17:00">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Organizer Name</label>
                                <input type="text" name="organizer" class="form-control"
                                       placeholder="e.g. GGI Health Team">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="Upcoming">Upcoming</option>
                                    <option value="Ongoing">Ongoing</option>
                                    <option value="Completed">Completed</option>
                                    <option value="Cancelled">Cancelled</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Assign Doctors</label>
                                <div style="border:2px solid #d5e8d4; border-radius:8px; padding:10px; max-height:130px; overflow-y:auto;">
                                    <?php if (mysqli_num_rows($all_doctors) > 0):
                                        mysqli_data_seek($all_doctors, 0);
                                        while ($doc = mysqli_fetch_assoc($all_doctors)): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   name="doctors[]" value="<?= $doc['id'] ?>"
                                                   id="doc_<?= $doc['id'] ?>">
                                            <label class="form-check-label" for="doc_<?= $doc['id'] ?>" style="font-size:13px;">
                                                <?= htmlspecialchars($doc['name']) ?>
                                                <?= $doc['specialization'] ? '<small class="text-muted">('.$doc['specialization'].')</small>' : '' ?>
                                            </label>
                                        </div>
                                    <?php endwhile; else: ?>
                                        <small class="text-muted">No doctors added yet. <a href="doctors.php">Add doctors first.</a></small>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-control" rows="2"
                                          placeholder="Any special instructions..."></textarea>
                            </div>

                            <button type="submit" name="add_camp" class="btn btn-primary w-100">
                                <i class="fas fa-save"></i> Create Camp
                            </button>

                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <span><i class="fas fa-list"></i> All Camps</span>
                        <div class="d-flex gap-1 flex-wrap">
                            <a href="camps.php" class="btn btn-sm <?= !$filter_status ? 'btn-primary' : 'btn-outline-secondary' ?>">All</a>
                            <a href="?filter=Upcoming"  class="btn btn-sm <?= $filter_status=='Upcoming'  ? 'btn-primary' : 'btn-outline-secondary' ?>">Upcoming</a>
                            <a href="?filter=Ongoing"   class="btn btn-sm <?= $filter_status=='Ongoing'   ? 'btn-primary' : 'btn-outline-secondary' ?>">Ongoing</a>
                            <a href="?filter=Completed" class="btn btn-sm <?= $filter_status=='Completed' ? 'btn-primary' : 'btn-outline-secondary' ?>">Completed</a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Camp Name</th>
                                    <th>Location</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($all_camps) > 0):
                                    $i = 1;
                                    while ($camp = mysqli_fetch_assoc($all_camps)):

                                    $badge = match($camp['status']) {
                                        'Upcoming'  => 'bg-primary',
                                        'Ongoing'   => 'bg-warning text-dark',
                                        'Completed' => 'bg-success',
                                        'Cancelled' => 'bg-danger',
                                        default     => 'bg-secondary'
                                    };

                                    $doc_count = mysqli_fetch_assoc(mysqli_query($conn,
                                        "SELECT COUNT(*) as cnt FROM camp_doctors
                                         WHERE camp_id='{$camp['id']}'"))['cnt'];

                                    $pat_count = mysqli_fetch_assoc(mysqli_query($conn,
                                        "SELECT COUNT(*) as cnt FROM patient_camps
                                         WHERE camp_id='{$camp['id']}'"))['cnt'];
                                ?>
                                <tr>
                                    <td><?= $i++ ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($camp['camp_name']) ?></strong><br>
                                        <?php if ($camp['village']): ?>
                                            <small class="text-muted"><i class="fas fa-map-pin"></i> <?= htmlspecialchars($camp['village']) ?></small>
                                        <?php endif; ?>
                                        <?php if ($camp['organizer']): ?>
                                            <br><small class="text-muted"><i class="fas fa-user-tie"></i> <?= htmlspecialchars($camp['organizer']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-size:13px;"><?= htmlspecialchars($camp['location']) ?></td>
                                    <td style="font-size:13px;"><strong><?= date('d M Y', strtotime($camp['camp_date'])) ?></strong></td>
                                    <td style="font-size:12px;">
                                        <?= $camp['start_time']
                                            ? date('h:i A', strtotime($camp['start_time']))
                                              . '<br>'
                                              . date('h:i A', strtotime($camp['end_time']))
                                            : '-' ?>
                                    </td>
                                    <td>
                                        <span class="badge <?= $badge ?>"><?= $camp['status'] ?></span><br>
                                        <small style="font-size:11px; color:#888;">
                                            👨‍⚕️ <?= $doc_count ?> doctors &nbsp;
                                            🧑‍🤝‍🧑 <?= $pat_count ?> patients
                                        </small>
                                    </td>
                                    <td>
                                        <a href="?view=<?= $camp['id'] ?>"
                                           class="btn btn-success btn-sm mb-1" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="?delete=<?= $camp['id'] ?>"
                                           class="btn btn-danger btn-sm mb-1"
                                           onclick="return confirm('Delete this camp?')" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-5">
                                        <i class="fas fa-campground fa-2x mb-2 d-block"></i>
                                        No camps created yet. Create your first camp!
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
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
