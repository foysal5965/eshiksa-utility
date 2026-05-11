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
        // --- ADD COURSE ---
        if ($_POST['action'] == 'add_course') {
            $name = trim($_POST['name']);
            if (empty($name)) throw new Exception("Course name cannot be empty.");
            
            $stmt = $pdo->prepare("INSERT INTO courses (name) VALUES (?)");
            $stmt->execute([$name]);
            
            ob_clean();
            echo json_encode(['status' => 'success']);
            exit;
        } 
        
        // --- UPDATE COURSE ---
        elseif ($_POST['action'] == 'update_course') {
            $name = trim($_POST['name']);
            if (empty($name)) throw new Exception("Course name cannot be empty.");
            
            $stmt = $pdo->prepare("UPDATE courses SET name = ? WHERE id = ?");
            $stmt->execute([$name, $id]);
            
            ob_clean();
            echo json_encode(['status' => 'success']);
            exit;
        }
        
        // --- DELETE COURSE ---
        elseif ($_POST['action'] == 'delete_course') {
            // Note: Because of ON DELETE CASCADE in your SQL, deleting a course 
            // will also remove it from 'institute_courses' and delete associated 'events'
            $stmt = $pdo->prepare("DELETE FROM courses WHERE id = ?");
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

// --- FETCH DATA FOR UI ---
$courses = $pdo->query("SELECT * FROM courses ORDER BY id ASC")->fetchAll();

require_once 'includes/header.php';
?>

<style>
    /* CSS Variables for Light/Dark Mode */
    :root {
        --bg-main: #f4f7f6;
        --bg-panel: #ffffff;
        --text-main: #333333;
        --border-color: #e9ecef;
        --shadow: 0 4px 10px rgba(0,0,0,0.08);
        --header-bg: #f8f9fa;
    }

    [data-theme="dark"] {
        --bg-main: #121212;
        --bg-panel: #1e1e1e;
        --text-main: #e0e0e0;
        --border-color: #333333;
        --shadow: 0 4px 10px rgba(0,0,0,0.4);
        --header-bg: #2d2d2d;
    }

    body { background-color: var(--bg-main); color: var(--text-main); transition: all 0.3s ease;}

    .crm-dashboard { max-width: 800px; margin: 20px auto; padding: 0 20px; display: flex; flex-direction: column; gap: 25px; }
    
    .dash-topbar { display: flex; justify-content: space-between; align-items: center; background: var(--bg-panel); padding: 15px 20px; border-radius: 8px; box-shadow: var(--shadow); }
    .dash-title { margin: 0; font-size: 24px; font-weight: bold; }
    .btn-theme { background: #6c757d; color: white; padding: 8px 15px; border-radius: 6px; font-weight: bold; cursor: pointer; border: none; }

    .section-panel { background: var(--bg-panel); padding: 20px; border-radius: 10px; box-shadow: var(--shadow); }
    
    .form-group { display: flex; gap: 10px; margin-bottom: 20px; }
    .form-input { flex: 1; padding: 10px; border: 1px solid var(--border-color); border-radius: 4px; background: var(--bg-main); color: var(--text-main);}
    .btn-add { background: #28a745; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: bold; }
    
    table { width: 100%; border-collapse: collapse; font-size: 14px; }
    th { background: var(--header-bg); color: var(--text-main); padding: 12px; text-align: left; font-weight: bold; border-bottom: 2px solid var(--border-color);}
    td { padding: 12px; border-bottom: 1px solid var(--border-color); color: var(--text-main); vertical-align: middle; }
    
    .btn-edit { background: #ffc107; color: #000; padding: 5px 10px; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: bold; }
    .btn-delete { background: #dc3545; color: white; padding: 5px 10px; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: bold; margin-left: 5px; }
</style>

<div class="crm-dashboard">

    <div class="dash-topbar">
        <h1 class="dash-title"> Manage Courses & Levels</h1>
    </div>

    <div class="section-panel">
        
        <div class="form-group">
            <input type="text" id="newCourseName" class="form-input" placeholder="Enter new course/level name (e.g., Diploma, PhD)">
            <button class="btn-add" onclick="addCourse()">+ Add Course</button>
        </div>

        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th style="width: 15%;">ID</th>
                        <th style="width: 60%;">Course / Level Name</th>
                        <th style="width: 25%; text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($courses as $course): ?>
                    <tr data-id="<?php echo $course['id']; ?>">
                        <td style="color: var(--text-muted);">#<?php echo $course['id']; ?></td>
                        <td><strong class="course-name"><?php echo htmlspecialchars($course['name']); ?></strong></td>
                        <td style="text-align:right;">
                            <button class="btn-edit" onclick="editCourse(<?php echo $course['id']; ?>, '<?php echo addslashes(htmlspecialchars($course['name'])); ?>')">Edit</button>
                            <button class="btn-delete" onclick="deleteCourse(<?php echo $course['id']; ?>)">Del</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<script>
    // --- THEME TOGGLE ---
    function toggleTheme() {
        const body = document.body;
        if(body.getAttribute('data-theme') === 'dark') {
            body.removeAttribute('data-theme');
            localStorage.setItem('crm_theme', 'light');
        } else {
            body.setAttribute('data-theme', 'dark');
            localStorage.setItem('crm_theme', 'dark');
        }
    }
    if(localStorage.getItem('crm_theme') === 'dark') document.body.setAttribute('data-theme', 'dark');

    // --- GENERIC AJAX REQUEST ---
    async function sendRequest(action, data) {
        const formData = new FormData();
        formData.append('action', action);
        for (const key in data) {
            formData.append(key, data[key]);
        }
        
        try {
            const response = await fetch('course_manager.php', { method: 'POST', body: formData });
            const res = await response.json();
            if (res.status === 'success') {
                location.reload(); // Refresh to show changes
            } else {
                alert("Error: " + res.message);
            }
        } catch (e) {
            console.error(e);
            alert("Server request failed.");
        }
    }

    // --- ADD COURSE ---
    function addCourse() {
        const name = document.getElementById('newCourseName').value;
        if (!name) return alert("Please enter a course name.");
        sendRequest('add_course', { name: name });
    }

    // --- EDIT COURSE ---
    function editCourse(id, oldName) {
        const newName = prompt("Edit Course Name:", oldName);
        if (newName && newName.trim() !== oldName) {
            sendRequest('update_course', { id: id, name: newName.trim() });
        }
    }

    // --- DELETE COURSE ---
    function deleteCourse(id) {
        if (confirm("⚠️ WARNING: Deleting a course will also delete EVERY EVENT associated with this course across all colleges! Are you absolutely sure?")) {
            sendRequest('delete_course', { id: id });
        }
    }
</script>

<?php require_once 'includes/footer.php'; ?>