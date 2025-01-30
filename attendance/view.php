<?php
require_once '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Get student ID and date range from URL
$student_id = isset($_GET['student_id']) ? $_GET['student_id'] : null;
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); // First day of current month
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');   // Last day of current month

if (!$student_id) {
    header("Location: index.php");
    exit();
}

// Get student information
$stmt = $conn->prepare("
    SELECT s.*, c.class_name
    FROM students s
    JOIN classes c ON s.class_id = c.id
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
    SELECT a.*, sub.subject_name
    FROM attendance a
    JOIN subjects sub ON a.subject_id = sub.id
    WHERE a.student_id = ? AND a.date BETWEEN ? AND ?
    ORDER BY a.date DESC, sub.subject_name
");
$stmt->execute([$student_id, $start_date, $end_date]);
$attendance_records = $stmt->fetchAll();

// Calculate attendance statistics
$total_days = count($attendance_records);
$present_days = count(array_filter($attendance_records, fn($r) => $r['status'] === 'present'));
$absent_days = count(array_filter($attendance_records, fn($r) => $r['status'] === 'absent'));
$late_days = count(array_filter($attendance_records, fn($r) => $r['status'] === 'late'));
$attendance_percentage = $total_days > 0 ? round(($present_days / $total_days) * 100, 2) : 0;

// Get subject-wise attendance
$subject_attendance = [];
foreach ($attendance_records as $record) {
    $subject = $record['subject_name'];
    if (!isset($subject_attendance[$subject])) {
        $subject_attendance[$subject] = [
            'total' => 0,
            'present' => 0,
            'absent' => 0,
            'late' => 0
        ];
    }
    $subject_attendance[$subject]['total']++;
    $subject_attendance[$subject][$record['status']]++;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Attendance - Student Management System</title>
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
                    <h2>Student Attendance Report</h2>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>

                <!-- Student Information -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <h6>Student ID</h6>
                                <p class="text-muted"><?php echo htmlspecialchars($student['student_id']); ?></p>
                            </div>
                            <div class="col-md-3">
                                <h6>Student Name</h6>
                                <p class="text-muted"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></p>
                            </div>
                            <div class="col-md-3">
                                <h6>Class</h6>
                                <p class="text-muted"><?php echo htmlspecialchars($student['class_name']); ?></p>
                            </div>
                            <div class="col-md-3">
                                <h6>Status</h6>
                                <span class="badge bg-<?php echo $student['status'] === 'active' ? 'success' : 'danger'; ?>">
                                    <?php echo ucfirst($student['status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Date Range Filter -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
                            
                            <div class="col-md-4">
                                <label class="form-label">Start Date</label>
                                <input type="date" name="start_date" class="form-control" 
                                       value="<?php echo $start_date; ?>">
                            </div>
                            
                            <div class="col-md-4">
                                <label class="form-label">End Date</label>
                                <input type="date" name="end_date" class="form-control"
                                       value="<?php echo $end_date; ?>">
                            </div>
                            
                            <div class="col-md-4">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-filter"></i> Filter
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Attendance Statistics -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Attendance Overview</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <canvas id="attendanceChart"></canvas>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="table-responsive">
                                            <table class="table">
                                                <tr>
                                                    <th>Total Days</th>
                                                    <td><?php echo $total_days; ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Present Days</th>
                                                    <td><?php echo $present_days; ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Absent Days</th>
                                                    <td><?php echo $absent_days; ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Late Days</th>
                                                    <td><?php echo $late_days; ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Attendance Percentage</th>
                                                    <td>
                                                        <span class="badge bg-<?php 
                                                            echo $attendance_percentage >= 75 ? 'success' : 
                                                                ($attendance_percentage >= 60 ? 'warning' : 'danger'); 
                                                        ?>">
                                                            <?php echo $attendance_percentage; ?>%
                                                        </span>
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Subject-wise Attendance</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Subject</th>
                                                <th>Present %</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($subject_attendance as $subject => $stats): ?>
                                                <?php 
                                                    $percentage = round(($stats['present'] / $stats['total']) * 100, 2);
                                                ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($subject); ?></td>
                                                    <td>
                                                        <div class="progress">
                                                            <div class="progress-bar bg-<?php 
                                                                echo $percentage >= 75 ? 'success' : 
                                                                    ($percentage >= 60 ? 'warning' : 'danger'); 
                                                            ?>" role="progressbar" style="width: <?php echo $percentage; ?>%">
                                                                <?php echo $percentage; ?>%
                                                            </div>
                                                        </div>
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

                <!-- Attendance Records -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Attendance Records</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Subject</th>
                                        <th>Status</th>
                                        <th>Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($attendance_records)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center">No attendance records found</td>
                                        </tr>
                                    <?php else: ?>
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
                                                <td><?php echo htmlspecialchars($record['remarks']); ?></td>
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
        // Attendance Chart
        var ctx = document.getElementById('attendanceChart').getContext('2d');
        var attendanceChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['Present', 'Absent', 'Late'],
                datasets: [{
                    data: [<?php echo $present_days; ?>, <?php echo $absent_days; ?>, <?php echo $late_days; ?>],
                    backgroundColor: ['#28a745', '#dc3545', '#ffc107']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>
