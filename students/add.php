<?php
require_once '../includes/config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Get all classes for the dropdown
$stmt = $conn->query("SELECT id, class_name FROM classes ORDER BY class_name");
$classes = $stmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Generate student ID (you might want to customize this)
        $year = date('Y');
        $stmt = $conn->query("SELECT MAX(CAST(SUBSTRING(student_id, 6) AS UNSIGNED)) as max_id FROM students WHERE student_id LIKE 'STU{$year}%'");
        $result = $stmt->fetch();
        $next_id = ($result['max_id'] ?? 0) + 1;
        $student_id = "STU{$year}" . str_pad($next_id, 4, '0', STR_PAD_LEFT);

        // Start transaction
        $conn->beginTransaction();

        // Create user account
        $username = strtolower($_POST['first_name'] . '.' . $_POST['last_name']);
        $email = $_POST['email'];
        $password = password_hash($student_id, PASSWORD_DEFAULT); // Use student ID as initial password

        $stmt = $conn->prepare("INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, 'student')");
        $stmt->execute([$username, $password, $email]);
        $user_id = $conn->lastInsertId();

        // Insert student
        $stmt = $conn->prepare("
            INSERT INTO students (
                user_id, student_id, first_name, last_name, date_of_birth, 
                gender, address, phone, class_id, parent_name, 
                parent_phone, parent_email, admission_date
            ) VALUES (
                ?, ?, ?, ?, ?, 
                ?, ?, ?, ?, ?,
                ?, ?, ?
            )
        ");

        $stmt->execute([
            $user_id, $student_id, $_POST['first_name'], $_POST['last_name'], $_POST['date_of_birth'],
            $_POST['gender'], $_POST['address'], $_POST['phone'], $_POST['class_id'], $_POST['parent_name'],
            $_POST['parent_phone'], $_POST['parent_email'], $_POST['admission_date']
        ]);

        $conn->commit();
        $success = "Student added successfully. Student ID: " . $student_id;
    } catch(PDOException $e) {
        $conn->rollBack();
        $error = "Error adding student: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Student - Student Management System</title>
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
                    <h2>Add New Student</h2>
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
                            <!-- Personal Information -->
                            <h5 class="mb-3">Personal Information</h5>
                            
                            <div class="col-md-6">
                                <label class="form-label">First Name</label>
                                <input type="text" name="first_name" class="form-control" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Last Name</label>
                                <input type="text" name="last_name" class="form-control" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Date of Birth</label>
                                <input type="date" name="date_of_birth" class="form-control" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Gender</label>
                                <select name="gender" class="form-select" required>
                                    <option value="">Select Gender</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="tel" name="phone" class="form-control">
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">Address</label>
                                <textarea name="address" class="form-control" rows="2"></textarea>
                            </div>

                            <!-- Academic Information -->
                            <h5 class="mb-3 mt-4">Academic Information</h5>
                            
                            <div class="col-md-6">
                                <label class="form-label">Class</label>
                                <select name="class_id" class="form-select" required>
                                    <option value="">Select Class</option>
                                    <?php foreach ($classes as $class): ?>
                                        <option value="<?php echo $class['id']; ?>">
                                            <?php echo htmlspecialchars($class['class_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Admission Date</label>
                                <input type="date" name="admission_date" class="form-control" required 
                                       value="<?php echo date('Y-m-d'); ?>">
                            </div>

                            <!-- Parent/Guardian Information -->
                            <h5 class="mb-3 mt-4">Parent/Guardian Information</h5>
                            
                            <div class="col-md-12">
                                <label class="form-label">Parent/Guardian Name</label>
                                <input type="text" name="parent_name" class="form-control" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Parent/Guardian Phone</label>
                                <input type="tel" name="parent_phone" class="form-control" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Parent/Guardian Email</label>
                                <input type="email" name="parent_email" class="form-control">
                            </div>

                            <div class="col-12 mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Add Student
                                </button>
                                <button type="reset" class="btn btn-secondary">
                                    <i class="fas fa-undo"></i> Reset
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
