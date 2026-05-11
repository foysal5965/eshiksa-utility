<?php
require_once 'includes/functions.php';
require_once 'includes/db.php';
check_login();

// --- CSV UPLOAD HANDLER ---
if (isset($_POST['upload_csv']) && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file']['tmp_name'];
    
    if (($handle = fopen($file, "r")) !== FALSE) {
        $isFirstRow = true;
        
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            // Skip header row if it exists (checks if first column says "college")
            if ($isFirstRow && stripos(trim($data[0]), 'college') !== false) {
                $isFirstRow = false;
                continue;
            }
            $isFirstRow = false;

            $college_name = trim($data[0] ?? '');
            $member_name = trim($data[1] ?? '');

            if (!empty($college_name)) {
                $member_id = null;
                
                // If a name is provided, look up their ID in the team_members table
                if (!empty($member_name)) {
                    $m_stmt = $pdo->prepare("SELECT id FROM team_members WHERE name LIKE ? LIMIT 1");
                    $m_stmt->execute(["%$member_name%"]);
                    $m_res = $m_stmt->fetchColumn();
                    if ($m_res) {
                        $member_id = $m_res;
                    }
                }

                // Insert the new college
                $stmt = $pdo->prepare("INSERT INTO institutes (name, member_id) VALUES (?, ?)");
                $stmt->execute([$college_name, $member_id]);
            }
        }
        fclose($handle);
        // Refresh page to prevent form resubmission on reload
        header("Location: institute_manager.php");
        exit;
    }
}

// --- AJAX HANDLER FOR CRUD OPERATIONS ---
if (isset($_POST['action'])) {
    ob_start();
    header('Content-Type: application/json');
    $id = (int)($_POST['id'] ?? 0);

    try {
        if ($_POST['action'] == 'add_institute') {
            $name = trim($_POST['name']);
            $member_id = (int)$_POST['member_id'];
            $course_ids = json_decode($_POST['course_ids'] ?? '[]', true);
            
            if (empty($name)) throw new Exception("College name cannot be empty.");
            
            $member_val = $member_id > 0 ? $member_id : null;

            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO institutes (name, member_id) VALUES (?, ?)");
            $stmt->execute([$name, $member_val]);
            $new_inst_id = $pdo->lastInsertId();

            if (is_array($course_ids) && count($course_ids) > 0) {
                $c_stmt = $pdo->prepare("INSERT INTO institute_courses (institute_id, course_id) VALUES (?, ?)");
                foreach($course_ids as $cid) {
                    $c_stmt->execute([$new_inst_id, $cid]);
                }
            }
            $pdo->commit();
            
            ob_clean();
            echo json_encode(['status' => 'success']);
            exit;
        } 
        
        elseif ($_POST['action'] == 'update_institute') {
            $name = trim($_POST['name']);
            $member_id = (int)$_POST['member_id'];
            $course_ids = json_decode($_POST['course_ids'] ?? '[]', true);
            
            if (empty($name)) throw new Exception("College name cannot be empty.");
            $member_val = $member_id > 0 ? $member_id : null;

            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE institutes SET name = ?, member_id = ? WHERE id = ?");
            $stmt->execute([$name, $member_val, $id]);

            // Clear old courses and insert new ones
            $pdo->prepare("DELETE FROM institute_courses WHERE institute_id = ?")->execute([$id]);
            if (is_array($course_ids) && count($course_ids) > 0) {
                $c_stmt = $pdo->prepare("INSERT INTO institute_courses (institute_id, course_id) VALUES (?, ?)");
                foreach($course_ids as $cid) {
                    $c_stmt->execute([$id, $cid]);
                }
            }
            $pdo->commit();

            ob_clean();
            echo json_encode(['status' => 'success']);
            exit;
        }
        
        elseif ($_POST['action'] == 'delete_institute') {
            $stmt = $pdo->prepare("DELETE FROM institutes WHERE id = ?");
            $stmt->execute([$id]);
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
$members = $pdo->query("SELECT m.id, m.name, t.name as team_name FROM team_members m LEFT JOIN teams t ON m.team_id = t.id ORDER BY t.name ASC, m.name ASC")->fetchAll();
$courses = $pdo->query("SELECT * FROM courses ORDER BY id ASC")->fetchAll();

$institutes_query = "
    SELECT i.id, i.name as college_name, i.member_id, 
           m.name as member_name, t.name as team_name,
           GROUP_CONCAT(c.id) as course_ids,
           GROUP_CONCAT(c.name SEPARATOR ', ') as course_names
    FROM institutes i
    LEFT JOIN team_members m ON i.member_id = m.id
    LEFT JOIN teams t ON m.team_id = t.id
    LEFT JOIN institute_courses ic ON i.id = ic.institute_id
    LEFT JOIN courses c ON ic.course_id = c.id
    GROUP BY i.id
    ORDER BY i.name ASC
";
$institutes = $pdo->query($institutes_query)->fetchAll();

// Extract unique members for the table filter dropdown
$unique_members = [];
foreach($institutes as $inst) {
    if($inst['member_name'] && !in_array($inst['member_name'], $unique_members)) {
        $unique_members[] = $inst['member_name'];
    }
}
sort($unique_members);

require_once 'includes/header.php';
?>

<style>
    .manager-container { max-width: 1100px; margin: 20px auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    .panel-header { border-bottom: 2px solid #007bff; padding-bottom: 10px; margin-bottom: 20px; font-size: 20px; font-weight: bold; }
    
    .toolbar { background: #f8f9fa; padding: 15px; border-radius: 8px; border: 1px solid #ddd; margin-bottom: 20px; }
    .form-group { display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-start;}
    .form-input { flex: 1; min-width: 200px; padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
    .btn { padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; color: white; font-weight: bold; }
    .btn-add { background: #28a745; }
    .btn-edit { background: #ffc107; color: #000; padding: 5px 10px; font-size: 12px; }
    .btn-delete { background: #dc3545; padding: 5px 10px; font-size: 12px; }
    .btn-save { background: #007bff; padding: 5px 10px; font-size: 12px; color: white; }
    .btn-cancel { background: #6c757d; padding: 5px 10px; font-size: 12px; color: white; }
    
    table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 14px;}
    th { background: #333; color: #fff; padding: 12px; text-align: left; }
    td { border-bottom: 1px solid #ddd; padding: 12px; vertical-align: top;}
    
    .badge { background: #e9ecef; padding: 4px 8px; border-radius: 12px; font-size: 12px; color: #333; border: 1px solid #ccc; }
    .badge-course { background: #d1ecf1; color: #0c5460; border-color: #bee5eb; margin-right:3px; margin-bottom:3px; display:inline-block;}
    .badge-unassigned { background: #fee2e2; color: #991b1b; border-color: #fca5a5; }

    .edit-mode { display: none; }
    .checkbox-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 5px; background: #fff; padding: 10px; border: 1px solid #ccc; border-radius: 4px; width: 100%;}
    .checkbox-grid label { font-size: 12px; display:flex; align-items:center; gap:5px; margin:0;}
    
    .filter-select { width: 100%; padding: 4px; border-radius: 4px; border: 1px solid #ccc; color: #333; font-weight: bold; margin-top: 5px;}
</style>

<div class="manager-container">
    <div class="panel-header">🏫 Institute & Course Manager</div>
    
    <div class="toolbar">
        <form method="POST" enctype="multipart/form-data" style="display:flex; gap:10px; align-items:flex-end; margin-bottom:20px; border-bottom: 1px solid #ddd; padding-bottom:20px;">
            <div style="flex-grow:1;">
                <label style="font-weight:bold; font-size:14px; display:block; margin-bottom:8px;">Bulk Upload Institutes (CSV)</label>
                <input type="file" name="csv_file" required accept=".csv" class="form-input" style="background:#fff;">
                <small style="display:block; color:#666; margin-top:5px;">Format: <b>College Name</b>, <b>Responsible Person Name</b> <i>(Must match exactly)</i></small>
            </div>
            <button type="submit" name="upload_csv" class="btn" style="background:#6c757d;">Upload CSV</button>
        </form>

        <label style="font-weight:bold; font-size:14px; display:block; margin-bottom:8px;">Add Single College & Levels</label>
        <div class="form-group" style="align-items: center;">
            <input type="text" id="newCollegeName" class="form-input" placeholder="Enter College Name">
            <select id="newResponsibleMember" class="form-input">
                <option value="0">-- Select Responsible Person --</option>
                <?php foreach($members as $m): ?>
                    <option value="<?php echo $m['id']; ?>"><?php echo htmlspecialchars($m['name']) . ' (' . htmlspecialchars($m['team_name']) . ')'; ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-add" onclick="addInstitute()">+ Add College</button>
        </div>
        <div class="checkbox-grid" style="margin-top:10px;">
            <?php foreach($courses as $c): ?>
                <label><input type="checkbox" class="add-course-cb" value="<?php echo $c['id']; ?>"> <?php echo htmlspecialchars($c['name']); ?></label>
            <?php endforeach; ?>
        </div>
    </div>

    <div style="overflow-x:auto;">
        <table id="instituteTable">
            <thead>
                <tr>
                    <th style="width: 25%;">College Name</th>
                    <th style="width: 35%;">Levels / Courses Offered</th>
                    <th style="width: 25%;">
                        Responsible Person
                        <select id="filterMember" class="filter-select" onchange="filterTable()">
                            <option value="">All Members</option>
                            <option value="Unassigned">⚠️ Unassigned Only</option>
                            <?php foreach($unique_members as $um): ?>
                                <option value="<?php echo htmlspecialchars(strtolower($um)); ?>"><?php echo htmlspecialchars($um); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </th>
                    <th style="width: 15%; text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                <?php foreach($institutes as $inst): 
                    $c_ids = explode(',', $inst['course_ids']);
                    $c_names = explode(',', $inst['course_names']);
                ?>
                <tr data-id="<?php echo $inst['id']; ?>" data-member="<?php echo htmlspecialchars(strtolower($inst['member_name'] ?? 'unassigned')); ?>">
                    <td>
                        <span class="view-mode name-display" style="font-weight:bold;"><?php echo htmlspecialchars($inst['college_name']); ?></span>
                        <input type="text" class="edit-mode name-input form-input" style="width:100%; padding:4px;" value="<?php echo htmlspecialchars($inst['college_name']); ?>">
                    </td>
                    
                    <td>
                        <div class="view-mode">
                            <?php if(!empty($inst['course_names'])): ?>
                                <?php foreach($c_names as $cname): ?>
                                    <span class="badge badge-course"><?php echo htmlspecialchars(trim($cname)); ?></span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span style="font-size:12px; color:#999;">No levels assigned</span>
                            <?php endif; ?>
                        </div>
                        <div class="edit-mode checkbox-grid">
                            <?php foreach($courses as $c): ?>
                                <label><input type="checkbox" class="edit-course-cb" value="<?php echo $c['id']; ?>" <?php echo in_array($c['id'], $c_ids) ? 'checked' : ''; ?>> <?php echo htmlspecialchars($c['name']); ?></label>
                            <?php endforeach; ?>
                        </div>
                    </td>

                    <td>
                        <span class="view-mode member-display">
                            <?php if ($inst['member_id']): ?>
                                <span class="badge">👤 <?php echo htmlspecialchars($inst['member_name']); ?></span>
                            <?php else: ?>
                                <span class="badge badge-unassigned">⚠️ Unassigned</span>
                            <?php endif; ?>
                        </span>
                        <select class="edit-mode member-input form-input" style="width:100%; padding:4px;">
                            <option value="0">-- Unassigned --</option>
                            <?php foreach($members as $m): ?>
                                <option value="<?php echo $m['id']; ?>" <?php echo ($inst['member_id'] == $m['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($m['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    
                    <td style="text-align:right; white-space:nowrap;">
                        <div class="view-mode">
                            <button class="btn btn-edit" onclick="toggleEdit(this, true)">Edit</button>
                            <button class="btn btn-delete" onclick="deleteInstitute(<?php echo $inst['id']; ?>)">Del</button>
                        </div>
                        <div class="edit-mode">
                            <button class="btn btn-save" onclick="saveInstitute(this, <?php echo $inst['id']; ?>)">Save</button>
                            <button class="btn btn-cancel" onclick="toggleEdit(this, false)">Cancel</button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    // --- TABLE FILTERING LOGIC ---
    function filterTable() {
        const memberFilter = document.getElementById('filterMember').value;
        const rows = document.querySelectorAll('#tableBody tr');

        rows.forEach(row => {
            const rMember = row.getAttribute('data-member');
            
            if (memberFilter === "" || rMember === memberFilter) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    // --- CRUD AJAX LOGIC ---
    async function addInstitute() {
        const name = document.getElementById('newCollegeName').value;
        const member_id = document.getElementById('newResponsibleMember').value;
        const course_ids = Array.from(document.querySelectorAll('.add-course-cb:checked')).map(cb => cb.value);
        
        if (!name) return alert("Please enter a College Name.");

        const formData = new FormData();
        formData.append('action', 'add_institute');
        formData.append('name', name);
        formData.append('member_id', member_id);
        formData.append('course_ids', JSON.stringify(course_ids));

        try {
            const response = await fetch('institute_manager.php', { method: 'POST', body: formData });
            const res = await response.json();
            if (res.status === 'success') location.reload();
            else alert("Error: " + res.message);
        } catch (e) { alert("Request failed."); }
    }

    function toggleEdit(btn, showEdit) {
        const row = btn.closest('tr');
        row.querySelectorAll('.view-mode').forEach(el => el.style.display = showEdit ? 'none' : '');
        row.querySelectorAll('.edit-mode').forEach(el => el.style.display = showEdit ? 'block' : 'none'); // block for grid
    }

    async function saveInstitute(btn, id) {
        const row = btn.closest('tr');
        const nameInput = row.querySelector('.name-input').value;
        const memberInput = row.querySelector('.member-input').value;
        const course_ids = Array.from(row.querySelectorAll('.edit-course-cb:checked')).map(cb => cb.value);

        if (!nameInput) return alert("College name cannot be empty.");

        const formData = new FormData();
        formData.append('action', 'update_institute');
        formData.append('id', id);
        formData.append('name', nameInput);
        formData.append('member_id', memberInput);
        formData.append('course_ids', JSON.stringify(course_ids));

        try {
            btn.innerText = "...";
            const response = await fetch('institute_manager.php', { method: 'POST', body: formData });
            const res = await response.json();
            if (res.status === 'success') location.reload(); 
            else alert("Error: " + res.message);
        } catch (e) { alert("Request failed."); }
    }

    async function deleteInstitute(id) {
        if (!confirm("Are you sure? All event tracking for this college will be deleted!")) return;
        const formData = new FormData();
        formData.append('action', 'delete_institute');
        formData.append('id', id);
        try {
            const response = await fetch('institute_manager.php', { method: 'POST', body: formData });
            const res = await response.json();
            if (res.status === 'success') document.querySelector(`tr[data-id='${id}']`).remove();
            else alert("Error: " + res.message);
        } catch (e) { alert("Request failed."); }
    }
</script>

<?php require_once 'includes/footer.php'; ?>