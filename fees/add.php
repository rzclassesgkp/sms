<?php
require_once '../includes/config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Get all classes
$stmt = $conn->query("SELECT id, class_name FROM classes ORDER BY class_name");
$classes = $stmt->fetchAll();

// Get all fee types
$stmt = $conn->query("SELECT id, type_name, amount FROM fee_types ORDER BY type_name");
$fee_types = $stmt->fetchAll();

// Get students for selected class
$students = [];
if (isset($_GET['class_id'])) {
    $stmt = $conn->prepare("
        SELECT id, student_id as student_code, CONCAT(first_name, ' ', last_name) as student_name
        FROM students 
        WHERE class_id = ?
        ORDER BY first_name, last_name
    ");
    $stmt->execute([$_GET['class_id']]);
    $students = $stmt->fetchAll();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->beginTransaction();

        $class_id = $_POST['class_id'];
        $fee_type_id = $_POST['fee_type_id'];
        $due_date = $_POST['due_date'];
        $students = isset($_POST['students']) ? $_POST['students'] : [];

        // Get fee type details
        $stmt = $conn->prepare("SELECT type_name, amount FROM fee_types WHERE id = ?");
        $stmt->execute([$fee_type_id]);
        $fee_type = $stmt->fetch();

        // Insert fee records
        $stmt = $conn->prepare("
            INSERT INTO fees (student_id, fee_type, amount, due_date, payment_status, invoice_number)
            VALUES (?, ?, ?, ?, 'pending', ?)
        ");

        foreach ($students as $student_id) {
            // Generate invoice number (YYYYMMDD-CLASS-STUDENT-TYPE)
            $invoice_number = date('Ymd', strtotime($due_date)) . '-' . 
                            str_pad($class_id, 3, '0', STR_PAD_LEFT) . '-' .
                            str_pad($student_id, 4, '0', STR_PAD_LEFT) . '-' .
                            str_pad($fee_type_id, 2, '0', STR_PAD_LEFT);

            $stmt->execute([
                $student_id,
                $fee_type['type_name'],
                $fee_type['amount'],
                $due_date,
                $invoice_number
            ]);
        }

        $conn->commit();
        header("Location: index.php");
        exit();
    } catch(PDOException $e) {
        $conn->rollBack();
        $error = "Error generating fees: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Fees - Student Management System</title>
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
                    <h2>Generate Fees</h2>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <!-- Class Selection -->
                        <?php if (empty($_GET['class_id'])): ?>
                            <form method="GET" class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Select Class</label>
                                    <select name="class_id" class="form-select" required>
                                        <option value="">Choose Class</option>
                                        <?php foreach ($classes as $class): ?>
                                            <option value="<?php echo $class['id']; ?>">
                                                <?php echo htmlspecialchars($class['class_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i> Get Students
                                    </button>
                                </div>
                            </form>
                        <?php else: ?>
                            <!-- Fee Generation Form -->
                            <form method="POST" class="needs-validation" novalidate>
                                <input type="hidden" name="class_id" value="<?php echo $_GET['class_id']; ?>">
                                
                                <div class="row g-3 mb-4">
                                    <div class="col-md-4">
                                        <label class="form-label">Fee Type</label>
                                        <select name="fee_type_id" class="form-select" required onchange="updateAmount(this)">
                                            <option value="">Choose Fee Type</option>
                                            <?php foreach ($fee_types as $type): ?>
                                                <option value="<?php echo $type['id']; ?>" 
                                                        data-amount="<?php echo $type['amount']; ?>">
                                                    <?php echo htmlspecialchars($type['type_name']); ?> 
                                                    (₹<?php echo number_format($type['amount'], 2); ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <label class="form-label">Due Date</label>
                                        <input type="date" name="due_date" class="form-control" required
                                               value="<?php echo date('Y-m-d', strtotime('+1 month')); ?>">
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">Amount</label>
                                        <h3 id="amountDisplay">₹0.00</h3>
                                    </div>
                                </div>

                                <!-- Quick Actions -->
                                <div class="mb-3">
                                    <button type="button" class="btn btn-secondary btn-sm me-2" onclick="toggleAllStudents(false)">
                                        Unselect All
                                    </button>
                                    <button type="button" class="btn btn-info btn-sm" onclick="toggleAllStudents(true)">
                                        Select All
                                    </button>
                                </div>

                                <!-- Students Table -->
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Select</th>
                                                <th>Student ID</th>
                                                <th>Student Name</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($students)): ?>
                                                <tr>
                                                    <td colspan="3" class="text-center">No students found in this class</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($students as $student): ?>
                                                    <tr>
                                                        <td>
                                                            <div class="form-check">
                                                                <input type="checkbox" class="form-check-input student-checkbox"
                                                                       name="students[]" value="<?php echo $student['id']; ?>">
                                                            </div>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($student['student_code']); ?></td>
                                                        <td><?php echo htmlspecialchars($student['student_name']); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <?php if (!empty($students)): ?>
                                    <div class="mt-3">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-file-invoice"></i> Generate Fees
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Update amount display when fee type changes
        function updateAmount(select) {
            const option = select.options[select.selectedIndex];
            const amount = option.getAttribute('data-amount');
            document.getElementById('amountDisplay').textContent = 
                amount ? `₹${parseFloat(amount).toFixed(2)}` : '₹0.00';
        }

        // Toggle all student checkboxes
        function toggleAllStudents(checked) {
            document.querySelectorAll('.student-checkbox').forEach(checkbox => {
                checkbox.checked = checked;
            });
        }

        // Form validation
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
        })()
    </script>
</body>
</html>
