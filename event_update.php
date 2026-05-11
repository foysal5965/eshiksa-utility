<?php
require_once 'includes/functions.php';
require_once 'includes/db.php';
check_login();

// View Mode Toggle (Active vs Archived)
$view_mode = $_GET['view'] ?? 'active';
$is_archived_filter = ($view_mode === 'archived') ? 1 : 0;

// --- AJAX HANDLER ---
if (isset($_POST['action'])) {
    ob_start();
    header('Content-Type: application/json');
    $id = (int)($_POST['id'] ?? 0);

    try {
        if ($_POST['action'] == 'auto_save_event') {
            $status = $_POST['status'];
            $notes = trim($_POST['notes']);
            $stmt = $pdo->prepare("UPDATE events SET status = ?, notes = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$status, $notes, $id]);
            ob_clean();
            echo json_encode(['status' => 'success']);
            exit;
        }

        elseif ($_POST['action'] == 'edit_event_details') {
            $institute_id = (int)$_POST['institute_id'];
            $course_id = (int)$_POST['course_id'];
            $event_name = trim($_POST['event_name']);
            $stmt = $pdo->prepare("UPDATE events SET institute_id = ?, course_id = ?, event_name = ? WHERE id = ?");
            $stmt->execute([$institute_id, $course_id, $event_name, $id]);
            ob_clean();
            echo json_encode(['status' => 'success']);
            exit;
        }

        elseif ($_POST['action'] == 'toggle_archive') {
            $archive_status = (int)$_POST['archive_status'];
            $stmt = $pdo->prepare("UPDATE events SET is_archived = ? WHERE id = ?");
            $stmt->execute([$archive_status, $id]);
            ob_clean();
            echo json_encode(['status' => 'success']);
            exit;
        }

        elseif ($_POST['action'] == 'delete_event') {
            $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
            $stmt->execute([$id]);
            ob_clean();
            echo json_encode(['status' => 'success']);
            exit;
        }
    } catch (Exception $e) {
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}

// --- FETCH DATA FOR EDIT MODAL ---
$courses = $pdo->query("SELECT id, name FROM courses ORDER BY id ASC")->fetchAll();
$institutes_raw = $pdo->query("SELECT i.id, i.name, GROUP_CONCAT(ic.course_id) as course_ids FROM institutes i LEFT JOIN institute_courses ic ON i.id = ic.institute_id GROUP BY i.id")->fetchAll();

// --- FETCH EVENTS (Filtered by Active/Archived) ---
$events_query = "
    SELECT e.id, e.event_name, e.status, e.notes, e.updated_at, e.institute_id, e.course_id, e.is_archived,
           i.name as college_name, c.name as course_name,
           m.name as member_name, t.name as team_name
    FROM events e
    JOIN institutes i ON e.institute_id = i.id
    JOIN courses c ON e.course_id = c.id
    LEFT JOIN team_members m ON i.member_id = m.id
    LEFT JOIN teams t ON m.team_id = t.id
    WHERE e.is_archived = ?
    ORDER BY e.updated_at DESC
";
$stmt = $pdo->prepare($events_query);
$stmt->execute([$is_archived_filter]);
$events = $stmt->fetchAll();

$statuses = ['Pending', 'Yes', 'No', 'No PG', 'Inactive'];

// Extract unique values for Filters
$unique_courses = array_filter(array_unique(array_column($events, 'course_name')));
$unique_events = array_filter(array_unique(array_column($events, 'event_name')));
$unique_members = array_filter(array_unique(array_column($events, 'member_name')));
sort($unique_courses); sort($unique_events); sort($unique_members);

require_once 'includes/header.php';
?>

<style>
    .crm-container { max-width: 1400px; margin: 20px auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    .panel-header { border-bottom: 2px solid #007bff; padding-bottom: 10px; margin-bottom: 15px; font-size: 22px; font-weight: bold; display: flex; justify-content: space-between; align-items: center;}
    
    table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 13px; }
    th { background: #333; color: #fff; padding: 10px; text-align: left; }
    td { border-bottom: 1px solid #ddd; padding: 8px 10px; vertical-align: middle; }
    
    .filter-row th { background: #f1f3f5; color: #333; padding: 5px 10px; border-bottom: 2px solid #ddd; }
    .filter-select { width: 100%; padding: 4px; border: 1px solid #ccc; border-radius: 4px; font-size: 12px; font-weight: bold; color: #333; background: #fff; }
    
    .badge-course { background:#d1ecf1; color:#0c5460; padding:4px 8px; border-radius:4px; font-size:11px; font-weight:bold;}
    
    .action-btn { background: none; border: none; cursor: pointer; font-size: 16px; margin: 0 2px; transition: transform 0.2s;}
    .action-btn:hover { transform: scale(1.2); }
    
    .status-select { padding: 5px; border-radius: 4px; font-weight: bold; border: 1px solid #ccc; cursor: pointer; width: 100%; font-size: 12px; }
    .status-select.val-Pending { background: #e0f7fa; color: #006064; }
    .status-select.val-Yes { background: #d4edda; color: #155724; }
    .status-select.val-No { background: #f8d7da; color: #721c24; }
    .status-select.val-NoPG { background: #fff3cd; color: #856404; }
    .status-select.val-Inactive { background: #e2e3e5; color: #383d41; }

    .remark-input { width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px; font-size: 12px; background: #fafafa; }
    .btn-toggle-view { padding: 8px 15px; border-radius: 6px; font-size: 14px; font-weight: bold; text-decoration: none; color: white; }
    .btn-toggle-view.archive { background: #6c757d; } .btn-toggle-view.active { background: #28a745; }

    .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; }
    .modal-content { display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 90%; max-width: 500px; background: white; padding: 25px; border-radius: 8px; z-index: 1001; }
    .form-control { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; margin-bottom:15px;}
</style>

<div class="crm-container">
    <div class="panel-header">
        <div>
            <span><?php echo $view_mode === 'archived' ? '📦 Archived Events' : '✏️ Update Active Events'; ?></span>
            <span id="globalSaving" style="font-size: 12px; color: #28a745; font-weight: normal; opacity: 0; transition: opacity 0.3s; margin-left: 15px;">✅ Saved</span>
        </div>
        <div>
            <?php if($view_mode === 'archived'): ?>
                <a href="?view=active" class="btn-toggle-view active">🔙 View Active Events</a>
            <?php else: ?>
                <a href="?view=archived" class="btn-toggle-view archive">📦 View Individual Archive</a>
            <?php endif; ?>
        </div>
    </div>

    <div style="overflow-x:auto;">
        <table id="eventsTable">
            <thead>
                <tr>
                    <th style="width: 8%;">Date</th><th style="width: 15%;">College</th><th style="width: 10%;">Course</th><th style="width: 15%;">Event Name</th>
                    <th style="width: 12%;">Responsible</th><th style="width: 12%;">Status</th><th style="width: 18%;">Remark</th><th style="width: 10%;">Actions</th>
                </tr>
                <tr class="filter-row">
                    <th></th><th></th>
                    <th><select id="filterCourse" class="filter-select" onchange="filterTable()"><option value="">All</option><?php foreach($unique_courses as $c): ?><option value="<?php echo htmlspecialchars(strtolower($c)); ?>"><?php echo htmlspecialchars($c); ?></option><?php endforeach; ?></select></th>
                    <th><select id="filterEvent" class="filter-select" onchange="filterTable()"><option value="">All</option><?php foreach($unique_events as $e): ?><option value="<?php echo htmlspecialchars(strtolower($e)); ?>"><?php echo htmlspecialchars($e); ?></option><?php endforeach; ?></select></th>
                    <th><select id="filterMember" class="filter-select" onchange="filterTable()"><option value="">All</option><?php foreach($unique_members as $m): ?><option value="<?php echo htmlspecialchars(strtolower($m)); ?>"><?php echo htmlspecialchars($m); ?></option><?php endforeach; ?></select></th>
                    <th><select id="filterStatus" class="filter-select" onchange="filterTable()"><option value="">All</option><?php foreach($statuses as $s): ?><option value="<?php echo htmlspecialchars(strtolower($s)); ?>"><?php echo $s; ?></option><?php endforeach; ?></select></th>
                    <th></th><th></th>
                </tr>
            </thead>
            <tbody id="tableBody">
                <?php if(count($events) === 0): ?>
                    <tr><td colspan="8" style="text-align:center; padding: 20px; color:#666;">No events found. Generate them from 'Create Event' first.</td></tr>
                <?php endif; ?>

                <?php foreach($events as $event): $cssClass = 'val-' . str_replace(' ', '', $event['status']); ?>
                <tr data-id="<?php echo $event['id']; ?>" data-course="<?php echo htmlspecialchars(strtolower($event['course_name'])); ?>" data-event="<?php echo htmlspecialchars(strtolower($event['event_name'])); ?>" data-member="<?php echo htmlspecialchars(strtolower($event['member_name'] ?? 'unassigned')); ?>" data-status="<?php echo htmlspecialchars(strtolower($event['status'])); ?>">
                    
                    <td style="color:#666;"><?php echo date('M d', strtotime($event['updated_at'])); ?></td>
                    <td style="font-weight:bold;"><?php echo htmlspecialchars($event['college_name']); ?></td>
                    <td><span class="badge-course"><?php echo htmlspecialchars($event['course_name']); ?></span></td>
                    <td><?php echo htmlspecialchars($event['event_name']); ?></td>
                    <td><?php echo htmlspecialchars($event['member_name'] ?? 'Unassigned'); ?></td>
                    
                    <td><select id="status_<?php echo $event['id']; ?>" class="status-select <?php echo $cssClass; ?>" onchange="autoSaveEvent(<?php echo $event['id']; ?>)"><?php foreach($statuses as $s): ?><option value="<?php echo $s; ?>" <?php echo ($event['status'] == $s) ? 'selected' : ''; ?>><?php echo $s; ?></option><?php endforeach; ?></select></td>
                    <td><input type="text" id="remark_<?php echo $event['id']; ?>" class="remark-input" value="<?php echo htmlspecialchars($event['notes'] ?? ''); ?>" onchange="autoSaveEvent(<?php echo $event['id']; ?>)"></td>
                    
                    <td style="text-align:center; white-space:nowrap;">
                        <button class="action-btn" title="Edit" onclick="openEditModal(<?php echo $event['id']; ?>, <?php echo $event['course_id']; ?>, <?php echo $event['institute_id']; ?>, '<?php echo addslashes(htmlspecialchars($event['event_name'])); ?>')">✏️</button>
                        <?php if($event['is_archived'] == 0): ?>
                            <button class="action-btn" title="Archive Row" onclick="toggleArchive(<?php echo $event['id']; ?>, 1)">📦</button>
                        <?php else: ?>
                            <button class="action-btn" title="Restore Row" onclick="toggleArchive(<?php echo $event['id']; ?>, 0)">🔙</button>
                        <?php endif; ?>
                        <button class="action-btn" title="Delete" onclick="deleteEvent(<?php echo $event['id']; ?>)">🗑</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal-overlay" id="editModalOverlay" onclick="closeEditModal()"></div>
<div class="modal-content" id="editModal">
    <h3>✏️ Edit Event Instance</h3>
    <input type="hidden" id="editEventId">
    
    <label style="font-weight:bold; font-size:13px; display:block; margin-bottom:5px;">Course</label>
    <select id="editCourse" class="form-control" onchange="filterEditColleges()"><?php foreach($courses as $course): ?><option value="<?php echo $course['id']; ?>"><?php echo htmlspecialchars($course['name']); ?></option><?php endforeach; ?></select>

    <label style="font-weight:bold; font-size:13px; display:block; margin-bottom:5px;">College</label>
    <select id="editInstitute" class="form-control"></select>

    <label style="font-weight:bold; font-size:13px; display:block; margin-bottom:5px;">Event Name</label>
    <input type="text" id="editEventName" class="form-control">

    <div style="text-align:right;">
        <button style="background:#6c757d; color:white; border:none; padding:8px 15px; border-radius:4px; cursor:pointer;" onclick="closeEditModal()">Cancel</button>
        <button style="background:#007bff; color:white; border:none; padding:8px 15px; border-radius:4px; cursor:pointer;" onclick="saveEventDetails()">Save</button>
    </div>
</div>

<script>
    const collegesData = <?php echo json_encode($institutes_raw); ?>;

    function filterTable() {
        const c = document.getElementById('filterCourse').value, e = document.getElementById('filterEvent').value, m = document.getElementById('filterMember').value, s = document.getElementById('filterStatus').value;
        document.querySelectorAll('#tableBody tr').forEach(r => {
            r.style.display = (c===""||r.getAttribute('data-course')===c) && (e===""||r.getAttribute('data-event')===e) && (m===""||r.getAttribute('data-member')===m) && (s===""||r.getAttribute('data-status')===s) ? '' : 'none';
        });
    }

    let saveTimeout;
    async function autoSaveEvent(id) {
        const sSelect = document.getElementById('status_'+id), rInput = document.getElementById('remark_'+id), ind = document.getElementById('globalSaving');
        sSelect.className = 'status-select val-' + sSelect.value.replace(/\s+/g, '');
        document.querySelector(`tr[data-id='${id}']`).setAttribute('data-status', sSelect.value.toLowerCase());
        ind.style.opacity = "1";
        
        const fd = new FormData(); fd.append('action','auto_save_event'); fd.append('id',id); fd.append('status',sSelect.value); fd.append('notes',rInput.value);
        await fetch('event_update.php?view=<?php echo $view_mode; ?>', {method:'POST', body:fd});
        clearTimeout(saveTimeout); saveTimeout = setTimeout(()=>ind.style.opacity="0", 2000);
    }

    function filterEditColleges(pre=null) {
        const cid = document.getElementById('editCourse').value, sel = document.getElementById('editInstitute');
        sel.innerHTML = '';
        collegesData.forEach(col => {
            if(col.course_ids && col.course_ids.split(',').includes(cid.toString())) {
                const opt = document.createElement('option'); opt.value = col.id; opt.innerText = col.name;
                if(pre && col.id == pre) opt.selected = true;
                sel.appendChild(opt);
            }
        });
    }

    function openEditModal(id, cid, iid, ev) {
        document.getElementById('editEventId').value=id; document.getElementById('editCourse').value=cid;
        filterEditColleges(iid); document.getElementById('editEventName').value=ev;
        document.getElementById('editModalOverlay').style.display='block'; document.getElementById('editModal').style.display='block';
    }
    function closeEditModal() { document.getElementById('editModalOverlay').style.display='none'; document.getElementById('editModal').style.display='none'; }

    async function saveEventDetails() {
        const fd = new FormData(); fd.append('action','edit_event_details'); fd.append('id',document.getElementById('editEventId').value); fd.append('institute_id',document.getElementById('editInstitute').value); fd.append('course_id',document.getElementById('editCourse').value); fd.append('event_name',document.getElementById('editEventName').value);
        await fetch('event_update.php?view=<?php echo $view_mode; ?>', {method:'POST', body:fd}); location.reload();
    }

    async function toggleArchive(id, status) {
        if(!confirm(status===1?"Archive this row?":"Restore this row?")) return;
        const fd = new FormData(); fd.append('action','toggle_archive'); fd.append('id',id); fd.append('archive_status',status);
        await fetch('event_update.php?view=<?php echo $view_mode; ?>', {method:'POST', body:fd}); location.reload();
    }

    async function deleteEvent(id) {
        if(!confirm("Permanently delete?")) return;
        const fd = new FormData(); fd.append('action','delete_event'); fd.append('id',id);
        await fetch('event_update.php?view=<?php echo $view_mode; ?>', {method:'POST', body:fd}); location.reload();
    }
</script>

<?php require_once 'includes/footer.php'; ?>