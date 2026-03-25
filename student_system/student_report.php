<?php
require_once 'config.php';

// Check login
if (!isLoggedIn()) {
    header("Location: index.php");
    exit();
}

// Validate student ID
if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$student_id = $conn->real_escape_string($_GET['id']);

// Get student info
$student_stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
$student_stmt->bind_param("s", $student_id);
$student_stmt->execute();
$student_result = $student_stmt->get_result();
$student = $student_result->fetch_assoc();
$student_stmt->close();

if(!$student) {
    header("Location: dashboard.php");
    exit();
}

// Get subjects
$subjects_stmt = $conn->prepare("SELECT * FROM subjects WHERE student_id = ? ORDER BY subject_name");
$subjects_stmt->bind_param("s", $student_id);
$subjects_stmt->execute();
$subjects = $subjects_stmt->get_result();

// Calculate totals
$total = 0;
$count = 0;
$subjects->data_seek(0);
while($subject = $subjects->fetch_assoc()) {
    $total += $subject['marks'];
    $count++;
}
$average = $count > 0 ? $total / $count : 0;
$grade = calculateGrade($average);

// Reset pointer
$subjects->data_seek(0);

// Get user avatar for header
$user_id = $_SESSION['user_id'];
$user_stmt = $conn->prepare("SELECT avatar FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_data = $user_result->fetch_assoc();
$user_avatar = $user_data['avatar'] ?? '';
$user_stmt->close();

$header_avatar_url = $user_avatar ?: 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($_SESSION['full_name']))) . '?d=mp';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Report</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
    .user-profile .avatar {
        background-size: cover;
        background-position: center;
    }

    .student-avatar {
        background-size: cover;
        background-position: center;
    }
    </style>
</head>

<body>
    <div class="header">
        <div class="container">
            <div class="header-content">
                <h1><i class="fas fa-graduation-cap"></i> Student Management System</h1>
                <div class="user-profile">
                    <div class="avatar"
                        style="background-color: <?php echo $_SESSION['avatar_color'] ?? '#3b82f6'; ?>; background-image: url('<?php echo $header_avatar_url; ?>'); background-size: cover; background-position: center;">
                        <?php if(!$user_avatar): ?>
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
                <a href="add_student.php"><i class="fas fa-user-plus"></i> Add Student</a>
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
                <i class="fas fa-file-alt"></i> Student Report: <?php echo htmlspecialchars($student['full_name']); ?>
            </div>

            <div class="student-report-header"
                style="display: flex; align-items: center; gap: 20px; margin-bottom: 30px; padding: 20px; background: var(--bg-tertiary); border-radius: 5px; flex-wrap: wrap;">
                <div class="student-avatar"
                    style="width: 80px; height: 80px; font-size: 32px; background-color: <?php echo $student['avatar_color'] ?? getAvatarColor($student['full_name']); ?>; background-image: url('<?php echo $student['avatar'] ?? ''; ?>'); background-size: cover; background-position: center; display: flex; align-items: center; justify-content: center; color: white; border-radius: 50%;">
                    <?php if(!$student['avatar']): ?>
                    <?php echo getInitials($student['full_name']); ?>
                    <?php endif; ?>
                </div>
                <div style="flex: 1;">
                    <h2><?php echo htmlspecialchars($student['full_name']); ?></h2>
                    <p><strong>Student ID:</strong> <?php echo htmlspecialchars($student['student_id']); ?></p>
                    <p><strong>Program:</strong> <?php echo htmlspecialchars($student['program']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($student['email'] ?: 'N/A'); ?></p>
                </div>
                <div
                    style="text-align: center; padding: 15px; background: var(--card-bg); border-radius: 5px; border: 1px solid var(--border-color);">
                    <div style="font-size: 36px; font-weight: bold; color: <?php echo getGradeColor($grade); ?>;">
                        <?php echo number_format($average, 1); ?>%</div>
                    <div>Overall Average</div>
                    <div style="margin-top: 5px;">
                        <span class="grade-badge"
                            style="background-color: <?php echo getGradeColor($grade); ?>20; color: <?php echo getGradeColor($grade); ?>; border-color: <?php echo getGradeColor($grade); ?>; font-size: 16px; padding: 5px 15px;">
                            Grade <?php echo $grade; ?>
                        </span>
                    </div>
                </div>
            </div>

            <h4 style="margin-bottom: 15px;"><i class="fas fa-book-open"></i> Academic Record</h4>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Marks</th>
                            <th>Grade</th>
                            <th>Performance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($subjects->num_rows > 0): ?>
                        <?php while($subject = $subjects->fetch_assoc()): 
                            $subGrade = calculateGrade($subject['marks']);
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($subject['subject_name']); ?></td>
                            <td><?php echo $subject['marks']; ?>%</td>
                            <td>
                                <span class="grade-badge"
                                    style="background-color: <?php echo getGradeColor($subGrade); ?>20; color: <?php echo getGradeColor($subGrade); ?>; border-color: <?php echo getGradeColor($subGrade); ?>">
                                    <?php echo $subGrade; ?>
                                </span>
                            </td>
                            <td style="width: 200px;">
                                <div class="progress-bar" style="width: 100%;">
                                    <div class="progress" style="width: <?php echo $subject['marks']; ?>%;"></div>
                                    <span><?php echo $subject['marks']; ?>%</span>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="4" class="empty-state">No subjects found</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3">Total Marks:</th>
                            <th><?php echo number_format($total, 2); ?></th>
                        </tr>
                        <tr>
                            <th colspan="3">Average:</th>
                            <th><?php echo number_format($average, 2); ?>%</th>
                        </tr>
                        <tr>
                            <th colspan="3">Overall Grade:</th>
                            <th>
                                <span class="grade-badge"
                                    style="background-color: <?php echo getGradeColor($grade); ?>20; color: <?php echo getGradeColor($grade); ?>; border-color: <?php echo getGradeColor($grade); ?>; font-size: 14px;">
                                    <?php echo $grade; ?>
                                </span>
                            </th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div style="margin-top: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
                <a href="update_student.php?id=<?php echo urlencode($student_id); ?>" class="btn btn-warning">
                    <i class="fas fa-edit"></i> Update Student
                </a>
                <a href="dashboard.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                <button onclick="window.print()" class="btn btn-info" style="margin-left: auto;">
                    <i class="fas fa-print"></i> Print Report
                </button>
            </div>
        </div>
    </div>

    <style media="print">
    .header,
    .nav,
    .theme-toggle,
    .btn,
    footer {
        display: none !important;
    }

    body {
        background: white;
        color: black;
    }

    .card {
        box-shadow: none;
        border: 1px solid #ddd;
    }
    </style>

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
<?php $subjects_stmt->close(); ?>