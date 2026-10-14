<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

include('includes/db.php');

$patient_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($patient_id <= 0) {
    die('Invalid patient ID.');
}

$patient = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT p.*, d.name as doctor_name, d.specialization as doctor_specialization
     FROM patients p
     LEFT JOIN doctors d ON p.doctor_id = d.id
     WHERE p.id = '$patient_id'"
));

if (!$patient) {
    die('Patient not found.');
}

if ($_SESSION['user_role'] === 'doctor') {
    $doctor = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT d.id FROM doctors d
         JOIN users u ON u.name = d.name
         WHERE u.id = '{$_SESSION['user_id']}'"
    ));

    if (!$doctor || intval($doctor['id']) !== intval($patient['doctor_id'])) {
        die('You do not have permission to download this patient report.');
    }
} elseif ($_SESSION['user_role'] !== 'admin') {
    die('You do not have permission to download patient reports.');
}

$camp_rows = mysqli_query($conn,
    "SELECT c.camp_name, c.camp_date, c.location, c.status
     FROM patient_camps pc
     JOIN camps c ON pc.camp_id = c.id
     WHERE pc.patient_id = '$patient_id'"
);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="patient_report_' . $patient_id . '.csv"');
$out = fopen('php://output', 'w');

fputcsv($out, ['Patient Report']);
fputcsv($out, ['Generated On', date('Y-m-d H:i:s')]);
fputcsv($out, []);
fputcsv($out, ['Patient ID', $patient['id']]);
fputcsv($out, ['Name', $patient['name']]);
fputcsv($out, ['Age', $patient['age']]);
fputcsv($out, ['Gender', $patient['gender']]);
fputcsv($out, ['Phone', $patient['phone'] ?: 'N/A']);
fputcsv($out, ['Village', $patient['village'] ?: 'N/A']);
fputcsv($out, ['Disease / Complaint', $patient['disease'] ?: 'N/A']);
fputcsv($out, ['Diagnosis', $patient['diagnosis'] ?: 'N/A']);
fputcsv($out, ['Prescription', $patient['prescription'] ?: 'N/A']);
fputcsv($out, ['Notes', $patient['notes'] ?: 'N/A']);
fputcsv($out, ['Doctor', $patient['doctor_name'] ?: 'Not Assigned']);
fputcsv($out, ['Doctor Specialization', $patient['doctor_specialization'] ?: 'N/A']);
fputcsv($out, ['Visit Date', $patient['visit_date']]);
fputcsv($out, []);
fputcsv($out, ['Camp Assignments']);
$fput_camp_row = false;
while ($camp = mysqli_fetch_assoc($camp_rows)) {
    if (!$fput_camp_row) {
        fputcsv($out, ['Camp Name', 'Date', 'Location', 'Status']);
        $fput_camp_row = true;
    }
    fputcsv($out, [
        $camp['camp_name'],
        $camp['camp_date'],
        $camp['location'],
        $camp['status'],
    ]);
}
if (!$fput_camp_row) {
    fputcsv($out, ['No camp assignments']);
}

fclose($out);
exit;
