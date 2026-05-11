<?php
// --- STEP 1: INCLUDE ALL NON-HTML LOGIC ---
require_once 'includes/functions.php';
require_once 'includes/db.php'; 

// --- STEP 2: RUN ALL PROCESSING AND REDIRECTS ---
check_login(); 

$form_error = null;
$form_success = null;

// --- HANDLE ADD COLLEGE FORM ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_college'])) {
    if (user_can('Add College')) {
        try {
            $stmt = $pdo->prepare("INSERT INTO college_info (college_name, ip_address, user_name, password, database_name, security_key) 
                                    VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['college_name'],
                $_POST['ip_address'],
                $_POST['user_name'],
                encrypt_data($_POST['password']),
                $_POST['database_name'],
                encrypt_data($_POST['security_key'])
            ]);
            header('Location: manage_colleges.php?success=College added successfully!');
            exit();
        } catch (PDOException $e) {
            $form_error = "Database error: " . $e->getMessage();
        }
    }
}

// --- HANDLE EDIT COLLEGE FORM ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_college'])) {
    if (user_can('Add College')) {
        try {
            $id = $_POST['edit_college_id'];
            $college_name = $_POST['edit_college_name'];
            $ip_address = $_POST['edit_ip_address'];
            $user_name = $_POST['edit_user_name'];
            $database_name = $_POST['edit_database_name'];

            // Get existing data to check if passwords need updating
            $stmt_old = $pdo->prepare("SELECT password, security_key FROM college_info WHERE id = ?");
            $stmt_old->execute([$id]);
            $old_data = $stmt_old->fetch();

            // Only update password if a new one was entered
            $password = !empty($_POST['edit_password']) ? encrypt_data($_POST['edit_password']) : $old_data['password'];
            $security_key = !empty($_POST['edit_security_key']) ? encrypt_data($_POST['edit_security_key']) : $old_data['security_key'];

            $stmt = $pdo->prepare("UPDATE college_info SET 
                                  college_name = ?, ip_address = ?, user_name = ?, 
                                  password = ?, database_name = ?, security_key = ? 
                                  WHERE id = ?");
            $stmt->execute([$college_name, $ip_address, $user_name, $password, $database_name, $security_key, $id]);
            
            header('Location: manage_colleges.php?success=College updated successfully!');
            exit();
        } catch (PDOException $e) {
            $form_error = "Database error: " . $e->getMessage();
        }
    }
}

// --- HANDLE DELETE COLLEGE FORM ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_college'])) {
    if (user_can('Add College')) {
        try {
            $id = $_POST['college_id'];
            $stmt = $pdo->prepare("DELETE FROM college_info WHERE id = ?");
            $stmt->execute([$id]);
            header('Location: manage_colleges.php?success=College deleted successfully!');
            exit();
        } catch (PDOException $e) {
            $form_error = "Database error: " . $e->getMessage();
        }
    }
}

// Check for success/error messages from redirects or form errors
if (isset($_GET['success'])) {
    $form_success = $_GET['success'];
}


// --- STEP 3: PAGINATION & SEARCH LOGIC ---
$search_term = isset($_GET['search']) ? $_GET['search'] : '';
$search_param = "%" . $search_term . "%";
$records_per_page = 6;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;
$count_sql = "SELECT COUNT(*) FROM college_info WHERE college_name LIKE :search_term";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->bindParam(':search_term', $search_param, PDO::PARAM_STR);
$count_stmt->execute();
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $records_per_page);
$sql = "SELECT id, college_name, ip_address, user_name, database_name 
        FROM college_info 
        WHERE college_name LIKE :search_term
        ORDER BY college_name
        LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':limit', $records_per_page, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->bindParam(':search_term', $search_param, PDO::PARAM_STR);
$stmt->execute();
$colleges = $stmt->fetchAll();


// --- STEP 4: START THE HTML PAGE ---
require_once 'includes/header.php'; 

// --- STEP 5: FINAL PERMISSION CHECK ---
if (!user_can('Add College')) {
    echo "<h1>Access Denied</h1>";
    echo "<p>You do not have permission to view this page.</p>";
    require_once 'includes/footer.php';
    exit();
}
?>

<!-- NEW: CSS STYLES FOR A USER-FRIENDLY UI -->
<style>
    :root {
        --primary-color: #007bff;
        --primary-hover: #0056b3;
        --danger-color: #dc3545;
        --danger-hover: #c82333;
        --secondary-color: #6c757d;
        --secondary-hover: #5a6268;
        --light-gray: #f8f9fa;
        --medium-gray: #e9ecef;
        --dark-gray: #343a40;
        --border-color: #dee2e6;
        --box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }

    h2 {
        color: var(--dark-gray);
        margin-bottom: 20px;
    }

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
        margin-bottom: 25px;
    }

    .search-form {
        display: flex;
    }

    .search-form input[type="text"] {
        font-size: 16px;
        padding: 10px 15px;
        border: 1px solid var(--border-color);
        border-radius: 5px 0 0 5px;
        min-width: 250px;
    }

    .search-form button {
        padding: 10px 20px;
        font-size: 16px;
        background-color: var(--secondary-color);
        color: white;
        border: none;
        border-radius: 0 5px 5px 0;
        cursor: pointer;
        transition: background-color 0.2s;
    }

    .search-form button:hover {
        background-color: var(--secondary-hover);
    }

    /* Button Base Styles */
    button, .btn {
        font-size: 16px;
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        transition: all 0.2s ease-in-out;
        font-weight: 500;
    }

    .btn-primary {
        background-color: var(--primary-color);
        color: white;
    }
    .btn-primary:hover {
        background-color: var(--primary-hover);
    }

    .btn-secondary {
        background-color: var(--secondary-color);
        color: white;
    }
    .btn-secondary:hover {
        background-color: var(--secondary-hover);
    }
    
    .btn-edit {
        background-color: #ffc107;
        color: #212529;
    }
    .btn-edit:hover {
        background-color: #e0a800;
    }

    .btn-delete {
        background-color: var(--danger-color);
        color: white;
    }
    .btn-delete:hover {
        background-color: var(--danger-hover);
    }

    /* Success and Error Messages */
    .success, .error {
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 5px;
        font-weight: 500;
    }
    .success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    .error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    /* College Grid */
    .college-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .college-card {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        box-shadow: var(--box-shadow);
        padding: 20px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .college-card h3 {
        margin-top: 0;
        margin-bottom: 20px;
        color: var(--primary-color);
    }

    /* NEW: Scannable card items */
    .card-item {
        display: flex;
        justify-content: space-between;
        margin-bottom: 12px;
        font-size: 14px;
    }
    .card-item .label {
        font-weight: 600;
        color: #555;
    }
    .card-item .value {
        color: #222;
        text-align: right;
    }

    .college-card .actions {
        margin-top: 20px;
        display: flex;
        gap: 10px;
    }

    /* Pagination */
    .pagination {
        display: flex;
        justify-content: center;
        gap: 5px;
    }
    .pagination a {
        color: var(--primary-color);
        padding: 8px 16px;
        text-decoration: none;
        border: 1px solid var(--border-color);
        border-radius: 5px;
        transition: all 0.2s;
    }
    .pagination a:hover {
        background-color: var(--primary-color);
        color: white;
    }
    .pagination a.active {
        background-color: var(--primary-color);
        color: white;
        font-weight: bold;
    }

    /* Modal Styles */
    .modal {
        display: none; 
        position: fixed; 
        z-index: 1000; 
        left: 0;
        top: 0;
        width: 100%; 
        height: 100%; 
        overflow: auto; 
        background-color: rgba(0,0,0,0.5); 
        animation: fadeIn 0.3s;
    }

    .modal-content {
        background-color: #fefefe;
        margin: 10% auto;
        border-radius: 8px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        max-width: 500px;
        animation: slideIn 0.3s;
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px 25px;
        border-bottom: 1px solid var(--border-color);
    }
    .modal-header h3 {
        margin: 0;
        font-size: 22px;
        color: var(--dark-gray);
    }
    .close-btn {
        color: #aaa;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
    }
    .close-btn:hover,
    .close-btn:focus {
        color: #333;
    }

    .modal-body {
        padding: 25px;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #333;
    }
    .form-group input[type="text"],
    .form-group input[type="password"] {
        width: 100%;
        padding: 12px;
        border: 1px solid var(--border-color);
        border-radius: 5px;
        box-sizing: border-box; /* Important */
        font-size: 16px;
    }

    .modal-footer {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        padding: 15px 25px;
        border-top: 1px solid var(--border-color);
        background-color: var(--light-gray);
        border-radius: 0 0 8px 8px;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    @keyframes slideIn {
        from { transform: translateY(-50px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
</style>
<!-- END OF NEW CSS -->


<h2>Manage Colleges</h2>

<div class="page-header">
    <form class="search-form" method="GET" action="manage_colleges.php">
        <input type="text" name="search" placeholder="Search for a college..." value="<?php echo htmlspecialchars($search_term); ?>">
        <button type="submit">Search</button>
    </form>
    <button id="addCollegeBtn" class="btn-primary">Add New College</button>
</div>

<!-- Show success/error messages -->
<?php if ($form_success): ?>
    <p class="success"><?php echo htmlspecialchars($form_success); ?></p>
<?php endif; ?>
<?php if ($form_error && !isset($_GET['success'])): // Only show error if it's not from a redirect ?>
    <p class="error" id="phpFormError"><?php echo htmlspecialchars($form_error); ?></p>
<?php endif; ?>


<div class="college-grid">
    <?php if (empty($colleges)): ?>
        <p>No colleges found. <?php if($search_term) echo 'Matching your search "' . htmlspecialchars($search_term) . '"'; ?>.</p>
    <?php else: ?>
        <?php foreach ($colleges as $college): ?>
            <!-- UPDATED: College Card HTML -->
            <div class="college-card">
                <div>
                    <h3><?php echo htmlspecialchars($college['college_name']); ?></h3>
                    <div class="card-item">
                        <span class="label">IP Address:</span>
                        <span class="value"><?php echo htmlspecialchars($college['ip_address']); ?></span>
                    </div>
                    <div class="card-item">
                        <span class="label">Database:</span>
                        <span class="value"><?php echo htmlspecialchars($college['database_name']); ?></span>
                    </div>
                    <div class="card-item">
                        <span class="label">Username:</span>
                        <span class="value"><?php echo htmlspecialchars($college['user_name']); ?></span>
                    </div>
                </div>
                
                <div class="actions">
                    <button class="btn-edit" data-id="<?php echo $college['id']; ?>">Edit</button>
                    
                    <!-- UPDATED: Removed inline confirm() and added class/data for JS -->
                    <form method="POST" action="manage_colleges.php" class="delete-form" style="display:inline;" data-name="<?php echo htmlspecialchars($college['college_name']); ?>">
                        <input type="hidden" name="college_id" value="<?php echo $college['id']; ?>">
                        <button type="submit" name="delete_college" class="btn-delete">Delete</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Pagination -->
<div class="pagination">
    <?php if ($total_pages > 1): ?>
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a 
                href="manage_colleges.php?page=<?php echo $i; ?>&search=<?php echo htmlspecialchars($search_term); ?>"
                class="<?php echo ($i == $page) ? 'active' : ''; ?>"
            >
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>
    <?php endif; ?>
</div>


<!-- UPDATED: Add New College Modal HTML -->
<div id="addCollegeModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Add New College</h3>
            <span class="close-btn">&times;</span>
        </div>
        <form action="manage_colleges.php" method="POST">
            <input type="hidden" name="add_college" value="1">
            <div class="modal-body">
                <div class="form-group">
                    <label for="add_college_name">College Name:</label>
                    <input type="text" id="add_college_name" name="college_name" placeholder="e.g., Govt. Haraganga College" required>
                </div>
                <div class="form-group">
                    <label for="add_ip_address">IP Address:</label>
                    <input type="text" id="add_ip_address" name="ip_address" placeholder="e.g., 103.140.235.134">
                </div>
                <div class="form-group">
                    <label for="add_database_name">Database Name:</label>
                    <input type="text" id="add_database_name" name="database_name" placeholder="e.g., haragdagb">
                </div>
                <div class="form-group">
                    <label for="add_user_name">Database Username:</label>
                    <input type="text" id="add_user_name" name="user_name" placeholder="e.g., hargclg">
                </div>
                <div class="form-group">
                    <label for="add_password">Database Password:</label>
                    <input type="password" id="add_password" name="password" placeholder="Enter new password">
                </div>
                <div class="form-group">
                    <label for="add_security_key">Security Key:</label>
                    <input type="password" id="add_security_key" name="security_key" placeholder="Enter new security key">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary close-btn-footer">Cancel</button>
                <button type="submit" class="btn-primary">Save College</button>
            </div>
        </form>
    </div>
</div>


<!-- UPDATED: Edit College Modal HTML -->
<div id="editCollegeModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Edit College</h3>
            <span class="close-btn">&times;</span>
        </div>
        <form action="manage_colleges.php" method="POST">
            <input type="hidden" name="edit_college" value="1">
            <input type="hidden" name="edit_college_id" id="edit_college_id">
            
            <div class="modal-body">
                <div class="form-group">
                    <label for="edit_college_name">College Name:</label>
                    <input type="text" id="edit_college_name" name="edit_college_name" required>
                </div>
                <div class="form-group">
                    <label for="edit_ip_address">IP Address:</label>
                    <input type="text" id="edit_ip_address" name="edit_ip_address">
                </div>
                <div class="form-group">
                    <label for="edit_database_name">Database Name:</label>
                    <input type="text" id="edit_database_name" name="edit_database_name">
                </div>
                <div class="form-group">
                    <label for="edit_user_name">Database Username:</label>
                    <input type="text" id="edit_user_name" name="edit_user_name">
                </div>
                <div class="form-group">
                    <label for="edit_password">Database Password:</label>
                    <input type="password" id="edit_password" name="edit_password" placeholder="Leave blank to keep unchanged">
                </div>
                <div class="form-group">
                    <label for="edit_security_key">Security Key:</label>
                    <input type="password" id="edit_security_key" name="edit_security_key" placeholder="Leave blank to keep unchanged">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary close-btn-footer">Cancel</button>
                <button type="submit" class="btn-primary">Update College</button>
            </div>
        </form>
    </div>
</div>

<!-- NEW: Custom Alert Modal (replaces alert()) -->
<div id="customAlertModal" class="modal">
    <div class="modal-content" style="max-width: 400px;">
        <div class="modal-header">
            <h3 id="customAlertTitle">Error</h3>
            <span class="close-btn" id="customAlertClose">&times;</span>
        </div>
        <div class="modal-body">
            <p id="customAlertMessage"></p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-primary" id="customAlertOk">OK</button>
        </div>
    </div>
</div>

<!-- NEW: Custom Confirm Modal (replaces confirm()) -->
<div id="customConfirmModal" class="modal">
    <div class="modal-content" style="max-width: 450px;">
        <div class="modal-header">
            <h3>Confirm Deletion</h3>
            <span class="close-btn" id="customConfirmClose">&times;</span>
        </div>
        <div class="modal-body">
            <p id="customConfirmMessage"></p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-secondary" id="customConfirmCancel">Cancel</button>
            <button type="button" class="btn-delete" id="customConfirmOk">Delete</button>
        </div>
    </div>
</div>


<!-- UPDATED: JavaScript with custom modals -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // --- Modal References ---
    var addModal = document.getElementById("addCollegeModal");
    var editModal = document.getElementById("editCollegeModal");
    var alertModal = document.getElementById("customAlertModal");
    var confirmModal = document.getElementById("customConfirmModal");

    // --- "Add" Modal Logic ---
    var addBtn = document.getElementById("addCollegeBtn");
    if(addBtn) {
        addBtn.onclick = function() { addModal.style.display = "block"; }
    }

    // --- "Edit" Modal Logic ---
    document.querySelectorAll('.btn-edit').forEach(button => {
        button.onclick = function() {
            var id = this.dataset.id;
            
            // Fetch data from server
            fetch('get_college_details.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        showAlert(data.error); // Use custom alert
                        return;
                    }
                    
                    // Populate the edit modal form
                    document.getElementById('edit_college_id').value = data.id;
                    document.getElementById('edit_college_name').value = data.college_name;
                    document.getElementById('edit_ip_address').value = data.ip_address;
                    document.getElementById('edit_database_name').value = data.database_name;
                    document.getElementById('edit_user_name').value = data.user_name;
                    
                    document.getElementById('edit_password').value = "";
                    document.getElementById('edit_security_key').value = "";
                    
                    editModal.style.display = "block";
                })
                .catch(error => {
                    console.error('Error fetching college details:', error);
                    showAlert('Could not fetch college details. Please check the console.');
                });
        }
    });

    // --- NEW: Custom Alert Modal Logic ---
    var alertMsg = document.getElementById("customAlertMessage");
    var alertTitle = document.getElementById("customAlertTitle");
    var alertOk = document.getElementById("customAlertOk");
    var alertClose = document.getElementById("customAlertClose");

    function showAlert(message, title = 'Error') {
        alertTitle.textContent = title;
        alertMsg.textContent = message;
        alertModal.style.display = "block";
    }

    alertOk.onclick = function() { alertModal.style.display = "none"; }
    alertClose.onclick = function() { alertModal.style.display = "none"; }

    // --- NEW: Custom Confirm Modal Logic ---
    var confirmMsg = document.getElementById("customConfirmMessage");
    var confirmOk = document.getElementById("customConfirmOk");
    var confirmCancel = document.getElementById("customConfirmCancel");
    var confirmClose = document.getElementById("customConfirmClose");
    var formToSubmit = null;

    document.querySelectorAll('.delete-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault(); // Stop the form from submitting
            var collegeName = this.dataset.name;
            formToSubmit = this; // Store the form
            
            confirmMsg.textContent = `Are you sure you want to delete "${collegeName}"? This action cannot be undone.`;
            confirmModal.style.display = "block";
        });
    });

    confirmOk.onclick = function() {
        if (formToSubmit) {
            formToSubmit.submit(); // Submit the stored form
        }
    }
    confirmCancel.onclick = function() {
        formToSubmit = null;
        confirmModal.style.display = "none";
    }
    confirmClose.onclick = function() {
        formToSubmit = null;
        confirmModal.style.display = "none";
    }

    // --- General Modal Close Logic ---
    // Close modals with X buttons, footer "Cancel" buttons
    document.querySelectorAll('.close-btn, .close-btn-footer').forEach(btn => {
        btn.onclick = function() {
            // Find the parent modal and close it
            var modal = this.closest('.modal');
            if(modal) {
                modal.style.display = 'none';
            }
        }
    });

    // Close modals on outside click
    window.onclick = function(event) {
        if (event.target == addModal) {
            addModal.style.display = "none";
        }
        if (event.target == editModal) {
            editModal.style.display = "none";
        }
        if (event.target == alertModal) {
            alertModal.style.display = "none";
        }
        if (event.target == confirmModal) {
            confirmModal.style.display = "none";
        }
    }
    
    // If PHP form had an error, show the correct modal on page load
    <?php if ($form_error && isset($_POST['add_college'])): ?>
    addModal.style.display = "block";
    // Show the error in the main page as well, in case the modal is closed
    document.getElementById('phpFormError').style.display = 'block'; 
    <?php elseif ($form_error && isset($_POST['edit_college'])): ?>
    // We can't re-open the edit modal with old data easily
    // so just make sure the error is visible (it already is by default)
    document.getElementById('phpFormError').style.display = 'block';
    <?php endif; ?>
});
</script>