<!-- <?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
// Start the session if not already started
ini_set('session.cookie_httponly', 1); 
ini_set('session.use_only_cookies', 1); 
ini_set('session.cookie_secure', 1);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/functions.php';
require_once 'includes/db.php';
if (isset($_SESSION['user_id'])) {
    try {
        //  Get fresh permissions from the database
        $stmt_refresh = $pdo->prepare("
            SELECT m.menu_key 
            FROM user_permissions up 
            JOIN menus m ON up.menu_id = m.id 
            WHERE up.user_id = ?
        ");
        $stmt_refresh->execute([$_SESSION['user_id']]);
        $fresh_permissions = $stmt_refresh->fetchAll(PDO::FETCH_COLUMN);
        
        //  Overwrite the session data with fresh data
        $_SESSION['permissions'] = $fresh_permissions;
    } catch (Exception $e) {
        // If DB fails, do nothing, keep old session data
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Education Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/PapaParse/5.4.1/papaparse.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    
    <style>
        /* --- GLOBAL STYLES --- */
        body { 
            font-family: 'Open Sans', sans-serif;
            margin: 0;
            background-color: #ffffff; 
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* --- HEADER SECTION STYLES (Matching the Image) --- */
        .main-header {
            background-color: #fff;
            padding: 15px 30px 10px 30px;
        }
        
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .header-titles h1 {
            font-family: 'Roboto', sans-serif;
            font-size: 32px;
            font-weight: 400;
            color: #000;
            margin: 0;
            line-height: 1.2;
        }

        .header-titles h2 {
            font-family: 'Roboto', sans-serif;
            font-size: 20px;
            font-weight: 700;
            color: #0056b3; /* The specific blue from the image */
            margin: 5px 0 0 0;
        }

        .user-info-area {
            text-align: right;
            font-size: 13px;
            color: #666;
        }

        .user-info-line {
            margin-bottom: 5px;
        }

        .user-name {
            color: #800080; /* Purple color for name */
            font-weight: 700;
        }

        .header-links a {
            color: #666;
            text-decoration: none;
            margin-left: 15px;
        }
        .header-links a:hover { text-decoration: underline; color: #333; }

        .logo-right {
            margin-top: 5px;
            display: flex;
            justify-content: flex-end;
            align-items: center;
        }
        /* Placeholder for the eShiksa logo */
        .eshiksa-logo-text {
            font-weight: bold;
            font-size: 18px;
            color: #d9534f; /* Reddish color */
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* --- NAVIGATION BAR STYLES (The Blue Lines) --- */
        .main-navbar {
            background-color: #fff;
            border-top: 2px solid #004085;    /* Dark blue top line */
            border-bottom: 2px solid #004085; /* Dark blue bottom line */
            padding: 0 30px;
        }

        .nav-list {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            flex-wrap: wrap;
        }

        .nav-item {
            position: relative;
        }

        /* The links inside the navbar */
        .nav-link, .dropdown-btn {
            display: block;
            padding: 12px 18px;
            color: #555;
            font-weight: 600;
            text-decoration: none;
            font-size: 14px;
            background: none;
            border: none;
            cursor: pointer;
            font-family: 'Open Sans', sans-serif;
        }

        /* Hover effects */
        .nav-item:hover > .nav-link, 
        .nav-item:hover > .dropdown-btn {
            color: #0056b3; /* Blue on hover */
            background-color: #f8f9fa;
        }

        /* Dropdown specific */
        .dropdown-btn::after {
            content: ' ▼';
            font-size: 0.7em;
            vertical-align: middle;
            color: #999;
        }

        .dropdown-menu {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            background-color: #fff;
            min-width: 220px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            border: 1px solid #ddd;
            z-index: 1000;
        }

        .dropdown-menu a {
            display: block;
            padding: 10px 15px;
            color: #444;
            text-decoration: none;
            font-size: 13px;
            border-bottom: 1px solid #f1f1f1;
        }

        .dropdown-menu a:hover {
            background-color: #f1f5f9;
            color: #0056b3;
        }

        .nav-item:hover .dropdown-menu {
            display: block;
        }

        /* --- CONTENT AREA --- */
        .content { 
            padding: 30px; 
            flex-grow: 1;
            background-color: #ffffff;
        }

        /* --- PREVIOUS GLOBAL UTILITIES (Kept for compatibility) --- */
        .form-section-card {
            background: #ffffff;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
            margin-top: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        /* Basic form inputs */
        .form-input, .form-select {
            padding: 8px 12px; border: 1px solid #d1d5db;
            border-radius: 5px; width: 100%;
        }
        .btn {
            padding: 10px 15px; color: white; background-color: #007bff;
            border: none; border-radius: 5px; cursor: pointer;
        }
        .btn:hover { background-color: #0056b3; }
        .error { color: red; } .success { color: green; }

        /* Footer */
        footer {
            text-align: center; padding: 20px; margin-top: auto;
            background-color: #f8f9fa; color: #666; font-size: 0.9em;
            border-top: 1px solid #ddd;
        }

        /* Modal */
        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4); }
        .modal-content { background-color: #fefefe; margin: 10% auto; padding: 20px; border: 1px solid #888; width: 500px; border-radius: 5px; }
        .close-btn { float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
    </style>
</head>
<body class="antialiased text-gray-800">

    <header class="main-header">
        <div class="header-top">
            <div class="header-titles">
                <h1>eShiksa Utility</h1>
            </div>
            
            <div class="user-info-area">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="user-info-line">
                        Welcome <span class="user-name"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                        <span class="header-links">
                            <a href="change_password.php">Change Password</a>
                            <a href="logout.php">Logout</a>
                        </span>
                    </div>
                <?php endif; ?>
                <div class="logo-right">
                    <div class="eshiksa-logo-text">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                        eShiksa Ltd.
                    </div>
                </div>
            </div>
        </div>
    </header>

    <nav class="main-navbar">
        <?php if (isset($_SESSION['user_id'])): ?>
        <ul class="nav-list">
            
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link">Home</a>
            </li>

            <?php if (user_can('Add College')): ?>
            <li class="nav-item">
                <a href="manage_colleges.php" class="nav-link">Manage Colleges</a>
            </li>
            <?php endif; ?>

            <?php if (user_can('MONITOR_ATTENDANCE')): ?>
            <li class="nav-item">
                <a href="attendance_monitoring.php" class="nav-link">Attendance</a>
            </li>
            <?php endif; ?>
            
            <?php 
            $hasToolsPermission = user_can('DATA_SORTER_TOOL') || 
                                  user_can('NU_EXTRACTOR_TOOL') || 
                                  user_can('NU_RESULT_EXTRACTOR') ||
                                  user_can('HSC_ADMISSION_EXTRACTOR') ||
                                  user_can('HSC_DATA_EXTRACTOR') ||
                                  user_can('REG_MATCHER_TOOL') ||
                                  user_can('IMAGE_RENAMER_TOOL')||
                                  user_can('IMAGE_EXTRACTOR_TOOL')||
                                  user_can('DOMAIN_MONITOR_TOOL')
                                //   user_can('OMR_SCANNER_TOOL')
            ?>

            <?php if ($hasToolsPermission): ?>
            <li class="nav-item">
                <button class="dropdown-btn">Reports & Tools</button>
                <div class="dropdown-menu">
                    <?php if (user_can('DATA_SORTER_TOOL')): ?>
                        <a href="data_sorter.php">Data Sorter Tool</a>
                    <?php endif; ?>
                    
                    <?php if (user_can('NU_AR_EXTRACTOR_TOOL')): ?>
                        <a href="nu_AR_extractor.php">NU Admission Extractor</a>
                    <?php endif; ?>
                    
                    <?php if (user_can('NU_RESULT_EXTRACTOR')): ?>
                        <a href="nu_result_extractor.php">NU Exam Result Extractor</a>
                    <?php endif; ?>
                    
                    <?php if (user_can('HSC_ADMISSION_EXTRACTOR')): ?>
                        <a href="hsc_admission_extractor.php">HSC Admission Extractor</a>
                    <?php endif; ?>

                    <?php if (user_can('HSC_DATA_EXTRACTOR')): ?>
                        <a href="hsc_data_extractor.php">HSC Data Extractor</a>
                    <?php endif; ?>

                    <?php if (user_can('REG_MATCHER_TOOL')): ?>
                        <a href="reg_matcher.php">Registration Matcher</a>
                    <?php endif; ?>
                   

                        <?php if (user_can('IMAGE_RENAMER_TOOL')): ?>
                            <a href="image_renamer.php">Image Processor</a>
                        <?php endif; ?>

                        <?php if (user_can('IMAGE_EXTRACTOR_TOOL')): ?>
    <a href="image_extractor.php">Image Extractor</a>
<?php endif; ?>
<?php if (user_can('DOMAIN_MONITOR_TOOL')): ?>
    <a href="domain_monitor.php">Domain Expiry Monitor</a>
<?php endif; ?>
                </div>
            </li>
            <?php endif; ?>


              <?php 
            $hasToolsPermission = user_can('Reorts') || 
                                  user_can('NU_EXTRACTOR_TOOL') || 
                                  user_can('NU_RESULT_EXTRACTOR') ||
                                  user_can('HSC_ADMISSION_EXTRACTOR') ||
                                  user_can('HSC_DATA_EXTRACTOR') ||
                                  user_can('REG_MATCHER_TOOL') ||
                                  user_can('IMAGE_RENAMER_TOOL')||
                                  user_can('IMAGE_EXTRACTOR_TOOL')||
                                  user_can('DOMAIN_MONITOR_TOOL')
                                //   user_can('OMR_SCANNER_TOOL')
            ?>
            <?php if ($hasToolsPermission): ?>
            <li class="nav-item">
                <button class="dropdown-btn">Reports & Tools</button>
                <div class="dropdown-menu">
                    <?php if (user_can('Reorts')): ?>
                        <a href="event_reports.php">Reports</a>
                    <?php endif; ?>
                    
                    <?php if (user_can('NU_AR_EXTRACTOR_TOOL')): ?>
                        <a href="nu_AR_extractor.php">NU Admission Extractor</a>
                    <?php endif; ?>
                    
                    <?php if (user_can('NU_RESULT_EXTRACTOR')): ?>
                        <a href="nu_result_extractor.php">NU Exam Result Extractor</a>
                    <?php endif; ?>
                    
                    <?php if (user_can('HSC_ADMISSION_EXTRACTOR')): ?>
                        <a href="hsc_admission_extractor.php">HSC Admission Extractor</a>
                    <?php endif; ?>

                    <?php if (user_can('HSC_DATA_EXTRACTOR')): ?>
                        <a href="hsc_data_extractor.php">HSC Data Extractor</a>
                    <?php endif; ?>

                    <?php if (user_can('REG_MATCHER_TOOL')): ?>
                        <a href="reg_matcher.php">Registration Matcher</a>
                    <?php endif; ?>
                   

                        <?php if (user_can('IMAGE_RENAMER_TOOL')): ?>
                            <a href="image_renamer.php">Image Processor</a>
                        <?php endif; ?>

                        <?php if (user_can('IMAGE_EXTRACTOR_TOOL')): ?>
    <a href="image_extractor.php">Image Extractor</a>
<?php endif; ?>
<?php if (user_can('DOMAIN_MONITOR_TOOL')): ?>
    <a href="domain_monitor.php">Domain Expiry Monitor</a>
<?php endif; ?>
                </div>
            </li>
            <?php endif; ?>







            <?php if (user_can('User Management')): ?>
            <li class="nav-item">
                <a href="user_management.php" class="nav-link">User Management</a>
            </li>
            <?php endif; ?>

        </ul>
        <?php endif; ?>
    </nav>

    <div class="content"> -->





    <?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
// Start the session if not already started
ini_set('session.cookie_httponly', 1); 
ini_set('session.use_only_cookies', 1); 
ini_set('session.cookie_secure', 1);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/functions.php';
require_once 'includes/db.php';
if (isset($_SESSION['user_id'])) {
    try {
        //  Get fresh permissions from the database
        $stmt_refresh = $pdo->prepare("
            SELECT m.menu_key 
            FROM user_permissions up 
            JOIN menus m ON up.menu_id = m.id 
            WHERE up.user_id = ?
        ");
        $stmt_refresh->execute([$_SESSION['user_id']]);
        $fresh_permissions = $stmt_refresh->fetchAll(PDO::FETCH_COLUMN);
        
        //  Overwrite the session data with fresh data
        $_SESSION['permissions'] = $fresh_permissions;
    } catch (Exception $e) {
        // If DB fails, do nothing, keep old session data
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Education Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/PapaParse/5.4.1/papaparse.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    
    <style>
        /* --- GLOBAL STYLES --- */
        body { 
            font-family: 'Open Sans', sans-serif;
            margin: 0;
            background-color: #ffffff; 
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* --- HEADER SECTION STYLES --- */
        .main-header {
            background-color: #fff;
            padding: 15px 30px 10px 30px;
        }
        
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .header-titles h1 {
            font-family: 'Roboto', sans-serif;
            font-size: 32px;
            font-weight: 400;
            color: #000;
            margin: 0;
            line-height: 1.2;
        }

        .user-info-area {
            text-align: right;
            font-size: 13px;
            color: #666;
        }

        .user-info-line {
            margin-bottom: 5px;
        }

        .user-name {
            color: #800080;
            font-weight: 700;
        }

        .header-links a {
            color: #666;
            text-decoration: none;
            margin-left: 15px;
        }
        .header-links a:hover { text-decoration: underline; color: #333; }

        .logo-right {
            margin-top: 5px;
            display: flex;
            justify-content: flex-end;
            align-items: center;
        }
        .eshiksa-logo-text {
            font-weight: bold;
            font-size: 18px;
            color: #d9534f;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* --- NAVIGATION BAR STYLES --- */
        .main-navbar {
            background-color: #fff;
            border-top: 2px solid #004085;
            border-bottom: 2px solid #004085;
            padding: 0 30px;
        }

        .nav-list {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            flex-wrap: wrap;
        }

        .nav-item {
            position: relative;
        }

        .nav-link, .dropdown-btn {
            display: block;
            padding: 12px 18px;
            color: #555;
            font-weight: 600;
            text-decoration: none;
            font-size: 14px;
            background: none;
            border: none;
            cursor: pointer;
            font-family: 'Open Sans', sans-serif;
        }

        .nav-item:hover > .nav-link, 
        .nav-item:hover > .dropdown-btn {
            color: #0056b3;
            background-color: #f8f9fa;
        }

        .dropdown-btn::after {
            content: ' ▼';
            font-size: 0.7em;
            vertical-align: middle;
            color: #999;
        }

        .dropdown-menu {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            background-color: #fff;
            min-width: 220px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            border: 1px solid #ddd;
            z-index: 1000;
        }

        .dropdown-menu a {
            display: block;
            padding: 10px 15px;
            color: #444;
            text-decoration: none;
            font-size: 13px;
            border-bottom: 1px solid #f1f1f1;
        }

        .dropdown-menu a:hover {
            background-color: #f1f5f9;
            color: #0056b3;
        }

        .nav-item:hover .dropdown-menu {
            display: block;
        }

        /* --- CONTENT AREA --- */
        .content { 
            padding: 30px; 
            flex-grow: 1;
            background-color: #ffffff;
        }

        /* --- PREVIOUS GLOBAL UTILITIES --- */
        .form-section-card {
            background: #ffffff;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
            margin-top: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .form-input, .form-select {
            padding: 8px 12px; border: 1px solid #d1d5db;
            border-radius: 5px; width: 100%;
        }
        .btn {
            padding: 10px 15px; color: white; background-color: #007bff;
            border: none; border-radius: 5px; cursor: pointer;
        }
        .btn:hover { background-color: #0056b3; }
        .error { color: red; } .success { color: green; }

        footer {
            text-align: center; padding: 20px; margin-top: auto;
            background-color: #f8f9fa; color: #666; font-size: 0.9em;
            border-top: 1px solid #ddd;
        }

        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4); }
        .modal-content { background-color: #fefefe; margin: 10% auto; padding: 20px; border: 1px solid #888; width: 500px; border-radius: 5px; }
        .close-btn { float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
    </style>
</head>
<body class="antialiased text-gray-800">

    <header class="main-header">
        <div class="header-top">
            <div class="header-titles">
                <h1>eShiksa Utility</h1>
            </div>
            
            <div class="user-info-area">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="user-info-line">
                        Welcome <span class="user-name"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                        <span class="header-links">
                            <a href="change_password.php">Change Password</a>
                            <a href="logout.php">Logout</a>
                        </span>
                    </div>
                <?php endif; ?>
                <div class="logo-right">
                    <div class="eshiksa-logo-text">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                        eShiksa Ltd.
                    </div>
                </div>
            </div>
        </div>
    </header>

    <nav class="main-navbar">
        <?php if (isset($_SESSION['user_id'])): ?>
        <ul class="nav-list">
            
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link">Home</a>
            </li>

            <?php 
            $hasCrmPermission = user_can('Reorts') || 
                                user_can('Create Event') || 
                                user_can('Update Events') || 
                                user_can('Manage Institutes') || 
                                user_can('Manage Teams')||
                                user_can('Manage Courses')||
                                user_can('Manage Global Events');

            ?>
            <?php if ($hasCrmPermission): ?>
            <li class="nav-item">
                <button class="dropdown-btn">Event CRM</button>
                <div class="dropdown-menu">
                    <?php if (user_can('Reorts')): ?>
                        <a href="event_reports.php">📊 Dashboard & Reports</a>
                    <?php endif; ?>
                    
                    <?php if (user_can('Update Events')): ?>
                        <a href="event_update.php">✏️ Update Events</a>
                    <?php endif; ?>
                    
                    <?php if (user_can('Create Event')): ?>
                        <a href="event_create.php">➕ Create Event</a>
                    <?php endif; ?>
                    
                    <?php if (user_can('Manage Teams')): ?>
                        <a href="team_manager.php">👥 Manage Teams</a>
                    <?php endif; ?>
                    
                    <?php if (user_can('Manage Institutes')): ?>
                        <a href="institute_manager.php">🏫 Institute Manager</a>
                    <?php endif; ?>

                    <?php if (user_can('Manage Courses')): ?>
                        <a href="course_manager.php">📚 Manage Courses</a>
                    <?php endif; ?>
                    <?php if (user_can('Manage Global Events')): ?>
                        <a href="global_event_manager.php">📚 Manage Events</a>
                    <?php endif; ?>
                </div>
            </li>
            <?php endif; ?>
            <?php if (user_can('Add College')): ?>
            <li class="nav-item">
                <a href="manage_colleges.php" class="nav-link">Manage Colleges</a>
            </li>
            <?php endif; ?>

            <?php if (user_can('MONITOR_ATTENDANCE')): ?>
            <li class="nav-item">
                <a href="attendance_monitoring.php" class="nav-link">Attendance</a>
            </li>
            <?php endif; ?>
            
            <?php 
            $hasToolsPermission = user_can('DATA_SORTER_TOOL') || 
                                  user_can('NU_EXTRACTOR_TOOL') || 
                                  user_can('NU_RESULT_EXTRACTOR') ||
                                  user_can('HSC_ADMISSION_EXTRACTOR') ||
                                  user_can('HSC_DATA_EXTRACTOR') ||
                                  user_can('REG_MATCHER_TOOL') ||
                                  user_can('IMAGE_RENAMER_TOOL') ||
                                  user_can('IMAGE_EXTRACTOR_TOOL') ||
                                  user_can('DOMAIN_MONITOR_TOOL');
            ?>

            <?php if ($hasToolsPermission): ?>
            <li class="nav-item">
                <button class="dropdown-btn">Reports & Tools</button>
                <div class="dropdown-menu">
                    <?php if (user_can('DATA_SORTER_TOOL')): ?>
                        <a href="data_sorter.php">Data Sorter Tool</a>
                    <?php endif; ?>
                    
                    <?php if (user_can('NU_AR_EXTRACTOR_TOOL')): ?>
                        <a href="nu_AR_extractor.php">NU Admission Extractor</a>
                    <?php endif; ?>
                    
                    <?php if (user_can('NU_RESULT_EXTRACTOR')): ?>
                        <a href="nu_result_extractor.php">NU Exam Result Extractor</a>
                    <?php endif; ?>
                    
                    <?php if (user_can('HSC_ADMISSION_EXTRACTOR')): ?>
                        <a href="hsc_admission_extractor.php">HSC Admission Extractor</a>
                    <?php endif; ?>

                    <?php if (user_can('HSC_DATA_EXTRACTOR')): ?>
                        <a href="hsc_data_extractor.php">HSC Data Extractor</a>
                    <?php endif; ?>

                    <?php if (user_can('REG_MATCHER_TOOL')): ?>
                        <a href="reg_matcher.php">Registration Matcher</a>
                    <?php endif; ?>

                    <?php if (user_can('IMAGE_RENAMER_TOOL')): ?>
                        <a href="image_renamer.php">Image Processor</a>
                    <?php endif; ?>

                    <?php if (user_can('IMAGE_EXTRACTOR_TOOL')): ?>
                        <a href="image_extractor.php">Image Extractor</a>
                    <?php endif; ?>

                    <?php if (user_can('DOMAIN_MONITOR_TOOL')): ?>
                        <a href="domain_monitor.php">Domain Expiry Monitor</a>
                    <?php endif; ?>
                </div>
            </li>
            <?php endif; ?>

            <?php if (user_can('User Management')): ?>
            <li class="nav-item">
                <a href="user_management.php" class="nav-link">User Management</a>
            </li>
            <?php endif; ?>

        </ul>
        <?php endif; ?>
    </nav>

    <div class="content">