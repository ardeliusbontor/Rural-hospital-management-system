<?php

session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['doctor', 'staff'])) {
    header("Location: /health_camp/login.php");
    exit();
}

include(__DIR__ . '/../includes/db.php');

$message = "";
$selected_patient = null;

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS patient_prescriptions (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    patient_id    INT NOT NULL,
    medicine_id   INT DEFAULT NULL,
    medicine_name VARCHAR(200) NOT NULL,
    dosage        VARCHAR(100),
    frequency     VARCHAR(100),
    duration      VARCHAR(100),
    qty_given     INT DEFAULT 0,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
)");

mysqli_query($conn, "ALTER TABLE patients ADD COLUMN IF NOT EXISTS diagnosis TEXT");
mysqli_query($conn, "ALTER TABLE patients ADD COLUMN IF NOT EXISTS prescription TEXT");
mysqli_query($conn, "ALTER TABLE patients ADD COLUMN IF NOT EXISTS notes TEXT");

$preselect_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : 0;


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_diagnosis'])) {

    $patient_id   = intval($_POST['patient_id']);
    $diagnosis    = mysqli_real_escape_string($conn, trim($_POST['diagnosis']));
    $prescription = mysqli_real_escape_string($conn, trim($_POST['prescription']));
    $notes        = mysqli_real_escape_string($conn, trim($_POST['notes']));
    $doctor_id    = intval($_POST['doctor_id']);

    if (empty($diagnosis)) {
        $message = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Diagnosis cannot be empty!</div>';
    } else {

        mysqli_query($conn, "UPDATE patients SET
                                diagnosis    = '$diagnosis',
                                prescription = '$prescription',
                                notes        = '$notes',
                                doctor_id    = '$doctor_id'
                             WHERE id = '$patient_id'");

        $old_meds = mysqli_query($conn,
            "SELECT medicine_id, qty_given FROM patient_prescriptions
             WHERE patient_id='$patient_id' AND medicine_id IS NOT NULL AND medicine_id > 0");
        while ($om = mysqli_fetch_assoc($old_meds)) {
            mysqli_query($conn,
                "UPDATE medicines SET quantity = quantity + {$om['qty_given']}
                 WHERE id = '{$om['medicine_id']}'");
        }

        mysqli_query($conn, "DELETE FROM patient_prescriptions WHERE patient_id = '$patient_id'");

        $med_errors  = [];
        $med_success = 0;

        $med_names  = $_POST['med_name']  ?? [];
        $med_ids    = $_POST['med_id']    ?? [];
        $dosages    = $_POST['dosage']    ?? [];
        $freqs      = $_POST['frequency'] ?? [];
        $durs       = $_POST['duration']  ?? [];
        $qty_givens = $_POST['qty_given'] ?? [];

        foreach ($med_names as $idx => $raw_name) {
            $med_name  = trim($raw_name);
            if ($med_name === '') continue;

            $med_id    = intval($med_ids[$idx]    ?? 0);
            $dosage    = mysqli_real_escape_string($conn, trim($dosages[$idx]    ?? ''));
            $freq      = mysqli_real_escape_string($conn, trim($freqs[$idx]      ?? ''));
            $dur       = mysqli_real_escape_string($conn, trim($durs[$idx]       ?? ''));
            $qty_given = intval($qty_givens[$idx] ?? 0);
            $med_name_esc = mysqli_real_escape_string($conn, $med_name);

            if ($med_id > 0 && $qty_given > 0) {
                $stock_row = mysqli_fetch_assoc(
                    mysqli_query($conn, "SELECT quantity FROM medicines WHERE id='$med_id'")
                );
                $available = intval($stock_row['quantity'] ?? 0);
                if ($qty_given > $available) {
                    $med_errors[] = "\"$med_name\" — only $available in stock, requested $qty_given.";
                    $qty_given = $available; 
                }
                if ($qty_given > 0) {
                    mysqli_query($conn,
                        "UPDATE medicines SET quantity = quantity - $qty_given WHERE id='$med_id'");
                }
            }

            mysqli_query($conn,
                "INSERT INTO patient_prescriptions
                    (patient_id, medicine_id, medicine_name, dosage, frequency, duration, qty_given)
                 VALUES
                    ('$patient_id',
                     " . ($med_id > 0 ? "'$med_id'" : "NULL") . ",
                     '$med_name_esc', '$dosage', '$freq', '$dur', '$qty_given')");
            $med_success++;
        }

        if (!empty($med_errors)) {
            $warn = implode('<br>', $med_errors);
            $message = '<div class="alert alert-warning">
                <i class="fas fa-check-circle"></i> Diagnosis saved. <strong>' . $med_success . '</strong> medicine(s) prescribed.<br>
                <small><strong>Stock warnings:</strong><br>' . $warn . '</small>
            </div>';
        } else {
            $message = '<div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                Diagnosis saved for Patient <strong>#' . $patient_id . '</strong>.
                ' . ($med_success > 0 ? '<strong>' . $med_success . '</strong> medicine(s) dispensed &amp; stock updated.' : '') . '
                <a href="view_patients.php" class="btn btn-sm btn-success ms-2">View All Records</a>
            </div>';
        }

        $preselect_id = $patient_id; 
    }
}

if ($preselect_id > 0) {
    $selected_patient = mysqli_fetch_assoc(
        mysqli_query($conn, "SELECT p.*, d.name as doctor_name FROM patients p
                             LEFT JOIN doctors d ON p.doctor_id = d.id
                             WHERE p.id = '$preselect_id'")
    );
}

$existing_prescriptions = [];
if ($preselect_id > 0) {
    $ep = mysqli_query($conn,
        "SELECT * FROM patient_prescriptions WHERE patient_id='$preselect_id' ORDER BY id ASC");
    while ($row = mysqli_fetch_assoc($ep)) {
        $existing_prescriptions[] = $row;
    }
}

$all_patients = mysqli_query($conn, "SELECT * FROM patients ORDER BY visit_date DESC, name ASC");

$doctors = mysqli_query($conn, "SELECT * FROM doctors ORDER BY name");

$medicines_raw = mysqli_query($conn, "SELECT id, name, quantity, unit FROM medicines ORDER BY name");
$medicines_js  = [];
while ($m = mysqli_fetch_assoc($medicines_raw)) {
    $medicines_js[] = $m;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enter Diagnosis</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <style>
        .med-row {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 12px 14px;
            margin-bottom: 10px;
            position: relative;
            transition: border-color .2s;
        }
        .med-row:hover { border-color: #adb5bd; }
        .med-row .remove-med {
            position: absolute; top: 10px; right: 12px;
            background: none; border: none;
            color: #dc3545; font-size: 1rem; cursor: pointer;
        }
        .autocomplete-list {
            position: absolute; z-index: 9999;
            background: #fff; border: 1px solid #ced4da;
            border-radius: 6px; width: 100%;
            max-height: 200px; overflow-y: auto;
            box-shadow: 0 4px 12px rgba(0,0,0,.1);
        }
        .autocomplete-list .ac-item {
            padding: 7px 12px; cursor: pointer; font-size: .88rem;
            display: flex; justify-content: space-between; align-items: center;
        }
        .autocomplete-list .ac-item:hover { background: #e8f4fd; }
        .ac-item .stock-info { font-size: .75rem; color: #6c757d; }
        .stock-badge {
            font-size: .72rem; padding: 2px 8px; border-radius: 20px;
        }
        .stock-ok   { background: #d1f2eb; color: #1a7a5e; }
        .stock-low  { background: #fef9e7; color: #b7770d; }
        .stock-out  { background: #fadbd8; color: #a93226; }
        .patient-row-active { background: #eafaf1 !important; }
    </style>
</head>
<body>

<?php include('sidebar_user.php'); ?>

<div class="main-content">

    <div class="topbar">
        <h4><i class="fas fa-stethoscope"></i> Enter Diagnosis</h4>
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
                        <span><i class="fas fa-search"></i> Select Patient</span>
                    </div>
                    <div class="card-body">
                        <input type="text" id="patientSearch" class="form-control mb-3"
                               placeholder="Type name to filter...">
                        <div style="max-height:450px; overflow-y:auto;">
                            <table class="table table-sm table-hover mb-0" id="patientTable">
                                <thead>
                                    <tr><th>ID</th><th>Name</th><th>Date</th></tr>
                                </thead>
                                <tbody>
                                    <?php while ($p = mysqli_fetch_assoc($all_patients)): ?>
                                    <tr class="<?= ($preselect_id == $p['id']) ? 'patient-row-active' : '' ?>"
                                        style="cursor:pointer;"
                                        onclick="window.location='enter_diagnosis.php?patient_id=<?= $p['id'] ?>'">
                                        <td><small>#<?= $p['id'] ?></small></td>
                                        <td>
                                            <strong><?= htmlspecialchars($p['name']) ?></strong><br>
                                            <small class="text-muted"><?= htmlspecialchars($p['village']) ?></small>
                                        </td>
                                        <td><small><?= date('d M', strtotime($p['visit_date'])) ?></small></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <?php if ($selected_patient): ?>

                <div class="card mb-3" style="border-left:4px solid var(--primary);">
                    <div class="card-body py-3">
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="mb-1" style="color:var(--primary);">
                                    <?= htmlspecialchars($selected_patient['name']) ?>
                                    <span class="badge <?= $selected_patient['gender']=='Male'?'bg-primary':'bg-danger' ?> ms-1">
                                        <?= $selected_patient['gender'] ?>
                                    </span>
                                </h5>
                                <p class="mb-0 text-muted">
                                    Age: <strong><?= $selected_patient['age'] ?></strong> &nbsp;|&nbsp;
                                    Village: <strong><?= htmlspecialchars($selected_patient['village'] ?: 'N/A') ?></strong>
                                </p>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <p class="mb-0 text-muted">Phone: <strong><?= $selected_patient['phone'] ?: 'N/A' ?></strong></p>
                                <p class="mb-0 text-muted">Visit: <strong><?= date('d M Y', strtotime($selected_patient['visit_date'])) ?></strong></p>
                            </div>
                        </div>
                        <?php if ($selected_patient['disease']): ?>
                        <div class="mt-2 p-2" style="background:#fff3cd; border-radius:6px;">
                            <small><strong>Complaint:</strong> <?= htmlspecialchars($selected_patient['disease']) ?></small>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <span><i class="fas fa-file-medical-alt"></i> Diagnosis Form — Patient #<?= $selected_patient['id'] ?></span>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" id="diagnosisForm">
                            <input type="hidden" name="patient_id" value="<?= $selected_patient['id'] ?>">

                            <div class="mb-3">
                                <label class="form-label">Assign / Confirm Doctor</label>
                                <select name="doctor_id" class="form-select">
                                    <option value="0">-- Not Assigned --</option>
                                    <?php mysqli_data_seek($doctors, 0); while ($doc = mysqli_fetch_assoc($doctors)): ?>
                                        <option value="<?= $doc['id'] ?>"
                                            <?= ($selected_patient['doctor_id'] == $doc['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($doc['name']) ?>
                                            <?= $doc['specialization'] ? '('.$doc['specialization'].')' : '' ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Diagnosis <span class="text-danger">*</span></label>
                                <textarea name="diagnosis" class="form-control" rows="3"
                                          placeholder="Write the diagnosis here..." required
                                ><?= htmlspecialchars($selected_patient['diagnosis'] ?? '') ?></textarea>
                            </div>

                         
                            <div class="mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-pills text-success me-1"></i>
                                    Medicines / Prescription
                                </label>

                                <div id="medicineRows">
                                    <?php if (!empty($existing_prescriptions)): ?>
                                        <?php foreach ($existing_prescriptions as $ep): ?>
                                        <div class="med-row">
                                            <button type="button" class="remove-med" onclick="removeMedRow(this)" title="Remove">
                                                <i class="fas fa-times-circle"></i>
                                            </button>
                                            <input type="hidden" name="med_id[]"
                                                   value="<?= $ep['medicine_id'] ?? 0 ?>">
                                            <div class="row g-2">
                                                <div class="col-md-4" style="position:relative;">
                                                    <label class="form-label form-label-sm">Medicine</label>
                                                    <input type="text" name="med_name[]"
                                                           class="form-control form-control-sm med-name-input"
                                                           value="<?= htmlspecialchars($ep['medicine_name']) ?>"
                                                           placeholder="Search or type...">
                                                    <div class="autocomplete-list" style="display:none;"></div>
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label form-label-sm">Dosage</label>
                                                    <input type="text" name="dosage[]"
                                                           class="form-control form-control-sm"
                                                           value="<?= htmlspecialchars($ep['dosage']) ?>"
                                                           placeholder="e.g. 500mg">
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label form-label-sm">Frequency</label>
                                                    <select name="frequency[]" class="form-select form-select-sm">
                                                        <option value="">--</option>
                                                        <?php
                                                        $freq_opts = ['Once daily','Twice daily','3x daily','4x daily','As needed','Before meals','After meals'];
                                                        foreach ($freq_opts as $fo):
                                                        ?>
                                                        <option value="<?= $fo ?>" <?= ($ep['frequency']==$fo)?'selected':'' ?>><?= $fo ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label form-label-sm">Duration</label>
                                                    <input type="text" name="duration[]"
                                                           class="form-control form-control-sm"
                                                           value="<?= htmlspecialchars($ep['duration']) ?>"
                                                           placeholder="e.g. 5 days">
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label form-label-sm">Qty Given</label>
                                                    <input type="number" name="qty_given[]"
                                                           class="form-control form-control-sm"
                                                           value="<?= $ep['qty_given'] ?>"
                                                           min="0" placeholder="0">
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>

                                <button type="button" class="btn btn-outline-success btn-sm mt-1" onclick="addMedRow()">
                                    <i class="fas fa-plus"></i> Add Medicine
                                </button>
                                <small class="text-muted ms-2">
                                    Stock will be deducted automatically on save.
                                </small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Prescription Notes</label>
                                <textarea name="prescription" class="form-control" rows="2"
                                          placeholder="Any additional prescription instructions..."
                                ><?= htmlspecialchars($selected_patient['prescription'] ?? '') ?></textarea>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Additional Notes</label>
                                <textarea name="notes" class="form-control" rows="2"
                                          placeholder="Follow-up, referrals, special notes..."
                                ><?= htmlspecialchars($selected_patient['notes'] ?? '') ?></textarea>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" name="save_diagnosis" class="btn btn-primary px-4">
                                    <i class="fas fa-save"></i> Save Diagnosis
                                </button>
                                <a href="view_patients.php" class="btn btn-secondary px-4">
                                    <i class="fas fa-arrow-left"></i> Back to List
                                </a>
                            </div>

                        </form>
                    </div>
                </div>

                <?php else: ?>
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-hand-point-left fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Select a patient from the left</h5>
                        <p class="text-muted">Click any patient name to open their diagnosis form.</p>
                        <a href="view_patients.php" class="btn btn-primary mt-2">
                            <i class="fas fa-list"></i> View All Patients
                        </a>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </div>

    </div>
</div>

<script>
const MEDICINES = <?= json_encode($medicines_js) ?>;

const FREQ_OPTS = ['Once daily','Twice daily','3x daily','4x daily','As needed','Before meals','After meals'];

function addMedRow(data = {}) {
    const container = document.getElementById('medicineRows');
    const div = document.createElement('div');
    div.className = 'med-row';

    const freqOptions = FREQ_OPTS.map(f =>
        `<option value="${f}" ${data.frequency===f?'selected':''}>${f}</option>`
    ).join('');

    div.innerHTML = `
        <button type="button" class="remove-med" onclick="removeMedRow(this)" title="Remove">
            <i class="fas fa-times-circle"></i>
        </button>
        <input type="hidden" name="med_id[]" value="${data.med_id || 0}">
        <div class="row g-2">
            <div class="col-md-4" style="position:relative;">
                <label class="form-label form-label-sm">Medicine</label>
                <input type="text" name="med_name[]"
                       class="form-control form-control-sm med-name-input"
                       value="${data.med_name || ''}"
                       placeholder="Search or type...">
                <div class="autocomplete-list" style="display:none;"></div>
            </div>
            <div class="col-md-2">
                <label class="form-label form-label-sm">Dosage</label>
                <input type="text" name="dosage[]" class="form-control form-control-sm"
                       value="${data.dosage || ''}" placeholder="e.g. 500mg">
            </div>
            <div class="col-md-2">
                <label class="form-label form-label-sm">Frequency</label>
                <select name="frequency[]" class="form-select form-select-sm">
                    <option value="">--</option>${freqOptions}
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label form-label-sm">Duration</label>
                <input type="text" name="duration[]" class="form-control form-control-sm"
                       value="${data.duration || ''}" placeholder="e.g. 5 days">
            </div>
            <div class="col-md-2">
                <label class="form-label form-label-sm">Qty Given</label>
                <input type="number" name="qty_given[]" class="form-control form-control-sm"
                       value="${data.qty_given || 0}" min="0" placeholder="0">
            </div>
        </div>`;

    container.appendChild(div);
    attachAutocomplete(div.querySelector('.med-name-input'));
}

function removeMedRow(btn) {
    btn.closest('.med-row').remove();
}

function attachAutocomplete(input) {
    const dropdown = input.nextElementSibling; 
    const hiddenId  = input.closest('.med-row').querySelector('input[name="med_id[]"]');

    input.addEventListener('input', function () {
        const q = this.value.toLowerCase().trim();
        dropdown.innerHTML = '';
        hiddenId.value = 0; 

        if (q.length < 1) { dropdown.style.display = 'none'; return; }

        const matches = MEDICINES.filter(m => m.name.toLowerCase().includes(q));
        if (matches.length === 0) { dropdown.style.display = 'none'; return; }

        matches.forEach(m => {
            const item = document.createElement('div');
            item.className = 'ac-item';
            const qty   = parseInt(m.quantity);
            const cls   = qty <= 0 ? 'stock-out' : qty < 10 ? 'stock-low' : 'stock-ok';
            const label = qty <= 0 ? 'Out of stock' : `${qty} ${m.unit || 'units'}`;
            item.innerHTML = `
                <span>${m.name}</span>
                <span class="stock-badge ${cls}">${label}</span>`;
            item.addEventListener('mousedown', function (e) {
                e.preventDefault();
                input.value    = m.name;
                hiddenId.value = m.id;
                dropdown.style.display = 'none';
            });
            dropdown.appendChild(item);
        });
        dropdown.style.display = 'block';
    });

    input.addEventListener('blur', () => {
        setTimeout(() => { dropdown.style.display = 'none'; }, 150);
    });
    input.addEventListener('focus', function () {
        if (this.value.length > 0) this.dispatchEvent(new Event('input'));
    });
}

document.querySelectorAll('.med-name-input').forEach(attachAutocomplete);
</script>

<script>
document.getElementById('patientSearch').addEventListener('keyup', function () {
    const filter = this.value.toLowerCase();
    document.querySelectorAll('#patientTable tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(filter) ? '' : 'none';
    });
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>