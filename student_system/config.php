<?php
// Database configuration
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'student_db';

// Create connection
$conn = new mysqli($host, $user, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8mb4");

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Function to calculate grade
function calculateGrade($average) {
    if ($average >= 75) return 'A';
    if ($average >= 65) return 'B';
    if ($average >= 50) return 'C';
    if ($average >= 40) return 'D';
    return 'F';
}

// Function to get grade color
function getGradeColor($grade) {
    $colors = [
        'A' => '#10b981',
        'B' => '#3b82f6',
        'C' => '#f59e0b',
        'D' => '#f97316',
        'F' => '#ef4444'
    ];
    return $colors[$grade] ?? '#6b7280';
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Escape string for safe output
function escape($str) {
    global $conn;
    return htmlspecialchars($conn->real_escape_string($str));
}

// Log activity
function logActivity($user_id, $action, $details = '') {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO activity_log (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $stmt->bind_param("isss", $user_id, $action, $details, $ip);
    $stmt->execute();
    $stmt->close();
}

// Get user's avatar color based on name
function getAvatarColor($name) {
    $colors = ['#f44336', '#e91e63', '#9c27b0', '#673ab7', '#3f51b5', '#2196f3', '#03a9f4', '#00bcd4', '#009688', '#4caf50', '#8bc34a', '#cddc39', '#ffc107', '#ff9800', '#ff5722'];
    $index = crc32($name) % count($colors);
    return $colors[$index];
}

// Get initials from name
function getInitials($name) {
    $words = explode(' ', $name);
    $initials = '';
    foreach ($words as $word) {
        if (!empty($word)) {
            $initials .= strtoupper($word[0]);
        }
    }
    return substr($initials, 0, 2);
}

// Get user avatar URL
function getUserAvatar($user) {
    if (!empty($user['avatar']) && file_exists($user['avatar'])) {
        return $user['avatar'];
    }
    return '';
}

// Fix avatar URL to ensure it's accessible
function fixAvatarUrl($url) {
    if (empty($url)) return '';
    if (strpos($url, 'http') === 0) return $url;
    return $url . '?t=' . time();
}
?>