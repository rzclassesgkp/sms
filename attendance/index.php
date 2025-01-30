<?php
require_once '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Get filter parameters
$class_id = isset($_GET['class_id']) ? $_GET['class_id'] : '';
$subject_id = isset($_GET['subject_id']) ? $_GET['subject_id'] : '';
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Get all classes for filter
$stmt = $conn->query("SELECT id, class_name FROM classes ORDER BY class_name");
$classes = $stmt->fetchAll();

// Get all subjects for filter
$stmt = $conn->query("SELECT id, subject_name FROM subjects ORDER BY subject_name");
$subjects = $stmt->fetchAll();

// Build attendance query
$query = "
    SELECT a.*, 
    s.student_id as student_code, 
    CONCAT(s.first_name, ' ', s.last_name) as student_name,
    c.class_name,
    sub.subject_name
    FROM attendance a
    JOIN students s ON a.student_id = s.id
    JOIN classes c ON a.class_id = c.id
    JOIN subjects sub ON a.subject_id = sub.id
    WHERE 1=1
";

$params = [];

if ($class_id) {
    $query .= " AND a.class_id = ?";
    $params[] = $class_id;
}

if ($subject_id) {
    $query .= " AND a.subject_id = ?";
    $params[] = $subject_id;
}

if ($date) {
    $query .= " AND DATE(a.date) = ?";
    $params[] = $date;
}

if ($status) {
    $query .= " AND a.status = ?";
    $params[] = $status;
}

$query .= " ORDER BY a.date DESC, c.class_name, s.first_name, s.last_name";

// Get attendance records
$stmt = $conn->prepare($query);
$stmt->execute($params);
$attendance_records = $stmt->fetchAll();

// Get attendance statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_records,
        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
        SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count,
        SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_count
    FROM attendance
    WHERE 1=1
";

if ($class_id) {
    $stats_query .= " AND class_id = ?";
}
if ($subject_id) {
    $stats_query .= " AND subject_id = ?";
}
if ($date) {
    $stats_query .= " AND DATE(date) = ?";
}

$stmt = $conn->prepare($stats_query);
$stmt->execute($params);
$stats = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance - Student Management System</title>
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
                    <h2>Attendance Records</h2>
                    <?php if ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'teacher'): ?>
                    <a href="take.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Take Attendance
                    </a>
                    <?php endif; ?>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Records</h5>
                                <h3><?php echo $stats['total_records']; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">Present</h5>
                                <h3><?php echo $stats['present_count']; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-danger text-white">
                            <div class="card-body">
                                <h5 class="card-title">Absent</h5>
                                <h3><?php echo $stats['absent_count']; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h5 class="card-title">Late</h5>
                                <h3><?php echo $stats['late_count']; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Class</label>
                                <select name="class_id" class="form-select">
                                    <option value="">All Classes</option>
                                    <?php foreach ($classes as $class): ?>
                                        <option value="<?php echo $class['id']; ?>" 
                                                <?php echo $class_id == $class['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($class['class_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label">Subject</label>
                                <select name="subject_id" class="form-select">
                                    <option value="">All Subjects</option>
                                    <?php foreach ($subjects as $subject): ?>
                                        <option value="<?php echo $subject['id']; ?>"
                                                <?php echo $subject_id == $subject['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($subject['subject_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label">Date</label>
                                <input type="date" name="date" class="form-control" 
                                       value="<?php echo $date; ?>">
                            </div>
                            
                            <div class="col-md-2">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="">All Status</option>
                                    <option value="present" <?php echo $status === 'present' ? 'selected' : ''; ?>>Present</option>
                                    <option value="absent" <?php echo $status === 'absent' ? 'selected' : ''; ?>>Absent</option>
                                    <option value="late" <?php echo $status === 'late' ? 'selected' : ''; ?>>Late</option>
                                </select>
                            </div>

                            <div class="col-md-1">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Attendance Records -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Student ID</th>
                                        <th>Student Name</th>
                                        <th>Class</th>
                                        <th>Subject</th>
                                        <th>Status</th>
                                        <th>Remarks</th>
                                        <?php if ($_SESSION['user_role'] === 'admin'): ?>
                                        <th>Actions</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($attendance_records)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center">No attendance records found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($attendance_records as $record): ?>
                                            <tr>
                                                <td><?php echo date('M d, Y', strtotime($record['date'])); ?></td>
                                                <td><?php echo htmlspecialchars($record['student_code']); ?></td>
                                                <td><?php echo htmlspecialchars($record['student_name']); ?></td>
                                                <td><?php echo htmlspecialchars($record['class_name']); ?></td>
                                                <td><?php echo htmlspecialchars($record['subject_name']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $record['status'] === 'present' ? 'success' : 
                                                            ($record['status'] === 'absent' ? 'danger' : 'warning'); 
                                                    ?>">
                                                        <?php echo ucfirst($record['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($record['remarks']); ?></td>
                                                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                                                <td>
                                                    <a href="edit.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-warning">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                </td>
                                                <?php endif; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
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
