<?php
require_once '../includes/config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Get grade ID from URL
$grade_id = isset($_GET['id']) ? $_GET['id'] : null;
if (!$grade_id) {
    header("Location: index.php");
    exit();
}

// Get grade record
$stmt = $conn->prepare("
    SELECT g.*, 
    s.student_id as student_code,
    CONCAT(s.first_name, ' ', s.last_name) as student_name,
    c.class_name,
    sub.subject_name
    FROM grades g
    JOIN students s ON g.student_id = s.id
    JOIN classes c ON g.class_id = c.id
    JOIN subjects sub ON g.subject_id = sub.id
    WHERE g.id = ?
");
$stmt->execute([$grade_id]);
$grade = $stmt->fetch();

if (!$grade) {
    header("Location: index.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Calculate grade letter based on new marks
        $marks = floatval($_POST['marks']);
        $grade_letter = calculateGradeLetter($marks);

        $stmt = $conn->prepare("
            UPDATE grades 
            SET marks = ?, grade_letter = ?, remarks = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $marks,
            $grade_letter,
            $_POST['remarks'],
            $grade_id
        ]);

        header("Location: index.php");
        exit();
    } catch(PDOException $e) {
        $error = "Error updating grade: " . $e->getMessage();
    }
}

// Function to calculate grade letter based on marks
function calculateGradeLetter($marks) {
    if ($marks >= 90) return 'A+';
    if ($marks >= 80) return 'A';
    if ($marks >= 75) return 'B+';
    if ($marks >= 70) return 'B';
    if ($marks >= 65) return 'C+';
    if ($marks >= 60) return 'C';
    if ($marks >= 55) return 'D+';
    if ($marks >= 50) return 'D';
    return 'F';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Grade - Student Management System</title>
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
                    <h2>Edit Grade</h2>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <h6>Student ID</h6>
                                <p class="text-muted"><?php echo htmlspecialchars($grade['student_code']); ?></p>
                            </div>
                            <div class="col-md-3">
                                <h6>Student Name</h6>
                                <p class="text-muted"><?php echo htmlspecialchars($grade['student_name']); ?></p>
                            </div>
                            <div class="col-md-3">
                                <h6>Class</h6>
                                <p class="text-muted"><?php echo htmlspecialchars($grade['class_name']); ?></p>
                            </div>
                            <div class="col-md-3">
                                <h6>Subject</h6>
                                <p class="text-muted"><?php echo htmlspecialchars($grade['subject_name']); ?></p>
                            </div>
                        </div>

                        <form method="POST" class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Exam Type</label>
                                <input type="text" class="form-control" value="<?php echo ucfirst($grade['exam_type']); ?>" readonly>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Exam Date</label>
                                <input type="date" class="form-control" value="<?php echo date('Y-m-d', strtotime($grade['exam_date'])); ?>" readonly>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Marks (%)</label>
                                <input type="number" name="marks" class="form-control" required
                                       min="0" max="100" step="0.01"
                                       value="<?php echo number_format($grade['marks'], 2); ?>"
                                       onchange="updateGrade(this)">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Grade</label>
                                <div class="mt-2">
                                    <span id="gradeDisplay" class="badge bg-<?php 
                                        echo $grade['grade_letter'] === 'F' ? 'danger' : 
                                            (in_array($grade['grade_letter'], ['A+', 'A']) ? 'success' : 
                                            (in_array($grade['grade_letter'], ['B+', 'B']) ? 'info' : 'warning')); 
                                    ?>">
                                        <?php echo $grade['grade_letter']; ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">Remarks</label>
                                <textarea name="remarks" class="form-control" rows="3"
                                          placeholder="Optional remarks"><?php echo htmlspecialchars($grade['remarks']); ?></textarea>
                            </div>

                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Grade
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Calculate grade letter based on marks
        function calculateGrade(marks) {
            if (marks >= 90) return ['A+', 'success'];
            if (marks >= 80) return ['A', 'success'];
            if (marks >= 75) return ['B+', 'info'];
            if (marks >= 70) return ['B', 'info'];
            if (marks >= 65) return ['C+', 'warning'];
            if (marks >= 60) return ['C', 'warning'];
            if (marks >= 55) return ['D+', 'warning'];
            if (marks >= 50) return ['D', 'warning'];
            return ['F', 'danger'];
        }

        // Update grade display when marks change
        function updateGrade(input) {
            const marks = parseFloat(input.value) || 0;
            const [grade, color] = calculateGrade(marks);
            const gradeDisplay = document.getElementById('gradeDisplay');
            gradeDisplay.textContent = grade;
            gradeDisplay.className = `badge bg-${color}`;
        }
    </script>
</body>
</html>
