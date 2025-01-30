<?php
require_once '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Get filter parameters
$class_id = isset($_GET['class_id']) ? $_GET['class_id'] : '';
$payment_status = isset($_GET['payment_status']) ? $_GET['payment_status'] : '';
$month = isset($_GET['month']) ? $_GET['month'] : date('m');
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');

// Get all classes for filter
$stmt = $conn->query("SELECT id, class_name FROM classes ORDER BY class_name");
$classes = $stmt->fetchAll();

// Build fees query
$query = "
    SELECT f.*, 
    s.student_id as student_code,
    CONCAT(s.first_name, ' ', s.last_name) as student_name,
    c.class_name
    FROM fees f
    JOIN students s ON f.student_id = s.id
    JOIN classes c ON s.class_id = c.id
    WHERE 1=1
";

$params = [];

if ($class_id) {
    $query .= " AND s.class_id = ?";
    $params[] = $class_id;
}

if ($payment_status) {
    $query .= " AND f.payment_status = ?";
    $params[] = $payment_status;
}

if ($month && $year) {
    $query .= " AND MONTH(f.due_date) = ? AND YEAR(f.due_date) = ?";
    $params[] = $month;
    $params[] = $year;
}

$query .= " ORDER BY f.due_date DESC, c.class_name, s.first_name, s.last_name";

// Get fees records
$stmt = $conn->prepare($query);
$stmt->execute($params);
$fees = $stmt->fetchAll();

// Calculate statistics
$total_fees = array_sum(array_column($fees, 'amount'));
$paid_fees = array_sum(array_map(function($fee) {
    return $fee['payment_status'] === 'paid' ? $fee['amount'] : 0;
}, $fees));
$pending_fees = array_sum(array_map(function($fee) {
    return $fee['payment_status'] === 'pending' ? $fee['amount'] : 0;
}, $fees));
$overdue_fees = array_sum(array_map(function($fee) {
    return $fee['payment_status'] === 'overdue' ? $fee['amount'] : 0;
}, $fees));

// Get payment status distribution
$status_distribution = [
    'paid' => 0,
    'pending' => 0,
    'overdue' => 0,
    'partial' => 0
];
foreach ($fees as $fee) {
    $status_distribution[$fee['payment_status']]++;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fees Management - Student Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include '../includes/sidebar.php'; ?>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Fees Management</h2>
                    <?php if ($_SESSION['user_role'] === 'admin'): ?>
                    <div>
                        <a href="add.php" class="btn btn-primary me-2">
                            <i class="fas fa-plus"></i> Generate Fee
                        </a>
                        <a href="fee_types.php" class="btn btn-info">
                            <i class="fas fa-cog"></i> Fee Types
                        </a>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Fees</h5>
                                <h3>₹<?php echo number_format($total_fees, 2); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">Paid Fees</h5>
                                <h3>₹<?php echo number_format($paid_fees, 2); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h5 class="card-title">Pending Fees</h5>
                                <h3>₹<?php echo number_format($pending_fees, 2); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-danger text-white">
                            <div class="card-body">
                                <h5 class="card-title">Overdue Fees</h5>
                                <h3>₹<?php echo number_format($overdue_fees, 2); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Status Chart -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <canvas id="paymentStatusChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Class</label>
                                <select name="class_id" class="form-select">
                                    <option value="">All Classes</option>
                                    <?php foreach ($classes as $class): ?>
                                        <option value="<?php echo $class['id']; ?>"
                                                <?php echo $class_id == $class['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($class['class_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label">Payment Status</label>
                                <select name="payment_status" class="form-select">
                                    <option value="">All Status</option>
                                    <option value="paid" <?php echo $payment_status === 'paid' ? 'selected' : ''; ?>>Paid</option>
                                    <option value="pending" <?php echo $payment_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="overdue" <?php echo $payment_status === 'overdue' ? 'selected' : ''; ?>>Overdue</option>
                                    <option value="partial" <?php echo $payment_status === 'partial' ? 'selected' : ''; ?>>Partial</option>
                                </select>
                            </div>
                            
                            <div class="col-md-2">
                                <label class="form-label">Month</label>
                                <select name="month" class="form-select">
                                    <?php for ($i = 1; $i <= 12; $i++): ?>
                                        <option value="<?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?>"
                                                <?php echo $month == str_pad($i, 2, '0', STR_PAD_LEFT) ? 'selected' : ''; ?>>
                                            <?php echo date('F', mktime(0, 0, 0, $i, 1)); ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-2">
                                <label class="form-label">Year</label>
                                <select name="year" class="form-select">
                                    <?php for ($i = date('Y') - 2; $i <= date('Y') + 2; $i++): ?>
                                        <option value="<?php echo $i; ?>"
                                                <?php echo $year == $i ? 'selected' : ''; ?>>
                                            <?php echo $i; ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search"></i> Filter
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Fees Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Invoice #</th>
                                        <th>Student ID</th>
                                        <th>Student Name</th>
                                        <th>Class</th>
                                        <th>Fee Type</th>
                                        <th>Amount</th>
                                        <th>Due Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($fees)): ?>
                                        <tr>
                                            <td colspan="9" class="text-center">No fees records found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($fees as $fee): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($fee['invoice_number']); ?></td>
                                                <td><?php echo htmlspecialchars($fee['student_code']); ?></td>
                                                <td><?php echo htmlspecialchars($fee['student_name']); ?></td>
                                                <td><?php echo htmlspecialchars($fee['class_name']); ?></td>
                                                <td><?php echo htmlspecialchars($fee['fee_type']); ?></td>
                                                <td>₹<?php echo number_format($fee['amount'], 2); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($fee['due_date'])); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $fee['payment_status'] === 'paid' ? 'success' : 
                                                            ($fee['payment_status'] === 'pending' ? 'warning' : 
                                                            ($fee['payment_status'] === 'partial' ? 'info' : 'danger')); 
                                                    ?>">
                                                        <?php echo ucfirst($fee['payment_status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="view.php?id=<?php echo $fee['id']; ?>" class="btn btn-sm btn-info">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <?php if ($_SESSION['user_role'] === 'admin'): ?>
                                                    <a href="edit.php?id=<?php echo $fee['id']; ?>" class="btn btn-sm btn-warning">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="receipt.php?id=<?php echo $fee['id']; ?>" class="btn btn-sm btn-success">
                                                        <i class="fas fa-receipt"></i>
                                                    </a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Payment Status Chart
        var ctx = document.getElementById('paymentStatusChart').getContext('2d');
        var statusChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Paid', 'Pending', 'Overdue', 'Partial'],
                datasets: [{
                    label: 'Number of Fees',
                    data: [
                        <?php echo $status_distribution['paid']; ?>,
                        <?php echo $status_distribution['pending']; ?>,
                        <?php echo $status_distribution['overdue']; ?>,
                        <?php echo $status_distribution['partial']; ?>
                    ],
                    backgroundColor: [
                        '#28a745',  // Success
                        '#ffc107',  // Warning
                        '#dc3545',  // Danger
                        '#17a2b8'   // Info
                    ]
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'Payment Status Distribution'
                    }
                }
            }
        });
    </script>
</body>
</html>
