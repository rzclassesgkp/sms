<?php
require_once '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Handle subject deletion
if (isset($_POST['delete_subject']) && $_SESSION['user_role'] === 'admin') {
    $subject_id = $_POST['subject_id'];
    try {
        $stmt = $conn->prepare("DELETE FROM subjects WHERE id = ?");
        $stmt->execute([$subject_id]);
        $success = "Subject deleted successfully";
    } catch(PDOException $e) {
        $error = "Error deleting subject: " . $e->getMessage();
    }
}

// Get all subjects with additional information
$query = "
    SELECT s.*, 
    (SELECT COUNT(DISTINCT cs.class_id) FROM class_subjects cs WHERE cs.subject_id = s.id) as class_count,
    (SELECT COUNT(DISTINCT cs.teacher_id) FROM class_subjects cs WHERE cs.subject_id = s.id) as teacher_count,
    (SELECT COUNT(DISTINCT g.student_id) FROM grades g WHERE g.subject_id = s.id) as student_count
    FROM subjects s
    ORDER BY s.subject_name
";
$stmt = $conn->query($query);
$subjects = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subjects - Student Management System</title>
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
                    <h2>Manage Subjects</h2>
                    <?php if ($_SESSION['user_role'] === 'admin'): ?>
                    <a href="add.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Subject
                    </a>
                    <?php endif; ?>
                </div>

                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Subject Code</th>
                                        <th>Subject Name</th>
                                        <th>Credits</th>
                                        <th>Classes</th>
                                        <th>Teachers</th>
                                        <th>Students</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($subjects as $subject): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($subject['subject_code']); ?></td>
                                            <td><?php echo htmlspecialchars($subject['subject_name']); ?></td>
                                            <td><?php echo htmlspecialchars($subject['credits']); ?></td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?php echo $subject['class_count']; ?> classes
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    <?php echo $subject['teacher_count']; ?> teachers
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary">
                                                    <?php echo $subject['student_count']; ?> students
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $subject['status'] === 'active' ? 'success' : 'danger'; ?>">
                                                    <?php echo ucfirst($subject['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="view.php?id=<?php echo $subject['id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                                                <a href="edit.php?id=<?php echo $subject['id']; ?>" class="btn btn-sm btn-warning">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this subject?');">
                                                    <input type="hidden" name="subject_id" value="<?php echo $subject['id']; ?>">
                                                    <button type="submit" name="delete_subject" class="btn btn-sm btn-danger">
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
</body>
</html>
