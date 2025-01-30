<?php
require_once '../includes/config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Get student ID from URL
$student_id = isset($_GET['id']) ? $_GET['id'] : null;
if (!$student_id) {
    header("Location: index.php");
    exit();
}

// Get student data with class name
$stmt = $conn->prepare("
    SELECT s.*, c.class_name, u.email 
    FROM students s 
    LEFT JOIN classes c ON s.class_id = c.id 
    JOIN users u ON s.user_id = u.id 
    WHERE s.id = ?
");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    header("Location: index.php");
    exit();
}

// Get attendance records
$stmt = $conn->prepare("
    SELECT a.*, s.subject_name 
    FROM attendance a 
    JOIN subjects s ON a.subject_id = s.id 
    WHERE a.student_id = ? 
    ORDER BY a.date DESC 
    LIMIT 10
");
$stmt->execute([$student_id]);
$attendance_records = $stmt->fetchAll();

// Get grade records
$stmt = $conn->prepare("
    SELECT g.*, s.subject_name 
    FROM grades g 
    JOIN subjects s ON g.subject_id = s.id 
    WHERE g.student_id = ? 
    ORDER BY g.exam_date DESC 
    LIMIT 10
");
$stmt->execute([$student_id]);
$grade_records = $stmt->fetchAll();

// Get fee records
$stmt = $conn->prepare("
    SELECT * FROM fees 
    WHERE student_id = ? 
    ORDER BY due_date DESC 
    LIMIT 10
");
$stmt->execute([$student_id]);
$fee_records = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Student - Student Management System</title>
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
                    <h2>Student Details</h2>
                    <div>
                        <a href="edit.php?id=<?php echo $student['id']; ?>" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>

                <!-- Student Information -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Personal Information</h5>
                            </div>
                            <div class="card-body">
                                <table class="table">
                                    <tr>
                                        <th width="35%">Student ID</th>
                                        <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Name</th>
                                        <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Date of Birth</th>
                                        <td><?php echo date('M d, Y', strtotime($student['date_of_birth'])); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Gender</th>
                                        <td><?php echo ucfirst($student['gender']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Email</th>
                                        <td><?php echo htmlspecialchars($student['email']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Phone</th>
                                        <td><?php echo htmlspecialchars($student['phone']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Address</th>
                                        <td><?php echo htmlspecialchars($student['address']); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Academic Information</h5>
                            </div>
                            <div class="card-body">
                                <table class="table">
                                    <tr>
                                        <th width="35%">Class</th>
                                        <td><?php echo htmlspecialchars($student['class_name'] ?? 'Not Assigned'); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Admission Date</th>
                                        <td><?php echo date('M d, Y', strtotime($student['admission_date'])); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Status</th>
                                        <td>
                                            <span class="badge bg-<?php echo $student['status'] === 'active' ? 'success' : 'danger'; ?>">
                                                <?php echo ucfirst($student['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Parent/Guardian Information</h5>
                            </div>
                            <div class="card-body">
                                <table class="table">
                                    <tr>
                                        <th width="35%">Name</th>
                                        <td><?php echo htmlspecialchars($student['parent_name']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Phone</th>
                                        <td><?php echo htmlspecialchars($student['parent_phone']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Email</th>
                                        <td><?php echo htmlspecialchars($student['parent_email']); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Records -->
                <div class="row">
                    <!-- Recent Attendance -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Recent Attendance</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Subject</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($attendance_records as $record): ?>
                                                <tr>
                                                    <td><?php echo date('M d, Y', strtotime($record['date'])); ?></td>
                                                    <td><?php echo htmlspecialchars($record['subject_name']); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php 
                                                            echo $record['status'] === 'present' ? 'success' : 
                                                                ($record['status'] === 'absent' ? 'danger' : 'warning'); 
                                                        ?>">
                                                            <?php echo ucfirst($record['status']); ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Grades -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Recent Grades</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Subject</th>
                                                <th>Grade</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($grade_records as $record): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($record['subject_name']); ?></td>
                                                    <td><?php echo $record['grade_letter']; ?></td>
                                                    <td><?php echo date('M d, Y', strtotime($record['exam_date'])); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Fees -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Recent Fees</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Type</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($fee_records as $record): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($record['fee_type']); ?></td>
                                                    <td>$<?php echo number_format($record['amount'], 2); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php 
                                                            echo $record['status'] === 'paid' ? 'success' : 
                                                                ($record['status'] === 'unpaid' ? 'danger' : 'warning'); 
                                                        ?>">
                                                            <?php echo ucfirst($record['status']); ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
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
