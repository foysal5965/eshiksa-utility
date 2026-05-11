<?php
// Force errors to show if something is still wrong
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/includes/header.php';
check_login(); 
?>

<div class="py-6">
    <h2 class="text-2xl font-bold mb-4">Dashboard</h2>
    <div class="bg-blue-50 border-l-4 border-blue-500 p-4">
        <p class="text-blue-700">
            Welcome back, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>!
        </p>
        <p class="mt-2">You have successfully connected to TiDB Cloud and Vercel.</p>
    </div>
</div>

<?php 
// Close the content div and body tags opened in header.php
echo '</div></body></html>'; 
?>