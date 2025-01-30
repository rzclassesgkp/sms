<?php
// Start session
session_start();

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sms_db');

// Create database connection
try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    // If database doesn't exist and we're not on setup page, redirect to setup
    if ($e->getCode() == 1049 && basename($_SERVER['PHP_SELF']) !== 'setup.php') {
        header("Location: /SMS/database/setup.php");
        exit();
    }
    die("Connection failed: " . $e->getMessage());
}

// Function to clean input data
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Function to generate a unique ID
function generate_unique_id($prefix = '', $length = 8) {
    $bytes = random_bytes(ceil($length / 2));
    return $prefix . bin2hex($bytes);
}

// Function to format date
function format_date($date) {
    return date('d M Y', strtotime($date));
}

// Function to format currency
function format_currency($amount) {
    return '₹ ' . number_format($amount, 2);
}

// Function to check if user has permission
function has_permission($required_role) {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $required_role;
}

// Function to get user details
function get_user_details($user_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
