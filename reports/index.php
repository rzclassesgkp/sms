<?php
require_once '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Get statistics for dashboard cards
$stats = [];

// Total Students
$stmt = $conn->query("SELECT COUNT(*) as count FROM students");
$stats['students'] = $stmt->fetch()['count'];

// Total Teachers
$stmt = $conn->query("SELECT COUNT(*) as count FROM teachers");
$stats['teachers'] = $stmt->fetch()['count'];

// Total Classes
$stmt = $conn->query("SELECT COUNT(*) as count FROM classes");
$stats['classes'] = $stmt->fetch()['count'];

// Attendance Overview (Last 30 days)
$stmt = $conn->query("
    SELECT status, COUNT(*) as count 
    FROM attendance 
    WHERE date >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
    GROUP BY status
");
$attendance = $stmt->fetchAll();
$attendance_data = ['present' => 0, 'absent' => 0, 'late' => 0];
foreach ($attendance as $record) {
    $attendance_data[$record['status']] = $record['count'];
}
$total_attendance = array_sum($attendance_data);
$attendance_percentage = $total_attendance > 0 ? 
    round(($attendance_data['present'] / $total_attendance) * 100, 2) : 0;

// Fee Collection Overview (Current Month)
$stmt = $conn->query("
    SELECT 
        SUM(amount) as total_fees,
        SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as collected_fees
    FROM fees
    WHERE MONTH(due_date) = MONTH(CURRENT_DATE)
    AND YEAR(due_date) = YEAR(CURRENT_DATE)
");
$fee_data = $stmt->fetch();
$total_fees = $fee_data['total_fees'] ?? 0;
$collected_fees = $fee_data['collected_fees'] ?? 0;
$collection_percentage = $total_fees > 0 ? 
    round(($collected_fees / $total_fees) * 100, 2) : 0;

// Grade Distribution
$stmt = $conn->query("
    SELECT grade_letter, COUNT(*) as count
    FROM grades
    WHERE created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH)
    GROUP BY grade_letter
    ORDER BY grade_letter
");
$grades = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Student Management System</title>
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
                    <h2>Reports Dashboard</h2>
                    <div>
                        <a href="student_performance.php" class="btn btn-primary">
                            <i class="fas fa-chart-line"></i> Student Performance
                        </a>
                        <a href="attendance_report.php" class="btn btn-success">
                            <i class="fas fa-calendar-check"></i> Attendance Report
                        </a>
                        <a href="fee_report.php" class="btn btn-info text-white">
                            <i class="fas fa-money-bill"></i> Fee Collection
                        </a>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Students</h5>
                                <p class="card-text display-6"><?php echo $stats['students']; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Teachers</h5>
                                <p class="card-text display-6"><?php echo $stats['teachers']; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Classes</h5>
                                <p class="card-text display-6"><?php echo $stats['classes']; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h5 class="card-title">Attendance Rate</h5>
                                <p class="card-text display-6"><?php echo $attendance_percentage; ?>%</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="row mb-4">
                    <!-- Attendance Chart -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Attendance Overview (Last 30 Days)</h5>
                                <canvas id="attendanceChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <!-- Grade Distribution Chart -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Grade Distribution (Last 6 Months)</h5>
                                <canvas id="gradeChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Fee Collection Progress -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Fee Collection Progress (Current Month)</h5>
                        <div class="progress" style="height: 25px;">
                            <div class="progress-bar bg-success" role="progressbar" 
                                 style="width: <?php echo $collection_percentage; ?>%"
                                 aria-valuenow="<?php echo $collection_percentage; ?>" 
                                 aria-valuemin="0" aria-valuemax="100">
                                <?php echo $collection_percentage; ?>%
                            </div>
                        </div>
                        <div class="mt-2">
                            <small class="text-muted">
                                Collected: <?php echo format_currency($collected_fees); ?> / 
                                Total: <?php echo format_currency($total_fees); ?>
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="row">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Student Reports</h5>
                                <ul class="list-group">
                                    <li class="list-group-item">
                                        <a href="student_performance.php" class="text-decoration-none">Performance Analysis</a>
                                    </li>
                                    <li class="list-group-item">
                                        <a href="student_attendance.php" class="text-decoration-none">Attendance Records</a>
                                    </li>
                                    <li class="list-group-item">
                                        <a href="student_fees.php" class="text-decoration-none">Fee Status</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Class Reports</h5>
                                <ul class="list-group">
                                    <li class="list-group-item">
                                        <a href="class_performance.php" class="text-decoration-none">Class Performance</a>
                                    </li>
                                    <li class="list-group-item">
                                        <a href="class_attendance.php" class="text-decoration-none">Class Attendance</a>
                                    </li>
                                    <li class="list-group-item">
                                        <a href="class_schedule.php" class="text-decoration-none">Class Schedule</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Financial Reports</h5>
                                <ul class="list-group">
                                    <li class="list-group-item">
                                        <a href="fee_collection.php" class="text-decoration-none">Fee Collection</a>
                                    </li>
                                    <li class="list-group-item">
                                        <a href="fee_defaulters.php" class="text-decoration-none">Fee Defaulters</a>
                                    </li>
                                    <li class="list-group-item">
                                        <a href="fee_summary.php" class="text-decoration-none">Monthly Summary</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Attendance Chart
        const attendanceCtx = document.getElementById('attendanceChart').getContext('2d');
        new Chart(attendanceCtx, {
            type: 'doughnut',
            data: {
                labels: ['Present', 'Absent', 'Late'],
                datasets: [{
                    data: [
                        <?php echo $attendance_data['present']; ?>,
                        <?php echo $attendance_data['absent']; ?>,
                        <?php echo $attendance_data['late']; ?>
                    ],
                    backgroundColor: [
                        'rgba(40, 167, 69, 0.8)',
                        'rgba(220, 53, 69, 0.8)',
                        'rgba(255, 193, 7, 0.8)'
                    ]
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

        // Grade Distribution Chart
        const gradeCtx = document.getElementById('gradeChart').getContext('2d');
        new Chart(gradeCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($grades, 'grade_letter')); ?>,
                datasets: [{
                    label: 'Number of Students',
                    data: <?php echo json_encode(array_column($grades, 'count')); ?>,
                    backgroundColor: 'rgba(23, 162, 184, 0.8)'
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
                    }
                }
            }
        });
    </script>
</body>
</html>
