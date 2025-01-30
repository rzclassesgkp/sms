<?php
require_once '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Get filter parameters
$class_id = isset($_GET['class_id']) ? $_GET['class_id'] : '';
$student_id = isset($_GET['student_id']) ? $_GET['student_id'] : '';
$month = isset($_GET['month']) ? $_GET['month'] : date('m');
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');

// Get all classes
$stmt = $conn->query("SELECT id, class_name FROM classes ORDER BY class_name");
$classes = $stmt->fetchAll();

// Get students based on class selection
$students = [];
if ($class_id) {
    $stmt = $conn->prepare("
        SELECT id, student_id as student_code, CONCAT(first_name, ' ', last_name) as student_name 
        FROM students 
        WHERE class_id = ? 
        ORDER BY first_name, last_name
    ");
    $stmt->execute([$class_id]);
    $students = $stmt->fetchAll();
}

// Build attendance query
$query = "
    SELECT 
        a.*,
        s.student_id as student_code,
        CONCAT(s.first_name, ' ', s.last_name) as student_name,
        c.class_name,
        sub.subject_name
    FROM attendance a
    JOIN students s ON a.student_id = s.id
    JOIN classes c ON a.class_id = c.id
    JOIN subjects sub ON a.subject_id = sub.id
    WHERE MONTH(a.date) = ? AND YEAR(a.date) = ?
";

$params = [$month, $year];

if ($class_id) {
    $query .= " AND a.class_id = ?";
    $params[] = $class_id;
}

if ($student_id) {
    $query .= " AND a.student_id = ?";
    $params[] = $student_id;
}

$query .= " ORDER BY a.date DESC, s.first_name, s.last_name";

// Get attendance records
$stmt = $conn->prepare($query);
$stmt->execute($params);
$attendance_records = $stmt->fetchAll();

// Calculate statistics
$total_days = count(array_unique(array_column($attendance_records, 'date')));
$attendance_stats = [
    'present' => 0,
    'absent' => 0,
    'late' => 0
];

foreach ($attendance_records as $record) {
    $attendance_stats[$record['status']]++;
}

$attendance_rate = $total_days > 0 ? 
    round((($attendance_stats['present'] + $attendance_stats['late']) / count($attendance_records)) * 100, 2) : 0;

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
    <title>Student Attendance Report - Student Management System</title>
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
                        <i class="fas fa-arrow-left"></i> Back to Reports
                    </a>
                </div>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Class</label>
                                <select name="class_id" class="form-select" onchange="this.form.submit()">
                                    <option value="">Select Class</option>
                                    <?php foreach ($classes as $class): ?>
                                    <option value="<?php echo $class['id']; ?>" <?php echo $class_id == $class['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($class['class_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Student</label>
                                <select name="student_id" class="form-select" <?php echo empty($students) ? 'disabled' : ''; ?>>
                                    <option value="">Select Student</option>
                                    <?php foreach ($students as $student): ?>
                                    <option value="<?php echo $student['id']; ?>" <?php echo $student_id == $student['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($student['student_code'] . ' - ' . $student['student_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Month</label>
                                <select name="month" class="form-select">
                                    <?php for ($i = 1; $i <= 12; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo $month == $i ? 'selected' : ''; ?>>
                                        <?php echo date('F', mktime(0, 0, 0, $i, 1)); ?>
                                    </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Year</label>
                                <select name="year" class="form-select">
                                    <?php for ($i = date('Y'); $i >= date('Y')-2; $i--): ?>
                                    <option value="<?php echo $i; ?>" <?php echo $year == $i ? 'selected' : ''; ?>>
                                        <?php echo $i; ?>
                                    </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary w-100">Filter</button>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if (!empty($attendance_records)): ?>
                <!-- Attendance Overview -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">Attendance Rate</h5>
                                <p class="card-text display-6"><?php echo $attendance_rate; ?>%</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">Present Days</h5>
                                <p class="card-text display-6"><?php echo $attendance_stats['present']; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h5 class="card-title">Late Days</h5>
                                <p class="card-text display-6"><?php echo $attendance_stats['late']; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-danger text-white">
                            <div class="card-body">
                                <h5 class="card-title">Absent Days</h5>
                                <p class="card-text display-6"><?php echo $attendance_stats['absent']; ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="row mb-4">
                    <!-- Attendance Distribution Chart -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Attendance Distribution</h5>
                                <canvas id="attendanceChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <!-- Subject-wise Attendance Chart -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Subject-wise Attendance</h5>
                                <canvas id="subjectChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detailed Attendance Table -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Detailed Attendance Records</h5>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Student</th>
                                        <th>Subject</th>
                                        <th>Status</th>
                                        <th>Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($attendance_records as $record): ?>
                                    <tr>
                                        <td><?php echo date('d M Y', strtotime($record['date'])); ?></td>
                                        <td><?php echo htmlspecialchars($record['student_name']); ?></td>
                                        <td><?php echo htmlspecialchars($record['subject_name']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $record['status'] === 'present' ? 'success' : 
                                                    ($record['status'] === 'late' ? 'warning' : 'danger'); 
                                            ?>">
                                                <?php echo ucfirst($record['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($record['remarks'] ?? ''); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No attendance records found for the selected criteria.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php if (!empty($attendance_records)): ?>
    <script>
        // Attendance Distribution Chart
        const attendanceCtx = document.getElementById('attendanceChart').getContext('2d');
        new Chart(attendanceCtx, {
            type: 'doughnut',
            data: {
                labels: ['Present', 'Late', 'Absent'],
                datasets: [{
                    data: [
                        <?php echo $attendance_stats['present']; ?>,
                        <?php echo $attendance_stats['late']; ?>,
                        <?php echo $attendance_stats['absent']; ?>
                    ],
                    backgroundColor: [
                        'rgba(40, 167, 69, 0.8)',
                        'rgba(255, 193, 7, 0.8)',
                        'rgba(220, 53, 69, 0.8)'
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

        // Subject-wise Attendance Chart
        const subjectCtx = document.getElementById('subjectChart').getContext('2d');
        new Chart(subjectCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_keys($subject_attendance)); ?>,
                datasets: [{
                    label: 'Present',
                    data: <?php echo json_encode(array_map(function($subject) {
                        return $subject['present'];
                    }, $subject_attendance)); ?>,
                    backgroundColor: 'rgba(40, 167, 69, 0.8)'
                }, {
                    label: 'Late',
                    data: <?php echo json_encode(array_map(function($subject) {
                        return $subject['late'];
                    }, $subject_attendance)); ?>,
                    backgroundColor: 'rgba(255, 193, 7, 0.8)'
                }, {
                    label: 'Absent',
                    data: <?php echo json_encode(array_map(function($subject) {
                        return $subject['absent'];
                    }, $subject_attendance)); ?>,
                    backgroundColor: 'rgba(220, 53, 69, 0.8)'
                }]
            },
            options: {
                responsive: true,
                scales: {
                    x: {
                        stacked: true
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>
