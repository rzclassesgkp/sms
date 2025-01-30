<?php
require_once '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Get filter parameters
$class_id = isset($_GET['class_id']) ? $_GET['class_id'] : '';
$subject_id = isset($_GET['subject_id']) ? $_GET['subject_id'] : '';
$exam_type = isset($_GET['exam_type']) ? $_GET['exam_type'] : '';
$grade = isset($_GET['grade']) ? $_GET['grade'] : '';

// Get all classes for filter
$stmt = $conn->query("SELECT id, class_name FROM classes ORDER BY class_name");
$classes = $stmt->fetchAll();

// Get all subjects for filter
$stmt = $conn->query("SELECT id, subject_name FROM subjects ORDER BY subject_name");
$subjects = $stmt->fetchAll();

// Build grades query
$query = "
    SELECT g.*, 
    s.student_id as student_code,
    CONCAT(s.first_name, ' ', s.last_name) as student_name,
    c.class_name,
    sub.subject_name,
    sub.credits
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

if ($subject_id) {
    $query .= " AND g.subject_id = ?";
    $params[] = $subject_id;
}

if ($exam_type) {
    $query .= " AND g.exam_type = ?";
    $params[] = $exam_type;
}

if ($grade) {
    $query .= " AND g.grade_letter = ?";
    $params[] = $grade;
}

$query .= " ORDER BY g.created_at DESC, c.class_name, s.first_name, s.last_name";

// Get grades
$stmt = $conn->prepare($query);
$stmt->execute($params);
$grades = $stmt->fetchAll();

// Calculate statistics
$total_grades = count($grades);
$total_marks = array_sum(array_column($grades, 'marks'));
$avg_marks = $total_grades > 0 ? round($total_marks / $total_grades, 2) : 0;
$pass_count = count(array_filter($grades, fn($g) => $g['grade_letter'] !== 'F'));
$pass_percentage = $total_grades > 0 ? round(($pass_count / $total_grades) * 100, 2) : 0;

// Get grade distribution
$grade_distribution = [
    'A+' => 0, 'A' => 0, 'B+' => 0, 'B' => 0,
    'C+' => 0, 'C' => 0, 'D+' => 0, 'D' => 0, 'F' => 0
];
foreach ($grades as $grade) {
    if (isset($grade['grade_letter']) && isset($grade_distribution[$grade['grade_letter']])) {
        $grade_distribution[$grade['grade_letter']]++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grades - Student Management System</title>
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
                    <h2>Grade Management</h2>
                    <?php if ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'teacher'): ?>
                    <a href="add.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Grade
                    </a>
                    <?php endif; ?>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Grades</h5>
                                <p class="card-text display-6"><?php echo $total_grades; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">Average Marks</h5>
                                <p class="card-text display-6"><?php echo $avg_marks; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h5 class="card-title">Pass Rate</h5>
                                <p class="card-text display-6"><?php echo $pass_percentage; ?>%</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h5 class="card-title">Pass Count</h5>
                                <p class="card-text display-6"><?php echo $pass_count; ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Class</label>
                                <select name="class_id" class="form-select">
                                    <option value="">All Classes</option>
                                    <?php foreach ($classes as $class): ?>
                                    <option value="<?php echo $class['id']; ?>" <?php echo $class_id == $class['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($class['class_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Subject</label>
                                <select name="subject_id" class="form-select">
                                    <option value="">All Subjects</option>
                                    <?php foreach ($subjects as $subject): ?>
                                    <option value="<?php echo $subject['id']; ?>" <?php echo $subject_id == $subject['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($subject['subject_name']); ?>
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
                            <div class="col-md-2">
                                <label class="form-label">Grade</label>
                                <select name="grade" class="form-select">
                                    <option value="">All Grades</option>
                                    <?php foreach (array_keys($grade_distribution) as $g): ?>
                                    <option value="<?php echo $g; ?>" <?php echo $grade === $g ? 'selected' : ''; ?>>
                                        <?php echo $g; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary w-100">Filter</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Grade Distribution Chart -->
                <div class="card mb-4">
                    <div class="card-body">
                        <canvas id="gradeDistribution"></canvas>
                    </div>
                </div>

                <!-- Grades Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Student ID</th>
                                        <th>Student Name</th>
                                        <th>Class</th>
                                        <th>Subject</th>
                                        <th>Exam Type</th>
                                        <th>Marks</th>
                                        <th>Grade</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($grades as $grade): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($grade['student_code']); ?></td>
                                        <td><?php echo htmlspecialchars($grade['student_name']); ?></td>
                                        <td><?php echo htmlspecialchars($grade['class_name']); ?></td>
                                        <td><?php echo htmlspecialchars($grade['subject_name']); ?></td>
                                        <td><?php echo htmlspecialchars($grade['exam_type']); ?></td>
                                        <td><?php echo htmlspecialchars($grade['marks']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $grade['grade_letter'] === 'F' ? 'danger' : 'success'; ?>">
                                                <?php echo htmlspecialchars($grade['grade_letter']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d M Y', strtotime($grade['created_at'])); ?></td>
                                        <td>
                                            <?php if ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'teacher'): ?>
                                            <a href="edit.php?id=<?php echo $grade['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this grade?');">
                                                <input type="hidden" name="grade_id" value="<?php echo $grade['id']; ?>">
                                                <button type="submit" name="delete_grade" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                            <?php endif; ?>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Grade Distribution Chart
        const ctx = document.getElementById('gradeDistribution').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_keys($grade_distribution)); ?>,
                datasets: [{
                    label: 'Number of Students',
                    data: <?php echo json_encode(array_values($grade_distribution)); ?>,
                    backgroundColor: [
                        'rgba(40, 167, 69, 0.8)',  // A+, A
                        'rgba(40, 167, 69, 0.6)',
                        'rgba(23, 162, 184, 0.8)', // B+, B
                        'rgba(23, 162, 184, 0.6)',
                        'rgba(255, 193, 7, 0.8)',  // C+, C
                        'rgba(255, 193, 7, 0.6)',
                        'rgba(255, 193, 7, 0.4)',  // D+, D
                        'rgba(255, 193, 7, 0.2)',
                        'rgba(220, 53, 69, 0.8)'   // F
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'Grade Distribution'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
