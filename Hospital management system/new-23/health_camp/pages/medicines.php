<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include('../includes/db.php');

$message = "";

mysqli_query($conn, "ALTER TABLE medicines ADD COLUMN IF NOT EXISTS total_stock INT DEFAULT 0");

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_medicine'])) {
    $name        = trim($_POST['name']);
    $quantity    = intval($_POST['quantity']);
    $total_stock = intval($_POST['total_stock']);
    $unit        = trim($_POST['unit']);

    if (empty($name)) {
        $message = '<div class="alert alert-danger">Medicine name is required!</div>';
    } else {
        if ($total_stock == 0) $total_stock = $quantity;

        $sql = "INSERT INTO medicines (name, quantity, total_stock, unit) VALUES ('$name', '$quantity', '$total_stock', '$unit')";
        if (mysqli_query($conn, $sql)) {
            $message = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Medicine added to inventory!</div>';
        } else {
            $message = '<div class="alert alert-danger">Error adding medicine.</div>';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_qty'])) {
    $med_id  = intval($_POST['med_id']);
    $new_qty = intval($_POST['new_qty']);
    mysqli_query($conn, "UPDATE medicines SET quantity = '$new_qty' WHERE id = '$med_id'");
    $message = '<div class="alert alert-success"><i class="fas fa-sync"></i> Stock updated!</div>';
}

if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM medicines WHERE id = '$delete_id'");
    $message = '<div class="alert alert-warning"><i class="fas fa-trash"></i> Medicine removed.</div>';
}

mysqli_query($conn, "UPDATE medicines SET total_stock = quantity WHERE total_stock = 0 OR total_stock IS NULL");

$result = mysqli_query($conn, "SELECT * FROM medicines ORDER BY name ASC");

$medicines_data  = [];
$chart_labels    = [];
$chart_remaining = [];
$chart_used      = [];
$total_remaining = 0;
$total_used      = 0;
$low_stock_count = 0;

while ($m = mysqli_fetch_assoc($result)) {
    $used            = max(0, $m['total_stock'] - $m['quantity']);
    $total_remaining += $m['quantity'];
    $total_used      += $used;
    if ($m['quantity'] < 50) $low_stock_count++;

    $chart_labels[]    = $m['name'];
    $chart_remaining[] = $m['quantity'];
    $chart_used[]      = $used;
    $medicines_data[]  = $m;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medicines - Health Camp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <style>
        .chart-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.06);
            padding: 20px;
            margin-bottom: 25px;
        }
        .chart-title {
            font-size: 15px;
            font-weight: 600;
            color: #1a7a4a;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .stock-bar-wrap {
            background: #f0f0f0;
            border-radius: 20px;
            height: 16px;
            overflow: hidden;
            margin: 4px 0;
        }
        .stock-bar-green {
            height: 100%;
            border-radius: 20px;
            background: linear-gradient(90deg, #1a7a4a, #27ae60);
        }
    </style>
</head>
<body>

<?php include('../includes/sidebar.php'); ?>

<div class="main-content">
    <div class="topbar">
        <h4><i class="fas fa-pills"></i> Medicine Inventory</h4>
        <div class="user-info">
            <div class="user-avatar"><?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?></div>
            <span><?= $_SESSION['user_name'] ?></span>
        </div>
    </div>

    <div class="page-content">

        <?= $message ?>

        <div class="row mb-3">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="icon-box bg-green"><i class="fas fa-boxes"></i></div>
                    <div><h3><?= count($medicines_data) ?></h3><p>Medicine Types</p></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="icon-box bg-blue"><i class="fas fa-check-circle"></i></div>
                    <div><h3><?= $total_remaining ?></h3><p>Total Remaining</p></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="icon-box bg-orange"><i class="fas fa-minus-circle"></i></div>
                    <div><h3><?= $total_used ?></h3><p>Total Used</p></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="icon-box" style="background:#e74c3c;"><i class="fas fa-exclamation-triangle"></i></div>
                    <div><h3><?= $low_stock_count ?></h3><p>Low / Out of Stock</p></div>
                </div>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-12">
                <div class="alert alert-warning mb-0">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Stock alert:</strong> items with quantity below 50 are marked as low stock. Use the table below to restock quickly.
                </div>
            </div>
        </div>

        <div class="row mb-3">

            <div class="col-md-8">
                <div class="chart-card">
                    <div class="chart-title">
                        <i class="fas fa-chart-bar"></i> Remaining vs Used — Per Medicine
                    </div>
                    <?php if (empty($medicines_data)): ?>
                        <p class="text-muted text-center py-4">No medicines added yet. Add medicines to see the chart.</p>
                    <?php else: ?>
                        <canvas id="barChart" height="120"></canvas>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-md-4">
                <div class="chart-card text-center">
                    <div class="chart-title justify-content-center">
                        <i class="fas fa-chart-pie"></i> Overall Stock
                    </div>
                    <?php if (empty($medicines_data)): ?>
                        <p class="text-muted py-4">No data yet.</p>
                    <?php else: ?>
                        <canvas id="doughnutChart" height="200"></canvas>
                        <div class="mt-3 d-flex justify-content-center gap-3 flex-wrap">
                            <span style="font-size:13px; font-weight:600; color:#1a7a4a;">
                                🟢 Remaining: <?= $total_remaining ?>
                            </span>
                            <span style="font-size:13px; font-weight:600; color:#e74c3c;">
                                🔴 Used: <?= $total_used ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if (!empty($medicines_data)): ?>
        <div class="chart-card mb-4">
            <div class="chart-title">
                <i class="fas fa-tasks"></i> Stock Level Per Medicine
            </div>
            <?php foreach ($medicines_data as $m):
                $total   = max(1, $m['total_stock']);
                $used    = max(0, $total - $m['quantity']);
                $rem_pct = min(100, round(($m['quantity'] / $total) * 100));
            ?>
            <div class="mb-3">
                <div class="d-flex justify-content-between mb-1">
                    <span style="font-size:13px; font-weight:600;">
                        <i class="fas fa-capsules text-success me-1"></i>
                        <?= htmlspecialchars($m['name']) ?>
                    </span>
                    <span style="font-size:12px;">
                        <span style="color:#1a7a4a;font-weight:600;"><?= $m['quantity'] ?> left</span>
                        &nbsp;|&nbsp;
                        <span style="color:#e74c3c;font-weight:600;"><?= $used ?> used</span>
                        &nbsp;|&nbsp;
                        <span style="color:#888;">Total: <?= $total ?> <?= $m['unit'] ?></span>
                    </span>
                </div>
                <div class="stock-bar-wrap">
                    <div class="stock-bar-green" style="width:<?= $rem_pct ?>%;"></div>
                </div>
                <div class="d-flex justify-content-between">
                    <small style="color:#1a7a4a;"><?= $rem_pct ?>% remaining</small>
                    <small style="color:#e74c3c;"><?= 100 - $rem_pct ?>% used</small>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="row">

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <span><i class="fas fa-plus-circle"></i> Add Medicine</span>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label class="form-label">Medicine Name *</label>
                                <input type="text" name="name" class="form-control" placeholder="e.g. Paracetamol" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Total Stock Received</label>
                                <input type="number" name="total_stock" class="form-control" placeholder="e.g. 500" min="0" value="0">
                                <small class="text-muted">Original amount you received</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Current Quantity</label>
                                <input type="number" name="quantity" class="form-control" placeholder="e.g. 500" min="0" value="0">
                                <small class="text-muted">Available right now</small>
                            </div>
                            <div class="mb-4">
                                <label class="form-label">Unit</label>
                                <select name="unit" class="form-select">
                                    <option value="Tablets">Tablets</option>
                                    <option value="Capsules">Capsules</option>
                                    <option value="Bottles">Bottles</option>
                                    <option value="Packets">Packets</option>
                                    <option value="Vials">Vials</option>
                                    <option value="Strips">Strips</option>
                                    <option value="ml">ml</option>
                                </select>
                            </div>
                            <button type="submit" name="add_medicine" class="btn btn-primary w-100">
                                <i class="fas fa-save"></i> Add Medicine
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <span><i class="fas fa-boxes"></i> Medicine Stock Table</span>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Medicine</th>
                                    <th style="color:#1a7a4a;">Remaining</th>
                                    <th style="color:#e74c3c;">Used</th>
                                    <th>Unit</th>
                                    <th>Status</th>
                                    <th>Update</th>
                                    <th>Del</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($medicines_data)):
                                    $i = 1;
                                    foreach ($medicines_data as $med):
                                        $used_qty = max(0, $med['total_stock'] - $med['quantity']);
                                ?>
                                <tr>
                                    <td><?= $i++ ?></td>
                                    <td>
                                        <i class="fas fa-capsules text-success me-1"></i>
                                        <strong><?= htmlspecialchars($med['name']) ?></strong>
                                    </td>
                                    <td><strong style="color:#1a7a4a;"><?= $med['quantity'] ?></strong></td>
                                    <td><strong style="color:#e74c3c;"><?= $used_qty ?></strong></td>
                                    <td><?= $med['unit'] ?></td>
                                    <td>
                                        <?php if ($med['quantity'] == 0): ?>
                                            <span class="badge bg-danger">Out of Stock</span>
                                        <?php elseif ($med['quantity'] < 50): ?>
                                            <span class="badge bg-warning text-dark">Low Stock</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Available</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="POST" action="" class="d-flex gap-1">
                                            <input type="hidden" name="med_id" value="<?= $med['id'] ?>">
                                            <input type="number" name="new_qty" class="form-control form-control-sm"
                                                   value="<?= $med['quantity'] ?>" min="0" style="width:70px">
                                            <button type="submit" name="update_qty" class="btn btn-warning btn-sm">
                                                <i class="fas fa-sync"></i>
                                            </button>
                                        </form>
                                    </td>
                                    <td>
                                        <a href="?delete=<?= $med['id'] ?>"
                                           class="btn btn-danger btn-sm"
                                           onclick="return confirm('Remove this medicine?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; else: ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        No medicines in inventory yet.
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
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
<?php if (!empty($medicines_data)): ?>

var labels    = <?= json_encode($chart_labels) ?>;
var remaining = <?= json_encode($chart_remaining) ?>;
var used      = <?= json_encode($chart_used) ?>;

var barCtx = document.getElementById('barChart').getContext('2d');
new Chart(barCtx, {
    type: 'bar',
    data: {
        labels: labels,
        datasets: [
            {
                label: 'Remaining Stock',
                data: remaining,
                backgroundColor: 'rgba(26, 122, 74, 0.85)',
                borderColor: '#1a7a4a',
                borderWidth: 1,
                borderRadius: 5
            },
            {
                label: 'Used',
                data: used,
                backgroundColor: 'rgba(231, 76, 60, 0.85)',
                borderColor: '#e74c3c',
                borderWidth: 1,
                borderRadius: 5
            }
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'top' } },
        scales: { y: { beginAtZero: true } }
    }
});

var dCtx = document.getElementById('doughnutChart').getContext('2d');
new Chart(dCtx, {
    type: 'doughnut',
    data: {
        labels: ['Remaining', 'Used'],
        datasets: [{
            data: [<?= $total_remaining ?>, <?= $total_used ?>],
            backgroundColor: ['#1a7a4a', '#e74c3c'],
            borderWidth: 3,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        cutout: '65%',
        plugins: { legend: { display: false } }
    }
});

<?php endif; ?>
</script>

</body>
</html>