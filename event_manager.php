<?php
require_once 'includes/functions.php';
require_once 'includes/db.php';
check_login();

// --- AJAX HANDLER FOR CRUD OPERATIONS ---
if (isset($_POST['action'])) {
    ob_start();
    header('Content-Type: application/json');
    $id = (int)($_POST['id'] ?? 0);

    try {
        if ($_POST['action'] == 'add_event') {
            $institute_id = (int)$_POST['institute_id'];
            $course_id = (int)$_POST['course_id'];
            $event_name = trim($_POST['event_name']);
            $status = $_POST['status'];

            if (!$institute_id || !$course_id || empty($event_name)) {
                throw new Exception("College, Course, and Event Name are required.");
            }

            $stmt = $pdo->prepare("INSERT INTO events (institute_id, course_id, event_name, status) VALUES (?, ?, ?, ?)");
            $stmt->execute([$institute_id, $course_id, $event_name, $status]);
            ob_clean();
            echo json_encode(['status' => 'success']);
            exit;
        } 
        
        elseif ($_POST['action'] == 'update_status') {
            $status = $_POST['status'];
            $stmt = $pdo->prepare("UPDATE events SET status = ? WHERE id = ?");
            $stmt->execute([$status, $id]);
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

// --- FETCH DATA FOR DROPDOWNS & FILTERING ---
$courses = $pdo->query("SELECT id, name FROM courses ORDER BY id ASC")->fetchAll();

// Fetch colleges and group their mapped courses as an array string for JavaScript logic
$inst_query = "
    SELECT i.id, i.name, GROUP_CONCAT(ic.course_id) as course_ids 
    FROM institutes i 
    LEFT JOIN institute_courses ic ON i.id = ic.institute_id 
    GROUP BY i.id
    ORDER BY i.name ASC
";
$institutes_raw = $pdo->query($inst_query)->fetchAll();

// --- FETCH EVENTS FOR TRACKER TABLE ---
$events_query = "
    SELECT e.id, e.event_name, e.status, e.updated_at,
           i.name as college_name, c.name as course_name,
           m.name as member_name, t.name as team_name
    FROM events e
    JOIN institutes i ON e.institute_id = i.id
    JOIN courses c ON e.course_id = c.id
    LEFT JOIN team_members m ON i.member_id = m.id
    LEFT JOIN teams t ON m.team_id = t.id
    ORDER BY e.updated_at DESC
";
$events = $pdo->query($events_query)->fetchAll();

// ==========================================
//          REPORT GENERATION LOGIC
// ==========================================
$statuses = ['Pending', 'Yes', 'No', 'No PG', 'Inactive'];

// 1. At A Glance (Overall Totals)
$overall_stats = array_fill_keys($statuses, 0);
$stmt = $pdo->query("SELECT status, COUNT(*) as count FROM events GROUP BY status");
while ($row = $stmt->fetch()) {
    $overall_stats[$row['status']] = $row['count'];
}

// 2. Team-Wise Report
$team_stats = [];
$stmt = $pdo->query("
    SELECT t.name as team_name, e.status, COUNT(*) as count 
    FROM events e
    JOIN institutes i ON e.institute_id = i.id
    JOIN team_members m ON i.member_id = m.id
    JOIN teams t ON m.team_id = t.id
    GROUP BY t.id, e.status
");
while ($row = $stmt->fetch()) {
    $team = $row['team_name'];
    if (!isset($team_stats[$team])) $team_stats[$team] = array_fill_keys($statuses, 0);
    $team_stats[$team][$row['status']] = $row['count'];
}

// 3. Member-Wise Report
$member_stats = [];
$stmt = $pdo->query("
    SELECT m.name as member_name, e.status, COUNT(*) as count 
    FROM events e
    JOIN institutes i ON e.institute_id = i.id
    JOIN team_members m ON i.member_id = m.id
    GROUP BY m.id, e.status
");
while ($row = $stmt->fetch()) {
    $member = $row['member_name'];
    if (!isset($member_stats[$member])) $member_stats[$member] = array_fill_keys($statuses, 0);
    $member_stats[$member][$row['status']] = $row['count'];
}

require_once 'includes/header.php';
?>

<style>
    .crm-container { max-width: 1300px; margin: 20px auto; display: flex; flex-direction: column; gap: 20px; }
    .panel { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    .panel-header { border-bottom: 2px solid #007bff; padding-bottom: 10px; margin-bottom: 15px; font-size: 18px; font-weight: bold; }
    
    .dashboard-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 20px;}
    .card { padding: 15px; border-radius: 8px; color: white; text-align: center; }
    .card h3 { margin: 0 0 5px 0; font-size: 28px; }
    .card p { margin: 0; font-size: 14px; text-transform: uppercase; font-weight: bold; opacity: 0.9; }
    
    .bg-pending { background: #17a2b8; } .bg-yes { background: #28a745; } .bg-no { background: #dc3545; }
    .bg-nopg { background: #fd7e14; } .bg-inactive { background: #6c757d; }

    table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 14px; }
    th { background: #333; color: #fff; padding: 10px; text-align: left; }
    td { border-bottom: 1px solid #ddd; padding: 10px; vertical-align: middle; }
    .report-table th { background: #f8f9fa; color: #333; border-top: 2px solid #ddd; }
    
    .form-row { display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end; background:#f8f9fa; padding:15px; border-radius:8px;}
    .form-group { flex: 1; min-width: 200px; }
    .form-group label { display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color:#555;}
    .form-control { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
    .btn-add { background: #007bff; color: white; border: none; padding: 9px 15px; border-radius: 4px; cursor: pointer; font-weight: bold; height: 35px;}
    .btn-delete { background: none; border: none; color: #dc3545; cursor: pointer; font-size: 16px; }
    
    .status-select { padding: 5px; border-radius: 4px; font-weight: bold; border: 1px solid #ccc; cursor: pointer; width:100%;}
    .status-select.val-Pending { background: #e0f7fa; color: #006064; }
    .status-select.val-Yes { background: #d4edda; color: #155724; }
    .status-select.val-No { background: #f8d7da; color: #721c24; }
    .status-select.val-NoPG { background: #fff3cd; color: #856404; }
    .status-select.val-Inactive { background: #e2e3e5; color: #383d41; }
</style>

<div class="crm-container">

    <div class="panel">
        <div class="panel-header">📊 Event Reports</div>
        
        <div class="dashboard-cards">
            <div class="card bg-pending"><h3><?php echo $overall_stats['Pending']; ?></h3><p>Pending</p></div>
            <div class="card bg-yes"><h3><?php echo $overall_stats['Yes']; ?></h3><p>Yes</p></div>
            <div class="card bg-no"><h3><?php echo $overall_stats['No']; ?></h3><p>No</p></div>
            <div class="card bg-nopg"><h3><?php echo $overall_stats['No PG']; ?></h3><p>No PG</p></div>
            <div class="card bg-inactive"><h3><?php echo $overall_stats['Inactive']; ?></h3><p>Inactive</p></div>
        </div>

        <div style="display:flex; gap:20px; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 400px; overflow-x: auto;">
                <h4 style="margin-bottom:10px; color:#555;">Team-Wise Report</h4>
                <table class="report-table">
                    <tr><th>Team</th><th>Yes</th><th>No</th><th>No PG</th><th>Pend</th><th>Inact</th></tr>
                    <?php foreach($team_stats as $team => $stats): ?>
                    <tr>
                        <td style="font-weight:bold;"><?php echo htmlspecialchars($team); ?></td>
                        <td style="color:green; font-weight:bold;"><?php echo $stats['Yes']; ?></td>
                        <td style="color:red; font-weight:bold;"><?php echo $stats['No']; ?></td>
                        <td style="color:orange; font-weight:bold;"><?php echo $stats['No PG']; ?></td>
                        <td style="color:#17a2b8; font-weight:bold;"><?php echo $stats['Pending']; ?></td>
                        <td><?php echo $stats['Inactive']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>

            <div style="flex: 1; min-width: 400px; overflow-x: auto;">
                <h4 style="margin-bottom:10px; color:#555;">Responsible Person Report</h4>
                <table class="report-table">
                    <tr><th>Member</th><th>Yes</th><th>No</th><th>No PG</th><th>Pend</th><th>Inact</th></tr>
                    <?php foreach($member_stats as $member => $stats): ?>
                    <tr>
                        <td style="font-weight:bold;"><?php echo htmlspecialchars($member); ?></td>
                        <td style="color:green; font-weight:bold;"><?php echo $stats['Yes']; ?></td>
                        <td style="color:red; font-weight:bold;"><?php echo $stats['No']; ?></td>
                        <td style="color:orange; font-weight:bold;"><?php echo $stats['No PG']; ?></td>
                        <td style="color:#17a2b8; font-weight:bold;"><?php echo $stats['Pending']; ?></td>
                        <td><?php echo $stats['Inactive']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header">📝 Log & Manage Events</div>

        <div class="form-row">
            
            <div class="form-group">
                <label>1. Select Course / Level First</label>
                <select id="newCourse" class="form-control" onchange="filterColleges()">
                    <option value="">-- Select Course --</option>
                    <?php foreach($courses as $course): ?>
                        <option value="<?php echo $course['id']; ?>"><?php echo htmlspecialchars($course['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>2. Select College (Filtered by Level)</label>
                <select id="newInstitute" class="form-control" disabled>
                    <option value="">-- Select Level First --</option>
                </select>
            </div>

            <div class="form-group">
                <label>3. Event Name</label>
                <input type="text" id="newEventName" list="eventSuggestions" class="form-control" placeholder="e.g., Admission, Form Fillup">
                <datalist id="eventSuggestions">
                    <option value="Admission">
                    <option value="Form Fillup">
                    <option value="Registration">
                    <option value="Result Publication">
                </datalist>
            </div>

            <div class="form-group">
                <label>4. Initial Status</label>
                <select id="newStatus" class="form-control">
                    <option value="Pending">Pending</option>
                    <option value="Yes">Yes (Completed/Active)</option>
                    <option value="No">No</option>
                    <option value="No PG">Not Payment Gateway Integrated</option>
                    <option value="Inactive">Inactive</option>
                </select>
            </div>

            <button class="btn-add" onclick="addEvent()">Log Event</button>
        </div>

        <div style="overflow-x:auto; margin-top: 20px;">
            <table id="eventsTable">
                <thead>
                    <tr>
                        <th>Date Logged</th>
                        <th>College</th>
                        <th>Course</th>
                        <th>Event</th>
                        <th>Responsible</th>
                        <th>Status (Auto-Saves)</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($events as $event): 
                        $cssClass = 'val-' . str_replace(' ', '', $event['status']); 
                    ?>
                    <tr data-id="<?php echo $event['id']; ?>">
                        <td style="font-size: 12px; color: #666;"><?php echo date('M d, Y', strtotime($event['updated_at'])); ?></td>
                        <td style="font-weight: bold;"><?php echo htmlspecialchars($event['college_name']); ?></td>
                        <td><span class="badge badge-course" style="background:#d1ecf1; padding:3px 6px; border-radius:4px; font-size:11px;"><?php echo htmlspecialchars($event['course_name']); ?></span></td>
                        <td><?php echo htmlspecialchars($event['event_name']); ?></td>
                        <td>
                            <?php if($event['member_name']): ?>
                                <?php echo htmlspecialchars($event['member_name']); ?> <span style="font-size:11px; opacity:0.6;">(<?php echo htmlspecialchars($event['team_name']); ?>)</span>
                            <?php else: ?>
                                <span style="color:red; font-size:12px;">Unassigned</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <select class="status-select <?php echo $cssClass; ?>" onchange="updateStatus(this, <?php echo $event['id']; ?>)">
                                <?php foreach($statuses as $s): ?>
                                    <option value="<?php echo $s; ?>" <?php echo ($event['status'] == $s) ? 'selected' : ''; ?>><?php echo $s; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td style="text-align:center;">
                            <button class="btn-delete" onclick="deleteEvent(<?php echo $event['id']; ?>)" title="Delete Event">🗑</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
    // --- SMART COLLEGE FILTERING ---
    // Inject PHP college data into JS
    const collegesData = <?php echo json_encode($institutes_raw); ?>;
    
    function filterColleges() {
        const courseId = document.getElementById('newCourse').value;
        const colSelect = document.getElementById('newInstitute');
        
        // Reset dropdown
        colSelect.innerHTML = '<option value="">-- Select College --</option>';
        colSelect.disabled = true;

        if(!courseId) return;

        // Find colleges that have this course ID in their course_ids string
        let hasMatches = false;
        collegesData.forEach(col => {
            if(col.course_ids && col.course_ids.split(',').includes(courseId)) {
                const opt = document.createElement('option');
                opt.value = col.id;
                opt.innerText = col.name;
                colSelect.appendChild(opt);
                hasMatches = true;
            }
        });

        if (hasMatches) {
            colSelect.disabled = false;
        } else {
            colSelect.innerHTML = '<option value="">-- No Colleges have this level --</option>';
        }
    }

    // --- ADD EVENT ---
    async function addEvent() {
        const institute_id = document.getElementById('newInstitute').value;
        const course_id = document.getElementById('newCourse').value;
        const event_name = document.getElementById('newEventName').value;
        const status = document.getElementById('newStatus').value;

        if (!institute_id || !course_id || !event_name) {
            return alert("Please complete steps 1, 2, and 3.");
        }

        const formData = new FormData();
        formData.append('action', 'add_event');
        formData.append('institute_id', institute_id);
        formData.append('course_id', course_id);
        formData.append('event_name', event_name);
        formData.append('status', status);

        try {
            const response = await fetch('event_manager.php', { method: 'POST', body: formData });
            const res = await response.json();
            if (res.status === 'success') location.reload();
            else alert("Error: " + res.message);
        } catch (e) { alert("Request failed."); }
    }

    // --- AUTO-SAVE STATUS ---
    async function updateStatus(selectElement, id) {
        const newStatus = selectElement.value;
        const originalClass = selectElement.className;

        selectElement.className = 'status-select val-' + newStatus.replace(/\s+/g, '');

        const formData = new FormData();
        formData.append('action', 'update_status');
        formData.append('id', id);
        formData.append('status', newStatus);

        try {
            const response = await fetch('event_manager.php', { method: 'POST', body: formData });
            const res = await response.json();
            if (res.status !== 'success') {
                alert("Error: " + res.message);
                selectElement.className = originalClass; 
            }
        } catch (e) { alert("Request failed."); selectElement.className = originalClass; }
    }

    // --- DELETE EVENT ---
    async function deleteEvent(id) {
        if (!confirm("Delete this event log?")) return;
        const formData = new FormData();
        formData.append('action', 'delete_event');
        formData.append('id', id);
        try {
            const response = await fetch('event_manager.php', { method: 'POST', body: formData });
            const res = await response.json();
            if (res.status === 'success') location.reload();
            else alert("Error: " + res.message);
        } catch (e) { alert("Request failed."); }
    }
</script>

<?php require_once 'includes/footer.php'; ?>