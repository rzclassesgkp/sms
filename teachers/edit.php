<?php
require_once '../includes/config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
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
    SELECT 
        t.id,
        t.teacher_id,
        t.first_name,
        t.last_name,
        t.date_of_birth,
        t.gender,
        t.address,
        t.phone,
        t.email as teacher_email,
        t.subject,
        t.qualification,
        t.user_id,
        u.email as user_email
    FROM teachers t 
    LEFT JOIN users u ON t.user_id = u.id 
    WHERE t.id = ?
");
$stmt->execute([$teacher_id]);
$teacher = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$teacher) {
    header("Location: index.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->beginTransaction();

        // Update user email if user account exists
        if ($teacher['user_id']) {
            $stmt = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
            $stmt->execute([$_POST['email'], $teacher['user_id']]);
        }

        // Update teacher
        $stmt = $conn->prepare("
            UPDATE teachers SET 
                first_name = ?, 
                last_name = ?, 
                date_of_birth = ?,
                gender = ?,
                address = ?,
                phone = ?, 
                email = ?,
                subject = ?,
                qualification = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $_POST['first_name'],
            $_POST['last_name'],
            $_POST['date_of_birth'],
            $_POST['gender'],
            $_POST['address'],
            $_POST['phone'],
            $_POST['email'],
            $_POST['subject'],
            $_POST['qualification'],
            $teacher_id
        ]);

        $conn->commit();
        header("Location: index.php?success=1");
        exit();
    } catch (PDOException $e) {
        $conn->rollBack();
        $error = "Error updating teacher: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Teacher - Student Management System</title>
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
                    <h2>Edit Teacher</h2>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Teachers
                    </a>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <form method="POST" class="row g-3">
                            <!-- Personal Information -->
                            <h5 class="mb-3">Personal Information</h5>
                            
                            <div class="col-md-6">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                       value="<?php echo htmlspecialchars($teacher['first_name']); ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" 
                                       value="<?php echo htmlspecialchars($teacher['last_name']); ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label for="date_of_birth" class="form-label">Date of Birth</label>
                                <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" 
                                       value="<?php echo $teacher['date_of_birth']; ?>">
                            </div>

                            <div class="col-md-6">
                                <label for="gender" class="form-label">Gender</label>
                                <select class="form-select" id="gender" name="gender">
                                    <option value="">Select Gender</option>
                                    <option value="male" <?php echo $teacher['gender'] === 'male' ? 'selected' : ''; ?>>Male</option>
                                    <option value="female" <?php echo $teacher['gender'] === 'female' ? 'selected' : ''; ?>>Female</option>
                                    <option value="other" <?php echo $teacher['gender'] === 'other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($teacher['address']); ?></textarea>
                            </div>

                            <!-- Contact Information -->
                            <h5 class="mb-3 mt-4">Contact Information</h5>

                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($teacher['teacher_email']); ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($teacher['phone']); ?>">
                            </div>

                            <!-- Professional Information -->
                            <h5 class="mb-3 mt-4">Professional Information</h5>

                            <div class="col-md-6">
                                <label for="subject" class="form-label">Subject</label>
                                <input type="text" class="form-control" id="subject" name="subject" 
                                       value="<?php echo htmlspecialchars($teacher['subject']); ?>">
                            </div>

                            <div class="col-md-6">
                                <label for="qualification" class="form-label">Qualification</label>
                                <input type="text" class="form-control" id="qualification" name="qualification" 
                                       value="<?php echo htmlspecialchars($teacher['qualification']); ?>">
                            </div>

                            <div class="col-12 mt-4">
                                <button type="submit" class="btn btn-primary">Update Teacher</button>
                                <a href="index.php" class="btn btn-secondary">Cancel</a>
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
