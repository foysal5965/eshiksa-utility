<?php
// --- STEP 1: SETUP & PERMISSIONS ---
require_once 'includes/db.php'; 
require_once 'includes/functions.php';
check_login(); 

// Check permission
if (!user_can('MONITOR_ATTENDANCE')) {
    require_once 'includes/header.php';
    echo "<div style='padding:30px;'><h1>Access Denied</h1><p>You do not have permission to view this page.</p></div>";
  
    exit();
}

// --- STEP 2: LOAD HEADER ---
require_once 'includes/header.php'; 

// --- STEP 3: FETCH DATA ---
$stmt = $pdo->query("SELECT id, college_name FROM college_info ORDER BY college_name");
$colleges = $stmt->fetchAll();
?>

<style>
    /* Container adjustments */
    .monitor-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }
    
    .page-title {
        color: #333;
        font-family: 'Segoe UI', sans-serif;
        font-weight: 600;
        margin-bottom: 10px;
        border-bottom: 2px solid #0056b3;
        padding-bottom: 10px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .page-desc {
        color: #666;
        margin-bottom: 25px;
        font-size: 0.95rem;
    }

    /* Grid Layout */
    .college-status-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
    }

    /* Card Style */
    .college-status-card {
        background: #fff;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        display: flex;
        align-items: center;
        justify-content: space-between;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .college-status-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    .college-info h4 {
        margin: 0;
        font-size: 1rem;
        color: #333;
        font-weight: 600;
    }

    /* Status Indicators */
    .status-indicator {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.9rem;
        font-weight: 600;
        padding: 6px 12px;
        border-radius: 20px;
        background-color: #f0f0f0; /* Default gray */
    }

    .status-dot {
        height: 10px;
        width: 10px;
        background-color: #bbb;
        border-radius: 50%;
        display: inline-block;
    }

    /* Dynamic Status Colors */
    .status-online .status-dot { background-color: #28a745; box-shadow: 0 0 5px #28a745; }
    .status-online { color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb; }

    .status-offline .status-dot { background-color: #dc3545; }
    .status-offline { color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; }

    .status-checking .status-dot { background-color: #ffc107; animation: pulse 1.5s infinite; }
    .status-checking { color: #856404; background-color: #fff3cd; border: 1px solid #ffeeba; }

    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.4; }
        100% { opacity: 1; }
    }
    
    .refresh-btn {
        background-color: #0056b3;
        color: white;
        border: none;
        padding: 8px 15px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 14px;
    }
    .refresh-btn:hover { background-color: #004494; }
</style>

<div class="monitor-container">
    
    <div class="page-title">
        <h2>Attendance Service Monitoring</h2>
        <button class="refresh-btn" onclick="location.reload()">↻ Refresh Status</button>
    </div>
    
    <p class="page-desc">
        Real-time status check of all connected college attendance APIs. 
        "Online" indicates the server responded with a 200 OK signal.
    </p>

    <div class="college-status-grid">
        <?php if (empty($colleges)): ?>
            <p>No colleges have been added to the system yet.</p>
        <?php else: ?>
            <?php foreach ($colleges as $college): ?>
                <div class="college-status-card" data-college-id="<?php echo $college['id']; ?>">
                    <div class="college-info">
                        <h4><?php echo htmlspecialchars($college['college_name']); ?></h4>
                    </div>
                    
                    <div class="status-indicator status-checking">
                        <span class="status-dot"></span>
                        <span class="status-text">Checking...</span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        const allCollegeCards = document.querySelectorAll('.college-status-card');

        allCollegeCards.forEach(card => {
            const collegeId = card.dataset.collegeId;
            const indicator = card.querySelector('.status-indicator');
            const statusDot = card.querySelector('.status-dot');
            const statusText = card.querySelector('.status-text');

            // Call helper file
            fetch('check_college_status.php?id=' + collegeId)
                .then(response => response.json())
                .then(data => {
                    // Remove 'checking' class
                    indicator.classList.remove('status-checking');

                    if (data.status === 'online') {
                        indicator.classList.add('status-online');
                        statusText.textContent = 'Online';
                    } else {
                        indicator.classList.add('status-offline');
                        statusText.textContent = 'Offline';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    indicator.classList.remove('status-checking');
                    indicator.classList.add('status-offline');
                    statusText.textContent = 'Error';
                });
        });
    });
</script>