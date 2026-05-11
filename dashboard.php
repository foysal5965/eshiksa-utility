<?php
require_once 'includes/header.php';
check_login(); // This function protects the page
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>

<h2>Dashboard</h2>
<p>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
<p>This is the main dashboard. You can see this page because you are logged in.</p>



