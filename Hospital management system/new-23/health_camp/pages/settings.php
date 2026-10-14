<?php

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

include('../includes/db.php');

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS settings (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$defaults = [
    'site_title' => 'Ardeliusnet.inc',
    'site_tagline' => 'Hospital Management System',
    'low_stock_threshold' => '50',
    'login_welcome_text' => 'Welcome to Ardeliusnet.inc portal',
];

foreach ($defaults as $key => $value) {
    $escaped = mysqli_real_escape_string($conn, $value);
    mysqli_query($conn, "INSERT IGNORE INTO settings (setting_key, setting_value) VALUES ('$key', '$escaped')");
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $site_title = trim($_POST['site_title']);
    $site_tagline = trim($_POST['site_tagline']);
    $low_stock_threshold = intval($_POST['low_stock_threshold']);
    $login_welcome_text = trim($_POST['login_welcome_text']);

    $pairs = [
        'low_stock_threshold' => $low_stock_threshold,
        'login_welcome_text' => $login_welcome_text,
    ];

    foreach ($pairs as $key => $value) {
        $escaped = mysqli_real_escape_string($conn, (string)$value);
        mysqli_query($conn, "INSERT INTO settings (setting_key, setting_value) VALUES ('$key', '$escaped') ON DUPLICATE KEY UPDATE setting_value = '$escaped'");
    }

    $message = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Settings saved successfully.</div>';
}

$settings = [];
$result = mysqli_query($conn, "SELECT setting_key, setting_value FROM settings");
while ($row = mysqli_fetch_assoc($result)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$site_title = $settings['site_title'] ?? $defaults['site_title'];
$site_tagline = $settings['site_tagline'] ?? $defaults['site_tagline'];
$low_stock_threshold = $settings['low_stock_threshold'] ?? $defaults['low_stock_threshold'];
$login_welcome_text = $settings['login_welcome_text'] ?? $defaults['login_welcome_text'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - <?= htmlspecialchars($site_title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
</head>
<body>

<?php include('../includes/sidebar.php'); ?>

<div class="main-content">
    <div class="topbar">
        <h4><i class="fas fa-cogs"></i> System Settings</h4>
        <div class="user-info">
            <div class="user-avatar"><?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?></div>
            <span><?= $_SESSION['user_name'] ?></span>
        </div>
    </div>

    <div class="page-content">
        <?= $message ?>

        <div class="card">
            <div class="card-header">
                <span><i class="fas fa-sliders-h"></i> General Settings</span>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="alert alert-info mb-3">
                        <i class="fas fa-shield-alt"></i> The system name is fixed as <strong>Ardeliusnet.inc</strong> and is not editable from this page.
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Low Stock Threshold</label>
                            <input type="number" name="low_stock_threshold" class="form-control" min="1" value="<?= htmlspecialchars($low_stock_threshold) ?>" required>
                            <small class="text-muted">Alerts appear when medicine quantity is below this value.</small>
                        </div>
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Login Welcome Text</label>
                            <input type="text" name="login_welcome_text" class="form-control" value="<?= htmlspecialchars($login_welcome_text) ?>">
                            <small class="text-muted">Displayed on the login page to welcome users.</small>
                        </div>
                    </div>

                    <button type="submit" name="save_settings" class="btn btn-primary px-4">
                        <i class="fas fa-save"></i> Save Settings
                    </button>
                </form>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-body">
                <h5>How to use settings</h5>
                <ul>
                    <li>Change your system title and tagline for the whole app.</li>
                    <li>Adjust the low-stock threshold to fit your health camp inventory.</li>
                    <li>Update the login welcome text shown to users before sign-in.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
