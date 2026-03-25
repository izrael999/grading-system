<?php
require_once 'config.php';

// Check login
if (!isLoggedIn()) {
    header("Location: index.php");
    exit();
}

// Get user's theme preference and avatar
$user_id = $_SESSION['user_id'];
$user_stmt = $conn->prepare("SELECT theme_preference, avatar, full_name, avatar_color FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_data = $user_result->fetch_assoc();
$user_theme = $user_data['theme_preference'] ?? 'dark';
$_SESSION['theme_preference'] = $user_theme;
$_SESSION['avatar'] = $user_data['avatar'] ?? '';
$user_stmt->close();

// Get statistics
$stats = [];

// Total students
$result = $conn->query("SELECT COUNT(*) as count FROM students");
$stats['total_students'] = $result->fetch_assoc()['count'];

// Active students
$result = $conn->query("SELECT COUNT(*) as count FROM students WHERE status = 'active'");
$stats['active_students'] = $result->fetch_assoc()['count'];

// Average performance
$result = $conn->query("SELECT AVG(marks) as avg FROM subjects");
$stats['avg_performance'] = number_format($result->fetch_assoc()['avg'] ?? 0, 2);

// Total subjects
$result = $conn->query("SELECT COUNT(*) as count FROM subjects");
$stats['total_subjects'] = $result->fetch_assoc()['count'];

// Get recent activity
$activity_stmt = $conn->prepare("
    SELECT a.*, u.full_name, u.avatar_color, u.avatar 
    FROM activity_log a 
    LEFT JOIN users u ON a.user_id = u.id 
    ORDER BY a.created_at DESC 
    LIMIT 5
");
$activity_stmt->execute();
$activities = $activity_stmt->get_result();

// Get all students
$sql = "SELECT s.*, 
        COUNT(sub.id) as subject_count,
        AVG(sub.marks) as average
        FROM students s
        LEFT JOIN subjects sub ON s.student_id = sub.student_id
        GROUP BY s.student_id
        ORDER BY s.full_name";

$students = $conn->query($sql);

// Check for message in URL
$message = isset($_GET['message']) ? $_GET['message'] : '';

// Get header avatar URL
$header_avatar_url = getUserAvatar($user_data);
?>

<!DOCTYPE html>
<html lang="en" data-theme="<?php echo $user_theme; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Student Management System</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
    <div class="theme-toggle">
        <button id="themeSwitcher" class="theme-btn">
            <i class="fas <?php echo $user_theme === 'dark' ? 'fa-moon' : 'fa-sun'; ?>"></i>
        </button>
    </div>

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
                <a href="dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a>
                <a href="add_student.php"><i class="fas fa-user-plus"></i> Add Student</a>
                <a href="search.php"><i class="fas fa-search"></i> Search</a>
                <a href="statistics.php"><i class="fas fa-chart-bar"></i> Statistics</a>
                <a href="import.php"><i class="fas fa-file-import"></i> Import</a>
                <a href="export.php"><i class="fas fa-file-export"></i> Export</a>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if($message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #3b82f6, #8b5cf6);">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <h3>Total Students</h3>
                    <div class="number"><?php echo $stats['total_students']; ?></div>
                    <small><?php echo $stats['active_students']; ?> active</small>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #10b981, #34d399);">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-content">
                    <h3>Average Performance</h3>
                    <div class="number"><?php echo $stats['avg_performance']; ?>%</div>
                    <small>Overall class average</small>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #f59e0b, #fbbf24);">
                    <i class="fas fa-book"></i>
                </div>
                <div class="stat-content">
                    <h3>Total Subjects</h3>
                    <div class="number"><?php echo $stats['total_subjects']; ?></div>
                    <small>Registered courses</small>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #ef4444, #f87171);">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <h3>Last Update</h3>
                    <div class="number">Today</div>
                    <small><?php echo date('h:i A'); ?></small>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="dashboard-grid">
            <!-- Student List -->
            <div class="card main-card">
                <div class="card-header">
                    <div class="header-title">
                        <i class="fas fa-users"></i>
                        <span>Student Directory</span>
                    </div>
                    <div class="header-actions">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" id="liveSearch" placeholder="Search students...">
                        </div>
                        <a href="add_student.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add New
                        </a>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="studentTable">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>ID</th>
                                <th>Program</th>
                                <th>Subjects</th>
                                <th>Average</th>
                                <th>Grade</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($students && $students->num_rows > 0): ?>
                            <?php while($row = $students->fetch_assoc()): 
                                $student_avatar = $row['avatar'] ?? '';
                            ?>
                            <tr>
                                <td>
                                    <div class="student-info">
                                        <div class="student-avatar"
                                            style="background-color: <?php echo $row['avatar_color'] ?? getAvatarColor($row['full_name']); ?>; background-image: url('<?php echo $student_avatar; ?>'); background-size: cover; background-position: center;">
                                            <?php if(empty($student_avatar)): ?>
                                            <?php echo getInitials($row['full_name']); ?>
                                            <?php endif; ?>
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
                                    <span class="status-badge status-<?php echo $row['status'] ?? 'active'; ?>">
                                        <?php echo ucfirst($row['status'] ?? 'active'); ?>
                                    </span>
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
                                        <a href="delete_student.php?id=<?php echo urlencode($row['student_id']); ?>"
                                            class="btn-icon delete" title="Delete"
                                            onclick="return confirm('Are you sure you want to delete this student?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="8" class="empty-state">
                                    <i class="fas fa-users-slash"></i>
                                    <p>No students found</p>
                                    <a href="add_student.php" class="btn btn-primary">Add your first student</a>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="sidebar">
                <!-- Recent Activity -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-history"></i> Recent Activity
                    </div>
                    <div class="activity-feed">
                        <?php if($activities && $activities->num_rows > 0): ?>
                        <?php while($activity = $activities->fetch_assoc()): 
                            $activity_avatar = $activity['avatar'] ?? '';
                        ?>
                        <div class="activity-item">
                            <div class="activity-avatar"
                                style="background-color: <?php echo $activity['avatar_color'] ?? '#3b82f6'; ?>; background-image: url('<?php echo $activity_avatar; ?>'); background-size: cover; background-position: center;">
                                <?php if(empty($activity_avatar)): ?>
                                <?php echo getInitials($activity['full_name'] ?? 'SY'); ?>
                                <?php endif; ?>
                            </div>
                            <div class="activity-content">
                                <strong><?php echo htmlspecialchars($activity['full_name'] ?? 'System'); ?></strong>
                                <p><?php echo htmlspecialchars($activity['action']); ?></p>
                                <small><?php echo date('h:i A', strtotime($activity['created_at'])); ?></small>
                            </div>
                        </div>
                        <?php endwhile; ?>
                        <?php else: ?>
                        <div class="empty-state small">
                            <i class="fas fa-clock"></i>
                            <p>No recent activity</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-bolt"></i> Quick Actions
                    </div>
                    <div class="quick-actions">
                        <a href="import.php" class="quick-action">
                            <i class="fas fa-file-import"></i>
                            <span>Import</span>
                        </a>
                        <a href="export.php" class="quick-action">
                            <i class="fas fa-file-export"></i>
                            <span>Export</span>
                        </a>
                        <a href="statistics.php" class="quick-action">
                            <i class="fas fa-chart-bar"></i>
                            <span>Stats</span>
                        </a>
                        <a href="profile.php" class="quick-action">
                            <i class="fas fa-user"></i>
                            <span>Profile</span>
                        </a>
                    </div>
                </div>

                <!-- Performance Chart -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-chart-pie"></i> Grade Distribution
                    </div>
                    <canvas id="gradeChart" style="height: 200px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Theme switcher
    const themeSwitcher = document.getElementById('themeSwitcher');
    const html = document.documentElement;

    themeSwitcher.addEventListener('click', () => {
        const currentTheme = html.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        html.setAttribute('data-theme', newTheme);

        fetch('save_theme.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'theme=' + newTheme
            }).then(response => response.json())
            .then(data => {
                if (data.success) {
                    const icon = themeSwitcher.querySelector('i');
                    icon.className = newTheme === 'dark' ? 'fas fa-moon' : 'fas fa-sun';
                }
            });
    });

    // Live search
    document.getElementById('liveSearch').addEventListener('keyup', function() {
        const searchText = this.value.toLowerCase();
        const table = document.getElementById('studentTable');
        const rows = table.getElementsByTagName('tr');

        for (let row of rows) {
            const name = row.querySelector('.student-details strong')?.textContent.toLowerCase() || '';
            const id = row.querySelector('.badge')?.textContent.toLowerCase() || '';
            const program = row.cells[2]?.textContent.toLowerCase() || '';

            if (name.includes(searchText) || id.includes(searchText) || program.includes(searchText)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        }
    });

    // Grade distribution chart
    const ctx = document.getElementById('gradeChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['A (75-100)', 'B (65-74)', 'C (50-64)', 'D (40-49)', 'F (0-39)'],
            datasets: [{
                data: [12, 19, 8, 5, 2],
                backgroundColor: ['#10b981', '#3b82f6', '#f59e0b', '#f97316', '#ef4444'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });

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
<?php $activity_stmt->close(); ?>