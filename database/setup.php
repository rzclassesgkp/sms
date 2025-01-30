<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sms_db');

try {
    // Connect without database first
    $conn = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if not exists
    $conn->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
    $conn->exec("USE " . DB_NAME);
    echo "Database created successfully.<br>";

    // Drop tables in correct order (child tables first)
    $conn->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    $conn->exec("DROP TABLE IF EXISTS fee_payments");
    $conn->exec("DROP TABLE IF EXISTS fees");
    $conn->exec("DROP TABLE IF EXISTS fee_types");
    $conn->exec("DROP TABLE IF EXISTS attendance");
    $conn->exec("DROP TABLE IF EXISTS grades");
    $conn->exec("DROP TABLE IF EXISTS class_subjects");
    $conn->exec("DROP TABLE IF EXISTS teachers");
    $conn->exec("DROP TABLE IF EXISTS students");
    $conn->exec("DROP TABLE IF EXISTS subjects");
    $conn->exec("DROP TABLE IF EXISTS classes");
    $conn->exec("DROP TABLE IF EXISTS users");
    
    $conn->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "Existing tables dropped successfully.<br>";

    // Create users table
    $users_table = "CREATE TABLE IF NOT EXISTS users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'teacher', 'student') NOT NULL,
        email VARCHAR(100),
        last_login TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    $conn->exec($users_table);
    echo "Users table created successfully.<br>";

    // Create classes table
    $classes_table = "CREATE TABLE IF NOT EXISTS classes (
        id INT PRIMARY KEY AUTO_INCREMENT,
        class_name VARCHAR(50) NOT NULL,
        section VARCHAR(10),
        capacity INT DEFAULT 40,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    $conn->exec($classes_table);
    echo "Classes table created successfully.<br>";

    // Create students table
    $students_table = "CREATE TABLE IF NOT EXISTS students (
        id INT PRIMARY KEY AUTO_INCREMENT,
        student_id VARCHAR(20) UNIQUE NOT NULL,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        date_of_birth DATE,
        gender ENUM('male', 'female', 'other'),
        address TEXT,
        phone VARCHAR(10),
        email VARCHAR(100),
        class_id INT,
        user_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (class_id) REFERENCES classes(id),
        FOREIGN KEY (user_id) REFERENCES users(id)
    )";
    
    $conn->exec($students_table);
    echo "Students table created successfully.<br>";

    // Create teachers table
    $teachers_table = "CREATE TABLE IF NOT EXISTS teachers (
        id INT PRIMARY KEY AUTO_INCREMENT,
        teacher_id VARCHAR(20) UNIQUE NOT NULL,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        date_of_birth DATE,
        gender ENUM('male', 'female', 'other'),
        address TEXT,
        phone VARCHAR(20),
        email VARCHAR(100),
        subject VARCHAR(50),
        qualification VARCHAR(100),
        user_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )";
    
    $conn->exec($teachers_table);
    echo "Teachers table created successfully.<br>";

    // Alter teachers table to add joining_date column if it doesn't exist
    $alter_teachers_table = "ALTER TABLE teachers ADD COLUMN IF NOT EXISTS joining_date DATE AFTER qualification";
    $conn->exec($alter_teachers_table);
    echo "Teachers table altered successfully.<br>";

    // Create subjects table
    $subjects_table = "CREATE TABLE IF NOT EXISTS subjects (
        id INT PRIMARY KEY AUTO_INCREMENT,
        subject_name VARCHAR(100) NOT NULL,
        subject_code VARCHAR(20) UNIQUE NOT NULL,
        description TEXT,
        credits INT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    $conn->exec($subjects_table);
    echo "Subjects table created successfully.<br>";

    // Create class_subjects table
    $class_subjects_table = "CREATE TABLE IF NOT EXISTS class_subjects (
        id INT PRIMARY KEY AUTO_INCREMENT,
        class_id INT NOT NULL,
        subject_id INT NOT NULL,
        teacher_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (class_id) REFERENCES classes(id),
        FOREIGN KEY (subject_id) REFERENCES subjects(id),
        FOREIGN KEY (teacher_id) REFERENCES teachers(id)
    )";
    
    $conn->exec($class_subjects_table);
    echo "Class Subjects table created successfully.<br>";

    // Create attendance table
    $attendance_table = "CREATE TABLE IF NOT EXISTS attendance (
        id INT PRIMARY KEY AUTO_INCREMENT,
        student_id INT NOT NULL,
        class_id INT NOT NULL,
        subject_id INT NOT NULL,
        date DATE NOT NULL,
        status ENUM('present', 'absent', 'late') DEFAULT 'present',
        remarks TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES students(id),
        FOREIGN KEY (class_id) REFERENCES classes(id),
        FOREIGN KEY (subject_id) REFERENCES subjects(id)
    )";
    
    $conn->exec($attendance_table);
    echo "Attendance table created successfully.<br>";

    // Create grades table
    $grades_table = "CREATE TABLE IF NOT EXISTS grades (
        id INT PRIMARY KEY AUTO_INCREMENT,
        student_id INT NOT NULL,
        class_id INT NOT NULL,
        subject_id INT NOT NULL,
        exam_type VARCHAR(50) NOT NULL,
        marks DECIMAL(5,2) NOT NULL,
        grade_letter VARCHAR(2),
        remarks TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES students(id),
        FOREIGN KEY (class_id) REFERENCES classes(id),
        FOREIGN KEY (subject_id) REFERENCES subjects(id)
    )";
    
    $conn->exec($grades_table);
    echo "Grades table created successfully.<br>";

    // Create fee_types table
    $fee_types_table = "CREATE TABLE IF NOT EXISTS fee_types (
        id INT PRIMARY KEY AUTO_INCREMENT,
        type_name VARCHAR(100) NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        frequency ENUM('monthly', 'quarterly', 'yearly', 'one-time') DEFAULT 'monthly',
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    $conn->exec($fee_types_table);
    echo "Fee Types table created successfully.<br>";

    // Create fees table
    $fees_table = "CREATE TABLE IF NOT EXISTS fees (
        id INT PRIMARY KEY AUTO_INCREMENT,
        student_id INT NOT NULL,
        fee_type_id INT NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        due_date DATE NOT NULL,
        status ENUM('paid', 'pending', 'partial', 'overdue') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES students(id),
        FOREIGN KEY (fee_type_id) REFERENCES fee_types(id)
    )";
    
    $conn->exec($fees_table);
    echo "Fees table created successfully.<br>";

    // Create fee_payments table
    $fee_payments_table = "CREATE TABLE IF NOT EXISTS fee_payments (
        id INT PRIMARY KEY AUTO_INCREMENT,
        fee_id INT NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        payment_date DATE NOT NULL,
        payment_method ENUM('cash', 'card', 'upi', 'bank_transfer', 'cheque') NOT NULL,
        transaction_id VARCHAR(100),
        remarks TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (fee_id) REFERENCES fees(id)
    )";
    
    $conn->exec($fee_payments_table);
    echo "Fee Payments table created successfully.<br>";

    // Insert default admin users
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $admin2_password = password_hash('admin456', PASSWORD_DEFAULT);
    
    $insert_admin = "INSERT INTO users (username, password, role, email) VALUES 
        ('admin', :password1, 'admin', 'admin@school.com'),
        ('admin2', :password2, 'admin', 'admin2@school.com')";
    
    $stmt = $conn->prepare($insert_admin);
    $stmt->execute([
        ':password1' => $admin_password,
        ':password2' => $admin2_password
    ]);
    echo "Default admin users created successfully.<br>";
    echo "Admin credentials:<br>";
    echo "Username: admin<br>";
    echo "Password: admin123<br>";
    echo "Username: admin2<br>";
    echo "Password: admin456<br>";

    // Insert sample data
    // Sample Classes
    $classes = [
        ['Class 1', 'A'],
        ['Class 2', 'A'],
        ['Class 3', 'A']
    ];

    $insert_class = $conn->prepare("INSERT INTO classes (class_name, section) VALUES (?, ?)");
    foreach ($classes as $class) {
        $insert_class->execute($class);
    }
    echo "Sample classes added successfully.<br>";

    // Sample Subjects
    $subjects = [
        ['Mathematics', 'MATH101', 'Basic Mathematics', 4],
        ['Science', 'SCI101', 'General Science', 4],
        ['English', 'ENG101', 'English Language', 3]
    ];

    $insert_subject = $conn->prepare("INSERT INTO subjects (subject_name, subject_code, description, credits) VALUES (?, ?, ?, ?)");
    foreach ($subjects as $subject) {
        $insert_subject->execute($subject);
    }
    echo "Sample subjects added successfully.<br>";

    // Sample Fee Types
    $fee_types = [
        ['Tuition Fee', 5000.00, 'monthly', 'Monthly tuition fee'],
        ['Registration Fee', 1000.00, 'one-time', 'One-time registration fee'],
        ['Library Fee', 500.00, 'yearly', 'Annual library fee']
    ];

    $insert_fee_type = $conn->prepare("INSERT INTO fee_types (type_name, amount, frequency, description) VALUES (?, ?, ?, ?)");
    foreach ($fee_types as $fee_type) {
        $insert_fee_type->execute($fee_type);
    }
    echo "Sample fee types added successfully.<br>";

    echo "<div class='alert alert-success'>Database setup completed successfully!</div>";

} catch(PDOException $e) {
    echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
}
