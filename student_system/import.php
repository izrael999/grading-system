<?php
require_once 'config.php';

// Check login
if (!isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$message = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];
    
    if ($file['error'] == UPLOAD_ERR_OK) {
        $handle = fopen($file['tmp_name'], 'r');
        
        // Skip header row
        $header = fgetcsv($handle);
        
        $imported = 0;
        $errors = [];
        
        while (($data = fgetcsv($handle)) !== FALSE) {
            if (count($data) >= 3) {
                $student_id = $conn->real_escape_string($data[0]);
                $full_name = $conn->real_escape_string($data[1]);
                $program = $conn->real_escape_string($data[2]);
                $email = $conn->real_escape_string($data[3] ?? '');
                $phone = $conn->real_escape_string($data[4] ?? '');
                
                // Check if student exists
                $check = $conn->query("SELECT student_id FROM students WHERE student_id = '$student_id'");
                
                if ($check->num_rows == 0) {
                    $avatar_color = getAvatarColor($full_name);
                    
                    $stmt = $conn->prepare("INSERT INTO students (student_id, full_name, program, email, phone, avatar_color) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("ssssss", $student_id, $full_name, $program, $email, $phone, $avatar_color);
                    
                    if ($stmt->execute()) {
                        $imported++;
                        logActivity($_SESSION['user_id'], 'IMPORT', "Imported student: $full_name");
                    }
                    $stmt->close();
                }
            }
        }
        
        fclose($handle);
        
        if ($imported > 0) {
            $message = "Successfully imported $imported students!";
        }
    } else {
        $error = "Error uploading file";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Students</title>
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
                <a href="import.php" class="active">Import</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-file-import"></i> Import Students from CSV
            </div>

            <?php if($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>

            <?php if($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="import-info">
                <h4>CSV Format:</h4>
                <p>Student ID, Full Name, Program, Email (optional), Phone (optional)</p>
                <p>Example: <code>STU101,John Doe,Computer Science,john@email.com,1234567890</code></p>
            </div>

            <form method="POST" enctype="multipart/form-data" class="import-form">
                <div class="file-upload">
                    <input type="file" name="csv_file" accept=".csv" required id="fileInput">
                    <label for="fileInput" class="file-label">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <span>Choose CSV File</span>
                    </label>
                    <div class="file-name" id="fileName"></div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-upload"></i> Import Students
                </button>
            </form>
        </div>
    </div>

    <script>
    document.getElementById('fileInput').addEventListener('change', function(e) {
        document.getElementById('fileName').textContent = e.target.files[0]?.name || '';
    });
    </script>
</body>

</html>