<?php
require_once '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Get teacher ID from URL
$teacher_id = isset($_GET['id']) ? $_GET['id'] : null;
if (!$teacher_id) {
    header("Location: index.php");
    exit();
}

// Get teacher data
$stmt = $conn->prepare("
    SELECT t.*, u.email 
    FROM teachers t 
    JOIN users u ON t.user_id = u.id 
    WHERE t.id = ?
");
$stmt->execute([$teacher_id]);
$teacher = $stmt->fetch();

if (!$teacher) {
    header("Location: index.php");
    exit();
}

// Get classes where this teacher is the class teacher
$stmt = $conn->prepare("
    SELECT c.*, 
    (SELECT COUNT(*) FROM students s WHERE s.class_id = c.id) as student_count
    FROM classes c 
    WHERE c.class_teacher_id = ?
");
$stmt->execute([$teacher_id]);
$classes = $stmt->fetchAll();

// Get subjects taught by this teacher
$stmt = $conn->prepare("
    SELECT DISTINCT s.subject_name, s.subject_code, c.class_name
    FROM class_subjects cs
    JOIN subjects s ON cs.subject_id = s.id
    JOIN classes c ON cs.class_id = c.id
    WHERE cs.teacher_id = ?
    ORDER BY c.class_name, s.subject_name
");
$stmt->execute([$teacher_id]);
$subjects = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Teacher - Student Management System</title>
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
                    <h2>Teacher Details</h2>
                    <div>
                        <?php if ($_SESSION['user_role'] === 'admin'): ?>
                        <a href="edit.php?id=<?php echo $teacher['id']; ?>" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <?php endif; ?>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>

                <div class="row">
                    <!-- Personal Information -->
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Personal Information</h5>
                            </div>
                            <div class="card-body">
                                <table class="table">
                                    <tr>
                                        <th width="35%">Teacher ID</th>
                                        <td><?php echo htmlspecialchars($teacher['teacher_id']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Name</th>
                                        <td><?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Email</th>
                                        <td><?php echo htmlspecialchars($teacher['email']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Phone</th>
                                        <td><?php echo htmlspecialchars($teacher['phone']); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Professional Information -->
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Professional Information</h5>
                            </div>
                            <div class="card-body">
                                <table class="table">
                                    <tr>
                                        <th width="35%">Qualification</th>
                                        <td><?php echo nl2br(htmlspecialchars($teacher['qualification'])); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Joining Date</th>
                                        <td><?php echo date('M d, Y', strtotime($teacher['joining_date'])); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Status</th>
                                        <td>
                                            <span class="badge bg-<?php echo $teacher['status'] === 'active' ? 'success' : 'danger'; ?>">
                                                <?php echo ucfirst($teacher['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Classes -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Classes as Class Teacher</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Class Name</th>
                                        <th>Room Number</th>
                                        <th>Students</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($classes)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center">Not assigned as class teacher to any class</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($classes as $class): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($class['class_name']); ?></td>
                                                <td><?php echo htmlspecialchars($class['room_number']); ?></td>
                                                <td>
                                                    <span class="badge bg-info">
                                                        <?php echo $class['student_count']; ?> students
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="../classes/view.php?id=<?php echo $class['id']; ?>" class="btn btn-sm btn-info">
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

                <!-- Subjects -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Subjects Teaching</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Subject</th>
                                        <th>Code</th>
                                        <th>Class</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($subjects)): ?>
                                        <tr>
                                            <td colspan="3" class="text-center">Not teaching any subjects</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($subjects as $subject): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($subject['subject_name']); ?></td>
                                                <td><?php echo htmlspecialchars($subject['subject_code']); ?></td>
                                                <td><?php echo htmlspecialchars($subject['class_name']); ?></td>
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
