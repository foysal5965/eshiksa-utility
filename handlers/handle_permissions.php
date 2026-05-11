<?php
require_once '../includes/functions.php'; // For session and user_can
require_once '../includes/db.php';

check_login();

// Double-check the logged-in user has permission to do this
if (!user_can('User Management')) {
    die('Access Denied');
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_POST['user_id'];
    // Get the array of checked menu IDs. If nothing is checked, default to an empty array.
    $menu_ids = isset($_POST['menu_ids']) ? $_POST['menu_ids'] : [];

    try {
        // Use a transaction for safety
        $pdo->beginTransaction();

        // 1. Delete all *old* permissions for this user
        $stmt_delete = $pdo->prepare("DELETE FROM user_permissions WHERE user_id = ?");
        $stmt_delete->execute([$user_id]);

        // 2. Insert all *new* permissions
        $stmt_insert = $pdo->prepare("INSERT INTO user_permissions (user_id, menu_id) VALUES (?, ?)");
        
        foreach ($menu_ids as $menu_id) {
            $stmt_insert->execute([$user_id, $menu_id]);
        }
        
        // 3. Commit the changes
        $pdo->commit();

        // Redirect back with a success message
        header('Location: ../user_management.php?user_id=' . $user_id . '&success=1');
        exit();

    } catch (Exception $e) {
        // If anything went wrong, roll back
        $pdo->rollBack();
        die("Error updating permissions: " . $e->getMessage());
    }
}
?>