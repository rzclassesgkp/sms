<?php
require_once '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Handle teacher deletion
if (isset($_POST['delete_teacher']) && $_SESSION['user_role'] === 'admin') {
    $teacher_id = $_POST['teacher_id'];
    try {
        $stmt = $conn->prepare("DELETE FROM teachers WHERE id = ?");
        $stmt->execute([$teacher_id]);
        $success = "Teacher deleted successfully";
    } catch(PDOException $e) {
        $error = "Error deleting teacher: " . $e->getMessage();
    }
}

// Get all teachers with their subject assignments
$query = "
    SELECT t.*, 
           (SELECT GROUP_CONCAT(DISTINCT s.subject_name SEPARATOR ', ') 
            FROM class_subjects cs 
            JOIN subjects s ON cs.subject_id = s.id 
            WHERE cs.teacher_id = t.id) as subjects
    FROM teachers t
    LEFT JOIN users u ON t.user_id = u.id
    ORDER BY t.first_name, t.last_name
";

$stmt = $conn->query($query);
$teachers = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teachers - Student Management System</title>
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
                    <h2>Manage Teachers</h2>
                    <?php if ($_SESSION['user_role'] === 'admin'): ?>
                    <a href="add.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Teacher
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
                                        <th>Teacher ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Qualification</th>
                                        <th>Subjects</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($teachers as $teacher): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($teacher['teacher_id']); ?></td>
                                            <td><?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($teacher['email']); ?></td>
                                            <td><?php echo htmlspecialchars($teacher['phone']); ?></td>
                                            <td><?php echo htmlspecialchars($teacher['qualification']); ?></td>
                                            <td><?php echo htmlspecialchars($teacher['subjects'] ?? 'No subjects assigned'); ?></td>
                                            <td>
                                                <a href="view.php?id=<?php echo $teacher['id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                                                    <a href="edit.php?id=<?php echo $teacher['id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this teacher?');">
                                                        <input type="hidden" name="teacher_id" value="<?php echo $teacher['id']; ?>">
                                                        <button type="submit" name="delete_teacher" class="btn btn-sm btn-danger">
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
