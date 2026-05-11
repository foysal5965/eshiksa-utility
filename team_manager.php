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
        // --- TEAM OPERATIONS ---
        if ($_POST['action'] == 'add_team') {
            $name = trim($_POST['name']);
            if (empty($name)) throw new Exception("Team name cannot be empty.");
            
            $stmt = $pdo->prepare("INSERT INTO teams (name) VALUES (?)");
            $stmt->execute([$name]);
            ob_clean();
            echo json_encode(['status' => 'success']);
            exit;
        } 
        elseif ($_POST['action'] == 'update_team') {
            $name = trim($_POST['name']);
            if (empty($name)) throw new Exception("Team name cannot be empty.");
            
            $stmt = $pdo->prepare("UPDATE teams SET name = ? WHERE id = ?");
            $stmt->execute([$name, $id]);
            ob_clean();
            echo json_encode(['status' => 'success']);
            exit;
        }
        elseif ($_POST['action'] == 'delete_team') {
            // Note: Because of ON DELETE CASCADE in SQL, deleting a team will delete its members
            $stmt = $pdo->prepare("DELETE FROM teams WHERE id = ?");
            $stmt->execute([$id]);
            ob_clean();
            echo json_encode(['status' => 'success']);
            exit;
        }

        // --- MEMBER OPERATIONS ---
        elseif ($_POST['action'] == 'add_member') {
            $name = trim($_POST['name']);
            $team_id = (int)$_POST['team_id'];
            if (empty($name) || !$team_id) throw new Exception("Name and Team are required.");
            
            $stmt = $pdo->prepare("INSERT INTO team_members (name, team_id) VALUES (?, ?)");
            $stmt->execute([$name, $team_id]);
            ob_clean();
            echo json_encode(['status' => 'success']);
            exit;
        }
        elseif ($_POST['action'] == 'update_member') {
            $name = trim($_POST['name']);
            $team_id = (int)$_POST['team_id'];
            if (empty($name) || !$team_id) throw new Exception("Name and Team are required.");
            
            $stmt = $pdo->prepare("UPDATE team_members SET name = ?, team_id = ? WHERE id = ?");
            $stmt->execute([$name, $team_id, $id]);
            ob_clean();
            echo json_encode(['status' => 'success']);
            exit;
        }
        elseif ($_POST['action'] == 'delete_member') {
            $stmt = $pdo->prepare("DELETE FROM team_members WHERE id = ?");
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
$teams = $pdo->query("SELECT * FROM teams ORDER BY name ASC")->fetchAll();

// Fetch members with their team names
$members_query = "
    SELECT m.id, m.name, m.team_id, t.name as team_name 
    FROM team_members m 
    LEFT JOIN teams t ON m.team_id = t.id 
    ORDER BY t.name ASC, m.name ASC
";
$members = $pdo->query($members_query)->fetchAll();

require_once 'includes/header.php';
?>

<style>
    .manager-container { max-width: 1200px; margin: 20px auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); display: flex; gap: 20px; flex-wrap: wrap; }
    .panel { flex: 1; min-width: 300px; background: #f8f9fa; padding: 15px; border-radius: 8px; border: 1px solid #ddd; }
    .panel-header { border-bottom: 2px solid #007bff; padding-bottom: 10px; margin-bottom: 15px; font-size: 18px; font-weight: bold; }
    
    .form-group { margin-bottom: 15px; display: flex; gap: 10px; }
    .form-input { flex: 1; padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
    .btn { padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; color: white; font-weight: bold; }
    .btn-add { background: #28a745; }
    .btn-edit { background: #ffc107; color: #000; padding: 4px 8px; font-size: 12px; }
    .btn-delete { background: #dc3545; padding: 4px 8px; font-size: 12px; }
    
    table { width: 100%; border-collapse: collapse; margin-top: 10px; background: #fff; }
    th { background: #333; color: #fff; padding: 10px; text-align: left; }
    td { border-bottom: 1px solid #ddd; padding: 10px; }
</style>

<div class="manager-container">
    
    <div class="panel">
        <div class="panel-header">👥 Manage Teams</div>
        
        <div class="form-group">
            <input type="text" id="newTeamName" class="form-input" placeholder="Enter new team name...">
            <button class="btn btn-add" onclick="addTeam()">+ Add</button>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Team Name</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($teams as $team): ?>
                <tr>
                    <td><?php echo htmlspecialchars($team['name']); ?></td>
                    <td style="text-align:right;">
                        <button class="btn btn-edit" onclick="editTeam(<?php echo $team['id']; ?>, '<?php echo htmlspecialchars($team['name']); ?>')">Edit</button>
                        <button class="btn btn-delete" onclick="deleteTeam(<?php echo $team['id']; ?>)">Del</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="panel">
        <div class="panel-header">👤 Manage Members</div>
        
        <div class="form-group" style="flex-wrap: wrap;">
            <input type="text" id="newMemberName" class="form-input" placeholder="Member Name..." style="min-width: 150px;">
            <select id="newMemberTeam" class="form-input" style="min-width: 120px;">
                <option value="">-- Select Team --</option>
                <?php foreach($teams as $team): ?>
                    <option value="<?php echo $team['id']; ?>"><?php echo htmlspecialchars($team['name']); ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-add" onclick="addMember()">+ Add</button>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Team</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($members as $member): ?>
                <tr>
                    <td><?php echo htmlspecialchars($member['name']); ?></td>
                    <td><span style="background:#e9ecef; padding:3px 8px; border-radius:10px; font-size:12px;"><?php echo htmlspecialchars($member['team_name']); ?></span></td>
                    <td style="text-align:right;">
                        <button class="btn btn-edit" onclick="editMember(<?php echo $member['id']; ?>, '<?php echo htmlspecialchars($member['name']); ?>', <?php echo $member['team_id']; ?>)">Edit</button>
                        <button class="btn btn-delete" onclick="deleteMember(<?php echo $member['id']; ?>)">Del</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>

<script>
    // --- GENERIC AJAX FUNCTION ---
    async function sendRequest(action, data) {
        const formData = new FormData();
        formData.append('action', action);
        for (const key in data) {
            formData.append(key, data[key]);
        }
        
        try {
            const response = await fetch('team_manager.php', { method: 'POST', body: formData });
            const res = await response.json();
            if (res.status === 'success') {
                location.reload(); // Quick refresh to show updated data
            } else {
                alert("Error: " + res.message);
            }
        } catch (e) {
            console.error(e);
            alert("Server request failed.");
        }
    }

    // --- TEAM FUNCTIONS ---
    function addTeam() {
        const name = document.getElementById('newTeamName').value;
        if (!name) return alert("Enter a team name");
        sendRequest('add_team', { name: name });
    }

    function editTeam(id, oldName) {
        const newName = prompt("Edit Team Name:", oldName);
        if (newName && newName !== oldName) {
            sendRequest('update_team', { id: id, name: newName });
        }
    }

    function deleteTeam(id) {
        if (confirm("WARNING: Deleting a team will delete all members inside it. Are you sure?")) {
            sendRequest('delete_team', { id: id });
        }
    }

    // --- MEMBER FUNCTIONS ---
    function addMember() {
        const name = document.getElementById('newMemberName').value;
        const team_id = document.getElementById('newMemberTeam').value;
        if (!name || !team_id) return alert("Enter name and select a team.");
        sendRequest('add_member', { name: name, team_id: team_id });
    }

    function editMember(id, oldName, oldTeamId) {
        // Simple prompt for name update. 
        // For team reassignment, they can delete and recreate or we can make a custom modal.
        // Keeping it simple with prompt for now.
        const newName = prompt("Edit Member Name (To change team, delete and recreate):", oldName);
        if (newName && newName !== oldName) {
            sendRequest('update_member', { id: id, name: newName, team_id: oldTeamId });
        }
    }

    function deleteMember(id) {
        if (confirm("Delete this member?")) {
            sendRequest('delete_member', { id: id });
        }
    }
</script>
