<?php
// check_user.php
require_once 'config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>User Check</title>
    <style>
        body { font-family: Arial; background: #0a0f1e; color: #fff; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: #141b2b; padding: 30px; border-radius: 10px; }
        pre { background: #1a2538; padding: 15px; border-radius: 5px; overflow: auto; }
        .success { color: #00ff88; }
        .warning { color: #ffaa00; }
        .error { color: #ff4444; }
        button { padding: 10px 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 5px; cursor: pointer; margin: 5px; }
        .nav { margin-top: 20px; }
        .nav a { color: #00a6ff; text-decoration: none; margin-right: 15px; }
    </style>
</head>
<body>
<div class='container'>";

echo "<h2>🔍 User Session & Database Check</h2>";

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT id, username, full_name, email, avatar, avatar_color, theme_preference, created_at FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    echo "<h3>📌 Current Session Data:</h3>";
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
    
    echo "<h3>📌 Database Record:</h3>";
    echo "<pre>";
    print_r($user);
    echo "</pre>";
    
    if ($user) {
        $matches = true;
        if ($_SESSION['full_name'] !== $user['full_name']) {
            echo "<p class='warning'>⚠️ Session name ('{$_SESSION['full_name']}') doesn't match database ('{$user['full_name']}')!</p>";
            $matches = false;
        }
        if ($_SESSION['username'] !== $user['username']) {
            echo "<p class='warning'>⚠️ Session username ('{$_SESSION['username']}') doesn't match database ('{$user['username']}')!</p>";
            $matches = false;
        }
        
        if (!$matches) {
            echo "<button onclick='updateSession()'>🔄 Update Session Now</button>";
        } else {
            echo "<p class='success'>✅ Session and database are synchronized!</p>";
        }
    }
} else {
    echo "<p class='error'>❌ Not logged in</p>";
    echo "<a href='index.php'><button>Go to Login</button></a>";
}

echo "<div class='nav'>";
echo "<a href='profile.php'>👤 Go to Profile</a>";
echo "<a href='dashboard.php'>📊 Dashboard</a>";
echo "<a href='logout.php'>🚪 Logout</a>";
echo "</div>";

echo "</div>

<script>
function updateSession() {
    fetch('update_session.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ Session updated! Page will reload.');
                location.reload();
            } else {
                alert('❌ Error: ' + data.message);
            }
        })
        .catch(error => {
            alert('❌ Error: ' + error);
        });
}
</script>

</body>
</html>";
?>