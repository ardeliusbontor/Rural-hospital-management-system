<?php

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

include('../includes/db.php');

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_patient'])) {
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
            $message = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> New patient registered successfully! ID: <strong>#' . $new_id . '</strong></div>';
        } else {
            $message = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Error saving patient. Please try again.</div>';
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
    <title>Register Patient - Health Camp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
</head>
<body>

<?php include('../includes/sidebar.php'); ?>

<div class="main-content">
    <div class="topbar">
        <h4><i class="fas fa-user-plus"></i> Register Patient</h4>
        <div class="user-info">
            <div class="user-avatar"><?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?></div>
            <span><?= $_SESSION['user_name'] ?></span>
        </div>
    </div>

    <div class="page-content">
        <?= $message ?>

        <div class="card">
            <div class="card-header">
                <span><i class="fas fa-notes-medical"></i> Patient Registration Form</span>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Patient Name *</label>
                            <input type="text" name="name" class="form-control" placeholder="Full name" required>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">Age *</label>
                            <input type="number" name="age" class="form-control" min="0" max="120" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Gender *</label>
                            <select name="gender" class="form-select" required>
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control" placeholder="Mobile number">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Village / Area</label>
                            <input type="text" name="village" class="form-control" placeholder="Village or area">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Visit Date *</label>
                            <input type="date" name="visit_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Doctor</label>
                            <select name="doctor_id" class="form-select">
                                <option value="0">No doctor assigned</option>
                                <?php while ($doc = mysqli_fetch_assoc($doctors)): ?>
                                    <option value="<?= $doc['id'] ?>"><?= htmlspecialchars($doc['name']) ?><?= $doc['specialization'] ? ' (' . htmlspecialchars($doc['specialization']) . ')' : '' ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Disease / Complaint</label>
                            <input type="text" name="disease" class="form-control" placeholder="Describe the illness">
                        </div>
                    </div>

                    <button type="submit" name="add_patient" class="btn btn-primary px-4">
                        <i class="fas fa-save"></i> Register Patient
                    </button>
                    <a href="patients.php" class="btn btn-outline-secondary px-4 ms-2">
                        <i class="fas fa-list"></i> View Patients
                    </a>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
