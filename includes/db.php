<?php
// ==========================================
// 1. DATABASE CONNECTION (SECURE VERCEL WAY)
// ==========================================

// Fetch from Vercel Environment Variables
// $host = getenv('DB_HOST');
// $port = getenv('DB_PORT') ?: 4000;
// $db   = getenv('DB_NAME');
// $user = getenv('DB_USER');
// $pass = getenv('DB_PASS');
$host = getenv('DB_HOST');
$port = getenv('DB_PORT') ?: 4000;
$db   = getenv('DB_NAME');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');

$dsn = "mysql://2aQth8dhqeE6RHh.root:ReeTPS4tMbBpIfQ1@gateway01.ap-southeast-1.prod.alicloud.tidbcloud.com:4000/sys";

// THE NUCLEAR FIX: Point directly to the downloaded cacert.pem file
// Make sure cacert.pem is saved in the same 'includes/' folder as this db.php file!
$ca_path = __DIR__ . '/cacert.pem';

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    
    // Force Secure TLS Connection (REQUIRED for TiDB) using our local file
    PDO::MYSQL_ATTR_SSL_CA       => $ca_path,
    PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false, 
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     die("Database Connection Failed: " . $e->getMessage());
}

// ==========================================
// 2. ENCRYPTION FUNCTIONS (BUG FIXED)
// ==========================================

// !! Change this to a long, random string and keep it safe !!
// If you ever change this string later, ALL your encrypted data will be unreadable.
define('ENCRYPTION_KEY', 'put-a-very-long-random-string-here-12345');

/**
 * Encrypts data properly by generating a unique IV and attaching it.
 */
function encrypt_data($data) {
    $cipher = "AES-256-CBC";
    $iv_length = openssl_cipher_iv_length($cipher);
    // Generate the unique IV INSIDE the function
    $iv = openssl_random_pseudo_bytes($iv_length); 
    
    $encrypted = openssl_encrypt($data, $cipher, ENCRYPTION_KEY, 0, $iv);
    // Return IV combined with encrypted data
    return base64_encode($iv . $encrypted);
}

/**
 * Decrypts data by dynamically splitting the IV from the stored string.
 */
function decrypt_data($data) {
    $cipher = "AES-256-CBC";
    $data = base64_decode($data);
    
    $iv_length = openssl_cipher_iv_length($cipher);
    // Extract the exact IV that was used when it was encrypted
    $iv = substr($data, 0, $iv_length);
    $encrypted = substr($data, $iv_length);
    
    return openssl_decrypt($encrypted, $cipher, ENCRYPTION_KEY, 0, $iv);
}
?>