<?php
// 1. FORCE ERROR REPORTING (Must be at the very top)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 2. PRE-FLIGHT CHECK
$required_files = [
    __DIR__ . '/includes/db.php',
    __DIR__ . '/includes/functions.php',
    __DIR__ . '/includes/header.php'
];

foreach ($required_files as $file) {
    if (!file_exists($file)) {
        die("Critical Error: File missing or Case-Sensitivity issue at: " . $file);
    }
}

// 3. START APP
require_once __DIR__ . '/includes/header.php';
// check_login(); // Temporarily comment this out if the page is still blank
?>

<div class="p-8">
    <h2 class="text-2xl font-bold">Dashboard</h2>
    <p>Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Guest'); ?>!</p>
    
    <div class="mt-4 p-4 bg-green-100 text-green-700 border border-green-300 rounded">
        If you can see this, the Dashboard is working!
    </div>
</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
