<?php
require_once 'includes/header.php';
require_once 'includes/db.php'; 
check_login();

// Permission Check
if (!user_can('User Management')) {
    echo "<div class='p-10 text-center text-red-600 text-xl font-bold'>Access Denied</div>";
  
    exit();
}

// --- PHP LOGIC ---
$msg = null;
$msg_type = "";

// 1. Create User Logic
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_user'])) {
    $username = trim($_POST['username']);
    $phone_no = trim($_POST['phone_no']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $msg = "Username and password are required."; $msg_type = "error";
    } else {
        $stmt_check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt_check->execute([$username]);
        if ($stmt_check->fetch()) {
            $msg = "Username already taken."; $msg_type = "error";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt_insert = $pdo->prepare("INSERT INTO users (username, phone_no, password) VALUES (?, ?, ?)");
            $stmt_insert->execute([$username, $phone_no, $hashed_password]);
            $msg = "New user created successfully!"; $msg_type = "success";
        }
    }
}

// 2. Reset Password Logic
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset_password'])) {
    $u_id = $_POST['reset_user_id'];
    $new_pass = $_POST['new_password'];
    if(!empty($u_id) && !empty($new_pass)){
        $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashed, $u_id]);
        $msg = "Password reset successfully."; $msg_type = "success";
    }
}

// 3. Delete User Logic
if (isset($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];
    if ($del_id == $_SESSION['user_id']) {
        $msg = "You cannot delete yourself!"; $msg_type = "error";
    } else {
        $stmt_del = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt_del->execute([$del_id]);
        $msg = "User deleted successfully."; $msg_type = "success";
    }
}

// 4. Fetch Data
$all_users = $pdo->query("SELECT * FROM users ORDER BY username ASC")->fetchAll();
$all_menus = $pdo->query("SELECT * FROM menus ORDER BY menu_name ASC")->fetchAll();

// 5. Handle User Selection for Permissions
$selected_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$user_permissions = [];
if ($selected_user_id > 0) {
    $perms_stmt = $pdo->prepare("SELECT menu_id FROM user_permissions WHERE user_id = ?");
    $perms_stmt->execute([$selected_user_id]);
    $user_permissions = $perms_stmt->fetchAll(PDO::FETCH_COLUMN);
}
?>

<style>
    /* --- PAGE SPECIFIC STYLES TO MATCH SCREENSHOT --- */
    .admin-panel {
        background: #fff;
        padding: 20px;
        max-width: 1100px;
        margin: 0 auto;
    }

    .section-title {
        color: #008B8B; /* Teal color from image */
        font-size: 22px;
        font-weight: bold;
        margin-bottom: 15px;
        border-bottom: 1px solid #eee;
        padding-bottom: 5px;
    }

    .input-group {
        margin-bottom: 15px;
    }
    .input-group label {
        display: block;
        color: #555;
        font-weight: 500;
        margin-bottom: 5px;
    }
    .form-control {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #ccc;
        border-radius: 4px;
        font-size: 14px;
        background-color: #fff;
    }

    /* --- BUTTONS --- */
    .btn-row {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 20px;
        margin-bottom: 30px;
    }
    .custom-btn {
        border: none;
        padding: 10px 20px;
        color: white;
        font-weight: 600;
        font-size: 14px;
        border-radius: 4px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        text-decoration: none;
        transition: opacity 0.2s;
    }
    .custom-btn:hover { opacity: 0.9; }

    /* Specific Colors */
    .btn-teal   { background-color: #009688; } /* View List */
    .btn-orange { background-color: #f0ad4e; } /* Add / Reset */
    .btn-red    { background-color: #d9534f; } /* Remove */
    .btn-blue   { background-color: #337ab7; } /* Create User */
    .btn-purple { background-color: #800080; } /* Edit User */
    .btn-sky    { background-color: #5bc0de; } /* Print */
    .btn-green  { background-color: #008000; } /* Profile */

    /* Checkbox Area */
    .perm-box {
        background: #f9f9f9;
        border: 1px solid #ddd;
        padding: 15px;
        border-radius: 4px;
        margin-top: 10px;
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 10px;
    }
    .perm-item { display: flex; align-items: center; gap: 8px; font-size: 14px; }

    /* Table Styles */
    .user-table-container {
        display: none; /* Hidden by default, toggled by button */
        margin-top: 20px;
        border: 1px solid #ccc;
    }
    .user-table { width: 100%; border-collapse: collapse; }
    .user-table th { background: #f0f0f0; padding: 10px; border: 1px solid #ccc; text-align: left; }
    .user-table td { padding: 8px; border: 1px solid #ccc; }

    /* Messages */
    .msg-box { padding: 10px; margin-bottom: 20px; border-radius: 4px; text-align: center; font-weight: bold; }
    .msg-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .msg-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
</style>

<div class="admin-panel">
    
    <?php if($msg): ?>
        <div class="msg-box <?php echo ($msg_type=='success')?'msg-success':'msg-error'; ?>">
            <?php echo $msg; ?>
        </div>
    <?php endif; ?>

    <div class="section-title">Menu Level Permission Settings</div>
    
    <form action="handlers/handle_permissions.php" method="POST" id="permForm">
        <div class="input-group">
            <label>Select User</label>
            <select name="user_id" class="form-control" id="userSelect" onchange="window.location.href='user_management.php?user_id='+this.value">
                <option value="">-- Select User --</option>
                <?php foreach ($all_users as $u): ?>
                    <option value="<?php echo $u['id']; ?>" <?php echo ($selected_user_id == $u['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($u['username']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <?php if ($selected_user_id > 0): ?>
            <div class="input-group">
                <label>Select Menu (Check to Assign)</label>
                <div class="perm-box">
                    <?php foreach ($all_menus as $menu): ?>
                        <label class="perm-item">
                            <input type="checkbox" name="menu_ids[]" value="<?php echo $menu['id']; ?>"
                                <?php echo in_array($menu['id'], $user_permissions) ? 'checked' : ''; ?>>
                            <?php echo htmlspecialchars($menu['menu_name']); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <input type="hidden" name="action" id="permAction" value="save">
        <?php endif; ?>

        <div class="btn-row">
            <button type="button" class="custom-btn btn-teal" onclick="toggleTable()">
                <i class="fas fa-list"></i> View List
            </button>

            <?php if ($selected_user_id > 0): ?>
                <button type="submit" class="custom-btn btn-orange">
                    <i class="fas fa-plus"></i> Add / Save
                </button>

                <button type="button" class="custom-btn btn-red" onclick="clearPermissions()">
                    <i class="fas fa-times"></i> Remove All
                </button>
            <?php endif; ?>
        </div>
    </form>

    <div id="userTableSection" class="user-table-container">
        <table class="user-table">
            <thead>
                <tr><th>ID</th><th>Username</th><th>Phone</th><th>Action</th></tr>
            </thead>
            <tbody>
                <?php foreach ($all_users as $u): ?>
                <tr>
                    <td><?php echo $u['id']; ?></td>
                    <td><?php echo htmlspecialchars($u['username']); ?></td>
                    <td><?php echo htmlspecialchars($u['phone_no']); ?></td>
                    <td>
                        <a href="user_management.php?delete_id=<?php echo $u['id']; ?>" 
                           onclick="return confirm('Delete this user?')" 
                           style="color:red;">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>


    <div class="section-title" style="margin-top: 40px;">Create New User and Reset Password</div>

    <div class="btn-row">
        <button type="button" class="custom-btn btn-blue" onclick="openModal('createModal')">
            <i class="fas fa-plus"></i> Create User
        </button>

        <button type="button" class="custom-btn btn-orange" onclick="openModal('resetModal')">
            <i class="fas fa-key"></i> Reset Password
        </button>

        <button type="button" class="custom-btn btn-purple" onclick="document.getElementById('userSelect').focus()">
            <i class="fas fa-edit"></i> Edit User
        </button>

        <button type="button" class="custom-btn btn-sky" onclick="printUserList()">
            <i class="fas fa-print"></i> Print User List
        </button>

        <button type="button" class="custom-btn btn-green" onclick="alert('Profile feature coming soon!')">
            <i class="fas fa-list-alt"></i> Show Profile
        </button>
    </div>

</div>

<div id="createModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal('createModal')">&times;</span>
        <h3 class="section-title" style="font-size:18px; border:none;">Create New User</h3>
        <form action="user_management.php" method="POST">
            <input type="hidden" name="create_user" value="1">
            <div class="input-group">
                <label>Username</label>
                <input type="text" name="username" class="form-control" required>
            </div>
            <div class="input-group">
                <label>Phone</label>
                <input type="text" name="phone_no" class="form-control">
            </div>
            <div class="input-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="custom-btn btn-blue" style="width:100%; justify-content:center;">Create</button>
        </form>
    </div>
</div>

<div id="resetModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal('resetModal')">&times;</span>
        <h3 class="section-title" style="font-size:18px; border:none;">Reset Password</h3>
        <form action="user_management.php" method="POST">
            <input type="hidden" name="reset_password" value="1">
            <div class="input-group">
                <label>Select User</label>
                <select name="reset_user_id" class="form-control" required>
                    <option value="">-- Select User --</option>
                    <?php foreach ($all_users as $u): ?>
                        <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['username']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="input-group">
                <label>New Password</label>
                <input type="password" name="new_password" class="form-control" required>
            </div>
            <button type="submit" class="custom-btn btn-orange" style="width:100%; justify-content:center;">Update Password</button>
        </form>
    </div>
</div>

<script>
    // Toggle Table
    function toggleTable() {
        var x = document.getElementById("userTableSection");
        if (x.style.display === "block") { x.style.display = "none"; } 
        else { x.style.display = "block"; }
    }

    // Modal Functions
    function openModal(id) { document.getElementById(id).style.display = "block"; }
    function closeModal(id) { document.getElementById(id).style.display = "none"; }
    
    // Close on outside click
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = "none";
        }
    }

    // Clear Permissions Logic
    function clearPermissions() {
        if(confirm("Are you sure you want to remove ALL permissions for this user?")) {
            // Uncheck all boxes
            var checkboxes = document.querySelectorAll('input[name="menu_ids[]"]');
            for (var i = 0; i < checkboxes.length; i++) {
                checkboxes[i].checked = false;
            }
            // Submit the form
            document.getElementById('permForm').submit();
        }
    }

    // Print Logic
    function printUserList() {
        // Show table, print, then hide/restore state
        document.getElementById("userTableSection").style.display = "block";
        window.print();
    }
</script>