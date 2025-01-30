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
    JOIN classes c ON f.class_id = c.id
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

// Get school information (you should store this in a settings table)
$school_name = "Your School Name";
$school_address = "School Address Line 1\nCity, State - PIN";
$school_phone = "+91 1234567890";
$school_email = "info@yourschool.com";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Receipt - <?php echo $fee['invoice_number']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
        }
        .receipt {
            max-width: 800px;
            margin: 2rem auto;
            background: white;
            padding: 2rem;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .receipt-header {
            border-bottom: 2px solid #dee2e6;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
        }
        .school-logo {
            max-width: 100px;
            height: auto;
        }
        .receipt-title {
            font-size: 1.5rem;
            color: #0d6efd;
            margin: 1rem 0;
        }
        .receipt-body {
            margin-bottom: 2rem;
        }
        .payment-info {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 0.5rem;
            margin: 1rem 0;
        }
        .signature-line {
            border-top: 1px solid #dee2e6;
            margin-top: 4rem;
            padding-top: 0.5rem;
            text-align: right;
        }
        @media print {
            body {
                background: white;
            }
            .receipt {
                box-shadow: none;
                margin: 0;
                padding: 1rem;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Action Buttons -->
        <div class="d-flex justify-content-end mb-4 no-print">
            <button onclick="window.print()" class="btn btn-primary me-2">
                <i class="fas fa-print"></i> Print Receipt
            </button>
            <a href="view.php?id=<?php echo $fee_id; ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>

        <!-- Receipt -->
        <div class="receipt">
            <!-- Receipt Header -->
            <div class="receipt-header text-center">
                <img src="../assets/images/logo.png" alt="School Logo" class="school-logo mb-3">
                <h2><?php echo htmlspecialchars($school_name); ?></h2>
                <p class="mb-1"><?php echo nl2br(htmlspecialchars($school_address)); ?></p>
                <p class="mb-1">Phone: <?php echo htmlspecialchars($school_phone); ?></p>
                <p>Email: <?php echo htmlspecialchars($school_email); ?></p>
                <div class="receipt-title">FEE RECEIPT</div>
            </div>

            <!-- Receipt Body -->
            <div class="receipt-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h5>Student Information</h5>
                        <table class="table table-borderless">
                            <tr>
                                <th>Student ID:</th>
                                <td><?php echo htmlspecialchars($fee['student_code']); ?></td>
                            </tr>
                            <tr>
                                <th>Name:</th>
                                <td><?php echo htmlspecialchars($fee['student_name']); ?></td>
                            </tr>
                            <tr>
                                <th>Class:</th>
                                <td><?php echo htmlspecialchars($fee['class_name']); ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <h5>Receipt Details</h5>
                        <table class="table table-borderless">
                            <tr>
                                <th>Receipt No:</th>
                                <td><?php echo htmlspecialchars($fee['invoice_number']); ?></td>
                            </tr>
                            <tr>
                                <th>Date:</th>
                                <td><?php echo date('F d, Y'); ?></td>
                            </tr>
                            <tr>
                                <th>Due Date:</th>
                                <td><?php echo date('F d, Y', strtotime($fee['due_date'])); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Fee Details -->
                <div class="table-responsive mb-4">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Description</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><?php echo htmlspecialchars($fee['fee_type']); ?></td>
                                <td class="text-end">₹<?php echo number_format($fee['amount'], 2); ?></td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>Total Amount</th>
                                <td class="text-end">₹<?php echo number_format($fee['amount'], 2); ?></td>
                            </tr>
                            <tr>
                                <th>Amount Paid</th>
                                <td class="text-end">₹<?php echo number_format($total_paid, 2); ?></td>
                            </tr>
                            <tr>
                                <th>Balance Due</th>
                                <td class="text-end">₹<?php echo number_format($remaining_amount, 2); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Payment History -->
                <?php if (!empty($payments)): ?>
                    <div class="payment-info">
                        <h5>Payment History</h5>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Transaction ID</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payments as $payment): ?>
                                        <tr>
                                            <td><?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></td>
                                            <td>₹<?php echo number_format($payment['amount'], 2); ?></td>
                                            <td><?php echo ucfirst($payment['payment_method']); ?></td>
                                            <td><?php echo $payment['transaction_id'] ?: '-'; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Status -->
                <div class="alert alert-<?php 
                    echo $fee['payment_status'] === 'paid' ? 'success' : 
                        ($fee['payment_status'] === 'pending' ? 'warning' : 
                        ($fee['payment_status'] === 'partial' ? 'info' : 'danger')); 
                ?> text-center">
                    Payment Status: <?php echo ucfirst($fee['payment_status']); ?>
                </div>

                <!-- Signature -->
                <div class="signature-line">
                    <p>Authorized Signatory</p>
                </div>

                <!-- Footer Note -->
                <div class="mt-4 text-center text-muted">
                    <small>This is a computer-generated receipt and does not require a physical signature.</small>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
