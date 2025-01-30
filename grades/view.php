<?php
require_once '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Get student ID from URL
$student_id = isset($_GET['student_id']) ? $_GET['student_id'] : null;
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

// Get all grades for the student
$stmt = $conn->prepare("
    SELECT g.*, sub.subject_name, sub.credits
    FROM grades g
    JOIN subjects sub ON g.subject_id = sub.id
    WHERE g.student_id = ?
    ORDER BY g.exam_date DESC
");
$stmt->execute([$student_id]);
$grades = $stmt->fetchAll();

// Calculate GPA and statistics
$total_credits = 0;
$total_grade_points = 0;
$subject_stats = [];

foreach ($grades as $grade) {
    // Calculate grade points
    $grade_points = 0;
    switch ($grade['grade_letter']) {
        case 'A+': $grade_points = 4.0; break;
        case 'A': $grade_points = 3.7; break;
        case 'B+': $grade_points = 3.3; break;
        case 'B': $grade_points = 3.0; break;
        case 'C+': $grade_points = 2.7; break;
        case 'C': $grade_points = 2.4; break;
        case 'D+': $grade_points = 2.2; break;
        case 'D': $grade_points = 2.0; break;
        case 'F': $grade_points = 0.0; break;
    }

    // Add to totals
    $total_credits += $grade['credits'];
    $total_grade_points += ($grade_points * $grade['credits']);

    // Collect subject statistics
    $subject = $grade['subject_name'];
    if (!isset($subject_stats[$subject])) {
        $subject_stats[$subject] = [
            'grades' => [],
            'average' => 0,
            'highest' => 0,
            'lowest' => 100
        ];
    }
    $subject_stats[$subject]['grades'][] = $grade['marks'];
    $subject_stats[$subject]['average'] = array_sum($subject_stats[$subject]['grades']) / count($subject_stats[$subject]['grades']);
    $subject_stats[$subject]['highest'] = max($subject_stats[$subject]['grades']);
    $subject_stats[$subject]['lowest'] = min($subject_stats[$subject]['grades']);
}

$gpa = $total_credits > 0 ? round($total_grade_points / $total_credits, 2) : 0;

// Get grade distribution
$grade_distribution = [
    'A+' => 0, 'A' => 0, 'B+' => 0, 'B' => 0,
    'C+' => 0, 'C' => 0, 'D+' => 0, 'D' => 0, 'F' => 0
];
foreach ($grades as $grade) {
    $grade_distribution[$grade['grade_letter']]++;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Grades - Student Management System</title>
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
                    <h2>Student Grade Report</h2>
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
                                <h6>GPA</h6>
                                <h3>
                                    <span class="badge bg-<?php 
                                        echo $gpa >= 3.5 ? 'success' : 
                                            ($gpa >= 3.0 ? 'info' : 
                                            ($gpa >= 2.0 ? 'warning' : 'danger')); 
                                    ?>">
                                        <?php echo number_format($gpa, 2); ?>
                                    </span>
                                </h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Grade Distribution and Subject Performance -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Grade Distribution</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="gradeDistributionChart"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Subject Performance</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Subject</th>
                                                <th>Average</th>
                                                <th>Highest</th>
                                                <th>Lowest</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($subject_stats as $subject => $stats): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($subject); ?></td>
                                                    <td><?php echo number_format($stats['average'], 2); ?>%</td>
                                                    <td><?php echo number_format($stats['highest'], 2); ?>%</td>
                                                    <td><?php echo number_format($stats['lowest'], 2); ?>%</td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Grade History -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Grade History</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Subject</th>
                                        <th>Exam Type</th>
                                        <th>Marks</th>
                                        <th>Grade</th>
                                        <th>Credits</th>
                                        <th>Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($grades)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No grades recorded yet</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($grades as $grade): ?>
                                            <tr>
                                                <td><?php echo date('M d, Y', strtotime($grade['exam_date'])); ?></td>
                                                <td><?php echo htmlspecialchars($grade['subject_name']); ?></td>
                                                <td><?php echo ucfirst($grade['exam_type']); ?></td>
                                                <td><?php echo number_format($grade['marks'], 2); ?>%</td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $grade['grade_letter'] === 'F' ? 'danger' : 
                                                            (in_array($grade['grade_letter'], ['A+', 'A']) ? 'success' : 
                                                            (in_array($grade['grade_letter'], ['B+', 'B']) ? 'info' : 'warning')); 
                                                    ?>">
                                                        <?php echo $grade['grade_letter']; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $grade['credits']; ?></td>
                                                <td><?php echo htmlspecialchars($grade['remarks']); ?></td>
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
        // Grade Distribution Chart
        var ctx = document.getElementById('gradeDistributionChart').getContext('2d');
        var gradeChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode(array_keys($grade_distribution)); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_values($grade_distribution)); ?>,
                    backgroundColor: [
                        '#28a745', '#28a745', // A+, A
                        '#17a2b8', '#17a2b8', // B+, B
                        '#ffc107', '#ffc107', // C+, C
                        '#fd7e14', '#fd7e14', // D+, D
                        '#dc3545'             // F
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    title: {
                        display: true,
                        text: 'Grade Distribution'
                    }
                }
            }
        });
    </script>
</body>
</html>
