<?php
// update_session.php
require_once 'config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

// Refresh session data from database
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['avatar_color'] = $user['avatar_color'];
    $_SESSION['avatar'] = $user['avatar'];
    $_SESSION['theme_preference'] = $user['theme_preference'] ?? 'dark';
    
    echo json_encode([
        'success' => true, 
        'message' => 'Session updated successfully',
        'user' => [
            'username' => $user['username'],
            'full_name' => $user['full_name'],
            'email' => $user['email']
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'User not found']);
}
?>