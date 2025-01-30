<?php
require_once '../includes/config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Get all subjects for multi-select
$stmt = $conn->query("SELECT id, subject_name, subject_code FROM subjects ORDER BY subject_name");
$subjects = $stmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->beginTransaction();

        // Generate teacher ID (e.g., TCH001)
        $stmt = $conn->query("SELECT MAX(CAST(SUBSTRING(teacher_id, 4) AS UNSIGNED)) as max_id FROM teachers");
        $result = $stmt->fetch();
        $next_id = ($result['max_id'] ?? 0) + 1;
        $teacher_id = 'TCH' . str_pad($next_id, 3, '0', STR_PAD_LEFT);

        // Create user account
        $username = strtolower(str_replace(' ', '', $_POST['first_name'] . $_POST['last_name']));
        $password = password_hash($teacher_id, PASSWORD_DEFAULT); // Use teacher_id as initial password

        $stmt = $conn->prepare("
            INSERT INTO users (username, password, email, role) 
            VALUES (?, ?, ?, 'teacher')
        ");
        $stmt->execute([$username, $password, $_POST['email']]);
        $user_id = $conn->lastInsertId();

        // Insert teacher
        $stmt = $conn->prepare("
            INSERT INTO teachers (
                user_id, teacher_id, first_name, last_name, email, phone,
                qualification, joining_date, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $user_id,
            $teacher_id,
            $_POST['first_name'],
            $_POST['last_name'],
            $_POST['email'],
            $_POST['phone'],
            $_POST['qualification'],
            $_POST['joining_date'],
            $_POST['status']
        ]);

        $conn->commit();
        
        $success = "Teacher added successfully. Initial password is: " . $teacher_id;
        header("refresh:2;url=index.php");
    } catch(PDOException $e) {
        $conn->rollBack();
        $error = "Error adding teacher: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Teacher - Student Management System</title>
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
                    <h2>Add New Teacher</h2>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Teachers
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
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="tel" name="phone" class="form-control">
                            </div>

                            <!-- Professional Information -->
                            <h5 class="mb-3 mt-4">Professional Information</h5>
                            
                            <div class="col-md-12">
                                <label class="form-label">Qualification</label>
                                <textarea name="qualification" class="form-control" rows="2"></textarea>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Joining Date</label>
                                <input type="date" name="joining_date" class="form-control" required
                                       value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select" required>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>

                            <div class="col-12 mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Add Teacher
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
