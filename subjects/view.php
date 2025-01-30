<?php
require_once '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Get subject ID from URL
$subject_id = isset($_GET['id']) ? $_GET['id'] : null;
if (!$subject_id) {
    header("Location: index.php");
    exit();
}

// Get subject data
$stmt = $conn->prepare("SELECT * FROM subjects WHERE id = ?");
$stmt->execute([$subject_id]);
$subject = $stmt->fetch();

if (!$subject) {
    header("Location: index.php");
    exit();
}

// Get classes where this subject is taught
$stmt = $conn->prepare("
    SELECT c.class_name, c.id as class_id,
    CONCAT(t.first_name, ' ', t.last_name) as teacher_name,
    (SELECT COUNT(*) FROM students s WHERE s.class_id = c.id) as student_count
    FROM class_subjects cs
    JOIN classes c ON cs.class_id = c.id
    LEFT JOIN teachers t ON cs.teacher_id = t.id
    WHERE cs.subject_id = ?
    ORDER BY c.class_name
");
$stmt->execute([$subject_id]);
$classes = $stmt->fetchAll();

// Get student performance statistics
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_grades,
        AVG(marks) as average_marks,
        MAX(marks) as highest_marks,
        MIN(marks) as lowest_marks,
        COUNT(CASE WHEN grade_letter IN ('A', 'A+') THEN 1 END) as a_grades,
        COUNT(CASE WHEN grade_letter IN ('B', 'B+') THEN 1 END) as b_grades,
        COUNT(CASE WHEN grade_letter IN ('C', 'C+') THEN 1 END) as c_grades,
        COUNT(CASE WHEN grade_letter IN ('D', 'D+') THEN 1 END) as d_grades,
        COUNT(CASE WHEN grade_letter = 'F' THEN 1 END) as f_grades
    FROM grades 
    WHERE subject_id = ?
");
$stmt->execute([$subject_id]);
$performance = $stmt->fetch();

// Get recent grades
$stmt = $conn->prepare("
    SELECT g.*, 
    CONCAT(s.first_name, ' ', s.last_name) as student_name,
    c.class_name
    FROM grades g
    JOIN students s ON g.student_id = s.id
    JOIN classes c ON g.class_id = c.id
    WHERE g.subject_id = ?
    ORDER BY g.exam_date DESC
    LIMIT 10
");
$stmt->execute([$subject_id]);
$recent_grades = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Subject - Student Management System</title>
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
                    <h2>Subject Details</h2>
                    <div>
                        <?php if ($_SESSION['user_role'] === 'admin'): ?>
                        <a href="edit.php?id=<?php echo $subject['id']; ?>" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <?php endif; ?>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>

                <div class="row">
                    <!-- Subject Information -->
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Subject Information</h5>
                            </div>
                            <div class="card-body">
                                <table class="table">
                                    <tr>
                                        <th width="35%">Subject Code</th>
                                        <td><?php echo htmlspecialchars($subject['subject_code']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Subject Name</th>
                                        <td><?php echo htmlspecialchars($subject['subject_name']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Credits</th>
                                        <td><?php echo htmlspecialchars($subject['credits']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Description</th>
                                        <td><?php echo nl2br(htmlspecialchars($subject['description'])); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Status</th>
                                        <td>
                                            <span class="badge bg-<?php echo $subject['status'] === 'active' ? 'success' : 'danger'; ?>">
                                                <?php echo ucfirst($subject['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Performance Statistics -->
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Performance Statistics</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <table class="table">
                                            <tr>
                                                <th>Total Grades</th>
                                                <td><?php echo $performance['total_grades']; ?></td>
                                            </tr>
                                            <tr>
                                                <th>Average Marks</th>
                                                <td><?php echo number_format($performance['average_marks'], 2); ?>%</td>
                                            </tr>
                                            <tr>
                                                <th>Highest Marks</th>
                                                <td><?php echo number_format($performance['highest_marks'], 2); ?>%</td>
                                            </tr>
                                            <tr>
                                                <th>Lowest Marks</th>
                                                <td><?php echo number_format($performance['lowest_marks'], 2); ?>%</td>
                                            </tr>
                                        </table>
                                    </div>
                                    <div class="col-md-6">
                                        <canvas id="gradeDistribution"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Classes -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Classes Taking This Subject</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Class</th>
                                        <th>Teacher</th>
                                        <th>Students</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($classes)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center">No classes assigned to this subject</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($classes as $class): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($class['class_name']); ?></td>
                                                <td><?php echo htmlspecialchars($class['teacher_name'] ?? 'Not Assigned'); ?></td>
                                                <td>
                                                    <span class="badge bg-info">
                                                        <?php echo $class['student_count']; ?> students
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="../classes/view.php?id=<?php echo $class['class_id']; ?>" class="btn btn-sm btn-info">
                                                        <i class="fas fa-eye"></i> View Class
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Recent Grades -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Recent Grades</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Class</th>
                                        <th>Exam Type</th>
                                        <th>Marks</th>
                                        <th>Grade</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recent_grades)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No grades recorded yet</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($recent_grades as $grade): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($grade['student_name']); ?></td>
                                                <td><?php echo htmlspecialchars($grade['class_name']); ?></td>
                                                <td><?php echo htmlspecialchars($grade['exam_type']); ?></td>
                                                <td><?php echo number_format($grade['marks'], 2); ?>%</td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $grade['grade_letter'] === 'F' ? 'danger' : 
                                                            ($grade['grade_letter'] === 'A' || $grade['grade_letter'] === 'A+' ? 'success' : 'warning'); 
                                                    ?>">
                                                        <?php echo $grade['grade_letter']; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($grade['exam_date'])); ?></td>
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
        // Grade distribution chart
        var ctx = document.getElementById('gradeDistribution').getContext('2d');
        var gradeChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['A/A+', 'B/B+', 'C/C+', 'D/D+', 'F'],
                datasets: [{
                    data: [
                        <?php echo $performance['a_grades']; ?>,
                        <?php echo $performance['b_grades']; ?>,
                        <?php echo $performance['c_grades']; ?>,
                        <?php echo $performance['d_grades']; ?>,
                        <?php echo $performance['f_grades']; ?>
                    ],
                    backgroundColor: [
                        '#28a745',
                        '#17a2b8',
                        '#ffc107',
                        '#fd7e14',
                        '#dc3545'
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
</body>
</html>
