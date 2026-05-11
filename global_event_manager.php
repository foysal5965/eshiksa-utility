<?php
require_once 'includes/functions.php';
require_once 'includes/db.php';
check_login();

if (isset($_POST['action'])) {
    ob_start();
    header('Content-Type: application/json');
    $id = (int)($_POST['id'] ?? 0);

    try {
        if ($_POST['action'] == 'add_event') {
            $name = trim($_POST['name']);
            $course_id = (int)$_POST['course_id'];
            if (empty($name) || !$course_id) throw new Exception("Event name and Level are required.");
            
            $stmt = $pdo->prepare("INSERT INTO global_events (name, course_id) VALUES (?, ?)");
            $stmt->execute([$name, $course_id]);
            
            ob_clean();
            echo json_encode(['status' => 'success']);
            exit;
        } 
        
        elseif ($_POST['action'] == 'update_event') {
            $new_name = trim($_POST['new_name']);
            $old_name = trim($_POST['old_name']); 
            $new_course_id = (int)$_POST['new_course_id'];
            $old_course_id = (int)$_POST['old_course_id'];
            
            if (empty($new_name) || !$new_course_id) throw new Exception("Event name and Level are required.");
            
            $pdo->beginTransaction();
            // 1. Update master table
            $stmt = $pdo->prepare("UPDATE global_events SET name = ?, course_id = ? WHERE id = ?");
            $stmt->execute([$new_name, $new_course_id, $id]);
            // 2. Update every college's tracked events that matched the old data
            $stmt2 = $pdo->prepare("UPDATE events SET event_name = ?, course_id = ? WHERE event_name = ? AND course_id = ?");
            $stmt2->execute([$new_name, $new_course_id, $old_name, $old_course_id]);
            $pdo->commit();
            
            ob_clean();
            echo json_encode(['status' => 'success']);
            exit;
        }
        
        elseif ($_POST['action'] == 'toggle_archive') {
            $status = (int)$_POST['archive_status'];
            $event_name = trim($_POST['event_name']);
            $course_id = (int)$_POST['course_id'];

            $pdo->beginTransaction();
            // 1. Update master table
            $stmt = $pdo->prepare("UPDATE global_events SET is_archived = ? WHERE id = ?");
            $stmt->execute([$status, $id]);
            // 2. Update all tracking rows for this specific level and event
            $stmt2 = $pdo->prepare("UPDATE events SET is_archived = ? WHERE event_name = ? AND course_id = ?");
            $stmt2->execute([$status, $event_name, $course_id]);
            $pdo->commit();

            ob_clean();
            echo json_encode(['status' => 'success']);
            exit;
        }

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}

// --- FETCH DATA FOR UI ---
$courses = $pdo->query("SELECT id, name FROM courses ORDER BY id ASC")->fetchAll();

$global_events = $pdo->query("
    SELECT ge.*, c.name as course_name 
    FROM global_events ge 
    LEFT JOIN courses c ON ge.course_id = c.id 
    ORDER BY ge.is_archived ASC, c.name ASC, ge.name ASC
")->fetchAll();

require_once 'includes/header.php';
?>

<style>
    :root {
        --bg-main: #f4f7f6; --bg-panel: #ffffff; --text-main: #333333;
        --border-color: #e9ecef; --shadow: 0 4px 10px rgba(0,0,0,0.08); --header-bg: #f8f9fa;
    }
    [data-theme="dark"] {
        --bg-main: #121212; --bg-panel: #1e1e1e; --text-main: #e0e0e0;
        --border-color: #333333; --shadow: 0 4px 10px rgba(0,0,0,0.4); --header-bg: #2d2d2d;
    }
    body { background-color: var(--bg-main); color: var(--text-main); transition: all 0.3s ease;}

    .crm-dashboard { max-width: 1000px; margin: 20px auto; padding: 0 20px; display: flex; flex-direction: column; gap: 25px; }
    .dash-topbar { display: flex; justify-content: space-between; align-items: center; background: var(--bg-panel); padding: 15px 20px; border-radius: 8px; box-shadow: var(--shadow); }
    .dash-title { margin: 0; font-size: 24px; font-weight: bold; }
    
    .section-panel { background: var(--bg-panel); padding: 20px; border-radius: 10px; box-shadow: var(--shadow); }
    .form-group { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap;}
    .form-input { flex: 1; min-width: 200px; padding: 10px; border: 1px solid var(--border-color); border-radius: 4px; background: var(--bg-main); color: var(--text-main);}
    .btn-add { background: #28a745; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: bold; white-space:nowrap;}
    
    table { width: 100%; border-collapse: collapse; font-size: 14px; }
    th { background: var(--header-bg); color: var(--text-main); padding: 12px; text-align: left; font-weight: bold; border-bottom: 2px solid var(--border-color);}
    td { padding: 12px; border-bottom: 1px solid var(--border-color); color: var(--text-main); vertical-align: middle; }
    
    .badge-course { background:#d1ecf1; color:#0c5460; padding:4px 8px; border-radius:4px; font-size:11px; font-weight:bold;}
    .btn-action { padding: 5px 12px; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: bold; margin-left: 5px; color: white;}
    .btn-edit { background: #ffc107; color: #000; }
    .btn-archive { background: #dc3545; }
    .btn-restore { background: #17a2b8; }
    
    .archived-row { opacity: 0.6; background: #f8f9fa; }
    [data-theme="dark"] .archived-row { background: #2a2a2a; }

    /* Modal Styles */
    .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; }
    .modal-content { display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 90%; max-width: 400px; background: var(--bg-panel); color: var(--text-main); padding: 25px; border-radius: 8px; z-index: 1001; box-shadow: var(--shadow); }
</style>

<div class="crm-dashboard">
    <div class="dash-topbar">
        <div>
            <h1 class="dash-title">⚙️ Global Event Manager</h1>
            <p style="margin:0; font-size:12px; color:var(--text-muted);">Changes made here affect all colleges assigned to that specific level.</p>
        </div>
    </div>

    <div class="section-panel">
        <div class="form-group">
            <select id="newCourseId" class="form-input" style="flex: 0.5;">
                <option value="">-- Select Level --</option>
                <?php foreach($courses as $c): ?>
                    <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" id="newEventName" class="form-input" placeholder="Create new global event (e.g., Result Publication)">
            <button class="btn-add" onclick="addGlobalEvent()">+ Create Event</button>
        </div>

        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th style="width: 25%;">Course / Level</th>
                        <th style="width: 40%;">Master Event Name</th>
                        <th style="width: 10%;">Status</th>
                        <th style="width: 25%; text-align:right;">Global Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($global_events as $event): 
                        $is_archived = $event['is_archived'] == 1;
                    ?>
                    <tr class="<?php echo $is_archived ? 'archived-row' : ''; ?>">
                        <td>
                            <?php if($event['course_name']): ?>
                                <span class="badge-course"><?php echo htmlspecialchars($event['course_name']); ?></span>
                            <?php else: ?>
                                <span style="color:red; font-size:12px;">No Level Set</span>
                            <?php endif; ?>
                        </td>
                        <td><strong style="font-size: 15px;"><?php echo htmlspecialchars($event['name']); ?></strong></td>
                        <td>
                            <?php if($is_archived): ?>
                                <span style="background:#dc3545; color:white; padding:3px 8px; border-radius:12px; font-size:11px; font-weight:bold;">Archived</span>
                            <?php else: ?>
                                <span style="background:#28a745; color:white; padding:3px 8px; border-radius:12px; font-size:11px; font-weight:bold;">Active</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:right;">
                            <button class="btn-action btn-edit" onclick="openEditModal(<?php echo $event['id']; ?>, <?php echo $event['course_id'] ?: 0; ?>, '<?php echo addslashes(htmlspecialchars($event['name'])); ?>')">Edit</button>
                            
                            <?php if($is_archived): ?>
                                <button class="btn-action btn-restore" onclick="toggleArchive(<?php echo $event['id']; ?>, <?php echo $event['course_id']; ?>, 0, '<?php echo addslashes(htmlspecialchars($event['name'])); ?>')">Restore All</button>
                            <?php else: ?>
                                <button class="btn-action btn-archive" onclick="toggleArchive(<?php echo $event['id']; ?>, <?php echo $event['course_id']; ?>, 1, '<?php echo addslashes(htmlspecialchars($event['name'])); ?>')">Archive All</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal-overlay" id="editModalOverlay" onclick="closeEditModal()"></div>
<div class="modal-content" id="editModal">
    <h3 style="margin-top:0; border-bottom:1px solid var(--border-color); padding-bottom:10px;">Edit Global Event</h3>
    <input type="hidden" id="editEventId">
    <input type="hidden" id="oldEventName">
    <input type="hidden" id="oldCourseId">
    
    <label style="font-weight:bold; font-size:13px; display:block; margin-bottom:5px;">Course / Level</label>
    <select id="editCourseId" class="form-input" style="width:100%; margin-bottom:15px;">
        <?php foreach($courses as $c): ?>
            <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
        <?php endforeach; ?>
    </select>

    <label style="font-weight:bold; font-size:13px; display:block; margin-bottom:5px;">Event Name</label>
    <input type="text" id="editEventName" class="form-input" style="width:100%;">

    <div style="text-align:right; margin-top:20px;">
        <button style="background:#6c757d; color:white; border:none; padding:8px 15px; border-radius:4px; cursor:pointer;" onclick="closeEditModal()">Cancel</button>
        <button style="background:#007bff; color:white; border:none; padding:8px 15px; border-radius:4px; cursor:pointer; margin-left:10px;" onclick="saveGlobalEvent()">Save Changes</button>
    </div>
</div>

<script>
    if(localStorage.getItem('crm_theme') === 'dark') document.body.setAttribute('data-theme', 'dark');

    async function sendRequest(action, data) {
        const formData = new FormData();
        formData.append('action', action);
        for (const key in data) formData.append(key, data[key]);
        
        try {
            const response = await fetch('global_event_manager.php', { method: 'POST', body: formData });
            const res = await response.json();
            if (res.status === 'success') location.reload();
            else alert("Error: " + res.message);
        } catch (e) { alert("Server request failed."); }
    }

    function addGlobalEvent() {
        const course_id = document.getElementById('newCourseId').value;
        const name = document.getElementById('newEventName').value;
        if (!course_id || !name) return alert("Please select a Level and enter an event name.");
        sendRequest('add_event', { course_id: course_id, name: name });
    }

    // Modal Logic
    function openEditModal(id, course_id, name) {
        document.getElementById('editEventId').value = id;
        document.getElementById('oldEventName').value = name;
        document.getElementById('oldCourseId').value = course_id;
        
        document.getElementById('editCourseId').value = course_id;
        document.getElementById('editEventName').value = name;
        
        document.getElementById('editModalOverlay').style.display = 'block';
        document.getElementById('editModal').style.display = 'block';
    }

    function closeEditModal() {
        document.getElementById('editModalOverlay').style.display = 'none';
        document.getElementById('editModal').style.display = 'none';
    }

    function saveGlobalEvent() {
        const id = document.getElementById('editEventId').value;
        const old_name = document.getElementById('oldEventName').value;
        const old_course_id = document.getElementById('oldCourseId').value;
        const new_name = document.getElementById('editEventName').value;
        const new_course_id = document.getElementById('editCourseId').value;
        
        if(!new_name || !new_course_id) return alert("All fields required.");
        
        sendRequest('update_event', { 
            id: id, new_name: new_name, old_name: old_name, 
            new_course_id: new_course_id, old_course_id: old_course_id 
        });
    }

    function toggleArchive(id, course_id, status, eventName) {
        const msg = status === 1 
            ? `⚠️ ARCHIVE GLOBAL EVENT?\n\nThis will instantly hide '${eventName}' for ALL colleges attached to this level. Are you sure?` 
            : `Restore '${eventName}' to active for all colleges?`;
            
        if (confirm(msg)) {
            sendRequest('toggle_archive', { id: id, course_id: course_id, archive_status: status, event_name: eventName });
        }
    }
</script>

<?php require_once 'includes/footer.php'; ?>