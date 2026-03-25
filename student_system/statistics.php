<?php
require_once 'config.php';

// Check login
if (!isLoggedIn()) {
    header("Location: index.php");
    exit();
}

// Get all students with averages using prepared statement
$students_stmt = $conn->prepare("
    SELECT s.*, 
           AVG(sub.marks) as average
    FROM students s
    LEFT JOIN subjects sub ON s.student_id = sub.student_id
    GROUP BY s.student_id
");
$students_stmt->execute();
$students = $students_stmt->get_result();

$averages = [];
$grade_dist = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'F' => 0];
$total_students = 0;

while($student = $students->fetch_assoc()) {
    if($student['average']) {
        $averages[] = $student['average'];
        $grade = calculateGrade($student['average']);
        $grade_dist[$grade]++;
    }
    $total_students++;
}

// Calculate statistics
$class_avg = count($averages) > 0 ? array_sum($averages) / count($averages) : 0;
$highest = count($averages) > 0 ? max($averages) : 0;
$lowest = count($averages) > 0 ? min($averages) : 0;

// Reset pointer
$students->data_seek(0);

// Get top students
$top_stmt = $conn->prepare("
    SELECT s.*, AVG(sub.marks) as average
    FROM students s
    JOIN subjects sub ON s.student_id = sub.student_id
    GROUP BY s.student_id
    HAVING average IS NOT NULL
    ORDER BY average DESC
    LIMIT 3
");
$top_stmt->execute();
$top_students = $top_stmt->get_result();

// Get program distribution
$programs_stmt = $conn->prepare("
    SELECT program, COUNT(*) as count
    FROM students
    GROUP BY program
");
$programs_stmt->execute();
$programs = $programs_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Statistics</title>
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
                <a href="search.php"><i class="fas fa-search"></i> Search</a>
                <a href="statistics.php" class="active"><i class="fas fa-chart-bar"></i> Statistics</a>
                <a href="import.php"><i class="fas fa-file-import"></i> Import</a>
                <a href="export.php"><i class="fas fa-file-export"></i> Export</a>
            </div>
        </div>
    </div>

    <div class="container">
        <h2><i class="fas fa-chart-pie"></i> Class Statistics</h2>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #3b82f6, #8b5cf6);">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <h3>Total Students</h3>
                    <div class="number"><?php echo $total_students; ?></div>
                    <small>Enrolled</small>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #10b981, #34d399);">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-content">
                    <h3>Class Average</h3>
                    <div class="number"><?php echo number_format($class_avg, 2); ?>%</div>
                    <small>Overall performance</small>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #f59e0b, #fbbf24);">
                    <i class="fas fa-trophy"></i>
                </div>
                <div class="stat-content">
                    <h3>Highest Average</h3>
                    <div class="number"><?php echo number_format($highest, 2); ?>%</div>
                    <small>Top performer</small>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #ef4444, #f87171);">
                    <i class="fas fa-arrow-down"></i>
                </div>
                <div class="stat-content">
                    <h3>Lowest Average</h3>
                    <div class="number"><?php echo number_format($lowest, 2); ?>%</div>
                    <small>Needs improvement</small>
                </div>
            </div>
        </div>

        <!-- Top Students -->
        <?php if($top_students->num_rows > 0): ?>
        <div class="card">
            <div class="card-header">
                <i class="fas fa-trophy" style="color: gold;"></i> Top Performing Students
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Student</th>
                            <th>ID</th>
                            <th>Program</th>
                            <th>Average</th>
                            <th>Grade</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $rank = 1;
                        while($top = $top_students->fetch_assoc()): 
                        ?>
                        <tr>
                            <td><strong>#<?php echo $rank++; ?></strong></td>
                            <td>
                                <div class="student-info">
                                    <div class="student-avatar"
                                        style="background-color: <?php echo $top['avatar_color'] ?? getAvatarColor($top['full_name']); ?>">
                                        <?php echo getInitials($top['full_name']); ?>
                                    </div>
                                    <strong><?php echo htmlspecialchars($top['full_name']); ?></strong>
                                </div>
                            </td>
                            <td><span class="badge"><?php echo htmlspecialchars($top['student_id']); ?></span></td>
                            <td><?php echo htmlspecialchars($top['program']); ?></td>
                            <td>
                                <div class="progress-bar">
                                    <div class="progress" style="width: <?php echo $top['average']; ?>%;"></div>
                                    <span><?php echo number_format($top['average'], 1); ?>%</span>
                                </div>
                            </td>
                            <td>
                                <?php 
                                $grade = calculateGrade($top['average']);
                                ?>
                                <span class="grade-badge"
                                    style="background-color: <?php echo getGradeColor($grade); ?>20; color: <?php echo getGradeColor($grade); ?>; border-color: <?php echo getGradeColor($grade); ?>">
                                    <?php echo $grade; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <!-- Grade Distribution -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-pie"></i> Grade Distribution
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Grade</th>
                                <th>Count</th>
                                <th>Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach(['A', 'B', 'C', 'D', 'F'] as $grade): ?>
                            <tr>
                                <td>
                                    <span class="grade-badge"
                                        style="background-color: <?php echo getGradeColor($grade); ?>20; color: <?php echo getGradeColor($grade); ?>; border-color: <?php echo getGradeColor($grade); ?>">
                                        <?php echo $grade; ?>
                                    </span>
                                </td>
                                <td><?php echo $grade_dist[$grade]; ?></td>
                                <td>
                                    <?php 
                                    $percentage = $total_students > 0 ? ($grade_dist[$grade] / $total_students * 100) : 0;
                                    echo number_format($percentage, 1) . '%';
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Program Distribution -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-book"></i> Program Distribution
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Program</th>
                                <th>Students</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($programs->num_rows > 0): ?>
                            <?php while($program = $programs->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($program['program']); ?></td>
                                <td><?php echo $program['count']; ?></td>
                            </tr>
                            <?php endwhile; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="2" class="empty-state">No programs found</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
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
<?php 
$students_stmt->close();
$top_stmt->close();
$programs_stmt->close();
?>