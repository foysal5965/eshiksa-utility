<?php
// --- STEP 1: SETUP ---
require_once 'includes/functions.php'; 
require_once 'includes/db.php';
check_login(); 

// --- AJAX HANDLER ---
if (isset($_POST['action'])) {
    ob_start();
    header('Content-Type: application/json');
    $id = (int)($_POST['id'] ?? 0);

    try {
        // --- 1. MANUAL DATE UPDATE ---
        if ($_POST['action'] == 'manual_update') {
            $expiry = strtotime($_POST['date']);
            ob_clean();
            save_domain_status($pdo, $id, $expiry);
            exit;
        }

        // --- 2. DELETE DOMAIN ---
        elseif ($_POST['action'] == 'delete_domain') {
            $stmt = $pdo->prepare("DELETE FROM domain_monitor WHERE id = ?");
            $stmt->execute([$id]);
            ob_clean();
            echo json_encode(['status' => 'success', 'message' => 'Deleted successfully']);
            exit;
        }

        // --- 3. UPDATE DOMAIN INFO ---
        elseif ($_POST['action'] == 'update_domain') {
            $name = trim($_POST['college_name']);
            $url = $_POST['domain_url'];
            
            // Clean URL
            $url = preg_replace('#^https?://#', '', $url);
            $url = preg_replace('#^www\.#', '', $url);
            $clean_url = preg_replace('/[^a-zA-Z0-9\.\-]/', '', $url);
            $clean_url = rtrim($clean_url, '/');

            if(empty($clean_url)) throw new Exception("Invalid URL");

            $stmt = $pdo->prepare("UPDATE domain_monitor SET college_name = ?, domain_url = ? WHERE id = ?");
            $stmt->execute([$name, $clean_url, $id]);
            
            ob_clean();
            echo json_encode(['status' => 'success', 'clean_url' => $clean_url]);
            exit;
        }

    } catch (Exception $e) {
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}

// --- HELPER: SAVE & CALCULATE ---
function save_domain_status($pdo, $id, $expiry) {
    $date_str = null;
    $days_left = 0;
    $badge_class = "badge-gray";

    if ($expiry) {
        $date_str = date('Y-m-d', $expiry);
        $diff = $expiry - time(); // Difference from NOW
        $days_left = floor($diff / (60 * 60 * 24));

        if ($days_left < 0) { $badge_class = "badge-danger"; }
        elseif ($days_left < 30) { $badge_class = "badge-danger"; }
        elseif ($days_left < 60) { $badge_class = "badge-warn"; }
        else { $badge_class = "badge-ok"; }
    }

    // UPDATE: We set last_checked = NOW() so you know when you last edited it manually
    $update = $pdo->prepare("UPDATE domain_monitor SET expiry_date = ?, last_checked = NOW() WHERE id = ?");
    $update->execute([$date_str, $id]);

    echo json_encode([
        'status' => 'success',
        'date' => $date_str ?? 'Unknown',
        'badge_html' => "<span class='badge $badge_class'>" . ($expiry ? "$days_left days" : "Unknown") . "</span>",
        'last_updated' => "Just now"
    ]);
}

// --- CSV UPLOAD ---
if (isset($_POST['upload_csv']) && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file']['tmp_name'];
    $handle = fopen($file, "r");
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $name = trim($data[0] ?? '');
        $url = trim($data[1] ?? '');
        $clean = preg_replace('#^https?://#', '', $url);
        $clean = preg_replace('#^www\.#', '', $clean);
        $clean = rtrim($clean, '/');
        if (!empty($name) && !empty($clean)) {
            $stmt = $pdo->prepare("INSERT INTO domain_monitor (college_name, domain_url) VALUES (?, ?) ON DUPLICATE KEY UPDATE college_name = ?");
            $stmt->execute([$name, $clean, $name]);
        }
    }
    fclose($handle);
}

$domains = $pdo->query("SELECT * FROM domain_monitor ORDER BY expiry_date ASC")->fetchAll();
require_once 'includes/header.php';
?>

<style>
    .monitor-container { max-width: 1200px; margin: 20px auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; display: inline-block; }
    .badge-ok { background: #d1fae5; color: #065f46; } 
    .badge-warn { background: #ffedd5; color: #9a3412; } 
    .badge-danger { background: #fee2e2; color: #991b1b; } 
    .badge-gray { background: #f3f4f6; color: #374151; }
    
    .toolbar { display: flex; gap: 15px; margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px; align-items: flex-end; flex-wrap: wrap;}
    
    .action-btn { background: none; border: none; cursor: pointer; font-size: 14px; padding: 0 5px; opacity: 0.7; }
    .action-btn:hover { opacity: 1; transform: scale(1.2); }
    .edit-area { display:none; margin-top:5px; }
    .date-input { border: 1px solid #ccc; padding: 4px; width: 120px; }
    .save-btn { background: #28a745; color: white; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; margin-left: 5px;}
    
    table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 14px; }
    th { background: #333; color: #fff; padding: 12px; text-align: left; }
    td { border-bottom: 1px solid #ddd; padding: 12px; vertical-align: middle; }
</style>

<div class="monitor-container">
    <h2 style="border-bottom: 2px solid #007bff; padding-bottom: 10px; margin-bottom: 20px;">🌐 Domain Expiry Monitor</h2>

    <div class="toolbar">
        <form method="POST" enctype="multipart/form-data" style="display:flex; gap:10px; align-items:flex-end; flex-grow:1;">
            <div style="flex-grow:1;">
                <label style="font-weight:bold; font-size:12px;">Add Domains via CSV</label>
                <input type="file" name="csv_file" required accept=".csv" style="border:1px solid #ddd; padding:6px; width:100%; background:#fff;">
            </div>
            <button type="submit" name="upload_csv" class="btn" style="background:#6c757d;">Upload</button>
        </form>
    </div>

    <div style="overflow-x:auto;">
        <table id="domainTable">
            <thead>
                <tr>
                    <th style="width: 35%;">College Name</th>
                    <th>Domain</th>
                    <th>Expiry Date</th>
                    <th>Days Left</th> <th>Last Updated</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($domains as $row): 
                    $days_left = "N/A";
                    $badge_class = "badge-gray";
                    $expiry_display = "Set Date";
                    
                    if ($row['expiry_date']) {
                        $expiry_display = $row['expiry_date'];
                        // DYNAMIC CALCULATION HAPPENS HERE ON EVERY LOAD
                        $diff = strtotime($row['expiry_date']) - time();
                        $d = floor($diff / 86400);
                        
                        if ($d < 0) { $badge_class = "badge-danger"; $days_left = "EXPIRED"; }
                        elseif ($d < 30) { $badge_class = "badge-danger"; $days_left = "$d days"; }
                        elseif ($d < 60) { $badge_class = "badge-warn"; $days_left = "$d days"; }
                        else { $badge_class = "badge-ok"; $days_left = "$d days"; }
                    }
                ?>
                <tr class="domain-row" data-id="<?php echo $row['id']; ?>">
                    <td>
                        <span class="name-text" style="font-weight:bold;"><?php echo htmlspecialchars($row['college_name']); ?></span>
                        <div style="margin-top:5px;">
                            <button class="action-btn" onclick="editDomain(this, <?php echo $row['id']; ?>)" title="Edit Name/URL">✏️</button>
                            <button class="action-btn" onclick="deleteDomain(this, <?php echo $row['id']; ?>)" title="Delete" style="color:red;">🗑</button>
                        </div>
                    </td>
                    <td>
                        <a href="http://<?php echo htmlspecialchars($row['domain_url']); ?>" target="_blank" class="url-text" style="color:#007bff; text-decoration:none;">
                            <?php echo htmlspecialchars($row['domain_url']); ?>
                        </a>
                    </td>
                    <td class="col-date">
                        <span class="date-text"><?php echo $expiry_display; ?></span>
                        <button class="action-btn" onclick="toggleDateEdit(this)" style="color:#007bff;">✎</button>
                        <div class="edit-area">
                            <input type="date" class="date-input" value="<?php echo $row['expiry_date']; ?>">
                            <button class="save-btn" onclick="saveManualDate(this, <?php echo $row['id']; ?>)">Save</button>
                        </div>
                    </td>
                    <td class="col-status"><span class="badge <?php echo $badge_class; ?>"><?php echo $days_left; ?></span></td>
                    <td class="col-last" style="font-size:12px; color:#666;">
                        <?php echo $row['last_checked'] ? date('M d, H:i', strtotime($row['last_checked'])) : 'Never'; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    // --- 1. EDIT DOMAIN INFO ---
    function editDomain(btn, id) {
        const row = btn.closest('tr');
        const nameSpan = row.querySelector('.name-text');
        const urlLink = row.querySelector('.url-text');
        
        const currentName = nameSpan.innerText.trim();
        let currentUrl = urlLink.innerText.trim();
        currentUrl = currentUrl.replace(' 🔗', ''); 

        const newName = prompt("Enter College Name:", currentName);
        if (newName === null) return; 

        const newUrl = prompt("Enter Domain URL:", currentUrl);
        if (newUrl === null) return; 

        if(newName && newUrl) {
            updateDomainOnServer(id, newName, newUrl, row);
        }
    }

    async function updateDomainOnServer(id, name, url, row) {
        try {
            const formData = new FormData();
            formData.append('action', 'update_domain');
            formData.append('id', id);
            formData.append('college_name', name);
            formData.append('domain_url', url);

            const response = await fetch('domain_monitor.php', { method: 'POST', body: formData });
            const res = await response.json();

            if (res.status === 'success') {
                row.querySelector('.name-text').innerText = name;
                const link = row.querySelector('.url-text');
                link.innerText = res.clean_url;
                link.href = "http://" + res.clean_url;
            } else {
                alert("Error: " + res.message);
            }
        } catch (e) { console.error(e); alert("Update failed."); }
    }

    // --- 2. DELETE DOMAIN ---
    async function deleteDomain(btn, id) {
        if (!confirm("Are you sure you want to delete this domain?")) return;
        try {
            const formData = new FormData();
            formData.append('action', 'delete_domain');
            formData.append('id', id);
            const response = await fetch('domain_monitor.php', { method: 'POST', body: formData });
            const res = await response.json();
            if (res.status === 'success') {
                btn.closest('tr').remove();
            } else { alert("Error: " + res.message); }
        } catch (e) { console.error(e); alert("Delete failed."); }
    }

    // --- 3. MANUAL DATE UPDATE ---
    function toggleDateEdit(btn) {
        const cell = btn.parentElement;
        const editArea = cell.querySelector('.edit-area');
        editArea.style.display = 'block';
        btn.style.display = 'none';
    }

    async function saveManualDate(btn, id) {
        const cell = btn.closest('td');
        const input = cell.querySelector('input');
        const row = btn.closest('tr');
        
        if (!input.value) return alert("Select date");
        
        btn.innerText = "...";
        try {
            const formData = new FormData();
            formData.append('action', 'manual_update');
            formData.append('id', id);
            formData.append('date', input.value);
            
            const response = await fetch('domain_monitor.php', { method: 'POST', body: formData });
            const res = await response.json();
            if(res.status === 'success') {
                cell.querySelector('.date-text').innerText = res.date;
                cell.querySelector('.edit-area').style.display = 'none';
                cell.querySelector('.action-btn').style.display = 'inline-block';
                // Update badge immediately
                row.querySelector('.col-status').innerHTML = res.badge_html;
                // Update timestamp
                row.querySelector('.col-last').innerText = res.last_updated;
            }
        } catch(e) { console.error(e); }
        btn.innerText = "Save";
    }
</script>

<?php require_once 'includes/footer.php'; ?>