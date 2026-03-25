<?php
require_once 'config.php';

// Check login
if (!isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$results = [];
$search_term = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['search_term'])) {
    $search_term = '%' . $conn->real_escape_string($_POST['search_term']) . '%';
    
    // Use prepared statement
    $stmt = $conn->prepare("
        SELECT s.*, 
               COUNT(sub.id) as subject_count,
               AVG(sub.marks) as average
        FROM students s
        LEFT JOIN subjects sub ON s.student_id = sub.student_id
        WHERE s.student_id LIKE ? OR s.full_name LIKE ?
        GROUP BY s.student_id
        ORDER BY s.full_name
    ");
    
    $stmt->bind_param("ss", $search_term, $search_term);
    $stmt->execute();
    $results = $stmt->get_result();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Students</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <div class="header">
        <div class="container">
            <div class="header-content">
                <h1><i class="fas fa-graduation-cap"></i> Student Management System</h1>
                <div class="user-profile">
                    <div class="avatar" style="background-color: <?php echo $_SESSION['avatar_color'] ?? '#3b82f6'; ?>">
                        <?php echo getInitials($_SESSION['full_name']); ?>
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
                <a href="search.php" class="active"><i class="fas fa-search"></i> Search</a>
                <a href="statistics.php"><i class="fas fa-chart-bar"></i> Statistics</a>
                <a href="import.php"><i class="fas fa-file-import"></i> Import</a>
                <a href="export.php"><i class="fas fa-file-export"></i> Export</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-search"></i> Search Students
            </div>

            <form method="POST" style="margin-bottom: 20px;">
                <div style="display: flex; gap: 10px;">
                    <input type="text" name="search_term" placeholder="Enter Student ID or Name"
                        value="<?php echo isset($_POST['search_term']) ? htmlspecialchars($_POST['search_term']) : ''; ?>"
                        style="flex: 1;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
            </form>

            <?php if(isset($results) && $results->num_rows > 0): ?>
            <h4><i class="fas fa-list"></i> Search Results (<?php echo $results->num_rows; ?> found)</h4>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>ID</th>
                            <th>Program</th>
                            <th>Subjects</th>
                            <th>Average</th>
                            <th>Grade</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $results->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <div class="student-info">
                                    <div class="student-avatar"
                                        style="background-color: <?php echo $row['avatar_color'] ?? getAvatarColor($row['full_name']); ?>">
                                        <?php echo getInitials($row['full_name']); ?>
                                    </div>
                                    <div class="student-details">
                                        <strong><?php echo htmlspecialchars($row['full_name']); ?></strong>
                                        <small><?php echo htmlspecialchars($row['email'] ?: 'No email'); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge"><?php echo htmlspecialchars($row['student_id']); ?></span></td>
                            <td><?php echo htmlspecialchars($row['program']); ?></td>
                            <td><?php echo $row['subject_count'] ?? 0; ?></td>
                            <td>
                                <?php if($row['average']): ?>
                                <div class="progress-bar">
                                    <div class="progress" style="width: <?php echo $row['average']; ?>%;"></div>
                                    <span><?php echo number_format($row['average'], 1); ?>%</span>
                                </div>
                                <?php else: ?> - <?php endif; ?>
                            </td>
                            <td>
                                <?php if($row['average']): 
                                    $grade = calculateGrade($row['average']);
                                ?>
                                <span class="grade-badge"
                                    style="background-color: <?php echo getGradeColor($grade); ?>20; color: <?php echo getGradeColor($grade); ?>; border-color: <?php echo getGradeColor($grade); ?>">
                                    <?php echo $grade; ?>
                                </span>
                                <?php else: ?> - <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="student_report.php?id=<?php echo urlencode($row['student_id']); ?>"
                                        class="btn-icon" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="update_student.php?id=<?php echo urlencode($row['student_id']); ?>"
                                        class="btn-icon" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php elseif($_SERVER["REQUEST_METHOD"] == "POST"): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i> No students found matching
                "<?php echo htmlspecialchars($_POST['search_term']); ?>"
            </div>
            <?php endif; ?>
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