<?php
require_once 'includes/functions.php';
require_once 'includes/db.php';
check_login();

// --- AJAX HANDLER ---
if (isset($_POST['action']) && $_POST['action'] == 'add_event') {
    ob_start();
    header('Content-Type: application/json');
    try {
        $course_id = (int)$_POST['course_id'];
        $event_name = trim($_POST['event_name']);
        $status = $_POST['status'];

        if (!$course_id || empty($event_name)) {
            throw new Exception("Course and Event Name are required.");
        }

        $pdo->beginTransaction();

        // 1. Find ALL colleges that offer this specific course/level
        $stmt = $pdo->prepare("SELECT institute_id FROM institute_courses WHERE course_id = ?");
        $stmt->execute([$course_id]);
        $institutes = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (count($institutes) === 0) {
            throw new Exception("No colleges are mapped to this level. Please assign this level to colleges in the Institute Manager first.");
        }

        // 2. Loop through every matched college and insert the event
        $insertStmt = $pdo->prepare("INSERT INTO events (institute_id, course_id, event_name, status) VALUES (?, ?, ?, ?)");
        
        $insertedCount = 0;
        foreach ($institutes as $inst_id) {
            // Check to prevent duplicating the same event for the same college
            $checkStmt = $pdo->prepare("SELECT id FROM events WHERE institute_id = ? AND course_id = ? AND event_name = ?");
            $checkStmt->execute([$inst_id, $course_id, $event_name]);
            if(!$checkStmt->fetch()) {
                $insertStmt->execute([$inst_id, $course_id, $event_name, $status]);
                $insertedCount++;
            }
        }

        $pdo->commit();
        
        ob_clean();
        if($insertedCount > 0){
             echo json_encode(['status' => 'success', 'message' => "Successfully created tracking rows for $insertedCount colleges!"]);
        } else {
             echo json_encode(['status' => 'error', 'message' => "This event is already actively tracked for all colleges in this level."]);
        }
        exit;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}

// --- FETCH DATA FOR DROPDOWNS ---
$courses = $pdo->query("SELECT id, name FROM courses ORDER BY id ASC")->fetchAll();
// Fetch active global events to power the dynamic JavaScript filter
$global_events_raw = $pdo->query("SELECT name, course_id FROM global_events WHERE is_archived = 0 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

require_once 'includes/header.php';
?>

<style>
    .crm-container { max-width: 800px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    .panel-header { border-bottom: 2px solid #28a745; padding-bottom: 10px; margin-bottom: 25px; font-size: 22px; font-weight: bold; }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; font-size: 14px; font-weight: bold; margin-bottom: 8px; color:#333;}
    .form-control { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 4px; font-size: 16px;}
    .btn-add { background: #28a745; color: white; border: none; padding: 12px 25px; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 16px; width: 100%;}
    .helper-text { font-size: 12px; color: #6c757d; margin-top: 5px; display: block; font-style: italic; }
</style>

<div class="crm-container">
    <div class="panel-header">➕ Create Event (Bulk Push)</div>

    <div class="form-group">
        <label>1. Select Course / Level</label>
        <select id="newCourse" class="form-control" onchange="filterEventsByCourse()">
            <option value="">-- Select Course --</option>
            <?php foreach($courses as $course): ?>
                <option value="<?php echo $course['id']; ?>"><?php echo htmlspecialchars($course['name']); ?></option>
            <?php endforeach; ?>
        </select>
        <span class="helper-text">This event will automatically be tracked for <strong>all colleges</strong> that offer this level.</span>
    </div>

    <div class="form-group">
        <label>2. Select Event Name</label>
        <select id="newEventName" class="form-control" disabled>
            <option value="">-- Select a Course First --</option>
        </select>
        <span class="helper-text">This list is managed in your Global Event Manager.</span>
    </div>

    <div class="form-group">
        <label>3. Initial Status</label>
        <select id="newStatus" class="form-control">
            <option value="Pending">Pending</option>
            <option value="Yes">Yes (Completed/Active)</option>
            <option value="No">No</option>
            <option value="No PG">Not Payment Gateway Integrated</option>
            <option value="Inactive">Inactive</option>
        </select>
    </div>

    <button class="btn-add" onclick="addEvent()">Log Event for All Colleges</button>
</div>

<script>
    const globalEvents = <?php echo json_encode($global_events_raw); ?>;

    function filterEventsByCourse() {
        const courseId = document.getElementById('newCourse').value;
        const eventSelect = document.getElementById('newEventName');
        
        eventSelect.innerHTML = '<option value="">-- Select Event --</option>';
        if(!courseId) { eventSelect.disabled = true; return; }

        let hasEvents = false;
        globalEvents.forEach(ev => {
            if(ev.course_id == courseId) {
                const opt = document.createElement('option');
                opt.value = ev.name; opt.innerText = ev.name;
                eventSelect.appendChild(opt);
                hasEvents = true;
            }
        });

        eventSelect.disabled = !hasEvents;
        if(!hasEvents) eventSelect.innerHTML = '<option value="">-- No active global events found for this level --</option>';
    }

    async function addEvent() {
        const course_id = document.getElementById('newCourse').value;
        const event_name = document.getElementById('newEventName').value;
        const status = document.getElementById('newStatus').value;

        if (!course_id || !event_name) return alert("Please select a Course and Event Name.");

        const formData = new FormData();
        formData.append('action', 'add_event');
        formData.append('course_id', course_id);
        formData.append('event_name', event_name);
        formData.append('status', status);

        try {
            const res = await fetch('event_create.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.status === 'success') {
                alert(data.message); 
                window.location.href = 'event_update.php'; 
            } else {
                alert("Error: " + data.message);
            }
        } catch (e) { alert("Request failed."); }
    }
</script>

<?php require_once 'includes/footer.php'; ?>