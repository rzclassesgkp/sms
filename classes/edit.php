<?php
require_once '../includes/config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Get class ID from URL
$class_id = isset($_GET['id']) ? $_GET['id'] : null;
if (!$class_id) {
    header("Location: index.php");
    exit();
}

// Get all teachers for dropdown
$stmt = $conn->query("SELECT id, first_name, last_name FROM teachers ORDER BY first_name, last_name");
$teachers = $stmt->fetchAll();

// Get all subjects for multi-select
$stmt = $conn->query("SELECT id, subject_name, subject_code FROM subjects ORDER BY subject_name");
$subjects = $stmt->fetchAll();

// Get class data
$stmt = $conn->prepare("SELECT * FROM classes WHERE id = ?");
$stmt->execute([$class_id]);
$class = $stmt->fetch();

if (!$class) {
    header("Location: index.php");
    exit();
}

// Get current class subjects
$stmt = $conn->prepare("SELECT subject_id FROM class_subjects WHERE class_id = ?");
$stmt->execute([$class_id]);
$class_subjects = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->beginTransaction();

        // Update class
        $stmt = $conn->prepare("
            UPDATE classes 
            SET class_name = ?, class_teacher_id = ?, room_number = ?, capacity = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $_POST['class_name'],
            $_POST['class_teacher_id'] ?: null,
            $_POST['room_number'],
            $_POST['capacity'],
            $class_id
        ]);

        // Delete existing class subjects
        $stmt = $conn->prepare("DELETE FROM class_subjects WHERE class_id = ?");
        $stmt->execute([$class_id]);

        // Insert new class subjects
        if (!empty($_POST['subjects'])) {
            $stmt = $conn->prepare("INSERT INTO class_subjects (class_id, subject_id) VALUES (?, ?)");
            foreach ($_POST['subjects'] as $subject_id) {
                $stmt->execute([$class_id, $subject_id]);
            }
        }

        $conn->commit();
        header("Location: index.php");
        exit();
    } catch(PDOException $e) {
        $conn->rollBack();
        $error = "Error updating class: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Class - Student Management System</title>
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
                    <h2>Edit Class</h2>
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
                                <input type="text" name="class_name" class="form-control" required
                                       value="<?php echo htmlspecialchars($class['class_name']); ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Class Teacher</label>
                                <select name="class_teacher_id" class="form-select">
                                    <option value="">Select Teacher</option>
                                    <?php foreach ($teachers as $teacher): ?>
                                        <option value="<?php echo $teacher['id']; ?>" 
                                                <?php echo $class['class_teacher_id'] == $teacher['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Room Number</label>
                                <input type="text" name="room_number" class="form-control"
                                       value="<?php echo htmlspecialchars($class['room_number']); ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Capacity</label>
                                <input type="number" name="capacity" class="form-control" min="1"
                                       value="<?php echo htmlspecialchars($class['capacity']); ?>">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Subjects</label>
                                <select name="subjects[]" class="form-select select2" multiple>
                                    <?php foreach ($subjects as $subject): ?>
                                        <option value="<?php echo $subject['id']; ?>"
                                                <?php echo in_array($subject['id'], $class_subjects) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($subject['subject_name'] . ' (' . $subject['subject_code'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="form-text text-muted">Hold Ctrl (Windows) or Command (Mac) to select multiple subjects</small>
                            </div>

                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Class
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
