<?php
require_once 'includes/functions.php';
require_once 'includes/db.php';

// 1. Security Check
check_login();

$msg = "";
$msg_type = "";

// 2. Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $user_id = $_SESSION['user_id'];

    // Basic Validation
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $msg = "All fields are required.";
        $msg_type = "error";
    } elseif ($new_password !== $confirm_password) {
        $msg = "New passwords do not match.";
        $msg_type = "error";
    } elseif (strlen($new_password) < 6) {
        $msg = "New password must be at least 6 characters long.";
        $msg_type = "error";
    } else {
        // 3. Verify Current Password
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if ($user && password_verify($current_password, $user['password'])) {
            // 4. Update to New Password
            $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            $update_stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($update_stmt->execute([$new_hashed_password, $user_id])) {
                
                // --- SUCCESS: REDIRECT LOGIC ---
                // 1. Destroy the current session (logout)
                session_unset();
                session_destroy();
                
                // 2. Redirect to login page with a success flag
                header("Location: login.php?success=password_changed");
                exit(); // Stop script execution
                
            } else {
                $msg = "Database error. Could not update password.";
                $msg_type = "error";
            }
        } else {
            $msg = "Current password is incorrect.";
            $msg_type = "error";
        }
    }
}

// 3. Load Header (Only if not redirected)
require_once 'includes/header.php'; 
?>

<style>
    .cp-container {
        max-width: 600px; margin: 0 auto; background: white;
        padding: 30px; border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05); border: 1px solid #e0e0e0;
    }
    .cp-title { text-align: center; color: #333; margin-bottom: 25px; font-size: 24px; font-weight: 600; }
    .alert { padding: 12px; margin-bottom: 20px; border-radius: 5px; text-align: center; font-weight: 500; }
    .alert-error { background-color: #fde8e8; color: #c53030; border: 1px solid #f8b4b4; }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; margin-bottom: 8px; font-weight: 500; color: #555; }
    .form-group input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px; }
    .form-group input:focus { border-color: #007bff; outline: none; box-shadow: 0 0 0 3px rgba(0,123,255,0.1); }
    .btn-submit {
        width: 100%; padding: 12px; background-color: #007bff; color: white;
        border: none; border-radius: 5px; font-size: 16px; font-weight: 600;
        cursor: pointer; transition: background 0.2s;
    }
    .btn-submit:hover { background-color: #0056b3; }
</style>

<div class="cp-container">
    <h2 class="cp-title">Change Password</h2>

    <?php if (!empty($msg)): ?>
        <div class="alert alert-error">
            <?php echo $msg; ?>
        </div>
    <?php endif; ?>

    <form action="change_password.php" method="POST">
        <div class="form-group">
            <label>Current Password</label>
            <input type="password" name="current_password" required placeholder="Enter current password">
        </div>

        <div class="form-group">
            <label>New Password</label>
            <input type="password" name="new_password" required placeholder="Enter new password (min 6 chars)">
        </div>

        <div class="form-group">
            <label>Confirm New Password</label>
            <input type="password" name="confirm_password" required placeholder="Re-enter new password">
        </div>

        <button type="submit" class="btn-submit">Update Password</button>
    </form>
</div>
