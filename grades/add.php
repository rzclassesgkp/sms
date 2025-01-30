<?php
require_once '../includes/config.php';

// Check if user is logged in and is admin or teacher
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'teacher'])) {
    header("Location: ../login.php");
    exit();
}

// Get all classes
$stmt = $conn->query("SELECT id, class_name FROM classes ORDER BY class_name");
$classes = $stmt->fetchAll();

// Get all subjects
$stmt = $conn->query("SELECT id, subject_name FROM subjects ORDER BY subject_name");
$subjects = $stmt->fetchAll();

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
        $subject_id = $_POST['subject_id'];
        $exam_type = $_POST['exam_type'];
        $exam_date = $_POST['exam_date'];
        $students = $_POST['students'];

        // Insert grade records
        $stmt = $conn->prepare("
            INSERT INTO grades (student_id, class_id, subject_id, exam_type, exam_date, marks, grade_letter, remarks)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($students as $student_id => $data) {
            // Calculate grade letter based on marks
            $marks = floatval($data['marks']);
            $grade_letter = calculateGradeLetter($marks);

            $stmt->execute([
                $student_id,
                $class_id,
                $subject_id,
                $exam_type,
                $exam_date,
                $marks,
                $grade_letter,
                $data['remarks'] ?? null
            ]);
        }

        $conn->commit();
        header("Location: index.php");
        exit();
    } catch(PDOException $e) {
        $conn->rollBack();
        $error = "Error adding grades: " . $e->getMessage();
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
    <title>Add Grades - Student Management System</title>
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
                    <h2>Add Grades</h2>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <!-- Class and Subject Selection -->
                        <?php if (empty($_GET['class_id'])): ?>
                            <form method="GET" class="row g-3">
                                <div class="col-md-4">
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
                            <!-- Grade Entry Form -->
                            <form method="POST" id="gradeForm" class="needs-validation" novalidate>
                                <input type="hidden" name="class_id" value="<?php echo $_GET['class_id']; ?>">
                                
                                <div class="row g-3 mb-4">
                                    <div class="col-md-4">
                                        <label class="form-label">Subject</label>
                                        <select name="subject_id" class="form-select" required>
                                            <option value="">Choose Subject</option>
                                            <?php foreach ($subjects as $subject): ?>
                                                <option value="<?php echo $subject['id']; ?>">
                                                    <?php echo htmlspecialchars($subject['subject_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <label class="form-label">Exam Type</label>
                                        <select name="exam_type" class="form-select" required>
                                            <option value="">Choose Type</option>
                                            <option value="midterm">Midterm</option>
                                            <option value="final">Final</option>
                                            <option value="quiz">Quiz</option>
                                            <option value="assignment">Assignment</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <label class="form-label">Exam Date</label>
                                        <input type="date" name="exam_date" class="form-control" required
                                               value="<?php echo date('Y-m-d'); ?>">
                                    </div>
                                </div>

                                <!-- Quick Actions -->
                                <div class="mb-3">
                                    <button type="button" class="btn btn-secondary btn-sm me-2" onclick="setAllMarks(0)">
                                        Clear All Marks
                                    </button>
                                    <button type="button" class="btn btn-info btn-sm" onclick="setAllMarks(50)">
                                        Set Passing Grade (50)
                                    </button>
                                </div>

                                <!-- Grade Entry Table -->
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Student ID</th>
                                                <th>Student Name</th>
                                                <th>Marks (%)</th>
                                                <th>Grade</th>
                                                <th>Remarks</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($students)): ?>
                                                <tr>
                                                    <td colspan="5" class="text-center">No students found in this class</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($students as $student): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($student['student_code']); ?></td>
                                                        <td><?php echo htmlspecialchars($student['student_name']); ?></td>
                                                        <td>
                                                            <input type="number" class="form-control marks-input" 
                                                                   name="students[<?php echo $student['id']; ?>][marks]"
                                                                   min="0" max="100" step="0.01" required
                                                                   onchange="updateGrade(this)">
                                                        </td>
                                                        <td>
                                                            <span class="grade-display badge bg-secondary">--</span>
                                                        </td>
                                                        <td>
                                                            <input type="text" class="form-control"
                                                                   name="students[<?php echo $student['id']; ?>][remarks]"
                                                                   placeholder="Optional remarks">
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <?php if (!empty($students)): ?>
                                    <div class="mt-3">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Save Grades
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
            const gradeDisplay = input.closest('tr').querySelector('.grade-display');
            gradeDisplay.textContent = grade;
            gradeDisplay.className = `grade-display badge bg-${color}`;
        }

        // Set marks for all students
        function setAllMarks(value) {
            document.querySelectorAll('.marks-input').forEach(input => {
                input.value = value;
                updateGrade(input);
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
