<?php
require '../includes/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $phone_no = $_POST['phone_no'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // 1. Validation
    if ($password !== $confirm_password) {
        header('Location: ../register.php?error=Passwords do not match');
        exit();
    }

    // 2. Check if username exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        header('Location: ../register.php?error=Username already taken');
        exit();
    }

    // 3. Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // 4. Insert user
    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, phone_no, password) VALUES (?, ?, ?)");
        $stmt->execute([$username, $phone_no, $hashed_password]);

        // 5. Redirect to login
        header('Location: ../login.php?success=1');
        exit();

    } catch (PDOException $e) {
        header('Location: ../register.php?error=Database error');
        exit();
    }
}
?>