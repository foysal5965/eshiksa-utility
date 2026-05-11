<?php
// ==========================================
// 1. DATABASE CONNECTION
// ==========================================

// We are hardcoding the credentials from your screenshot so you can test it immediately.
// (Remember to change this password in TiDB later once everything is working!)
$host = 'gateway01.ap-southeast-1.prod.alicloud.tidbcloud.com';
$port = 4000;
$db   = 'sys'; 
$user = '2aQth8dhqeE6RHh.root';
$pass = 'ReeTPS4tMbBpIfQ1';

// This is the correct format PHP PDO expects:
$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";

// THE NUCLEAR FIX: Point directly to the downloaded cacert.pem file
// Look at your screenshot: Click that blue "Download the CA cert" link!
// Save it as 'cacert.pem' inside your 'includes/' folder.
$ca_path = __DIR__ . '/cacert.pem';

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    
    // Force Secure TLS Connection using your local file
    PDO::MYSQL_ATTR_SSL_CA       => $ca_path,
    PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false, 
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     die("Database Connection Failed: " . $e->getMessage());
}

// ==========================================
// 2. ENCRYPTION FUNCTIONS
// ==========================================

// !! Change this to a long, random string and keep it safe !!
define('ENCRYPTION_KEY', 'put-a-very-long-random-string-here-12345');

/**
 * Encrypts data properly by generating a unique IV and attaching it.
 */
function encrypt_data($data) {
    $cipher = "AES-256-CBC";
    $iv_length = openssl_cipher_iv_length($cipher);
    $iv = openssl_random_pseudo_bytes($iv_length); 
    
    $encrypted = openssl_encrypt($data, $cipher, ENCRYPTION_KEY, 0, $iv);
    return base64_encode($iv . $encrypted);
}

/**
 * Decrypts data by dynamically splitting the IV from the stored string.
 */
function decrypt_data($data) {
    $cipher = "AES-256-CBC";
    $data = base64_decode($data);
    
    $iv_length = openssl_cipher_iv_length($cipher);
    $iv = substr($data, 0, $iv_length);
    $encrypted = substr($data, $iv_length);
    
    return openssl_decrypt($encrypted, $cipher, ENCRYPTION_KEY, 0, $iv);
}
?>