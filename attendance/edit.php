<?php
require_once '../includes/config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Get attendance ID from URL
$attendance_id = isset($_GET['id']) ? $_GET['id'] : null;
if (!$attendance_id) {
    header("Location: index.php");
    exit();
}

// Get attendance record
$stmt = $conn->prepare("
    SELECT a.*, 
    s.student_id as student_code,
    CONCAT(s.first_name, ' ', s.last_name) as student_name,
    c.class_name,
    sub.subject_name
    FROM attendance a
    JOIN students s ON a.student_id = s.id
    JOIN classes c ON a.class_id = c.id
    JOIN subjects sub ON a.subject_id = sub.id
    WHERE a.id = ?
");
$stmt->execute([$attendance_id]);
$attendance = $stmt->fetch();

if (!$attendance) {
    header("Location: index.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $conn->prepare("
            UPDATE attendance 
            SET status = ?, remarks = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $_POST['status'],
            $_POST['remarks'],
            $attendance_id
        ]);

        header("Location: index.php");
        exit();
    } catch(PDOException $e) {
        $error = "Error updating attendance: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Attendance - Student Management System</title>
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
                    <h2>Edit Attendance Record</h2>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <h6>Student ID</h6>
                                <p class="text-muted"><?php echo htmlspecialchars($attendance['student_code']); ?></p>
                            </div>
                            <div class="col-md-3">
                                <h6>Student Name</h6>
                                <p class="text-muted"><?php echo htmlspecialchars($attendance['student_name']); ?></p>
                            </div>
                            <div class="col-md-3">
                                <h6>Class</h6>
                                <p class="text-muted"><?php echo htmlspecialchars($attendance['class_name']); ?></p>
                            </div>
                            <div class="col-md-3">
                                <h6>Subject</h6>
                                <p class="text-muted"><?php echo htmlspecialchars($attendance['subject_name']); ?></p>
                            </div>
                        </div>

                        <form method="POST" class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Date</label>
                                <input type="date" class="form-control" value="<?php echo date('Y-m-d', strtotime($attendance['date'])); ?>" readonly>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <div class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="status" id="present" value="present"
                                           <?php echo $attendance['status'] === 'present' ? 'checked' : ''; ?> required>
                                    <label class="btn btn-outline-success" for="present">Present</label>

                                    <input type="radio" class="btn-check" name="status" id="absent" value="absent"
                                           <?php echo $attendance['status'] === 'absent' ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-danger" for="absent">Absent</label>

                                    <input type="radio" class="btn-check" name="status" id="late" value="late"
                                           <?php echo $attendance['status'] === 'late' ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-warning" for="late">Late</label>
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">Remarks</label>
                                <textarea name="remarks" class="form-control" rows="3"
                                          placeholder="Optional remarks"><?php echo htmlspecialchars($attendance['remarks']); ?></textarea>
                            </div>

                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Attendance
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
