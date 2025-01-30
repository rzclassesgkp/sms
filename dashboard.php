<?php
require_once 'includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user role
$role = $_SESSION['user_role'];

// Get user information
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Student Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background-color: #343a40;
        }
        .nav-link {
            color: #fff;
            padding: 10px 20px;
        }
        .nav-link:hover {
            background-color: #495057;
            color: #fff;
        }
        .nav-link.active {
            background-color: #0d6efd;
        }
        .main-content {
            padding: 20px;
        }
        .card-counter {
            padding: 20px;
            border-radius: 5px;
            color: #fff;
            transition: .3s linear all;
        }
        .card-counter i {
            font-size: 4em;
            opacity: 0.4;
        }
        .card-counter .count {
            font-size: 2em;
        }
        .bg-info {
            background-color: #17a2b8;
        }
        .bg-warning {
            background-color: #ffc107;
        }
        .bg-success {
            background-color: #28a745;
        }
        .bg-danger {
            background-color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0 sidebar">
                <div class="text-center py-4">
                    <i class="fas fa-graduation-cap fa-3x text-white"></i>
                    <h4 class="text-white mt-2">SMS</h4>
                </div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                        </a>
                    </li>
                    <?php if ($role === 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="students/index.php">
                            <i class="fas fa-user-graduate me-2"></i> Students
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="teachers/index.php">
                            <i class="fas fa-chalkboard-teacher me-2"></i> Teachers
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="classes/index.php">
                            <i class="fas fa-chalkboard me-2"></i> Classes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="subjects/index.php">
                            <i class="fas fa-book me-2"></i> Subjects
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="attendance/index.php">
                            <i class="fas fa-calendar-check me-2"></i> Attendance
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="grades/index.php">
                            <i class="fas fa-chart-line me-2"></i> Grades
                        </a>
                    </li>
                    <?php if ($role === 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="fees/index.php">
                            <i class="fas fa-money-bill-wave me-2"></i> Fees
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reports/index.php">
                            <i class="fas fa-file-alt me-2"></i> Reports
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item mt-4">
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Dashboard</h2>
                    <div class="user-info">
                        <span class="me-2"><?php echo htmlspecialchars($user['username']); ?></span>
                        <span class="badge bg-primary"><?php echo ucfirst($role); ?></span>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row">
                    <div class="col-md-3 mb-4">
                        <div class="card-counter bg-info">
                            <i class="fas fa-user-graduate"></i>
                            <span class="count">
                                <?php
                                $stmt = $conn->query("SELECT COUNT(*) FROM students");
                                echo $stmt->fetchColumn();
                                ?>
                            </span>
                            <span class="name">Students</span>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card-counter bg-warning">
                            <i class="fas fa-chalkboard-teacher"></i>
                            <span class="count">
                                <?php
                                $stmt = $conn->query("SELECT COUNT(*) FROM teachers");
                                echo $stmt->fetchColumn();
                                ?>
                            </span>
                            <span class="name">Teachers</span>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card-counter bg-success">
                            <i class="fas fa-chalkboard"></i>
                            <span class="count">
                                <?php
                                $stmt = $conn->query("SELECT COUNT(*) FROM classes");
                                echo $stmt->fetchColumn();
                                ?>
                            </span>
                            <span class="name">Classes</span>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card-counter bg-danger">
                            <i class="fas fa-book"></i>
                            <span class="count">
                                <?php
                                $stmt = $conn->query("SELECT COUNT(*) FROM subjects");
                                echo $stmt->fetchColumn();
                                ?>
                            </span>
                            <span class="name">Subjects</span>
                        </div>
                    </div>
                </div>

                <!-- Recent Activities -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Recent Attendance</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Student</th>
                                                <th>Date</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $stmt = $conn->query("
                                                SELECT a.*, s.first_name, s.last_name 
                                                FROM attendance a 
                                                JOIN students s ON a.student_id = s.id 
                                                ORDER BY a.date DESC LIMIT 5
                                            ");
                                            while ($row = $stmt->fetch()) {
                                                echo "<tr>";
                                                echo "<td>" . htmlspecialchars($row['first_name'] . " " . $row['last_name']) . "</td>";
                                                echo "<td>" . htmlspecialchars($row['date']) . "</td>";
                                                echo "<td><span class='badge bg-" . 
                                                    ($row['status'] == 'present' ? 'success' : 
                                                    ($row['status'] == 'absent' ? 'danger' : 'warning')) . 
                                                    "'>" . ucfirst($row['status']) . "</span></td>";
                                                echo "</tr>";
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Recent Fee Payments</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Student</th>
                                                <th>Amount</th>
                                                <th>Date</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $stmt = $conn->query("
                                                SELECT 
                                                    f.*, 
                                                    s.first_name, 
                                                    s.last_name,
                                                    COALESCE(
                                                        (SELECT payment_date 
                                                        FROM fee_payments 
                                                        WHERE fee_id = f.id 
                                                        ORDER BY payment_date DESC 
                                                        LIMIT 1),
                                                        f.created_at
                                                    ) as last_payment_date
                                                FROM fees f 
                                                JOIN students s ON f.student_id = s.id 
                                                ORDER BY last_payment_date DESC 
                                                LIMIT 5
                                            ");
                                            while ($row = $stmt->fetch()) {
                                                echo "<tr>";
                                                echo "<td>" . htmlspecialchars($row['first_name'] . " " . $row['last_name']) . "</td>";
                                                echo "<td>₹" . number_format($row['amount'], 2) . "</td>";
                                                echo "<td>" . ($row['last_payment_date'] ? date('Y-m-d', strtotime($row['last_payment_date'])) : '-') . "</td>";
                                                echo "<td><span class='badge bg-" . 
                                                    ($row['payment_status'] == 'paid' ? 'success' : 
                                                    ($row['payment_status'] == 'pending' ? 'danger' : 
                                                    ($row['payment_status'] == 'partial' ? 'warning' : 'dark'))) . 
                                                    "'>" . ucfirst($row['payment_status']) . "</span></td>";
                                                echo "</tr>";
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
