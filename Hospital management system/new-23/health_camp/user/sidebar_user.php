<?php
$current_page = basename($_SERVER['PHP_SELF']);
$base = "/health_camp";
?>

<div class="sidebar" id="sidebar">

    <div class="sidebar-header">
        <div><i class="fas fa-user-md"></i></div>
        <h5>Ardeliusnet.inc<br>
        <small style="font-size:11px; opacity:0.8;">
            <?= ucfirst($_SESSION['user_role']) ?> Portal
        </small>
        </h5>
    </div>

    <ul class="sidebar-menu">

        <li>
            <a href="<?= $base ?>/user/dashboard.php"
               class="<?= ($current_page == 'dashboard.php') ? 'active' : '' ?>">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
        </li>

        <li>
            <a href="<?= $base ?>/user/register_patient.php"
               class="<?= ($current_page == 'register_patient.php') ? 'active' : '' ?>">
                <i class="fas fa-user-plus"></i> Register Patient
            </a>
        </li>

        <li>
            <a href="<?= $base ?>/user/view_patients.php"
               class="<?= ($current_page == 'view_patients.php') ? 'active' : '' ?>">
                <i class="fas fa-list-alt"></i> View Patient Records
            </a>
        </li>

        <?php if ($_SESSION['user_role'] === 'doctor'): ?>
        <li>
            <a href="<?= $base ?>/user/doctor_reports.php"
               class="<?= ($current_page == 'doctor_reports.php') ? 'active' : '' ?>">
                <i class="fas fa-file-medical-alt"></i> Doctor Reports
            </a>
        </li>
        <?php endif; ?>

        <li>
            <a href="<?= $base ?>/user/enter_diagnosis.php"
               class="<?= ($current_page == 'enter_diagnosis.php') ? 'active' : '' ?>">
                <i class="fas fa-stethoscope"></i> Enter Diagnosis
            </a>
        </li>

        <li>
            <a href="<?= $base ?>/user/change_password.php"
               class="<?= ($current_page == 'change_password.php') ? 'active' : '' ?>">
                <i class="fas fa-key"></i> Change Password
            </a>
        </li>

        <li style="margin-top:20px; border-top:1px solid rgba(255,255,255,0.1); padding-top:10px;">
            <a href="<?= $base ?>/logout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </li>

    </ul>
</div>
