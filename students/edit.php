<?php
require_once '../includes/config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Get student ID from URL
$student_id = isset($_GET['id']) ? $_GET['id'] : null;
if (!$student_id) {
    header("Location: index.php");
    exit();
}

// Get all classes for the dropdown
$stmt = $conn->query("SELECT id, class_name FROM classes ORDER BY class_name");
$classes = $stmt->fetchAll();

// Get student data
$stmt = $conn->prepare("
    SELECT s.*, u.email 
    FROM students s 
    JOIN users u ON s.user_id = u.id 
    WHERE s.id = ?
");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    header("Location: index.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Start transaction
        $conn->beginTransaction();

        // Update user email
        $stmt = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
        $stmt->execute([$_POST['email'], $student['user_id']]);

        // Update student
        $stmt = $conn->prepare("
            UPDATE students SET 
                first_name = ?, 
                last_name = ?, 
                date_of_birth = ?,
                gender = ?, 
                address = ?, 
                phone = ?, 
                class_id = ?, 
                parent_name = ?,
                parent_phone = ?, 
                parent_email = ?, 
                status = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $_POST['first_name'],
            $_POST['last_name'],
            $_POST['date_of_birth'],
            $_POST['gender'],
            $_POST['address'],
            $_POST['phone'],
            $_POST['class_id'],
            $_POST['parent_name'],
            $_POST['parent_phone'],
            $_POST['parent_email'],
            $_POST['status'],
            $student_id
        ]);

        $conn->commit();
        $success = "Student updated successfully";
        
        // Refresh student data
        $stmt = $conn->prepare("
            SELECT s.*, u.email 
            FROM students s 
            JOIN users u ON s.user_id = u.id 
            WHERE s.id = ?
        ");
        $stmt->execute([$student_id]);
        $student = $stmt->fetch();
    } catch(PDOException $e) {
        $conn->rollBack();
        $error = "Error updating student: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student - Student Management System</title>
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
                    <h2>Edit Student</h2>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Students
                    </a>
                </div>

                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <form method="POST" class="row g-3">
                            <!-- Student ID Display -->
                            <div class="col-md-12">
                                <div class="alert alert-info">
                                    Student ID: <?php echo htmlspecialchars($student['student_id']); ?>
                                </div>
                            </div>

                            <!-- Personal Information -->
                            <h5 class="mb-3">Personal Information</h5>
                            
                            <div class="col-md-6">
                                <label class="form-label">First Name</label>
                                <input type="text" name="first_name" class="form-control" required
                                       value="<?php echo htmlspecialchars($student['first_name']); ?>">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Last Name</label>
                                <input type="text" name="last_name" class="form-control" required
                                       value="<?php echo htmlspecialchars($student['last_name']); ?>">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Date of Birth</label>
                                <input type="date" name="date_of_birth" class="form-control" required
                                       value="<?php echo $student['date_of_birth']; ?>">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Gender</label>
                                <select name="gender" class="form-select" required>
                                    <option value="male" <?php echo $student['gender'] === 'male' ? 'selected' : ''; ?>>Male</option>
                                    <option value="female" <?php echo $student['gender'] === 'female' ? 'selected' : ''; ?>>Female</option>
                                    <option value="other" <?php echo $student['gender'] === 'other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" required
                                       value="<?php echo htmlspecialchars($student['email']); ?>">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="tel" name="phone" class="form-control"
                                       value="<?php echo htmlspecialchars($student['phone']); ?>">
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">Address</label>
                                <textarea name="address" class="form-control" rows="2"><?php echo htmlspecialchars($student['address']); ?></textarea>
                            </div>

                            <!-- Academic Information -->
                            <h5 class="mb-3 mt-4">Academic Information</h5>
                            
                            <div class="col-md-6">
                                <label class="form-label">Class</label>
                                <select name="class_id" class="form-select" required>
                                    <option value="">Select Class</option>
                                    <?php foreach ($classes as $class): ?>
                                        <option value="<?php echo $class['id']; ?>" 
                                                <?php echo $student['class_id'] == $class['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($class['class_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select" required>
                                    <option value="active" <?php echo $student['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo $student['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>

                            <!-- Parent/Guardian Information -->
                            <h5 class="mb-3 mt-4">Parent/Guardian Information</h5>
                            
                            <div class="col-md-12">
                                <label class="form-label">Parent/Guardian Name</label>
                                <input type="text" name="parent_name" class="form-control" required
                                       value="<?php echo htmlspecialchars($student['parent_name']); ?>">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Parent/Guardian Phone</label>
                                <input type="tel" name="parent_phone" class="form-control" required
                                       value="<?php echo htmlspecialchars($student['parent_phone']); ?>">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Parent/Guardian Email</label>
                                <input type="email" name="parent_email" class="form-control"
                                       value="<?php echo htmlspecialchars($student['parent_email']); ?>">
                            </div>

                            <div class="col-12 mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Student
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
