<?php
require_once 'config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Database Update</title>
    <style>
        body { font-family: Arial; background: #0a0f1e; color: #fff; padding: 20px; }
        .success { color: #00ff88; }
        .container { max-width: 800px; margin: 0 auto; background: #141b2b; padding: 30px; border-radius: 10px; }
        .btn { display: inline-block; padding: 10px 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 5px; margin-top: 20px; }
    </style>
</head>
<body>
<div class='container'>";

// Create uploads directory
if (!file_exists('uploads')) {
    mkdir('uploads', 0777, true);
    echo "<p class='success'>✓ Created uploads directory</p>";
}
if (!file_exists('uploads/avatars')) {
    mkdir('uploads/avatars', 0777, true);
    echo "<p class='success'>✓ Created uploads/avatars directory</p>";
}

// Add avatar column to users table if it doesn't exist
$check_column = $conn->query("SHOW COLUMNS FROM users LIKE 'avatar'");
if ($check_column->num_rows == 0) {
    $conn->query("ALTER TABLE users ADD COLUMN avatar VARCHAR(255) DEFAULT NULL AFTER email");
    echo "<p class='success'>✓ Added avatar column to users table</p>";
} else {
    echo "<p>✓ Avatar column already exists in users table</p>";
}

// Add avatar column to students table if it doesn't exist
$check_column = $conn->query("SHOW COLUMNS FROM students LIKE 'avatar'");
if ($check_column->num_rows == 0) {
    $conn->query("ALTER TABLE students ADD COLUMN avatar VARCHAR(255) DEFAULT NULL AFTER avatar_color");
    echo "<p class='success'>✓ Added avatar column to students table</p>";
} else {
    echo "<p>✓ Avatar column already exists in students table</p>";
}

echo "<h3>✅ Database Update Complete!</h3>";
echo "<a href='profile.php' class='btn'>Go to Profile Page</a>";
echo "</div></body></html>";
?>