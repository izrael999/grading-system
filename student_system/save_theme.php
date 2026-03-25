<?php
require_once 'config.php';

if (!isLoggedIn()) {
    http_response_code(401);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['theme'])) {
    $theme = $_POST['theme'];
    $user_id = $_SESSION['user_id'];
    
    $stmt = $conn->prepare("UPDATE users SET theme_preference = ? WHERE id = ?");
    $stmt->bind_param("si", $theme, $user_id);
    $stmt->execute();
    $stmt->close();
    
    echo json_encode(['success' => true]);
}
?>