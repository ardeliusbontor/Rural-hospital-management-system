<?php
$current_page = basename($_SERVER['PHP_SELF']);
$base = "/health_camp";
?>
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div><i class="fas fa-hospital-alt"></i></div>
        <h5 class="brand-title">Ardeliusnet.inc<br><span>Hospital Management System</span></h5>
    </div>
    <ul class="sidebar-menu">
        <li>
            <a href="<?= $base ?>/index.php" class="<?= ($current_page == 'index.php') ? 'active' : '' ?>">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
        </li>
        <li>
            <a href="<?= $base ?>/pages/camps.php" class="<?= ($current_page == 'camps.php') ? 'active' : '' ?>">
                <i class="fas fa-campground"></i> Manage Camps
            </a>
        </li>
        <li>
            <a href="<?= $base ?>/pages/doctors.php" class="<?= ($current_page == 'doctors.php') ? 'active' : '' ?>">
                <i class="fas fa-user-md"></i> Doctors
            </a>
        </li>
        <li>
            <a href="<?= $base ?>/pages/staff.php" class="<?= ($current_page == 'staff.php') ? 'active' : '' ?>">
                <i class="fas fa-users"></i> Doctor & Staff Records
            </a>
        </li>
        <li>
            <a href="<?= $base ?>/pages/medicines.php" class="<?= ($current_page == 'medicines.php') ? 'active' : '' ?>">
                <i class="fas fa-pills"></i> Medicines
            </a>
        </li>
        <li>
            <a href="<?= $base ?>/pages/register_patient.php" class="<?= ($current_page == 'register_patient.php') ? 'active' : '' ?>">
                <i class="fas fa-user-plus"></i> Register Patient
            </a>
        </li>
        <li>
            <a href="<?= $base ?>/pages/reports.php" class="<?= ($current_page == 'reports.php') ? 'active' : '' ?>">
                <i class="fas fa-chart-bar"></i> Reports
            </a>
        </li>
        <li>
            <a href="<?= $base ?>/pages/reset_tokens.php" class="<?= ($current_page == 'reset_tokens.php') ? 'active' : '' ?>">
                <i class="fas fa-key"></i> Reset Tokens
            </a>
        </li>
        <li>
            <a href="<?= $base ?>/pages/settings.php" class="<?= ($current_page == 'settings.php') ? 'active' : '' ?>">
                <i class="fas fa-cogs"></i> Settings
            </a>
        </li>
        <li style="margin-top:20px; border-top:1px solid rgba(255,255,255,0.1); padding-top:10px;">
            <a href="<?= $base ?>/logout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </li>
    </ul>
</div>
