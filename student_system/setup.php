<?php
require_once 'config.php';

// Function to create tables and insert admin user
function setupDatabase() {
    global $conn;
    
    // Create users table
    $users_table = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if (!$conn->query($users_table)) {
        die("Error creating users table: " . $conn->error);
    }
    
    // Create students table
    $students_table = "CREATE TABLE IF NOT EXISTS students (
        student_id VARCHAR(20) PRIMARY KEY,
        full_name VARCHAR(100) NOT NULL,
        program VARCHAR(50) NOT NULL,
        email VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if (!$conn->query($students_table)) {
        die("Error creating students table: " . $conn->error);
    }
    
    // Create subjects table with foreign key
    $subjects_table = "CREATE TABLE IF NOT EXISTS subjects (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id VARCHAR(20),
        subject_name VARCHAR(100) NOT NULL,
        marks DECIMAL(5,2) CHECK (marks >= 0 AND marks <= 100),
        FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE
    )";
    
    if (!$conn->query($subjects_table)) {
        die("Error creating subjects table: " . $conn->error);
    }
    
    // Check if admin user exists
    $check_admin = $conn->query("SELECT * FROM users WHERE username = 'admin'");
    
    if ($check_admin->num_rows == 0) {
        // Hash the password
        $hashed_password = password_hash('admin123', PASSWORD_DEFAULT);
        
        // Insert admin user
        $insert_admin = $conn->prepare("INSERT INTO users (username, password, full_name) VALUES (?, ?, ?)");
        $admin_name = 'Administrator';
        $insert_admin->bind_param("sss", $username, $hashed_password, $admin_name);
        $username = 'admin';
        
        if ($insert_admin->execute()) {
            echo "<div style='color: green; padding: 10px; margin: 10px; border: 1px solid green; border-radius: 5px;'>✓ Admin user created successfully!<br>Username: admin<br>Password: admin123</div>";
        } else {
            echo "<div style='color: red; padding: 10px; margin: 10px; border: 1px solid red; border-radius: 5px;'>✗ Error creating admin user: " . $conn->error . "</div>";
        }
        $insert_admin->close();
    } else {
        echo "<div style='color: blue; padding: 10px; margin: 10px; border: 1px solid blue; border-radius: 5px;'>ℹ Admin user already exists.</div>";
    }
    
    echo "<div style='color: green; padding: 10px; margin: 10px; border: 1px solid green; border-radius: 5px;'>✓ Database setup completed successfully!</div>";
}

// Run the setup
setupDatabase();

// Display setup complete message with link to login
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup</title>
    <style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        margin: 0;
        padding: 20px;
    }

    .setup-container {
        background: white;
        border-radius: 10px;
        padding: 30px;
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        max-width: 600px;
        width: 100%;
    }

    h1 {
        color: #333;
        text-align: center;
        margin-bottom: 30px;
    }

    .login-link {
        display: inline-block;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        text-decoration: none;
        padding: 12px 30px;
        border-radius: 5px;
        font-weight: bold;
        margin-top: 20px;
        transition: transform 0.3s;
    }

    .login-link:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }

    .info-box {
        background: #f8f9fa;
        border-left: 4px solid #667eea;
        padding: 15px;
        margin: 20px 0;
        border-radius: 5px;
    }

    .credentials {
        background: #e8f4fd;
        padding: 15px;
        border-radius: 5px;
        font-family: monospace;
        margin: 15px 0;
    }
    </style>
</head>

<body>
    <div class="setup-container">
        <h1>📚 Student Management System Setup</h1>

        <div class="info-box">
            <strong>Setup Status:</strong>
            <?php
            // Re-run setup to show messages
            setupDatabase();
            ?>
        </div>

        <div class="credentials">
            <strong>Login Credentials:</strong><br>
            Username: <code>admin</code><br>
            Password: <code>admin123</code>
        </div>

        <div style="text-align: center;">
            <a href="index.php" class="login-link">Go to Login Page</a>
        </div>

        <div style="margin-top: 20px; font-size: 12px; color: #666; text-align: center;">
            <p>If you encounter any issues, make sure:</p>
            <ul style="text-align: left;">
                <li>MySQL server is running</li>
                <li>Database 'student_db' exists (it will be created automatically)</li>
                <li>Your config.php has correct database credentials</li>
            </ul>
        </div>
    </div>
</body>

</html>