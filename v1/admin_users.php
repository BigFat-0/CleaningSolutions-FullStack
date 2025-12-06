<?php
// v1/admin_users.php
require_once 'db_connect.php';
require_once 'admin_header.php';

$message = '';

// Handle Create User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone_number']);
    $billing = trim($_POST['billing_address']);
    $sec_q = $_POST['security_question'];
    $sec_a = $_POST['security_answer'];
    $pass = $_POST['password'];
    $role = $_POST['role'];

    if ($email && $pass && $first_name && $last_name && $sec_a) {
        $pass_hash = password_hash($pass, PASSWORD_DEFAULT);
        $sec_hash = password_hash($sec_a, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, phone_number, billing_address, security_question, security_answer_hash, password_hash, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        try {
            $stmt->execute([$first_name, $last_name, $email, $phone, $billing, $sec_q, $sec_hash, $pass_hash, $role]);
            $message = "User created successfully.";
        } catch (PDOException $e) {
            $message = "Error: " . $e->getMessage();
        }
    }
}

// Handle Edit User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    $id = $_POST['edit_id'];
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $billing = trim($_POST['billing_address']);
    $new_pass = $_POST['new_password'];

    $sql = "UPDATE users SET first_name = ?, last_name = ?, email = ?, billing_address = ?";
    $params = [$first_name, $last_name, $email, $billing];

    if (!empty($new_pass)) {
        $sql .= ", password_hash = ?";
        $params[] = password_hash($new_pass, PASSWORD_DEFAULT);
    }

    $sql .= " WHERE id = ?";
    $params[] = $id;
    $stmt = $pdo->prepare($sql);
    if ($stmt->execute($params)) {
        $message = "User updated.";
    } else {
        $message = "Update failed.";
    }
}

// Handle Delete/Toggle
if (isset($_GET['delete_id'])) {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    if ($stmt->execute([$_GET['delete_id']])) $message = "User deleted.";
}
if (isset($_GET['toggle_role_id'])) {
    $id = $_GET['toggle_role_id'];
    $new_role = ($_GET['current_role'] === 'staff') ? 'customer' : 'staff';
    $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
    if ($stmt->execute([$new_role, $id])) $message = "Role updated.";
}

// Search & Sort
$search = $_GET['search'] ?? '';
$sort_by = $_GET['sort'] ?? 'created_at';
$order = $_GET['order'] ?? 'DESC';
$next_order = ($order === 'ASC') ? 'DESC' : 'ASC';

// Whitelist columns
$allowed_sort = ['first_name', 'email', 'total_revenue', 'created_at'];
if (!in_array($sort_by, $allowed_sort)) $sort_by = 'created_at';

$sql = "SELECT u.*, 
        (SELECT COALESCE(SUM(actual_bill), 0) FROM bookings WHERE user_id = u.id AND status = 'completed') as total_revenue 
        FROM users u 
        WHERE (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)
        ORDER BY $sort_by $order";

$stmt = $pdo->prepare($sql);
$term = "%$search%";
$stmt->execute([$term, $term, $term]);
$users = $stmt->fetchAll();
?>

<div class="admin-container page-padding">
    <div class="page-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h1><i class="fas fa-users"></i> User Management</h1>
        <button onclick="openAddUserModal()" class="btn btn-primary"><i class="fas fa-plus"></i> New User</button>
    </div>

    <?php if ($message): ?>
        <div style="background: #d4edda; color: #155724; padding: 10px; margin-bottom: 20px; border-radius: 4px;">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Search -->
    <form method="get" style="margin: 20px 0;">
        <div class="mobile-stack">
            <input type="text" name="search" placeholder="Search Name or Email..." value="<?php echo htmlspecialchars($search); ?>" class="form-control" style="flex:1;">
            <button type="submit" class="btn btn-secondary">Search</button>
        </div>
    </form>

    <!-- Table -->
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th><a href="?sort=first_name&order=<?php echo $next_order; ?>&search=<?php echo $search; ?>">Name <?php echo ($sort_by=='first_name')?($order=='ASC'?'▲':'▼'):''; ?></a></th>
                    <th><a href="?sort=email&order=<?php echo $next_order; ?>&search=<?php echo $search; ?>">Email <?php echo ($sort_by=='email')?($order=='ASC'?'▲':'▼'):''; ?></a></th>
                    <th>Role</th>
                    <th><a href="?sort=total_revenue&order=<?php echo $next_order; ?>&search=<?php echo $search; ?>">Revenue <?php echo ($sort_by=='total_revenue')?($order=='ASC'?'▲':'▼'):''; ?></a></th>
                    <th><a href="?sort=created_at&order=<?php echo $next_order; ?>&search=<?php echo $search; ?>">Joined <?php echo ($sort_by=='created_at')?($order=='ASC'?'▲':'▼'):''; ?></a></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr class="clickable-row" onclick='openUserModal(<?php echo json_encode($u); ?>)'>
                    <td>#<?php echo $u['id']; ?></td>
                    <td><?php echo htmlspecialchars($u['first_name'] . ' ' . $u['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                    <td><span class="badge badge-<?php echo $u['role']; ?>"><?php echo $u['role']; ?></span></td>
                    <td>$<?php echo number_format($u['total_revenue'], 2); ?></td>
                    <td><?php echo date('d M Y', strtotime($u['created_at'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add User Modal -->
<div id="addUserModal" class="modal">
    <div class="modal-content">
        <h3>Add New User</h3>
        <form method="post">
            <div class="form-group">
                <label>First Name</label>
                <input type="text" name="first_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Last Name</label>
                <input type="text" name="last_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="phone_number" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Billing Address</label>
                <textarea name="billing_address" class="form-control" required></textarea>
            </div>
            <div class="form-group">
                <label>Security Question</label>
                <select name="security_question" class="form-control" required>
                    <option value="Mother's Maiden Name">Mother's Maiden Name</option>
                    <option value="First Pet">First Pet</option>
                    <option value="Primary School">Primary School</option>
                </select>
            </div>
            <div class="form-group">
                <label>Security Answer</label>
                <input type="text" name="security_answer" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Role</label>
                <select name="role" class="form-control">
                    <option value="customer">Customer</option>
                    <option value="staff">Staff</option>
                </select>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-danger" onclick="document.getElementById('addUserModal').style.display='none'">Cancel</button>
                <button type="submit" name="create_user" class="btn btn-primary">Create User</button>
            </div>
        </form>
    </div>
</div>

<!-- User Details / Edit Modal -->
<div id="userModal" class="modal">
    <div class="modal-content">
        <h3 id="modal_title">User Details</h3>
        
        <!-- View Mode -->
        <div id="view_mode">
            <p><strong>Name:</strong> <span id="view_name"></span></p>
            <p><strong>Email:</strong> <span id="view_email"></span></p>
            <p><strong>Role:</strong> <span id="view_role"></span></p>
            <p><strong>Revenue:</strong> <span id="view_revenue"></span></p>
            <p><strong>Billing Address:</strong> <span id="view_billing"></span></p>
            
            <div class="form-actions" style="margin-top: 20px;">
                <button class="btn btn-primary" onclick="switchToEditMode()">Edit</button>
                <a id="role_link" class="btn btn-secondary" onclick="return confirm('Toggle role?');">Toggle Role</a>
                <a id="delete_link" class="btn btn-danger" onclick="return confirm('Delete?');">Delete</a>
                <button class="btn btn-secondary" onclick="document.getElementById('userModal').style.display='none'">Close</button>
            </div>
        </div>

        <!-- Edit Mode (Hidden by default) -->
        <div id="edit_mode" style="display:none;">
            <form method="post">
                <input type="hidden" name="edit_id" id="modal_edit_id">
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" name="first_name" id="modal_first" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="last_name" id="modal_last" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" id="modal_email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Billing Address</label>
                    <textarea name="billing_address" id="modal_billing" class="form-control" required></textarea>
                </div>
                <div class="form-group">
                    <label>Reset Password (Optional)</label>
                    <input type="password" name="new_password" class="form-control">
                </div>
                <div class="form-actions" style="margin-top:10px;">
                    <button type="button" class="btn btn-danger" onclick="switchToViewMode()">Cancel</button>
                    <button type="submit" name="edit_user" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openAddUserModal() {
    document.getElementById('addUserModal').style.display = 'flex';
}

function openUserModal(u) {
    document.getElementById('userModal').style.display = 'flex';
    switchToViewMode();

    // Populate View
    document.getElementById('view_name').textContent = u.first_name + ' ' + u.last_name;
    document.getElementById('view_email').textContent = u.email;
    document.getElementById('view_role').textContent = u.role;
    document.getElementById('view_revenue').textContent = '$' + (parseFloat(u.total_revenue)||0).toFixed(2);
    document.getElementById('view_billing').textContent = u.billing_address;

    // Links
    document.getElementById('role_link').href = "?toggle_role_id=" + u.id + "&current_role=" + u.role;
    document.getElementById('delete_link').href = "?delete_id=" + u.id;
    if (u.id == <?php echo $_SESSION['user_id']; ?>) {
         document.getElementById('delete_link').style.display = 'none';
    } else {
         document.getElementById('delete_link').style.display = 'inline-block';
    }

    // Populate Edit Form
    document.getElementById('modal_edit_id').value = u.id;
    document.getElementById('modal_first').value = u.first_name;
    document.getElementById('modal_last').value = u.last_name;
    document.getElementById('modal_email').value = u.email;
    document.getElementById('modal_billing').value = u.billing_address;
}

function switchToEditMode() {
    document.getElementById('view_mode').style.display = 'none';
    document.getElementById('edit_mode').style.display = 'block';
    document.getElementById('modal_title').textContent = 'Edit User';
}

function switchToViewMode() {
    document.getElementById('view_mode').style.display = 'block';
    document.getElementById('edit_mode').style.display = 'none';
    document.getElementById('modal_title').textContent = 'User Details';
}
</script>
</body>
</html>