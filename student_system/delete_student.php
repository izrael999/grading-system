<?php
require_once 'config.php';

// Check login
if (!isLoggedIn()) {
    header("Location: index.php");
    exit();
}

// Validate and sanitize input
if(isset($_GET['id']) && !empty($_GET['id'])) {
    $student_id = $conn->real_escape_string($_GET['id']);
    
    // Get student name for log
    $name_stmt = $conn->prepare("SELECT full_name FROM students WHERE student_id = ?");
    $name_stmt->bind_param("s", $student_id);
    $name_stmt->execute();
    $name_result = $name_stmt->get_result();
    $student_name = $name_result->fetch_assoc()['full_name'] ?? $student_id;
    $name_stmt->close();
    
    // Delete student (subjects will auto-delete due to foreign key)
    $stmt = $conn->prepare("DELETE FROM students WHERE student_id = ?");
    $stmt->bind_param("s", $student_id);
    
    if ($stmt->execute()) {
        logActivity($_SESSION['user_id'], 'DELETE', "Deleted student: $student_name");
        $_SESSION['success_message'] = "Student deleted successfully!";
        header("Location: dashboard.php");
    } else {
        $_SESSION['error_message'] = "Error deleting student!";
        header("Location: dashboard.php");
    }
    $stmt->close();
} else {
    header("Location: dashboard.php");
}
exit();
?>