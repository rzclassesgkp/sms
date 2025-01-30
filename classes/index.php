<?php
require_once '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Handle class deletion
if (isset($_POST['delete_class']) && $_SESSION['user_role'] === 'admin') {
    $class_id = $_POST['class_id'];
    try {
        $stmt = $conn->prepare("DELETE FROM classes WHERE id = ?");
        $stmt->execute([$class_id]);
        $success = "Class deleted successfully";
    } catch(PDOException $e) {
        $error = "Error deleting class: " . $e->getMessage();
    }
}

// Get all classes with student count and subject count
$query = "
    SELECT 
        c.*,
        (SELECT COUNT(*) FROM students s WHERE s.class_id = c.id) as student_count,
        (SELECT COUNT(DISTINCT cs.subject_id) 
         FROM class_subjects cs 
         WHERE cs.class_id = c.id) as subject_count,
        (SELECT GROUP_CONCAT(DISTINCT CONCAT(t.first_name, ' ', t.last_name) SEPARATOR ', ')
         FROM class_subjects cs
         JOIN teachers t ON cs.teacher_id = t.id
         WHERE cs.class_id = c.id) as teachers
    FROM classes c 
    ORDER BY c.class_name
";

$stmt = $conn->query($query);
$classes = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Classes - Student Management System</title>
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
                    <h2>Manage Classes</h2>
                    <?php if ($_SESSION['user_role'] === 'admin'): ?>
                    <a href="add.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Class
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
                                        <th>Class Name</th>
                                        <th>Teachers</th>
                                        <th>Students</th>
                                        <th>Subjects</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($classes as $class): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($class['class_name']); ?></td>
                                            <td><?php echo htmlspecialchars($class['teachers'] ?? 'No teachers assigned'); ?></td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?php echo $class['student_count']; ?> students
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    <?php echo $class['subject_count']; ?> subjects
                                                </span>
                                            </td>
                                            <td>
                                                <a href="view.php?id=<?php echo $class['id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                                                <a href="edit.php?id=<?php echo $class['id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this class?');">
                                                    <input type="hidden" name="class_id" value="<?php echo $class['id']; ?>">
                                                    <button type="submit" name="delete_class" class="btn btn-sm btn-danger">
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
