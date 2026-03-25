<?php
require_once 'config.php';

// Check login
if (!isLoggedIn()) {
    header("Location: index.php");
    exit();
}

if (isset($_GET['format'])) {
    $format = $_GET['format'];
    
    if ($format == 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="students_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Student ID', 'Full Name', 'Program', 'Email', 'Phone', 'Status']);
        
        $result = $conn->query("SELECT student_id, full_name, program, email, phone, status FROM students ORDER BY full_name");
        
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, $row);
        }
        
        fclose($output);
        logActivity($_SESSION['user_id'], 'EXPORT', 'Exported data to CSV');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export Data</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <div class="header">
        <div class="container">
            <h1><i class="fas fa-graduation-cap"></i> Student Management System</h1>
            <div class="nav">
                <a href="dashboard.php">Dashboard</a>
                <a href="add_student.php">Add Student</a>
                <a href="search.php">Search</a>
                <a href="statistics.php">Statistics</a>
                <a href="export.php" class="active">Export</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-file-export"></i> Export Data
            </div>

            <div class="export-options">
                <a href="?format=csv" class="export-option">
                    <i class="fas fa-file-csv"></i>
                    <h3>CSV Export</h3>
                    <p>Export all student data to CSV format</p>
                </a>
            </div>
        </div>
    </div>
</body>

</html>