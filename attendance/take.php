<?php
require_once '../includes/config.php';

// Check if user is logged in and is admin or teacher
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'teacher'])) {
    header("Location: ../login.php");
    exit();
}

// Get all active classes
$stmt = $conn->query("SELECT id, class_name FROM classes WHERE status = 'active' ORDER BY class_name");
$classes = $stmt->fetchAll();

// Get all active subjects
$stmt = $conn->query("SELECT id, subject_name FROM subjects WHERE status = 'active' ORDER BY subject_name");
$subjects = $stmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->beginTransaction();

        $class_id = $_POST['class_id'];
        $subject_id = $_POST['subject_id'];
        $date = $_POST['date'];
        $students = $_POST['students'];

        // Insert attendance records
        $stmt = $conn->prepare("
            INSERT INTO attendance (student_id, class_id, subject_id, date, status, remarks)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        foreach ($students as $student_id => $data) {
            $stmt->execute([
                $student_id,
                $class_id,
                $subject_id,
                $date,
                $data['status'],
                $data['remarks'] ?? null
            ]);
        }

        $conn->commit();
        header("Location: index.php");
        exit();
    } catch(PDOException $e) {
        $conn->rollBack();
        $error = "Error taking attendance: " . $e->getMessage();
    }
}

// Get students for selected class
$students = [];
if (isset($_GET['class_id'])) {
    $stmt = $conn->prepare("
        SELECT id, student_id as student_code, CONCAT(first_name, ' ', last_name) as student_name
        FROM students 
        WHERE class_id = ? AND status = 'active'
        ORDER BY first_name, last_name
    ");
    $stmt->execute([$_GET['class_id']]);
    $students = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Take Attendance - Student Management System</title>
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
                    <h2>Take Attendance</h2>
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
                            <!-- Attendance Form -->
                            <form method="POST" id="attendanceForm">
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
                                        <label class="form-label">Date</label>
                                        <input type="date" name="date" class="form-control" required
                                               value="<?php echo date('Y-m-d'); ?>">
                                    </div>
                                </div>

                                <!-- Quick Actions -->
                                <div class="mb-3">
                                    <button type="button" class="btn btn-success btn-sm me-2" onclick="markAll('present')">
                                        Mark All Present
                                    </button>
                                    <button type="button" class="btn btn-danger btn-sm me-2" onclick="markAll('absent')">
                                        Mark All Absent
                                    </button>
                                    <button type="button" class="btn btn-warning btn-sm" onclick="markAll('late')">
                                        Mark All Late
                                    </button>
                                </div>

                                <!-- Students List -->
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Student ID</th>
                                                <th>Student Name</th>
                                                <th>Status</th>
                                                <th>Remarks</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($students)): ?>
                                                <tr>
                                                    <td colspan="4" class="text-center">No students found in this class</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($students as $student): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($student['student_code']); ?></td>
                                                        <td><?php echo htmlspecialchars($student['student_name']); ?></td>
                                                        <td>
                                                            <div class="btn-group" role="group">
                                                                <input type="radio" class="btn-check" name="students[<?php echo $student['id']; ?>][status]" 
                                                                       id="present_<?php echo $student['id']; ?>" value="present" required>
                                                                <label class="btn btn-outline-success" for="present_<?php echo $student['id']; ?>">
                                                                    Present
                                                                </label>

                                                                <input type="radio" class="btn-check" name="students[<?php echo $student['id']; ?>][status]" 
                                                                       id="absent_<?php echo $student['id']; ?>" value="absent">
                                                                <label class="btn btn-outline-danger" for="absent_<?php echo $student['id']; ?>">
                                                                    Absent
                                                                </label>

                                                                <input type="radio" class="btn-check" name="students[<?php echo $student['id']; ?>][status]" 
                                                                       id="late_<?php echo $student['id']; ?>" value="late">
                                                                <label class="btn btn-outline-warning" for="late_<?php echo $student['id']; ?>">
                                                                    Late
                                                                </label>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <input type="text" name="students[<?php echo $student['id']; ?>][remarks]" 
                                                                   class="form-control" placeholder="Optional remarks">
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
                                            <i class="fas fa-save"></i> Save Attendance
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
        function markAll(status) {
            document.querySelectorAll(`input[type="radio"][value="${status}"]`).forEach(radio => {
                radio.checked = true;
            });
        }
    </script>
</body>
</html>
