<?php
require_once 'includes/header.php';
check_login(); // This function protects the page
?>

<h2>Dashboard</h2>
<p>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
<p>This is the main dashboard. You can see this page because you are logged in.</p>



