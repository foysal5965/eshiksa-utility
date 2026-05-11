<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require '../includes/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // 1. Find user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    // 2. Verify password
    if ($user && password_verify($password, $user['password'])) {
        
        // 3. Store user info in session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];

        // 4. Fetch and store permissions
        $sql = "SELECT m.menu_key 
                FROM user_permissions up
                JOIN menus m ON up.menu_id = m.id
                WHERE up.user_id = ?";
                
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user['id']]);
        
        $permissions = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $_SESSION['permissions'] = $permissions;
        
        // 5. Redirect to dashboard
        header('Location: ../dashboard.php');
        exit();

    } else {
        // Failed login
        header('Location: ../login.php?error=1');
        exit();
    }
}
?>