<?php
require_once '../includes/config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Get subject ID from URL
$subject_id = isset($_GET['id']) ? $_GET['id'] : null;
if (!$subject_id) {
    header("Location: index.php");
    exit();
}

// Get all teachers for subject assignment
$stmt = $conn->query("SELECT id, first_name, last_name FROM teachers WHERE status = 'active' ORDER BY first_name, last_name");
$teachers = $stmt->fetchAll();

// Get all classes for subject assignment
$stmt = $conn->query("SELECT id, class_name FROM classes ORDER BY class_name");
$classes = $stmt->fetchAll();

// Get subject data
$stmt = $conn->prepare("SELECT * FROM subjects WHERE id = ?");
$stmt->execute([$subject_id]);
$subject = $stmt->fetch();

if (!$subject) {
    header("Location: index.php");
    exit();
}

// Get current class assignments
$stmt = $conn->prepare("
    SELECT class_id, teacher_id 
    FROM class_subjects 
    WHERE subject_id = ?
");
$stmt->execute([$subject_id]);
$class_assignments = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->beginTransaction();

        // Update subject
        $stmt = $conn->prepare("
            UPDATE subjects 
            SET subject_code = ?, subject_name = ?, description = ?, credits = ?, status = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $_POST['subject_code'],
            $_POST['subject_name'],
            $_POST['description'],
            $_POST['credits'],
            $_POST['status'],
            $subject_id
        ]);

        // Delete existing class assignments
        $stmt = $conn->prepare("DELETE FROM class_subjects WHERE subject_id = ?");
        $stmt->execute([$subject_id]);

        // Insert new class assignments
        if (!empty($_POST['classes'])) {
            $stmt = $conn->prepare("
                INSERT INTO class_subjects (class_id, subject_id, teacher_id) 
                VALUES (?, ?, ?)
            ");
            foreach ($_POST['classes'] as $class_id) {
                $teacher_id = $_POST['teacher_' . $class_id] ?? null;
                $stmt->execute([$class_id, $subject_id, $teacher_id]);
            }
        }

        $conn->commit();
        header("Location: index.php");
        exit();
    } catch(PDOException $e) {
        $conn->rollBack();
        $error = "Error updating subject: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Subject - Student Management System</title>
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
                    <h2>Edit Subject</h2>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Subjects
                    </a>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <form method="POST" class="row g-3">
                            <!-- Subject Information -->
                            <h5 class="mb-3">Subject Information</h5>
                            
                            <div class="col-md-6">
                                <label class="form-label">Subject Code</label>
                                <input type="text" name="subject_code" class="form-control" required
                                       value="<?php echo htmlspecialchars($subject['subject_code']); ?>">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Subject Name</label>
                                <input type="text" name="subject_name" class="form-control" required
                                       value="<?php echo htmlspecialchars($subject['subject_name']); ?>">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Credits</label>
                                <input type="number" name="credits" class="form-control" required
                                       min="1" max="6" value="<?php echo htmlspecialchars($subject['credits']); ?>">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select" required>
                                    <option value="active" <?php echo $subject['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo $subject['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($subject['description']); ?></textarea>
                            </div>

                            <!-- Class Assignment -->
                            <h5 class="mb-3 mt-4">Class Assignment</h5>
                            
                            <div class="col-12">
                                <label class="form-label">Select Classes</label>
                                <select name="classes[]" id="classes" class="form-select select2" multiple>
                                    <?php foreach ($classes as $class): ?>
                                        <option value="<?php echo $class['id']; ?>"
                                                <?php echo array_key_exists($class['id'], $class_assignments) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($class['class_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="form-text text-muted">Hold Ctrl (Windows) or Command (Mac) to select multiple classes</small>
                            </div>

                            <!-- Teacher Assignment -->
                            <div id="teacherAssignments" class="col-12 mt-3">
                                <h6>Assign Teachers to Classes</h6>
                                <?php foreach ($classes as $class): ?>
                                    <div class="teacher-select mb-2" data-class="<?php echo $class['id']; ?>"
                                         style="display: <?php echo array_key_exists($class['id'], $class_assignments) ? 'block' : 'none'; ?>">
                                        <label class="form-label">Teacher for <?php echo htmlspecialchars($class['class_name']); ?></label>
                                        <select name="teacher_<?php echo $class['id']; ?>" class="form-select">
                                            <option value="">Select Teacher</option>
                                            <?php foreach ($teachers as $teacher): ?>
                                                <option value="<?php echo $teacher['id']; ?>"
                                                        <?php echo ($class_assignments[$class['id']] ?? null) == $teacher['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="col-12 mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Subject
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

            // Show/hide teacher assignments based on selected classes
            $('#classes').on('change', function() {
                var selectedClasses = $(this).val();
                
                // First hide all teacher selections
                $('.teacher-select').hide();
                
                // Show teacher selection for selected classes
                if (selectedClasses && selectedClasses.length > 0) {
                    $('#teacherAssignments').show();
                    selectedClasses.forEach(function(classId) {
                        $('.teacher-select[data-class="' + classId + '"]').show();
                    });
                } else {
                    $('#teacherAssignments').hide();
                }
            });
        });
    </script>
</body>
</html>
