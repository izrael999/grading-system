<?php
require_once 'config.php';

echo "<h2>Login Debug</h2>";

// Check if users table exists
$result = $conn->query("SHOW TABLES LIKE 'users'");
if ($result->num_rows == 0) {
    die("❌ Users table does not exist! Please run install.php first.");
}
echo "✅ Users table exists<br>";

// Check if admin user exists
$stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
$username = 'admin';
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("❌ Admin user not found! Please insert admin user.");
}
echo "✅ Admin user found<br>";

$user = $result->fetch_assoc();
echo "Username: " . $user['username'] . "<br>";
echo "Full Name: " . $user['full_name'] . "<br>";
echo "Password hash: " . $user['password'] . "<br>";

// Test password verification
$test_password = 'admin123';
if (password_verify($test_password, $user['password'])) {
    echo "✅ Password 'admin123' verifies correctly!<br>";
} else {
    echo "❌ Password verification failed!<br>";
    
    // Generate new hash
    $new_hash = password_hash('admin123', PASSWORD_DEFAULT);
    echo "New hash for 'admin123': " . $new_hash . "<br>";
    echo "Copy this hash and update the database:<br>";
    echo "<code>UPDATE users SET password = '$new_hash' WHERE username = 'admin';</code>";
}

$stmt->close();
?>

<p><a href="index.php">Go back to login</a></p>