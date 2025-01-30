<?php
require_once '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Get class ID from URL
$class_id = isset($_GET['id']) ? $_GET['id'] : null;
if (!$class_id) {
    header("Location: index.php");
    exit();
}

// Get class data with teacher name
$stmt = $conn->prepare("
    SELECT c.*, CONCAT(t.first_name, ' ', t.last_name) as teacher_name
    FROM classes c 
    LEFT JOIN teachers t ON c.class_teacher_id = t.id 
    WHERE c.id = ?
");
$stmt->execute([$class_id]);
$class = $stmt->fetch();

if (!$class) {
    header("Location: index.php");
    exit();
}

// Get class subjects with teachers
$stmt = $conn->prepare("
    SELECT s.subject_name, s.subject_code, CONCAT(t.first_name, ' ', t.last_name) as teacher_name
    FROM class_subjects cs
    JOIN subjects s ON cs.subject_id = s.id
    LEFT JOIN teachers t ON cs.teacher_id = t.id
    WHERE cs.class_id = ?
");
$stmt->execute([$class_id]);
$subjects = $stmt->fetchAll();

// Get students in this class
$stmt = $conn->prepare("
    SELECT id, student_id, first_name, last_name, gender, phone
    FROM students 
    WHERE class_id = ?
    ORDER BY first_name, last_name
");
$stmt->execute([$class_id]);
$students = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Class - Student Management System</title>
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
                    <h2>Class Details</h2>
                    <div>
                        <?php if ($_SESSION['user_role'] === 'admin'): ?>
                        <a href="edit.php?id=<?php echo $class['id']; ?>" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <?php endif; ?>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>

                <div class="row">
                    <!-- Class Information -->
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Class Information</h5>
                            </div>
                            <div class="card-body">
                                <table class="table">
                                    <tr>
                                        <th width="35%">Class Name</th>
                                        <td><?php echo htmlspecialchars($class['class_name']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Class Teacher</th>
                                        <td><?php echo htmlspecialchars($class['teacher_name'] ?? 'Not Assigned'); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Room Number</th>
                                        <td><?php echo htmlspecialchars($class['room_number'] ?? 'Not Assigned'); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Capacity</th>
                                        <td><?php echo htmlspecialchars($class['capacity']); ?> students</td>
                                    </tr>
                                    <tr>
                                        <th>Current Students</th>
                                        <td><?php echo count($students); ?> students</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Subjects -->
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Class Subjects</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Subject</th>
                                                <th>Code</th>
                                                <th>Teacher</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($subjects)): ?>
                                                <tr>
                                                    <td colspan="3" class="text-center">No subjects assigned</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($subjects as $subject): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($subject['subject_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($subject['subject_code']); ?></td>
                                                        <td><?php echo htmlspecialchars($subject['teacher_name'] ?? 'Not Assigned'); ?></td>
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

                <!-- Students List -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Students in this Class</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Student ID</th>
                                        <th>Name</th>
                                        <th>Gender</th>
                                        <th>Phone</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($students)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center">No students in this class</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($students as $student): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                                                <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                                <td><?php echo ucfirst($student['gender']); ?></td>
                                                <td><?php echo htmlspecialchars($student['phone']); ?></td>
                                                <td>
                                                    <a href="../students/view.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-info">
                                                        <i class="fas fa-eye"></i>
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
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
