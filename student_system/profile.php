<?php
require_once 'config.php';

// Check login
if (!isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Get user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Handle profile update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update_profile'])) {
        $full_name = $conn->real_escape_string($_POST['full_name']);
        $email = $conn->real_escape_string($_POST['email']);
        
        $update_stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?");
        $update_stmt->bind_param("ssi", $full_name, $email, $user_id);
        
        if ($update_stmt->execute()) {
            // Refresh all session data
            $_SESSION['full_name'] = $full_name;
            
            // Get complete fresh user data
            $refresh_stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
            $refresh_stmt->bind_param("i", $user_id);
            $refresh_stmt->execute();
            $fresh_data = $refresh_stmt->get_result()->fetch_assoc();
            $refresh_stmt->close();
            
            $_SESSION['username'] = $fresh_data['username'];
            $_SESSION['avatar_color'] = $fresh_data['avatar_color'];
            $_SESSION['avatar'] = $fresh_data['avatar'];
            $_SESSION['theme_preference'] = $fresh_data['theme_preference'] ?? 'dark';
            
            $message = "Profile updated successfully!";
            logActivity($user_id, 'PROFILE_UPDATE', 'Updated profile information');
            
            // Refresh user data
            $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        }
        $update_stmt->close();
    }
    
    if (isset($_POST['change_password'])) {
        $current = $_POST['current_password'];
        $new = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];
        
        if (password_verify($current, $user['password'])) {
            if ($new === $confirm) {
                if (strlen($new) >= 6) {
                    $hashed = password_hash($new, PASSWORD_DEFAULT);
                    $pass_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $pass_stmt->bind_param("si", $hashed, $user_id);
                    
                    if ($pass_stmt->execute()) {
                        $message = "Password changed successfully!";
                        logActivity($user_id, 'PASSWORD_CHANGE', 'Changed password');
                    }
                    $pass_stmt->close();
                } else {
                    $error = "Password must be at least 6 characters";
                }
            } else {
                $error = "New passwords do not match";
            }
        } else {
            $error = "Current password is incorrect";
        }
    }
    
    if (isset($_POST['change_username'])) {
        $new_username = $conn->real_escape_string($_POST['new_username']);
        
        // Check if username already exists
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $check_stmt->bind_param("si", $new_username, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows == 0) {
            $update_stmt = $conn->prepare("UPDATE users SET username = ? WHERE id = ?");
            $update_stmt->bind_param("si", $new_username, $user_id);
            
            if ($update_stmt->execute()) {
                $_SESSION['username'] = $new_username;
                $message = "Username changed successfully!";
                logActivity($user_id, 'USERNAME_CHANGE', 'Changed username');
                
                // Refresh user data
                $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $user = $stmt->get_result()->fetch_assoc();
                $stmt->close();
            }
            $update_stmt->close();
        } else {
            $error = "Username already exists!";
        }
        $check_stmt->close();
    }
}

// Get user statistics
$stats = [];
$result = $conn->query("SELECT COUNT(*) as count FROM students");
$stats['total_students'] = $result->fetch_assoc()['count'];

// Recent activity
$activity_stmt = $conn->prepare("SELECT * FROM activity_log WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$activity_stmt->bind_param("i", $user_id);
$activity_stmt->execute();
$activities = $activity_stmt->get_result();

// Get avatar URL
$avatar_url = getUserAvatar($user);
?>

<!DOCTYPE html>
<html lang="en" data-theme="<?php echo $user['theme_preference'] ?? 'dark'; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Student Management System</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
    .avatar-upload {
        position: relative;
        width: 150px;
        height: 150px;
        margin: 0 auto 20px;
        cursor: pointer;
    }

    .avatar-upload img {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid var(--accent-primary);
        box-shadow: 0 0 20px var(--shadow-color);
    }

    .avatar-upload .avatar-initials {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 48px;
        font-weight: bold;
        color: white;
        border: 4px solid var(--accent-primary);
        box-shadow: 0 0 20px var(--shadow-color);
    }

    .avatar-upload-overlay {
        position: absolute;
        bottom: 5px;
        right: 5px;
        background: var(--accent-primary);
        color: white;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        border: 2px solid white;
        transition: all 0.3s;
    }

    .avatar-upload-overlay:hover {
        transform: scale(1.1);
        background: var(--accent-secondary);
    }

    #fileInput {
        display: none;
    }

    .upload-progress {
        display: none;
        margin-top: 10px;
    }

    .upload-progress.active {
        display: block;
    }

    .progress-bar-custom {
        width: 100%;
        height: 6px;
        background: var(--bg-tertiary);
        border-radius: 3px;
        overflow: hidden;
    }

    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, var(--accent-primary), var(--accent-secondary));
        width: 0%;
        transition: width 0.3s;
    }

    .profile-header {
        position: relative;
    }

    .tab-container {
        display: flex;
        border-bottom: 2px solid var(--border-color);
        margin-bottom: 20px;
    }

    .tab {
        padding: 10px 20px;
        cursor: pointer;
        border: 1px solid transparent;
        border-bottom: none;
        border-radius: 5px 5px 0 0;
        background: transparent;
        color: var(--text-secondary);
    }

    .tab.active {
        background: var(--bg-tertiary);
        color: var(--accent-primary);
        border-color: var(--border-color);
        border-bottom-color: transparent;
    }

    .tab-content {
        display: none;
    }

    .tab-content.active {
        display: block;
    }
    </style>
</head>

<body>
    <div class="theme-toggle">
        <button id="themeSwitcher" class="theme-btn">
            <i class="fas <?php echo ($user['theme_preference'] ?? 'dark') === 'dark' ? 'fa-moon' : 'fa-sun'; ?>"></i>
        </button>
    </div>

    <div class="header">
        <div class="container">
            <div class="header-content">
                <h1><i class="fas fa-graduation-cap"></i> Student Management System</h1>
                <div class="user-profile">
                    <div class="avatar"
                        style="background-color: <?php echo $user['avatar_color'] ?? '#3b82f6'; ?>; background-image: url('<?php echo $avatar_url; ?>'); background-size: cover; background-position: center;">
                        <?php if(empty($user['avatar'])): ?>
                        <?php echo getInitials($user['full_name']); ?>
                        <?php endif; ?>
                    </div>
                    <span class="username"><?php echo htmlspecialchars($user['full_name']); ?></span>
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
        <?php if($message): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $message; ?></div>
        <?php endif; ?>

        <?php if($error): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <div class="profile-grid">
            <!-- Profile Card -->
            <div class="card profile-card">
                <div class="profile-header">
                    <div class="avatar-upload" onclick="document.getElementById('fileInput').click()">
                        <?php if(!empty($user['avatar']) && file_exists($user['avatar'])): ?>
                        <img src="<?php echo $user['avatar']; ?>?t=<?php echo time(); ?>" alt="Profile Picture"
                            id="profileImage">
                        <?php else: ?>
                        <div class="avatar-initials" id="avatarInitials"
                            style="background-color: <?php echo $user['avatar_color'] ?? '#3b82f6'; ?>">
                            <?php echo getInitials($user['full_name']); ?>
                        </div>
                        <img src="" alt="Profile Picture" id="profileImage" style="display: none;">
                        <?php endif; ?>
                        <div class="avatar-upload-overlay">
                            <i class="fas fa-camera"></i>
                        </div>
                    </div>

                    <input type="file" id="fileInput" accept="image/jpeg,image/png,image/gif,image/webp">

                    <div class="upload-progress" id="uploadProgress">
                        <div class="progress-bar-custom">
                            <div class="progress-fill" id="progressFill"></div>
                        </div>
                        <p id="uploadStatus" style="font-size: 12px; margin-top: 5px;"></p>
                    </div>

                    <h2><?php echo htmlspecialchars($user['full_name']); ?></h2>
                    <p class="username">@<?php echo htmlspecialchars($user['username']); ?></p>
                    <p class="member-since">Member since <?php echo date('F Y', strtotime($user['created_at'])); ?></p>
                </div>

                <div class="profile-stats">
                    <div class="stat">
                        <span class="value"><?php echo $stats['total_students']; ?></span>
                        <span class="label">Students</span>
                    </div>
                    <div class="stat">
                        <span class="value"><?php echo $activities->num_rows; ?></span>
                        <span class="label">Activities</span>
                    </div>
                    <div class="stat">
                        <span class="value"><?php echo date('M Y', strtotime($user['created_at'])); ?></span>
                        <span class="label">Joined</span>
                    </div>
                </div>
            </div>

            <!-- Settings Tabs -->
            <div class="card full-width">
                <div class="tab-container">
                    <div class="tab active" onclick="showTab('profile')">Edit Profile</div>
                    <div class="tab" onclick="showTab('username')">Change Username</div>
                    <div class="tab" onclick="showTab('password')">Change Password</div>
                </div>

                <!-- Edit Profile Tab -->
                <div id="tab-profile" class="tab-content active">
                    <form method="POST">
                        <div class="form-group">
                            <label><i class="fas fa-user"></i> Full Name</label>
                            <input type="text" name="full_name"
                                value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-envelope"></i> Email</label>
                            <input type="email" name="email"
                                value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
                        </div>
                        <button type="submit" name="update_profile" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Profile
                        </button>
                    </form>
                </div>

                <!-- Change Username Tab -->
                <div id="tab-username" class="tab-content">
                    <form method="POST">
                        <div class="form-group">
                            <label><i class="fas fa-user-tag"></i> New Username</label>
                            <input type="text" name="new_username" required placeholder="Enter new username"
                                value="<?php echo htmlspecialchars($user['username']); ?>">
                            <small style="color: var(--text-secondary);">Username must be unique</small>
                        </div>
                        <button type="submit" name="change_username" class="btn btn-warning">
                            <i class="fas fa-sync-alt"></i> Change Username
                        </button>
                    </form>
                </div>

                <!-- Change Password Tab -->
                <div id="tab-password" class="tab-content">
                    <form method="POST">
                        <div class="form-group">
                            <label><i class="fas fa-key"></i> Current Password</label>
                            <input type="password" name="current_password" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-lock"></i> New Password</label>
                            <input type="password" name="new_password" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-lock"></i> Confirm New Password</label>
                            <input type="password" name="confirm_password" required>
                        </div>
                        <button type="submit" name="change_password" class="btn btn-warning">
                            <i class="fas fa-sync-alt"></i> Change Password
                        </button>
                    </form>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="card full-width">
                <div class="card-header"><i class="fas fa-history"></i> Recent Activity</div>
                <div class="activity-timeline">
                    <?php if($activities && $activities->num_rows > 0): ?>
                    <?php while($activity = $activities->fetch_assoc()): ?>
                    <div class="timeline-item">
                        <div class="timeline-icon">
                            <?php
                                $icon = 'fa-circle';
                                if (strpos($activity['action'], 'LOGIN') !== false) $icon = 'fa-sign-in-alt';
                                if (strpos($activity['action'], 'ADD') !== false) $icon = 'fa-plus-circle';
                                if (strpos($activity['action'], 'UPDATE') !== false) $icon = 'fa-edit';
                                if (strpos($activity['action'], 'DELETE') !== false) $icon = 'fa-trash';
                                if (strpos($activity['action'], 'AVATAR') !== false) $icon = 'fa-camera';
                                if (strpos($activity['action'], 'USERNAME') !== false) $icon = 'fa-user-tag';
                                if (strpos($activity['action'], 'PASSWORD') !== false) $icon = 'fa-key';
                                if (strpos($activity['action'], 'IMPORT') !== false) $icon = 'fa-file-import';
                                if (strpos($activity['action'], 'EXPORT') !== false) $icon = 'fa-file-export';
                            ?>
                            <i class="fas <?php echo $icon; ?>"></i>
                        </div>
                        <div class="timeline-content">
                            <strong><?php echo htmlspecialchars($activity['action']); ?></strong>
                            <p><?php echo htmlspecialchars($activity['details']); ?></p>
                            <small>
                                <i class="fas fa-clock"></i>
                                <?php echo date('M d, Y h:i A', strtotime($activity['created_at'])); ?>
                                <i class="fas fa-globe"></i> <?php echo $activity['ip_address']; ?>
                            </small>
                        </div>
                    </div>
                    <?php endwhile; ?>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-history"></i>
                        <p>No activity yet</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
    function showTab(tabName) {
        // Hide all tabs
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.remove('active');
        });

        // Remove active class from all tab buttons
        document.querySelectorAll('.tab').forEach(tab => {
            tab.classList.remove('active');
        });

        // Show selected tab
        document.getElementById('tab-' + tabName).classList.add('active');

        // Add active class to clicked tab
        event.target.classList.add('active');
    }

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

    // Avatar upload
    document.getElementById('fileInput').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;

        // Validate file type
        const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!validTypes.includes(file.type)) {
            alert('❌ Please select a valid image file (JPG, PNG, GIF, WEBP)');
            return;
        }

        // Validate file size (5MB)
        if (file.size > 5 * 1024 * 1024) {
            alert('❌ File too large! Maximum size is 5MB');
            return;
        }

        // Show preview
        const reader = new FileReader();
        reader.onload = function(e) {
            const profileImage = document.getElementById('profileImage');
            const avatarInitials = document.getElementById('avatarInitials');

            profileImage.src = e.target.result;
            profileImage.style.display = 'block';
            if (avatarInitials) avatarInitials.style.display = 'none';
        };
        reader.readAsDataURL(file);

        // Upload file
        uploadAvatar(file);
    });

    function uploadAvatar(file) {
        const formData = new FormData();
        formData.append('avatar', file);
        formData.append('type', 'user');

        const progressDiv = document.getElementById('uploadProgress');
        const progressFill = document.getElementById('progressFill');
        const uploadStatus = document.getElementById('uploadStatus');

        progressDiv.classList.add('active');
        uploadStatus.textContent = 'Uploading... 0%';

        let progress = 0;
        const interval = setInterval(() => {
            if (progress < 90) {
                progress += 10;
                progressFill.style.width = progress + '%';
                uploadStatus.textContent = `Uploading... ${progress}%`;
            }
        }, 300);

        fetch('upload_avatar.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                clearInterval(interval);

                if (data.success) {
                    progressFill.style.width = '100%';
                    uploadStatus.textContent = '✅ Upload complete!';

                    // Update image with cache busting
                    const profileImage = document.getElementById('profileImage');
                    profileImage.src = data.avatar_url + '?t=' + new Date().getTime();

                    // Update header avatar
                    const headerAvatar = document.querySelector('.user-profile .avatar');
                    if (headerAvatar) {
                        headerAvatar.style.backgroundImage = `url('${data.avatar_url}?t=${new Date().getTime()}')`;
                        headerAvatar.style.backgroundSize = 'cover';
                        headerAvatar.style.backgroundPosition = 'center';
                        headerAvatar.textContent = '';
                    }

                    setTimeout(() => {
                        progressDiv.classList.remove('active');
                        progressFill.style.width = '0%';
                    }, 1500);
                } else {
                    alert('❌ Upload failed: ' + data.message);
                    progressDiv.classList.remove('active');
                }
            })
            .catch(error => {
                clearInterval(interval);
                console.error('Error:', error);
                alert('❌ Upload failed. Please try again.');
                progressDiv.classList.remove('active');
            });
    }

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