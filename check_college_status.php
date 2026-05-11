<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Start session and check login
check_login();

// Check if the user has permission AND a college ID was sent
if (!user_can('MONITOR_ATTENDANCE') || !isset($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Access Denied or Missing ID']);
    exit();
}

$id = (int)$_GET['id'];
$status = 'offline'; // Default to offline

try {
    // 1. Fetch the college details
    $stmt = $pdo->prepare("SELECT * FROM college_info WHERE id = ?");
    $stmt->execute([$id]);
    $college = $stmt->fetch();

    if ($college) {
        // 2. Decrypt the sensitive data
        // We must URL-encode them in case they have special characters
        $ip = $college['ip_address'];
        $user = urlencode($college['user_name']);
        $pass = urlencode(decrypt_data($college['password']));
        $db = urlencode($college['database_name']);
        $sk = urlencode(decrypt_data($college['security_key']));

        // 3. Build the full URL
        $url = "http://{$ip}/attendance_data_service.php?u={$user}&c={$pass}&s={$db}&sk={$sk}";

        // 4. Use cURL to check the URL with a short timeout
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5); // 5-second timeout
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3); // 3-second connect timeout
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // 5. Check for a successful response
        // We consider it "online" if we get a 200 OK and *any* response
        if ($http_code == 200 && !empty($response)) {
            $status = 'online';
        }
    }

} catch (Exception $e) {
    // Error during decryption or DB fetch, leave status as 'offline'
    $status = 'offline';
}

// 6. Return the status as JSON
header('Content-Type: application/json');
echo json_encode(['status' => $status]);
exit();
?>