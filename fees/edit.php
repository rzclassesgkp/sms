<?php
require_once '../includes/config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->beginTransaction();

        if (isset($_POST['payment_amount'])) {
            // Add new payment
            $payment_amount = floatval($_POST['payment_amount']);
            $payment_date = $_POST['payment_date'];
            $payment_method = $_POST['payment_method'];
            $transaction_id = $_POST['transaction_id'];
            $remarks = $_POST['remarks'];

            // Insert payment record
            $stmt = $conn->prepare("
                INSERT INTO fee_payments (fee_id, amount, payment_date, payment_method, transaction_id, remarks)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $fee_id,
                $payment_amount,
                $payment_date,
                $payment_method,
                $transaction_id,
                $remarks
            ]);

            // Update fee status
            $new_total_paid = $total_paid + $payment_amount;
            $new_status = $new_total_paid >= $fee['amount'] ? 'paid' : 
                         ($new_total_paid > 0 ? 'partial' : 'pending');

            $stmt = $conn->prepare("
                UPDATE fees 
                SET payment_status = ?
                WHERE id = ?
            ");
            $stmt->execute([$new_status, $fee_id]);

        } else {
            // Update fee details
            $stmt = $conn->prepare("
                UPDATE fees 
                SET due_date = ?, remarks = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $_POST['due_date'],
                $_POST['remarks'],
                $fee_id
            ]);
        }

        $conn->commit();
        header("Location: view.php?id=" . $fee_id);
        exit();
    } catch(PDOException $e) {
        $conn->rollBack();
        $error = "Error updating fee: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Fee - Student Management System</title>
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
                    <h2>Edit Fee</h2>
                    <div>
                        <a href="view.php?id=<?php echo $fee_id; ?>" class="btn btn-info me-2">
                            <i class="fas fa-eye"></i> View Details
                        </a>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="row">
                    <!-- Fee Information -->
                    <div class="col-md-4">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Fee Information</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label">Invoice Number</label>
                                        <input type="text" class="form-control" 
                                               value="<?php echo htmlspecialchars($fee['invoice_number']); ?>" readonly>
                                    </div>
                                    
                                    <div class="col-12">
                                        <label class="form-label">Student</label>
                                        <input type="text" class="form-control" 
                                               value="<?php echo htmlspecialchars($fee['student_name']); ?>" readonly>
                                    </div>
                                    
                                    <div class="col-12">
                                        <label class="form-label">Class</label>
                                        <input type="text" class="form-control" 
                                               value="<?php echo htmlspecialchars($fee['class_name']); ?>" readonly>
                                    </div>
                                    
                                    <div class="col-12">
                                        <label class="form-label">Fee Type</label>
                                        <input type="text" class="form-control" 
                                               value="<?php echo htmlspecialchars($fee['fee_type']); ?>" readonly>
                                    </div>
                                    
                                    <div class="col-12">
                                        <label class="form-label">Amount</label>
                                        <input type="text" class="form-control" 
                                               value="₹<?php echo number_format($fee['amount'], 2); ?>" readonly>
                                    </div>
                                    
                                    <div class="col-12">
                                        <label class="form-label">Due Date</label>
                                        <input type="date" name="due_date" class="form-control" required
                                               value="<?php echo date('Y-m-d', strtotime($fee['due_date'])); ?>">
                                    </div>
                                    
                                    <div class="col-12">
                                        <label class="form-label">Remarks</label>
                                        <textarea name="remarks" class="form-control" rows="3"
                                                  placeholder="Optional remarks"><?php echo htmlspecialchars($fee['remarks']); ?></textarea>
                                    </div>

                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="fas fa-save"></i> Update Fee
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Section -->
                    <div class="col-md-8">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Add Payment</h5>
                            </div>
                            <div class="card-body">
                                <div class="row mb-4">
                                    <div class="col-md-4">
                                        <div class="card bg-primary text-white">
                                            <div class="card-body">
                                                <h6>Total Amount</h6>
                                                <h4>₹<?php echo number_format($fee['amount'], 2); ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card bg-success text-white">
                                            <div class="card-body">
                                                <h6>Paid Amount</h6>
                                                <h4>₹<?php echo number_format($total_paid, 2); ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card bg-warning text-white">
                                            <div class="card-body">
                                                <h6>Remaining Amount</h6>
                                                <h4>₹<?php echo number_format($remaining_amount, 2); ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <form method="POST" class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Payment Amount</label>
                                        <input type="number" name="payment_amount" class="form-control" required
                                               min="0.01" max="<?php echo $remaining_amount; ?>" step="0.01">
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">Payment Date</label>
                                        <input type="date" name="payment_date" class="form-control" required
                                               value="<?php echo date('Y-m-d'); ?>">
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">Payment Method</label>
                                        <select name="payment_method" class="form-select" required>
                                            <option value="">Choose Method</option>
                                            <option value="cash">Cash</option>
                                            <option value="card">Card</option>
                                            <option value="upi">UPI</option>
                                            <option value="bank_transfer">Bank Transfer</option>
                                            <option value="cheque">Cheque</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">Transaction ID</label>
                                        <input type="text" name="transaction_id" class="form-control"
                                               placeholder="Optional">
                                    </div>
                                    
                                    <div class="col-12">
                                        <label class="form-label">Remarks</label>
                                        <textarea name="remarks" class="form-control" rows="2"
                                                  placeholder="Optional payment remarks"></textarea>
                                    </div>

                                    <div class="col-12">
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-money-bill"></i> Add Payment
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Payment History -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Payment History</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
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
