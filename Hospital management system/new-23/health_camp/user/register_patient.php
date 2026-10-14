<?php

session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['doctor', 'staff'])) {
    header("Location: /health_camp/login.php");
    exit();
}

include(__DIR__ . '/../includes/db.php');

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_patient'])) {

    $name      = trim($_POST['name']);
    $age       = intval($_POST['age']);
    $gender    = $_POST['gender'];
    $phone     = trim($_POST['phone']);
    $village   = trim($_POST['village']);
    $disease   = trim($_POST['disease']);
    $doctor_id = intval($_POST['doctor_id']);
    $visit_date = $_POST['visit_date'];

    if (empty($name)) {
        $message = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Patient name is required!</div>';
    } else {
        $sql = "INSERT INTO patients (name, age, gender, phone, village, disease, doctor_id, visit_date)
                VALUES ('$name', '$age', '$gender', '$phone', '$village', '$disease', '$doctor_id', '$visit_date')";

        if (mysqli_query($conn, $sql)) {
            $new_id = mysqli_insert_id($conn);
            $message = '<div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                Patient <strong>' . htmlspecialchars($name) . '</strong> registered successfully!
                Patient ID: <strong>#' . $new_id . '</strong>
                <a href="view_patients.php" class="btn btn-sm btn-success ms-2">View All Patients</a>
            </div>';
        } else {
            $message = '<div class="alert alert-danger">Error saving. Please try again.</div>';
        }
    }
}

$doctors = mysqli_query($conn, "SELECT * FROM doctors ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Patient</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
</head>
<body>

<?php include('sidebar_user.php'); ?>

<div class="main-content">

    <div class="topbar">
        <h4><i class="fas fa-user-plus"></i> Register New Patient</h4>
        <div class="user-info">
            <div class="user-avatar"><?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?></div>
            <span><?= $_SESSION['user_name'] ?></span>
        </div>
    </div>

    <div class="page-content">

        <?= $message ?>

        <div class="card">
            <div class="card-header">
                <span><i class="fas fa-file-medical"></i> Patient Registration Form</span>
            </div>
            <div class="card-body">
                <form method="POST" action="">

                    <div class="row">
                        <div class="col-md-5 mb-3">
                            <label class="form-label">Patient Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control"
                                   placeholder="Enter patient full name" required>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">Age <span class="text-danger">*</span></label>
                            <input type="number" name="age" class="form-control"
                                   placeholder="Years" min="0" max="120" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Gender <span class="text-danger">*</span></label>
                            <select name="gender" class="form-select" required>
                                <option value="">-- Select Gender --</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">Visit Date <span class="text-danger">*</span></label>
                            <input type="date" name="visit_date" class="form-control"
                                   value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="text" name="phone" class="form-control"
                                   placeholder="Mobile number (optional)">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Village / Area</label>
                            <input type="text" name="village" class="form-control"
                                   placeholder="Village or area name">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Assign Doctor</label>
                            <select name="doctor_id" class="form-select">
                                <option value="0">-- Not Assigned Yet --</option>
                                <?php while ($doc = mysqli_fetch_assoc($doctors)): ?>
                                    <option value="<?= $doc['id'] ?>">
                                        <?= htmlspecialchars($doc['name']) ?>
                                        <?= $doc['specialization'] ? '(' . $doc['specialization'] . ')' : '' ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 mb-4">
                            <label class="form-label">Main Complaint / Disease</label>
                            <textarea name="disease" class="form-control" rows="2"
                                      placeholder="Describe the patient's main complaint or symptoms..."></textarea>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" name="add_patient" class="btn btn-primary px-4">
                            <i class="fas fa-save"></i> Register Patient
                        </button>
                        <button type="reset" class="btn btn-secondary px-4">
                            <i class="fas fa-undo"></i> Clear Form
                        </button>
                        <a href="view_patients.php" class="btn btn-outline-primary px-4">
                            <i class="fas fa-list"></i> View All Patients
                        </a>
                    </div>

                </form>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
