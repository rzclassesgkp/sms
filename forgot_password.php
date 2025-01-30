<?php
require_once 'includes/config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = clean_input($_POST['student_id']);

    if (empty($student_id)) {
        $error = "Student ID is required.";
    } else {
        // Insert password recovery logic here (e.g., send recovery email)
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
</head>
<body>
    <div class="container">
        <div class="forgot-password-container">
            <h2>Forgot Password</h2>
            <form method="POST" action="">
                <input type="text" name="student_id" placeholder="Student ID" required>
                <button type="submit">Recover Password</button>
            </form>
            <?php if ($error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
