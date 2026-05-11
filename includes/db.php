<?php
$host = 'gateway01.ap-southeast-1.prod.alicloud.tidbcloud.com';
$db   = 'sys'; // The database name you used in phpMyAdmin
$user = '2aQth8dhqeE6RHh.root';
$pass = 'ReeTPS4tMbBpIfQ1';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     throw new \PDOException($e->getMessage(), (int)$e->getCode());
}


// --- NEW SECURITY FUNCTIONS ---


// !! Change this to a long, random string and keep it safe !!
define('ENCRYPTION_KEY', 'put-a-very-long-random-string-here-12345');
define('ENCRYPTION_IV', openssl_random_pseudo_bytes(16)); 


//   Encrypts data 

function encrypt_data($data) {
    $cipher = "AES-256-CBC";
    $iv = ENCRYPTION_IV;
    $encrypted = openssl_encrypt($data, $cipher, ENCRYPTION_KEY, 0, $iv);
    // Return IV and encrypted data, so we can decrypt it
    return base64_encode($iv . $encrypted);
}

/**
 * Decrypts data
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