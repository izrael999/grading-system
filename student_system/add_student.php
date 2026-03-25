<?php
require_once 'config.php';

// Check login
if (!isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$message = '';
$error = '';

// Get user data for header
$user_id = $_SESSION['user_id'];
$user_stmt = $conn->prepare("SELECT avatar FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_data = $user_result->fetch_assoc();
$user_stmt->close();

$header_avatar_url = getUserAvatar($user_data);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = $conn->real_escape_string($_POST['student_id']);
    $full_name = $conn->real_escape_string($_POST['full_name']);
    $program = $conn->real_escape_string($_POST['program']);
    $email = $conn->real_escape_string($_POST['email']);
    
    // Check if student ID exists using prepared statement
    $check_stmt = $conn->prepare("SELECT student_id FROM students WHERE student_id = ?");
    $check_stmt->bind_param("s", $student_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $error = "Student ID already exists!";
    } else {
        // Insert student using prepared statement
        $insert_stmt = $conn->prepare("INSERT INTO students (student_id, full_name, program, email, avatar_color) VALUES (?, ?, ?, ?, ?)");
        $avatar_color = getAvatarColor($full_name);
        $insert_stmt->bind_param("sssss", $student_id, $full_name, $program, $email, $avatar_color);
        
        if ($insert_stmt->execute()) {
            // Add subjects
            $subject_inserted = 0;
            for($i = 1; $i <= 3; $i++) {
                if(isset($_POST["subject_$i"]) && isset($_POST["marks_$i"])) {
                    $subject = $conn->real_escape_string($_POST["subject_$i"]);
                    $marks = floatval($_POST["marks_$i"]);
                    
                    if(!empty($subject) && $marks >= 0 && $marks <= 100) {
                        $sub_stmt = $conn->prepare("INSERT INTO subjects (student_id, subject_name, marks) VALUES (?, ?, ?)");
                        $sub_stmt->bind_param("ssd", $student_id, $subject, $marks);
                        if($sub_stmt->execute()) {
                            $subject_inserted++;
                        }
                        $sub_stmt->close();
                    }
                }
            }
            
            logActivity($_SESSION['user_id'], 'ADD', "Added student: $full_name (ID: $student_id)");
            
            if($subject_inserted >= 3) {
                $message = "Student added successfully with $subject_inserted subjects!";
            } else {
                $message = "Student added but only $subject_inserted subjects were saved!";
            }
        } else {
            $error = "Error: " . $conn->error;
        }
        $insert_stmt->close();
    }
    $check_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Student</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <div class="header">
        <div class="container">
            <div class="header-content">
                <h1><i class="fas fa-graduation-cap"></i> Student Management System</h1>
                <div class="user-profile">
                    <div class="avatar"
                        style="background-color: <?php echo $_SESSION['avatar_color'] ?? '#3b82f6'; ?>; background-image: url('<?php echo $header_avatar_url; ?>'); background-size: cover; background-position: center;">
                        <?php if(empty($_SESSION['avatar'])): ?>
                        <?php echo getInitials($_SESSION['full_name']); ?>
                        <?php endif; ?>
                    </div>
                    <span class="username"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                    <div class="dropdown-menu">
                        <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </div>
            <div class="nav">
                <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                <a href="add_student.php" class="active"><i class="fas fa-user-plus"></i> Add Student</a>
                <a href="search.php"><i class="fas fa-search"></i> Search</a>
                <a href="statistics.php"><i class="fas fa-chart-bar"></i> Statistics</a>
                <a href="import.php"><i class="fas fa-file-import"></i> Import</a>
                <a href="export.php"><i class="fas fa-file-export"></i> Export</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-user-plus"></i> Add New Student
            </div>

            <?php if($message): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $message; ?></div>
            <?php endif; ?>

            <?php if($error): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label><i class="fas fa-id-card"></i> Student ID *</label>
                    <input type="text" name="student_id" required maxlength="20" placeholder="Enter student ID">
                </div>

                <div class="form-group">
                    <label><i class="fas fa-user"></i> Full Name *</label>
                    <input type="text" name="full_name" required maxlength="100" placeholder="Enter full name">
                </div>

                <div class="form-group">
                    <label><i class="fas fa-book"></i> Program/Class *</label>
                    <select name="program" required>
                        <option value="">Select Program</option>
                        <option value="Computer Science">Computer Science</option>
                        <option value="Information Technology">Information Technology</option>
                        <option value="Business">Business</option>
                        <option value="Engineering">Engineering</option>
                        <option value="Mathematics">Mathematics</option>
                    </select>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" name="email" maxlength="100" placeholder="Enter email address">
                </div>

                <h4 style="margin: 20px 0 10px; color: var(--accent-primary);">
                    <i class="fas fa-book-open"></i> Subjects (Minimum 3)
                </h4>

                <?php for($i = 1; $i <= 3; $i++): ?>
                <div class="subject-card"
                    style="background: var(--bg-tertiary); padding: 15px; margin-bottom: 15px; border-radius: 5px; border: 1px solid var(--border-color);">
                    <h5 style="margin-bottom: 10px; color: var(--accent-primary);">Subject <?php echo $i; ?></h5>
                    <div class="form-group">
                        <label>Subject Name</label>
                        <input type="text" name="subject_<?php echo $i; ?>" required maxlength="100"
                            placeholder="e.g., Mathematics">
                    </div>
                    <div class="form-group">
                        <label>Marks (0-100)</label>
                        <input type="number" name="marks_<?php echo $i; ?>" min="0" max="100" step="0.01" required
                            placeholder="Enter marks">
                    </div>
                </div>
                <?php endfor; ?>

                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Add Student
                    </button>
                    <a href="dashboard.php" class="btn" style="background: #6c757d; color: white;">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
    // Auto-hide alerts
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(alert => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        });
    }, 5000);
    </script>
</body>

</html>