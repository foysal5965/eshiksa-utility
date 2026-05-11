<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Checks if the currently logged-in user has a specific permission.
 */
function user_can($permission_key) {
    if (isset($_SESSION['permissions']) && in_array($permission_key, $_SESSION['permissions'])) {
        return true;
    }
    return false;
}

/**
 * A wrapper to protect pages. Call this at the top of any page
 * that requires a user to be logged in.
 */
function check_login() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }
}
?>