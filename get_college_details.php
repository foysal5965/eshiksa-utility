<?php
// This file securely fetches a single college's details for the edit modal

require_once 'includes/db.php';
require_once 'includes/functions.php';

// Start session and check login
check_login();

// Check for permission AND if an ID was provided
if (!user_can('Add College') || !isset($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Access Denied or Missing ID']);
    exit();
}

$id = (int)$_GET['id'];

try {
    $stmt = $pdo->prepare("SELECT * FROM college_info WHERE id = ?");
    $stmt->execute([$id]);
    $college = $stmt->fetch();

    if ($college) {
        // Decrypt sensitive data before sending it to the edit form
        $college['password'] = !empty($college['password']) ? decrypt_data($college['password']) : '';
        $college['security_key'] = !empty($college['security_key']) ? decrypt_data($college['security_key']) : '';
        
        header('Content-Type: application/json');
        echo json_encode($college);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'College not found']);
    }

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
}
?>