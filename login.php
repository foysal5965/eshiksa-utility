<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login to EMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Segoe+UI:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #ffffff;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
           
        }

        .login-container {
            width: 100%;
            max-width: 380px;
            text-align: center;
            padding: 40px 30px; 
            
            /* Your requested border, plus card styling */
            border: 1px solid #dee2e6; 
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        }

        /* Logo Style */
        .logo-img {
            width: 150px; 
            height: 150px;
            object-fit: contain;
            margin-bottom: 20px;
        }

        /* "Login to EMS" Title */
        h2 {
            color: #004085; 
            font-size: 24px;
            font-weight: 400;
            margin-bottom: 30px;
            margin-top: 0;
        }

        /* Input Fields */
        .form-group {
            margin-bottom: 15px;
        }

        input[type="text"], 
        input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            font-size: 15px;
            border: 1px solid #ced4da; /* Light grey border */
            border-radius: 5px;
            background-color: #e8f0fe; /* The light blue background from the image */
            box-sizing: border-box; /* Ensures padding doesn't increase width */
            color: #333;
            outline: none;
            transition: border-color 0.2s;
        }

        input[type="text"]:focus, 
        input[type="password"]:focus {
            border-color: #357abd;
            box-shadow: 0 0 0 2px rgba(53, 122, 189, 0.2);
        }

        /* Sign In Button */
        .btn-login {
            width: 100%;
            padding: 12px;
            background-color: #337ab7; /* The blue button color */
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 10px;
            transition: background-color 0.2s;
        }

        .btn-login:hover {
            background-color: #286090;
        }

        /* Links (Forgot Password | Register) */
        .auth-links {
            margin-top: 20px;
            font-size: 14px;
            color: #0056b3;
        }

        .auth-links a {
            color: #0056b3;
            text-decoration: none;
        }
        
        .auth-links a:hover {
            text-decoration: underline;
        }

        .divider {
            margin: 0 8px;
            color: #ccc;
        }

        /* Footer Text */
        .footer-copy {
            margin-top: 40px;
            font-size: 12px;
            color: #555;
        }

        .eshiksa-red {
            color: red;
            font-weight: bold;
        }
        .eshiksa-black {
            color: black;
            font-weight: bold;
        }

        /* Error Message */
        .error-msg {
            color: #dc3545;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .success-msg {
            color: #155724;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
        }
    </style>
</head>
<body>

    <div class="login-container">
        
        <img src="assets/images/college_logo_placeholder.png" alt="College Logo" class="logo-img">
        
        <h2>Login to EMS</h2>

        <?php if(isset($_GET['error'])): ?>
            <div class="error-msg">Invalid username or password.</div>
        <?php endif; ?>
        <?php if(isset($_GET['success'])): ?>
            <div class="success-msg">Registration successful! Please login.</div>
        <?php endif; ?>

        <form action="handlers/handle_login.php" method="POST">
            <div class="form-group">
                <input type="text" name="username" placeholder="Username" required>
            </div>
            <div class="form-group">
                <input type="password" name="password" placeholder="Password" required>
            </div>
            
            <button type="submit" class="btn-login">Sign In</button>
        </form>

        <div class="auth-links">
            <a href="#">Forgot Password?</a>
            <span class="divider">|</span>
            <a href="register.php">Register Now</a>
        </div>

        <div class="footer-copy">
            Copyright &copy; 2025 All rights reserved <span class="eshiksa-red">e</span><span class="eshiksa-black">Shiksa</span>
        </div>

    </div>

</body>
</html>