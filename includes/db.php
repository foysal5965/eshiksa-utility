<?php
$host = getenv('DB_HOST');
$port = getenv('DB_PORT');
$db   = getenv('DB_NAME');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    // Aiven strictly requires this SSL certificate to connect
    PDO::MYSQL_ATTR_SSL_CA       => __DIR__ . '/ca.pem',
    PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
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