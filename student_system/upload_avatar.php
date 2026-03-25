<?php
require_once 'config.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$user_id = $_SESSION['user_id'];
$type = $_POST['type'] ?? 'user';
$student_id = $_POST['student_id'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
    $file = $_FILES['avatar'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    // Validate file
    if (!in_array($file['type'], $allowed_types)) {
        echo json_encode(['success' => false, 'message' => 'Only JPG, PNG, GIF, WEBP allowed']);
        exit();
    }
    
    if ($file['size'] > $max_size) {
        echo json_encode(['success' => false, 'message' => 'File too large (max 5MB)']);
        exit();
    }
    
    // Create upload directory if not exists
    $upload_dir = 'uploads/avatars/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $type . '_' . ($type === 'user' ? $user_id : $student_id) . '_' . time() . '.' . $extension;
    $target_path = $upload_dir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        if ($type === 'user') {
            // Get old avatar to delete
            $sql = "SELECT avatar FROM users WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $old = $result->fetch_assoc();
            $stmt->close();
            
            // Delete old avatar if exists and not default
            if (!empty($old['avatar']) && file_exists($old['avatar']) && $old['avatar'] !== $target_path) {
                unlink($old['avatar']);
            }
            
            // Update user avatar
            $update = "UPDATE users SET avatar = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update);
            $update_stmt->bind_param("si", $target_path, $user_id);
            
            if ($update_stmt->execute()) {
                // Update session
                $_SESSION['avatar'] = $target_path;
                logActivity($user_id, 'AVATAR_UPDATE', 'Updated profile picture');
                echo json_encode([
                    'success' => true, 
                    'message' => 'Avatar uploaded successfully',
                    'avatar_url' => $target_path
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Database update failed']);
            }
            $update_stmt->close();
        } else {
            // Check if student exists
            $check = $conn->prepare("SELECT student_id FROM students WHERE student_id = ?");
            $check->bind_param("s", $student_id);
            $check->execute();
            $check_result = $check->get_result();
            
            if ($check_result->num_rows == 0) {
                echo json_encode(['success' => false, 'message' => 'Student not found']);
                $check->close();
                exit();
            }
            $check->close();
            
            // Get old avatar to delete
            $sql = "SELECT avatar FROM students WHERE student_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $student_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $old = $result->fetch_assoc();
            $stmt->close();
            
            // Delete old avatar if exists
            if (!empty($old['avatar']) && file_exists($old['avatar']) && $old['avatar'] !== $target_path) {
                unlink($old['avatar']);
            }
            
            // Update student avatar
            $update = "UPDATE students SET avatar = ? WHERE student_id = ?";
            $update_stmt = $conn->prepare($update);
            $update_stmt->bind_param("ss", $target_path, $student_id);
            
            if ($update_stmt->execute()) {
                logActivity($user_id, 'STUDENT_AVATAR', "Updated avatar for student: $student_id");
                echo json_encode([
                    'success' => true, 
                    'message' => 'Student avatar uploaded successfully',
                    'avatar_url' => $target_path
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Database update failed']);
            }
            $update_stmt->close();
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Upload failed - check folder permissions']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'No file uploaded']);
}
?>