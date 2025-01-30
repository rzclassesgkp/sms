<?php
require_once '../includes/config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Get all teachers for dropdown
$stmt = $conn->query("SELECT id, first_name, last_name FROM teachers ORDER BY first_name, last_name");
$teachers = $stmt->fetchAll();

// Get all subjects for multi-select
$stmt = $conn->query("SELECT id, subject_name, subject_code FROM subjects ORDER BY subject_name");
$subjects = $stmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->beginTransaction();

        // Insert into classes table
        $stmt = $conn->prepare("
            INSERT INTO classes (class_name, class_teacher_id, room_number, capacity) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $_POST['class_name'],
            $_POST['class_teacher_id'] ?: null,
            $_POST['room_number'],
            $_POST['capacity']
        ]);

        $class_id = $conn->lastInsertId();

        // Insert class subjects if any selected
        if (!empty($_POST['subjects'])) {
            $stmt = $conn->prepare("
                INSERT INTO class_subjects (class_id, subject_id) 
                VALUES (?, ?)
            ");
            foreach ($_POST['subjects'] as $subject_id) {
                $stmt->execute([$class_id, $subject_id]);
            }
        }

        $conn->commit();
        header("Location: index.php");
        exit();
    } catch(PDOException $e) {
        $conn->rollBack();
        $error = "Error adding class: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Class - Student Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
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
                    <h2>Add New Class</h2>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Classes
                    </a>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <form method="POST" class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Class Name</label>
                                <input type="text" name="class_name" class="form-control" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Class Teacher</label>
                                <select name="class_teacher_id" class="form-select">
                                    <option value="">Select Teacher</option>
                                    <?php foreach ($teachers as $teacher): ?>
                                        <option value="<?php echo $teacher['id']; ?>">
                                            <?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Room Number</label>
                                <input type="text" name="room_number" class="form-control">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Capacity</label>
                                <input type="number" name="capacity" class="form-control" min="1">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Subjects</label>
                                <select name="subjects[]" class="form-select select2" multiple>
                                    <?php foreach ($subjects as $subject): ?>
                                        <option value="<?php echo $subject['id']; ?>">
                                            <?php echo htmlspecialchars($subject['subject_name'] . ' (' . $subject['subject_code'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="form-text text-muted">Hold Ctrl (Windows) or Command (Mac) to select multiple subjects</small>
                            </div>

                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Add Class
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.select2').select2({
                theme: 'bootstrap-5'
            });
        });
    </script>
</body>
</html>
