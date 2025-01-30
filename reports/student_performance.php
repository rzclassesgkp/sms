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
$exam_type = isset($_GET['exam_type']) ? $_GET['exam_type'] : '';

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

// Build performance query
$query = "
    SELECT 
        s.student_id as student_code,
        CONCAT(s.first_name, ' ', s.last_name) as student_name,
        c.class_name,
        sub.subject_name,
        g.exam_type,
        g.marks,
        g.grade_letter,
        g.created_at as exam_date,
        (SELECT AVG(marks) 
         FROM grades g2 
         WHERE g2.class_id = g.class_id 
         AND g2.subject_id = g.subject_id 
         AND g2.exam_type = g.exam_type) as class_average
    FROM grades g
    JOIN students s ON g.student_id = s.id
    JOIN classes c ON g.class_id = c.id
    JOIN subjects sub ON g.subject_id = sub.id
    WHERE 1=1
";

$params = [];

if ($class_id) {
    $query .= " AND g.class_id = ?";
    $params[] = $class_id;
}

if ($student_id) {
    $query .= " AND g.student_id = ?";
    $params[] = $student_id;
}

if ($exam_type) {
    $query .= " AND g.exam_type = ?";
    $params[] = $exam_type;
}

$query .= " ORDER BY g.created_at DESC, sub.subject_name";

// Get performance data
$stmt = $conn->prepare($query);
$stmt->execute($params);
$performances = $stmt->fetchAll();

// Calculate statistics
$total_exams = count($performances);
$total_marks = array_sum(array_column($performances, 'marks'));
$avg_marks = $total_exams > 0 ? round($total_marks / $total_exams, 2) : 0;

// Calculate subject-wise performance
$subject_performance = [];
foreach ($performances as $perf) {
    $subject = $perf['subject_name'];
    if (!isset($subject_performance[$subject])) {
        $subject_performance[$subject] = [
            'marks' => [],
            'class_avg' => []
        ];
    }
    $subject_performance[$subject]['marks'][] = $perf['marks'];
    $subject_performance[$subject]['class_avg'][] = $perf['class_average'];
}

// Calculate averages for each subject
foreach ($subject_performance as $subject => $data) {
    $subject_performance[$subject]['avg'] = array_sum($data['marks']) / count($data['marks']);
    $subject_performance[$subject]['class_avg'] = array_sum($data['class_avg']) / count($data['class_avg']);
}

// Get grade distribution
$grade_distribution = array_count_values(array_column($performances, 'grade_letter'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Performance Report - Student Management System</title>
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
                    <h2>Student Performance Report</h2>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Reports
                    </a>
                </div>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
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
                            <div class="col-md-4">
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
                            <div class="col-md-3">
                                <label class="form-label">Exam Type</label>
                                <select name="exam_type" class="form-select">
                                    <option value="">All Types</option>
                                    <option value="Midterm" <?php echo $exam_type === 'Midterm' ? 'selected' : ''; ?>>Midterm</option>
                                    <option value="Final" <?php echo $exam_type === 'Final' ? 'selected' : ''; ?>>Final</option>
                                    <option value="Quiz" <?php echo $exam_type === 'Quiz' ? 'selected' : ''; ?>>Quiz</option>
                                </select>
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary w-100">Filter</button>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if (!empty($performances)): ?>
                <!-- Performance Overview -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">Average Score</h5>
                                <p class="card-text display-6"><?php echo $avg_marks; ?>%</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Exams</h5>
                                <p class="card-text display-6"><?php echo $total_exams; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h5 class="card-title">Subjects</h5>
                                <p class="card-text display-6"><?php echo count($subject_performance); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="row mb-4">
                    <!-- Subject Performance Chart -->
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Subject-wise Performance</h5>
                                <canvas id="subjectChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <!-- Grade Distribution Chart -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Grade Distribution</h5>
                                <canvas id="gradeChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detailed Performance Table -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Detailed Performance</h5>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Subject</th>
                                        <th>Exam Type</th>
                                        <th>Marks</th>
                                        <th>Class Average</th>
                                        <th>Grade</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($performances as $performance): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($performance['subject_name']); ?></td>
                                        <td><?php echo htmlspecialchars($performance['exam_type']); ?></td>
                                        <td><?php echo htmlspecialchars($performance['marks']); ?>%</td>
                                        <td><?php echo round($performance['class_average'], 2); ?>%</td>
                                        <td>
                                            <span class="badge bg-<?php echo $performance['grade_letter'] === 'F' ? 'danger' : 'success'; ?>">
                                                <?php echo htmlspecialchars($performance['grade_letter']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d M Y', strtotime($performance['exam_date'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Select a class and student to view performance report.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php if (!empty($performances)): ?>
    <script>
        // Subject Performance Chart
        const subjectCtx = document.getElementById('subjectChart').getContext('2d');
        new Chart(subjectCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_keys($subject_performance)); ?>,
                datasets: [{
                    label: 'Student Average',
                    data: <?php echo json_encode(array_map(function($subject) {
                        return round($subject['avg'], 2);
                    }, $subject_performance)); ?>,
                    backgroundColor: 'rgba(40, 167, 69, 0.8)'
                }, {
                    label: 'Class Average',
                    data: <?php echo json_encode(array_map(function($subject) {
                        return round($subject['class_avg'], 2);
                    }, $subject_performance)); ?>,
                    backgroundColor: 'rgba(23, 162, 184, 0.8)'
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100
                    }
                }
            }
        });

        // Grade Distribution Chart
        const gradeCtx = document.getElementById('gradeChart').getContext('2d');
        new Chart(gradeCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_keys($grade_distribution)); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_values($grade_distribution)); ?>,
                    backgroundColor: [
                        'rgba(40, 167, 69, 0.8)',  // A
                        'rgba(23, 162, 184, 0.8)', // B
                        'rgba(255, 193, 7, 0.8)',  // C
                        'rgba(220, 53, 69, 0.8)'   // F
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
    </script>
    <?php endif; ?>
</body>
</html>
