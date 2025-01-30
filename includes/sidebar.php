<?php
if (!isset($role)) {
    $role = $_SESSION['user_role'];
}
?>
<div class="col-md-3 col-lg-2 px-0 sidebar">
    <div class="text-center py-4">
        <i class="fas fa-graduation-cap fa-3x text-white"></i>
        <h4 class="text-white mt-2">SMS</h4>
    </div>
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link" href="../dashboard.php">
                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
            </a>
        </li>
        <?php if ($role === 'admin'): ?>
        <li class="nav-item">
            <a class="nav-link" href="../students/index.php">
                <i class="fas fa-user-graduate me-2"></i> Students
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="../teachers/index.php">
                <i class="fas fa-chalkboard-teacher me-2"></i> Teachers
            </a>
        </li>
        <?php endif; ?>
        <li class="nav-item">
            <a class="nav-link" href="../classes/index.php">
                <i class="fas fa-chalkboard me-2"></i> Classes
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="../subjects/index.php">
                <i class="fas fa-book me-2"></i> Subjects
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="../attendance/index.php">
                <i class="fas fa-calendar-check me-2"></i> Attendance
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="../grades/index.php">
                <i class="fas fa-chart-line me-2"></i> Grades
            </a>
        </li>
        <?php if ($role === 'admin'): ?>
        <li class="nav-item">
            <a class="nav-link" href="../fees/index.php">
                <i class="fas fa-money-bill-wave me-2"></i> Fees
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="../reports/index.php">
                <i class="fas fa-file-alt me-2"></i> Reports
            </a>
        </li>
        <?php endif; ?>
        <li class="nav-item mt-4">
            <a href="<?php echo $base_url; ?>change_password.php" class="nav-link">
                <i class="fas fa-key me-2"></i> Change Password
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo $base_url; ?>logout.php" class="nav-link">
                <i class="fas fa-sign-out-alt me-2"></i> Logout
            </a>
        </li>
    </ul>
</div>
