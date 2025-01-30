<?php
require_once '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Get fee ID from URL
$fee_id = isset($_GET['id']) ? $_GET['id'] : null;
if (!$fee_id) {
    header("Location: index.php");
    exit();
}

// Get fee record
$stmt = $conn->prepare("
    SELECT f.*, 
    s.student_id as student_code,
    CONCAT(s.first_name, ' ', s.last_name) as student_name,
    c.class_name
    FROM fees f
    JOIN students s ON f.student_id = s.id
    JOIN classes c ON s.class_id = c.id
    WHERE f.id = ?
");
$stmt->execute([$fee_id]);
$fee = $stmt->fetch();

if (!$fee) {
    header("Location: index.php");
    exit();
}

// Get payment history
$stmt = $conn->prepare("
    SELECT * FROM fee_payments 
    WHERE fee_id = ? 
    ORDER BY payment_date DESC
");
$stmt->execute([$fee_id]);
$payments = $stmt->fetchAll();

// Calculate total paid amount
$total_paid = array_sum(array_column($payments, 'amount'));
$remaining_amount = $fee['amount'] - $total_paid;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Fee - Student Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include '../includes/sidebar.php'; ?>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Fee Details</h2>
                    <div>
                        <?php if ($_SESSION['user_role'] === 'admin'): ?>
                            <a href="edit.php?id=<?php echo $fee_id; ?>" class="btn btn-warning me-2">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="receipt.php?id=<?php echo $fee_id; ?>" class="btn btn-success me-2">
                                <i class="fas fa-receipt"></i> Generate Receipt
                            </a>
                        <?php endif; ?>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>

                <div class="row">
                    <!-- Fee Information -->
                    <div class="col-md-4">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Fee Information</h5>
                            </div>
                            <div class="card-body">
                                <table class="table">
                                    <tr>
                                        <th>Invoice Number</th>
                                        <td><?php echo htmlspecialchars($fee['invoice_number']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Student ID</th>
                                        <td><?php echo htmlspecialchars($fee['student_code']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Student Name</th>
                                        <td><?php echo htmlspecialchars($fee['student_name']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Class</th>
                                        <td><?php echo htmlspecialchars($fee['class_name']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Fee Type</th>
                                        <td><?php echo htmlspecialchars($fee['fee_type']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Amount</th>
                                        <td>₹<?php echo number_format($fee['amount'], 2); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Due Date</th>
                                        <td><?php echo date('M d, Y', strtotime($fee['due_date'])); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Status</th>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $fee['payment_status'] === 'paid' ? 'success' : 
                                                    ($fee['payment_status'] === 'pending' ? 'warning' : 
                                                    ($fee['payment_status'] === 'partial' ? 'info' : 'danger')); 
                                            ?>">
                                                <?php echo ucfirst($fee['payment_status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php if ($fee['remarks']): ?>
                                        <tr>
                                            <th>Remarks</th>
                                            <td><?php echo htmlspecialchars($fee['remarks']); ?></td>
                                        </tr>
                                    <?php endif; ?>
                                </table>
                            </div>
                        </div>

                        <!-- Payment Summary -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Payment Summary</h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <div class="card bg-primary text-white">
                                            <div class="card-body">
                                                <h6>Total Amount</h6>
                                                <h4>₹<?php echo number_format($fee['amount'], 2); ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="card bg-success text-white">
                                            <div class="card-body">
                                                <h6>Paid Amount</h6>
                                                <h4>₹<?php echo number_format($total_paid, 2); ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="card bg-warning text-white">
                                            <div class="card-body">
                                                <h6>Remaining Amount</h6>
                                                <h4>₹<?php echo number_format($remaining_amount, 2); ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment History -->
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Payment History</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Payment Date</th>
                                                <th>Amount</th>
                                                <th>Method</th>
                                                <th>Transaction ID</th>
                                                <th>Remarks</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($payments)): ?>
                                                <tr>
                                                    <td colspan="5" class="text-center">No payments recorded yet</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($payments as $payment): ?>
                                                    <tr>
                                                        <td><?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></td>
                                                        <td>₹<?php echo number_format($payment['amount'], 2); ?></td>
                                                        <td><?php echo ucfirst($payment['payment_method']); ?></td>
                                                        <td><?php echo $payment['transaction_id'] ?: '-'; ?></td>
                                                        <td><?php echo htmlspecialchars($payment['remarks']) ?: '-'; ?></td>
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
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
