<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include('../includes/db.php');

$message = "";


mysqli_query($conn, "CREATE TABLE IF NOT EXISTS patient_camps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    camp_id INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_assignment (patient_id, camp_id),
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (camp_id) REFERENCES camps(id) ON DELETE CASCADE
)");


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['assign_camp'])) {
    $patient_id = intval($_POST['patient_id']);
    $camp_id    = intval($_POST['camp_id']);

    if ($patient_id && $camp_id) {
        $check = mysqli_query($conn, "SELECT id FROM patient_camps WHERE patient_id='$patient_id' AND camp_id='$camp_id'");
        if (mysqli_num_rows($check) > 0) {
            $message = '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Patient is already assigned to that camp.</div>';
        } else {
            $sql = "INSERT INTO patient_camps (patient_id, camp_id) VALUES ('$patient_id', '$camp_id')";
            if (mysqli_query($conn, $sql)) {
                $message = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Patient assigned to camp successfully!</div>';
            } else {
                $message = '<div class="alert alert-danger">Error assigning patient to camp.</div>';
            }
        }
    } else {
        $message = '<div class="alert alert-danger">Please select a camp.</div>';
    }
}


if (isset($_GET['remove_camp'])) {
    $pc_id = intval($_GET['remove_camp']);
    mysqli_query($conn, "DELETE FROM patient_camps WHERE id='$pc_id'");
    $message = '<div class="alert alert-warning"><i class="fas fa-minus-circle"></i> Patient removed from camp.</div>';
}


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_patient'])) {
    $name       = trim($_POST['name']);
    $age        = intval($_POST['age']);
    $gender     = $_POST['gender'];
    $phone      = trim($_POST['phone']);
    $village    = trim($_POST['village']);
    $disease    = trim($_POST['disease']);
    $doctor_id  = intval($_POST['doctor_id']);
    $visit_date = $_POST['visit_date'];
    $camp_id    = isset($_POST['camp_id']) ? intval($_POST['camp_id']) : 0;

    if (empty($name)) {
        $message = '<div class="alert alert-danger">Patient name is required!</div>';
    } else {
        $sql = "INSERT INTO patients (name, age, gender, phone, village, disease, doctor_id, visit_date)
                VALUES ('$name', '$age', '$gender', '$phone', '$village', '$disease', '$doctor_id', '$visit_date')";
        if (mysqli_query($conn, $sql)) {
            $new_patient_id = mysqli_insert_id($conn);
            if ($camp_id > 0) {
                mysqli_query($conn, "INSERT IGNORE INTO patient_camps (patient_id, camp_id)
                                     VALUES ('$new_patient_id', '$camp_id')");
                $message = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Patient registered and assigned to camp successfully!</div>';
            } else {
                $message = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Patient registered successfully!</div>';
            }
        } else {
            $message = '<div class="alert alert-danger">Error saving patient. Try again.</div>';
        }
    }
}


if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM patients WHERE id='$delete_id'");
    $message = '<div class="alert alert-warning"><i class="fas fa-trash"></i> Patient deleted.</div>';
}


$search = "";
if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
}

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="patients_export.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID', 'Patient Name', 'Age', 'Gender', 'Phone', 'Village', 'Disease', 'Doctor', 'Visit Date']);

    $query = $search
        ? "SELECT p.*, d.name as doctor_name FROM patients p LEFT JOIN doctors d ON p.doctor_id = d.id WHERE p.name LIKE '%$search%' OR p.village LIKE '%$search%' ORDER BY p.created_at DESC"
        : "SELECT p.*, d.name as doctor_name FROM patients p LEFT JOIN doctors d ON p.doctor_id = d.id ORDER BY p.created_at DESC";

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
            $row['doctor_name'] ?: 'Not Assigned',
            $row['visit_date'],
        ]);
    }

    fclose($out);
    exit;
}

if ($search) {
    $patients = mysqli_query($conn, "SELECT p.*, d.name as doctor_name FROM patients p
                                     LEFT JOIN doctors d ON p.doctor_id = d.id
                                     WHERE p.name LIKE '%$search%' OR p.village LIKE '%$search%'
                                     ORDER BY p.created_at DESC");
} else {
    $patients = mysqli_query($conn, "SELECT p.*, d.name as doctor_name FROM patients p
                                     LEFT JOIN doctors d ON p.doctor_id = d.id
                                     ORDER BY p.created_at DESC");
}

$doctors = mysqli_query($conn, "SELECT * FROM doctors ORDER BY name");

$all_camps = mysqli_query($conn, "SELECT * FROM camps WHERE status != 'Cancelled' && status != 'Completed' ORDER BY camp_date DESC");
$camps_list = [];
while ($c = mysqli_fetch_assoc($all_camps)) {
    $camps_list[] = $c;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patients - Health Camp</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">

    <style>
        .camp-badge {
            font-size: 0.72rem;
            padding: 3px 8px;
            border-radius: 20px;
            background: #e8f4fd;
            color: #1a6fac;
            border: 1px solid #bee3f8;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            margin: 2px;
            white-space: nowrap;
        }
        .camp-badge .remove-camp {
            cursor: pointer;
            color: #c0392b;
            font-size: 0.65rem;
            line-height: 1;
        }
        .camp-badge .remove-camp:hover { color: #e74c3c; }

        #assignCampModal .modal-header {
            background: linear-gradient(135deg, #2980b9, #1a5276);
            color: #fff;
        }
        #assignCampModal .camp-option {
            border: 2px solid #dee2e6;
            border-radius: 10px;
            padding: 10px 14px;
            margin-bottom: 8px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        #assignCampModal .camp-option:hover,
        #assignCampModal .camp-option.selected {
            border-color: #2980b9;
            background: #eaf4fd;
        }
        #assignCampModal .camp-option input[type=radio] { accent-color: #2980b9; }
        .camp-option .camp-meta { font-size: 0.78rem; color: #666; }
        .status-dot {
            width: 9px; height: 9px; border-radius: 50%; display: inline-block;
        }
        .dot-Upcoming  { background: #f39c12; }
        .dot-Ongoing   { background: #27ae60; }
        .dot-Completed { background: #7f8c8d; }
    </style>
</head>
<body>

<?php include('../includes/sidebar.php'); ?>

<div class="main-content">

    <div class="topbar">
        <h4><i class="fas fa-user-injured"></i> Patients</h4>
        <div class="user-info">
            <div class="user-avatar"><?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?></div>
            <span><?= $_SESSION['user_name'] ?></span>
        </div>
    </div>

    <div class="page-content">

        <?= $message ?>


        <div class="card mb-4">
            <div class="card-header">
                <span><i class="fas fa-plus-circle"></i> Register New Patient</span>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="row">

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Patient Name *</label>
                            <input type="text" name="name" class="form-control" placeholder="Full name" required>
                        </div>

                        <div class="col-md-2 mb-3">
                            <label class="form-label">Age *</label>
                            <input type="number" name="age" class="form-control" placeholder="Age" min="0" max="120" required>
                        </div>

                        <div class="col-md-2 mb-3">
                            <label class="form-label">Gender *</label>
                            <select name="gender" class="form-select" required>
                                <option value="">Select</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="text" name="phone" class="form-control" placeholder="Mobile number">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Village / Area</label>
                            <input type="text" name="village" class="form-control" placeholder="Village name">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Disease / Complaint</label>
                            <input type="text" name="disease" class="form-control" placeholder="Main complaint">
                        </div>

                        <div class="col-md-2 mb-3">
                            <label class="form-label">Assign Doctor</label>
                            <select name="doctor_id" class="form-select">
                                <option value="">Not Assigned</option>
                                <?php while ($doc = mysqli_fetch_assoc($doctors)): ?>
                                    <option value="<?= $doc['id'] ?>"><?= htmlspecialchars($doc['name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">
                                <i class="fas fa-campground text-success me-1"></i>Assign to Camp
                            </label>
                            <select name="camp_id" class="form-select">
                                <option value="">— No Camp —</option>
                                <?php foreach ($camps_list as $camp): ?>
                                    <option value="<?= $camp['id'] ?>">
                                        <?= htmlspecialchars($camp['camp_name']) ?>
                                        (<?= date('d M', strtotime($camp['camp_date'])) ?>
                                        · <?= $camp['status'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-2 mb-3">
                            <label class="form-label">Visit Date *</label>
                            <input type="date" name="visit_date" class="form-control"
                                   value="<?= date('Y-m-d') ?>" required>
                        </div>

                    </div>

                    <button type="submit" name="add_patient" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Patient
                    </button>
                </form>
            </div>
        </div>


        <div class="card">
            <div class="card-header">
                <span><i class="fas fa-list"></i> All Patients</span>
                <form method="GET" action="" class="d-flex gap-2 flex-wrap align-items-center">
                    <input type="text" name="search" class="form-control form-control-sm"
                           placeholder="Search by name or village..." value="<?= htmlspecialchars($search) ?>" style="width:220px">
                    <button type="submit" class="btn btn-primary btn-sm">Search</button>
                    <?php if ($search): ?>
                        <a href="patients.php" class="btn btn-secondary btn-sm">Clear</a>
                    <?php endif; ?>
                    <a href="patients.php?search=<?= urlencode($search) ?>&export=csv" class="btn btn-success btn-sm">
                        <i class="fas fa-file-excel"></i> Export Excel
                    </a>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                        <i class="fas fa-file-pdf"></i> Print / PDF
                    </button>
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Age</th>
                                <th>Gender</th>
                                <th>Phone</th>
                                <th>Village</th>
                                <th>Disease</th>
                                <th>Doctor</th>
                                <th>Camps</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($patients) > 0): ?>
                                <?php $i = 1; while ($p = mysqli_fetch_assoc($patients)): ?>

                                <?php
                                $assigned = mysqli_query($conn,
                                    "SELECT pc.id as pc_id, c.camp_name, c.status FROM patient_camps pc
                                     JOIN camps c ON pc.camp_id = c.id
                                     WHERE pc.patient_id = '{$p['id']}'
                                     ORDER BY c.camp_date DESC"
                                );
                                ?>

                                <tr>
                                    <td><?= $i++ ?></td>
                                    <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
                                    <td><?= $p['age'] ?></td>
                                    <td>
                                        <span class="badge <?= $p['gender']=='Male' ? 'bg-primary' : ($p['gender']=='Female' ? 'bg-danger' : 'bg-secondary') ?>">
                                            <?= $p['gender'] ?>
                                        </span>
                                    </td>
                                    <td><?= $p['phone'] ? $p['phone'] : '-' ?></td>
                                    <td><?= htmlspecialchars($p['village']) ?></td>
                                    <td><?= htmlspecialchars($p['disease']) ?></td>
                                    <td>
                                        <?= $p['doctor_name']
                                            ? htmlspecialchars($p['doctor_name'])
                                            : '<span class="text-muted">Not Assigned</span>' ?>
                                    </td>

                                    <td style="min-width:160px">
                                        <?php if (mysqli_num_rows($assigned) > 0): ?>
                                            <?php while ($ac = mysqli_fetch_assoc($assigned)): ?>
                                                <span class="camp-badge">
                                                    <span class="status-dot dot-<?= $ac['status'] ?>"></span>
                                                    <?= htmlspecialchars($ac['camp_name']) ?>
                                                    <a href="?remove_camp=<?= $ac['pc_id'] ?>"
                                                       class="remove-camp"
                                                       title="Remove from this camp"
                                                       onclick="return confirm('Remove patient from this camp?')">
                                                        <i class="fas fa-times"></i>
                                                    </a>
                                                </span>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <span class="text-muted small">None</span>
                                        <?php endif; ?>

                                        <button class="btn btn-outline-info btn-sm mt-1"
                                                style="font-size:0.72rem; padding:2px 8px;"
                                                onclick="openAssignModal(<?= $p['id'] ?>, '<?= addslashes(htmlspecialchars($p['name'])) ?>')">
                                            <i class="fas fa-plus"></i> Camp
                                        </button>
                                    </td>

                                    <td><?= date('d M Y', strtotime($p['visit_date'])) ?></td>
                                    <td>
                                        <a href="../patient_report.php?id=<?= $p['id'] ?>"
                                           class="btn btn-info btn-sm me-1"
                                           title="Download Patient Report">
                                            <i class="fas fa-download"></i>
                                        </a>
                                        <a href="?delete=<?= $p['id'] ?>"
                                           class="btn btn-danger btn-sm"
                                           onclick="return confirm('Delete this patient?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="11" class="text-center text-muted py-4">
                                        <?= $search ? 'No patients found for "'.htmlspecialchars($search).'"' : 'No patients registered yet.' ?>
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


<div class="modal fade" id="assignCampModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-campground me-2"></i>
                    Assign <span id="modalPatientName">Patient</span> to a Camp
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="assign_camp" value="1">
                <input type="hidden" name="patient_id" id="modalPatientId">

                <div class="modal-body">
                    <?php if (count($camps_list) > 0): ?>
                        <p class="text-muted small mb-3">
                            <i class="fas fa-info-circle"></i>
                            Select a camp below. Cancelled camps are hidden. A patient can be in multiple camps.
                        </p>
                        <div id="campOptionsList">
                            <?php foreach ($camps_list as $camp): ?>
                                <label class="camp-option w-100">
                                    <input type="radio" name="camp_id" value="<?= $camp['id'] ?>" required>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold">
                                            <span class="status-dot dot-<?= $camp['status'] ?> me-1"></span>
                                            <?= htmlspecialchars($camp['camp_name']) ?>
                                            <span class="badge bg-<?= $camp['status']=='Upcoming' ? 'warning text-dark' : ($camp['status']=='Ongoing' ? 'success' : 'secondary') ?> ms-1" style="font-size:0.65rem">
                                                <?= $camp['status'] ?>
                                            </span>
                                        </div>
                                        <div class="camp-meta">
                                            <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($camp['location']) ?>
                                            &nbsp;&nbsp;
                                            <i class="fas fa-calendar-alt"></i> <?= date('d M Y', strtotime($camp['camp_date'])) ?>
                                            <?php if ($camp['village']): ?>
                                                &nbsp;&nbsp;<i class="fas fa-home"></i> <?= htmlspecialchars($camp['village']) ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-campground fa-2x mb-2 d-block"></i>
                            No active camps available.<br>
                            <small>Add camps from the Camps page first.</small>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <?php if (count($camps_list) > 0): ?>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-check"></i> Assign to Camp
                        </button>
                    <?php endif; ?>
                </div>
            </form>

        </div>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>

function openAssignModal(patientId, patientName) {
    document.getElementById('modalPatientId').value = patientId;
    document.getElementById('modalPatientName').textContent = patientName;

    document.querySelectorAll('#campOptionsList input[type=radio]').forEach(r => {
        r.checked = false;
        r.closest('.camp-option').classList.remove('selected');
    });

    new bootstrap.Modal(document.getElementById('assignCampModal')).show();
}

document.addEventListener('change', function(e) {
    if (e.target.name === 'camp_id') {
        document.querySelectorAll('.camp-option').forEach(el => el.classList.remove('selected'));
        e.target.closest('.camp-option').classList.add('selected');
    }
});
</script>
</body>
</html>
