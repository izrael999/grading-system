<?php
// Database configuration
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'student_db';

// Create connection without database
$conn = new mysqli($host, $user, $password);

// Check connection
if ($conn->connect_error) {
    die("<h2 style='color:red'>❌ Connection failed: " . $conn->connect_error . "</h2>");
}

// Create database if not exists
$conn->query("CREATE DATABASE IF NOT EXISTS $database");
$conn->select_db($database);

// Drop existing tables (clean install)
$conn->query("DROP TABLE IF EXISTS activity_log");
$conn->query("DROP TABLE IF EXISTS subjects");
$conn->query("DROP TABLE IF EXISTS students");
$conn->query("DROP TABLE IF EXISTS users");

echo "<!DOCTYPE html>
<html>
<head>
    <title>Student Management System - Installation</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .install-container {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 800px;
            width: 100%;
        }
        h1 { color: #333; margin-bottom: 20px; text-align: center; }
        h2 { color: #4a5568; margin: 20px 0 10px; }
        .success { 
            background: #c6f6d5; 
            color: #22543d; 
            padding: 10px 15px; 
            border-radius: 5px; 
            margin: 5px 0;
            border-left: 4px solid #48bb78;
        }
        .info {
            background: #bee3f8;
            color: #2c5282;
            padding: 10px 15px;
            border-radius: 5px;
            margin: 5px 0;
            border-left: 4px solid #4299e1;
        }
        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            padding: 12px 30px;
            border-radius: 5px;
            font-weight: bold;
            margin-top: 20px;
            transition: transform 0.3s;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .credentials {
            background: #f7fafc;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            border: 1px solid #e2e8f0;
        }
        code {
            background: #edf2f7;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 14px;
        }
    </style>
</head>
<body>
<div class='install-container'>
    <h1>📚 Student Management System Installation</h1>";

// Create users table with all necessary fields
$users_table = "CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    avatar VARCHAR(255) DEFAULT NULL,
    avatar_color VARCHAR(7) DEFAULT '#3b82f6',
    theme_preference VARCHAR(10) DEFAULT 'dark',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($users_table)) {
    echo "<div class='success'>✓ Users table created successfully</div>";
}

// Create students table with all necessary fields
$students_table = "CREATE TABLE students (
    student_id VARCHAR(20) PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    program VARCHAR(50) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    enrollment_date DATE,
    status ENUM('active', 'inactive', 'graduated', 'suspended') DEFAULT 'active',
    avatar VARCHAR(255) DEFAULT NULL,
    avatar_color VARCHAR(7),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($students_table)) {
    echo "<div class='success'>✓ Students table created successfully</div>";
}

// Create subjects table
$subjects_table = "CREATE TABLE subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20),
    subject_name VARCHAR(100) NOT NULL,
    marks DECIMAL(5,2),
    semester INT,
    academic_year VARCHAR(20),
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    CHECK (marks >= 0 AND marks <= 100)
)";

if ($conn->query($subjects_table)) {
    echo "<div class='success'>✓ Subjects table created successfully</div>";
}

// Create activity_log table
$activity_log = "CREATE TABLE activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
)";

if ($conn->query($activity_log)) {
    echo "<div class='success'>✓ Activity log table created successfully</div>";
}

// Hash the password
$hashed_password = password_hash('admin123', PASSWORD_DEFAULT);

// Insert admin user
$stmt = $conn->prepare("INSERT INTO users (username, password, full_name, email, avatar_color) VALUES (?, ?, ?, ?, ?)");
$username = 'admin';
$full_name = 'Administrator';
$email = 'admin@school.com';
$avatar_color = '#3b82f6';
$stmt->bind_param("sssss", $username, $hashed_password, $full_name, $email, $avatar_color);

if ($stmt->execute()) {
    echo "<div class='success'>✓ Admin user created successfully</div>";
}

// Insert sample students with avatar colors
$sample_students = [
    ['STU001', 'John Smith', 'Computer Science', 'john@email.com', '1234567890', '123 Main St', '2024-01-15'],
    ['STU002', 'Sarah Johnson', 'Information Technology', 'sarah@email.com', '1234567891', '456 Oak Ave', '2024-01-15'],
    ['STU003', 'Mike Chen', 'Engineering', 'mike@email.com', '1234567892', '789 Pine Rd', '2024-01-16'],
    ['STU004', 'Emily Brown', 'Business', 'emily@email.com', '1234567893', '321 Elm St', '2024-01-16'],
    ['STU005', 'David Wilson', 'Mathematics', 'david@email.com', '1234567894', '654 Maple Dr', '2024-01-17']
];

function getAvatarColor($name) {
    $colors = ['#f44336', '#e91e63', '#9c27b0', '#673ab7', '#3f51b5', '#2196f3', '#03a9f4', '#00bcd4', '#009688', '#4caf50', '#8bc34a', '#cddc39', '#ffc107', '#ff9800', '#ff5722'];
    $index = crc32($name) % count($colors);
    return $colors[$index];
}

$student_stmt = $conn->prepare("INSERT INTO students (student_id, full_name, program, email, phone, address, enrollment_date, avatar_color) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

foreach ($sample_students as $student) {
    $avatar_color = getAvatarColor($student[1]);
    $student_stmt->bind_param("ssssssss", $student[0], $student[1], $student[2], $student[3], $student[4], $student[5], $student[6], $avatar_color);
    $student_stmt->execute();
}
echo "<div class='success'>✓ Sample students added</div>";

// Sample subjects
$sample_subjects = [
    ['STU001', 'Mathematics', 85, 1, '2024'],
    ['STU001', 'Physics', 78, 1, '2024'],
    ['STU001', 'Programming', 92, 1, '2024'],
    ['STU002', 'Database', 88, 1, '2024'],
    ['STU002', 'Networking', 82, 1, '2024'],
    ['STU002', 'Web Development', 90, 1, '2024'],
    ['STU003', 'Calculus', 75, 1, '2024'],
    ['STU003', 'Mechanics', 80, 1, '2024'],
    ['STU003', 'Thermodynamics', 72, 1, '2024'],
    ['STU004', 'Marketing', 89, 1, '2024'],
    ['STU004', 'Finance', 84, 1, '2024'],
    ['STU004', 'Economics', 87, 1, '2024'],
    ['STU005', 'Algebra', 94, 1, '2024'],
    ['STU005', 'Statistics', 91, 1, '2024'],
    ['STU005', 'Geometry', 86, 1, '2024']
];

$subject_stmt = $conn->prepare("INSERT INTO subjects (student_id, subject_name, marks, semester, academic_year) VALUES (?, ?, ?, ?, ?)");

foreach ($sample_subjects as $subject) {
    $subject_stmt->bind_param("ssdis", $subject[0], $subject[1], $subject[2], $subject[3], $subject[4]);
    $subject_stmt->execute();
}
echo "<div class='success'>✓ Sample subjects added</div>";

// Create upload directories with proper permissions
if (!file_exists('uploads')) {
    mkdir('uploads', 0777, true);
    echo "<div class='info'>✓ Created uploads directory</div>";
}
if (!file_exists('uploads/avatars')) {
    mkdir('uploads/avatars', 0777, true);
    echo "<div class='info'>✓ Created uploads/avatars directory</div>";
}

// Create .htaccess file to allow access to uploads
$htaccess = "uploads/.htaccess";
if (!file_exists($htaccess)) {
    file_put_contents($htaccess, "Options -Indexes\nAllow from all");
    echo "<div class='info'>✓ Created .htaccess for uploads</div>";
}

echo "<div class='credentials'>";
echo "<h3>✅ Installation Complete!</h3>";
echo "<p><strong>Login Credentials:</strong></p>";
echo "<p>Username: <code>admin</code></p>";
echo "<p>Password: <code>admin123</code></p>";
echo "</div>";

echo "<div style='text-align: center;'>";
echo "<a href='index.php' class='btn'>Go to Login Page</a>";
echo "</div>";

echo "</div></body></html>";

$conn->close();
?>