<?php
require_once 'includes/config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = clean_input($_POST['student_id']);
    $password = $_POST['password'];
    $first_name = clean_input($_POST['first_name']);
    $last_name = clean_input($_POST['last_name']);

    if (empty($student_id) || empty($password) || empty($first_name) || empty($last_name)) {
        $error = "All fields are required.";
    } else {
        // Insert registration logic here (e.g., save to database)
        // Redirect to login page after successful registration
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
</head>
<body>
    <div class="container">
        <div class="signup-container">
            <h2>Sign Up</h2>
            <form method="POST" action="">
                <input type="text" name="first_name" placeholder="First Name" required>
                <input type="text" name="last_name" placeholder="Last Name" required>
                <input type="text" name="student_id" placeholder="Student ID" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit">Register</button>
            </form>
            <?php if ($error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
