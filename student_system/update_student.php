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
$message = '';
$error = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if(isset($_POST['update_marks']) && isset($_POST['subject_id']) && isset($_POST['new_marks'])) {
        $subject_id = intval($_POST['subject_id']);
        $new_marks = floatval($_POST['new_marks']);
        
        if($new_marks >= 0 && $new_marks <= 100) {
            $stmt = $conn->prepare("UPDATE subjects SET marks = ? WHERE id = ? AND student_id = ?");
            $stmt->bind_param("dis", $new_marks, $subject_id, $student_id);
            if($stmt->execute()) {
                $message = "Marks updated successfully!";
                logActivity($_SESSION['user_id'], 'UPDATE', "Updated marks for subject ID: $subject_id");
            }
            $stmt->close();
        } else {
            $error = "Marks must be between 0 and 100!";
        }
    }
    
    if(isset($_POST['add_subject']) && isset($_POST['new_subject']) && isset($_POST['new_marks_add'])) {
        $subject_name = $conn->real_escape_string($_POST['new_subject']);
        $marks = floatval($_POST['new_marks_add']);
        
        if($marks >= 0 && $marks <= 100) {
            $stmt = $conn->prepare("INSERT INTO subjects (student_id, subject_name, marks) VALUES (?, ?, ?)");
            $stmt->bind_param("ssd", $student_id, $subject_name, $marks);
            if($stmt->execute()) {
                $message = "Subject added successfully!";
                logActivity($_SESSION['user_id'], 'ADD', "Added subject: $subject_name for student");
            }
            $stmt->close();
        } else {
            $error = "Marks must be between 0 and 100!";
        }
    }
    
    if(isset($_POST['remove_subject']) && isset($_POST['subject_id_remove'])) {
        $subject_id = intval($_POST['subject_id_remove']);
        
        // Check if at least 3 subjects will remain
        $count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM subjects WHERE student_id = ?");
        $count_stmt->bind_param("s", $student_id);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $count = $count_result->fetch_assoc()['total'];
        $count_stmt->close();
        
        if($count > 3) {
            $del_stmt = $conn->prepare("DELETE FROM subjects WHERE id = ? AND student_id = ?");
            $del_stmt->bind_param("is", $subject_id, $student_id);
            if($del_stmt->execute()) {
                $message = "Subject removed successfully!";
                logActivity($_SESSION['user_id'], 'DELETE', "Removed subject ID: $subject_id");
            }
            $del_stmt->close();
        } else {
            $error = "Cannot remove subject. Minimum 3 subjects required!";
        }
    }
}

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
    <title>Update Student</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
    .avatar-upload {
        position: relative;
        width: 100px;
        height: 100px;
        margin: 0 auto;
        cursor: pointer;
    }

    .avatar-upload img {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid var(--accent-primary);
    }

    .avatar-upload .student-avatar {
        width: 100px;
        height: 100px;
        font-size: 36px;
        border: 3px solid var(--accent-primary);
    }

    .avatar-upload-overlay {
        position: absolute;
        bottom: 0;
        right: 0;
        background: var(--accent-primary);
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        border: 2px solid white;
        transition: all 0.3s;
    }

    .avatar-upload-overlay:hover {
        transform: scale(1.1);
        background: var(--accent-secondary);
    }

    #studentFileInput {
        display: none;
    }

    .upload-progress {
        display: none;
        margin-top: 10px;
    }

    .upload-progress.active {
        display: block;
    }

    .user-profile .avatar {
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
                <i class="fas fa-edit"></i> Update Student: <?php echo htmlspecialchars($student['full_name']); ?>
            </div>

            <?php if($message): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $message; ?></div>
            <?php endif; ?>

            <?php if($error): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <div class="student-info"
                style="margin-bottom: 20px; padding: 15px; background: var(--bg-tertiary); border-radius: 5px;">
                <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                    <div class="avatar-upload" onclick="document.getElementById('studentFileInput').click()">
                        <?php if($student['avatar']): ?>
                        <img src="<?php echo $student['avatar']; ?>?t=<?php echo time(); ?>" alt="Student Photo"
                            id="studentImage">
                        <?php else: ?>
                        <div class="student-avatar" id="studentInitials"
                            style="background-color: <?php echo $student['avatar_color'] ?? getAvatarColor($student['full_name']); ?>; display: flex; align-items: center; justify-content: center; border-radius: 50%;">
                            <?php echo getInitials($student['full_name']); ?>
                        </div>
                        <img src="" alt="Student Photo" id="studentImage" style="display: none;">
                        <?php endif; ?>
                        <div class="avatar-upload-overlay">
                            <i class="fas fa-camera"></i>
                        </div>
                    </div>

                    <input type="file" id="studentFileInput" accept="image/jpeg,image/png,image/gif,image/webp">

                    <div class="upload-progress" id="uploadProgress">
                        <div class="progress-bar-custom">
                            <div class="progress-fill" id="progressFill"></div>
                        </div>
                        <p id="uploadStatus" style="font-size: 12px; margin-top: 5px;"></p>
                    </div>

                    <div style="flex: 1;">
                        <h3><?php echo htmlspecialchars($student['full_name']); ?></h3>
                        <p><strong>ID:</strong> <?php echo htmlspecialchars($student['student_id']); ?> |
                            <strong>Program:</strong> <?php echo htmlspecialchars($student['program']); ?>
                        </p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($student['email'] ?: 'N/A'); ?></p>
                    </div>
                </div>
            </div>

            <h4 style="margin-bottom: 15px;"><i class="fas fa-book-open"></i> Current Subjects</h4>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Marks</th>
                            <th>Grade</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($subjects->num_rows > 0): ?>
                        <?php while($subject = $subjects->fetch_assoc()): 
                            $subGrade = calculateGrade($subject['marks']);
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($subject['subject_name']); ?></td>
                            <td>
                                <div class="progress-bar" style="width: 150px;">
                                    <div class="progress" style="width: <?php echo $subject['marks']; ?>%;"></div>
                                    <span><?php echo $subject['marks']; ?>%</span>
                                </div>
                            </td>
                            <td>
                                <span class="grade-badge"
                                    style="background-color: <?php echo getGradeColor($subGrade); ?>20; color: <?php echo getGradeColor($subGrade); ?>; border-color: <?php echo getGradeColor($subGrade); ?>">
                                    <?php echo $subGrade; ?>
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; gap: 5px; align-items: center;">
                                    <form method="POST" style="display: flex; gap: 5px;">
                                        <input type="hidden" name="subject_id" value="<?php echo $subject['id']; ?>">
                                        <input type="number" name="new_marks" min="0" max="100" step="0.01"
                                            placeholder="New marks" style="width: 100px;" required>
                                        <button type="submit" name="update_marks" class="btn btn-success btn-icon"
                                            title="Update">
                                            <i class="fas fa-save"></i>
                                        </button>
                                    </form>
                                    <form method="POST" style="display: inline;"
                                        onsubmit="return confirm('Remove this subject?')">
                                        <input type="hidden" name="subject_id_remove"
                                            value="<?php echo $subject['id']; ?>">
                                        <button type="submit" name="remove_subject" class="btn btn-danger btn-icon"
                                            title="Remove">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
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
                </table>
            </div>

            <h4 style="margin-top: 30px; margin-bottom: 15px;"><i class="fas fa-plus-circle"></i> Add New Subject</h4>
            <form method="POST" style="background: var(--bg-tertiary); padding: 20px; border-radius: 5px;">
                <div style="display: grid; grid-template-columns: 2fr 1fr auto; gap: 10px; align-items: end;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label>Subject Name</label>
                        <input type="text" name="new_subject" required maxlength="100" placeholder="e.g., Physics">
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label>Marks (0-100)</label>
                        <input type="number" name="new_marks_add" min="0" max="100" step="0.01" required
                            placeholder="Enter marks">
                    </div>
                    <button type="submit" name="add_subject" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Subject
                    </button>
                </div>
            </form>

            <div style="margin-top: 20px; display: flex; gap: 10px;">
                <a href="student_report.php?id=<?php echo urlencode($student_id); ?>" class="btn btn-info">
                    <i class="fas fa-file-alt"></i> View Full Report
                </a>
                <a href="dashboard.php" class="btn" style="background: #6c757d; color: white;">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <script>
    // Student avatar upload
    document.getElementById('studentFileInput').addEventListener('change', function(e) {
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
            const studentImage = document.getElementById('studentImage');
            const studentInitials = document.getElementById('studentInitials');

            studentImage.src = e.target.result;
            studentImage.style.display = 'block';
            if (studentInitials) studentInitials.style.display = 'none';
        };
        reader.readAsDataURL(file);

        // Upload file
        uploadStudentAvatar(file, '<?php echo $student_id; ?>');
    });

    function uploadStudentAvatar(file, studentId) {
        const formData = new FormData();
        formData.append('avatar', file);
        formData.append('type', 'student');
        formData.append('student_id', studentId);

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
                    const studentImage = document.getElementById('studentImage');
                    studentImage.src = data.avatar_url + '?t=' + new Date().getTime();

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
<?php $subjects_stmt->close(); ?>