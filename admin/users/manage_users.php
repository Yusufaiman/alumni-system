<?php
/**
 * ADMIN - MANAGE USERS
 * View, search, filter and manage all system users.
 */
session_start();
require_once "../../config/db.php";
require_once "../../config/functions.php";

// Access Control
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'ADMIN') {
    header("Location: ../../auth/login.php");
    exit();
}

$successMsg = "";
$errorMsg = "";

// Handle Actions (Activate/Deactivate, Verify/Unverify, Delete, Role Change)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $targetUserID = intval($_POST['user_id']);
    $action = $_POST['action'];
    $adminID = $_SESSION['userID'];

    // Prevent self-action
    if ($targetUserID === $adminID) {
        $errorMsg = "You cannot perform actions on your own account.";
    } else {
        if ($action === 'toggle_status') {
            $currentStatus = $_POST['current_status'];
            $newStatus = ($currentStatus === 'ACTIVE') ? 'INACTIVE' : 'ACTIVE';
            $stmt = $conn->prepare("UPDATE `user` SET status = ? WHERE userID = ?");
            $stmt->bind_param("si", $newStatus, $targetUserID);
            if ($stmt->execute()) {
                logActivity($conn, $adminID, 'ADMIN', 'Toggle User Status', 'Users', "Changed status of user #$targetUserID to $newStatus");
                $successMsg = "User status update to $newStatus.";
            }
        } elseif ($action === 'delete') {
            $stmt = $conn->prepare("UPDATE `user` SET status = 'DELETED' WHERE userID = ?");
            $stmt->bind_param("i", $targetUserID);
            if ($stmt->execute()) {
                logActivity($conn, $adminID, 'ADMIN', 'Soft Delete User', 'Users', "Soft deleted user #$targetUserID");
                $successMsg = "User soft deleted.";
            }
        } elseif ($action === 'edit_role') {
            $newRole = $_POST['new_role'];
            $stmt = $conn->prepare("UPDATE `user` SET role = ? WHERE userID = ?");
            $stmt->bind_param("si", $newRole, $targetUserID);
            if ($stmt->execute()) {
                logActivity($conn, $adminID, 'ADMIN', 'Update User Role', 'Users', "Updated role of user #$targetUserID to $newRole");
                $successMsg = "User role updated to $newRole.";
            }
        }
    }
}

// Fetch Users with Filter & Search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$roleFilter = isset($_GET['role']) ? $_GET['role'] : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';

// Safe Query Template (Verified Schema)
try {
    $sql = "
        SELECT 
            userID,
            name,
            email,
            role,
            status,
            createdDate
        FROM `user`
        WHERE status != 'DELETED'
    ";

    $params = [];
    $types = "";

    if ($search) {
        $sql .= " AND (email LIKE ? OR name LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= "ss";
    }
    if ($roleFilter) {
        $sql .= " AND role = ?";
        $params[] = $roleFilter;
        $types .= "s";
    }
    if ($statusFilter) {
        $sql .= " AND status = ?";
        $params[] = $statusFilter;
        $types .= "s";
    }

    $sql .= " ORDER BY userID DESC LIMIT 50";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Failed to prepare SQL statement: " . $conn->error);
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $result = $stmt->get_result();

} catch (Exception $e) {
    die("Database Error: " . $e->getMessage());
}

$pageTitle = "Manage Users";
ob_start();
?>

<style>
    /* Local styles for this page (not covered by global layout yet) */
    .filters { display: flex; gap: 1rem; margin-bottom: 1.5rem; background: var(--bg-card); padding: 1rem; border-radius: 8px; border: 1px solid var(--border); }
    .filter-select { padding:0.5rem; border-radius:6px; border:1px solid var(--border); background: #fff; min-width: 150px; }
    
    .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 1000; }
    .modal.active { display: flex; }
    .modal-content { background: white; padding: 2rem; border-radius: 8px; width: 400px; box-shadow: var(--shadow-md); }

    .role-student { background: #DBEAFE; color: #1E40AF; }
    .role-alumni { background: #D1FAE5; color: #065F46; }
    .role-admin { background: #F3E8FF; color: #6B21A8; }
    .role-cso { background: #FFEDD5; color: #9A3412; }
</style>

<div class="page-header" style="display:flex; justify-content:space-between; align-items:center;">
    <div>
        <h1 class="page-title">Manage Users</h1>
        <p class="page-desc">View and control all platform users</p>
    </div>
    <button style="background:var(--primary); color:white; border:none; padding:0.75rem 1.5rem; border-radius:6px; font-weight:600; cursor:pointer;">
        <i data-lucide="plus" size="16" style="display:inline; vertical-align:middle; margin-right:4px;"></i> Add User
    </button>
</div>

<!-- Filters -->
<form class="filters">
    <input type="text" name="search" class="search-input" placeholder="Search by name or email..." value="<?php echo htmlspecialchars($search); ?>" style="flex:1;">
    
    <select name="role" class="filter-select">
        <option value="">All Roles</option>
        <option value="STUDENT" <?php if($roleFilter=='STUDENT') echo 'selected'; ?>>Student</option>
        <option value="ALUMNI" <?php if($roleFilter=='ALUMNI') echo 'selected'; ?>>Alumni</option>
        <option value="ADMIN" <?php if($roleFilter=='ADMIN') echo 'selected'; ?>>Admin</option>
    </select>
    
    <select name="status" class="filter-select">
        <option value="">All Status</option>
        <option value="ACTIVE" <?php if($statusFilter=='ACTIVE') echo 'selected'; ?>>Active</option>
        <option value="INACTIVE" <?php if($statusFilter=='INACTIVE') echo 'selected'; ?>>Inactive</option>
    </select>
    
    <button type="submit" style="background:#F1F5F9; border:1px solid var(--border); padding:0 1rem; border-radius:6px; cursor:pointer;">Filter</button>
</form>

<?php if($successMsg): ?>
    <div style="background:#DCFCE7; color:#166534; padding:1rem; border-radius:6px; margin-bottom:1.5rem;">
        <?php echo $successMsg; ?>
    </div>
<?php endif; ?>

<?php if($errorMsg): ?>
    <div style="background:#FEE2E2; color:#991B1B; padding:1rem; border-radius:6px; margin-bottom:1.5rem;">
        <?php echo $errorMsg; ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="table-wrapper">
        <table class="clean-table">
            <thead>
                <tr>
                    <th>User ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $row['userID']; ?></td>
                            <td>
                                <div style="font-weight:500;"><?php echo htmlspecialchars($row['name']); ?></div>
                            </td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td>
                                <span class="badge role-<?php echo strtolower($row['role']); ?>" style="padding:4px 8px; border-radius:99px; font-size:0.75rem; font-weight:600;">
                                    <?php echo $row['role']; ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower($row['status']); ?>">
                                    <?php echo $row['status']; ?>
                                </span>
                            </td>
                            <td style="font-size:0.875rem; color:var(--text-light);">
                                <?php echo date('d M Y', strtotime($row['createdDate'])); ?>
                            </td>
                            <td style="display:flex; gap:0.5rem;">
                                <button class="btn-icon" title="Edit" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($row)); ?>)">
                                    <i data-lucide="edit-2" size="18"></i>
                                </button>
                                
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Change status?');">
                                    <input type="hidden" name="user_id" value="<?php echo $row['userID']; ?>">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="current_status" value="<?php echo $row['status']; ?>">
                                    <button type="submit" class="btn-icon" title="Toggle Status">
                                        <i data-lucide="power" size="18" color="<?php echo $row['status']=='ACTIVE' ? 'green' : 'red'; ?>"></i>
                                    </button>
                                </form>
                                
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                    <input type="hidden" name="user_id" value="<?php echo $row['userID']; ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button type="submit" class="btn-icon" title="Delete" style="color:#EF4444;">
                                        <i data-lucide="trash-2" size="18"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7" style="text-align:center; padding:2rem; color:var(--text-light);">No users found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit User Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <h2 style="margin-top:0;">Edit User Role</h2>
        <form method="POST">
            <input type="hidden" name="user_id" id="editUserID">
            <input type="hidden" name="action" value="edit_role">
            
            <div style="margin-bottom:1rem;">
                <label style="display:block; margin-bottom:0.5rem; font-weight:500;">Role</label>
                <select name="new_role" id="editRole" style="width:100%; padding:0.75rem; border-radius:6px; border:1px solid var(--border);">
                    <option value="STUDENT">Student</option>
                    <option value="ALUMNI">Alumni</option>
                    <option value="CAREER_SERVICE_OFFICER">Career Officer</option>
                    <option value="ADMIN">Admin</option>
                </select>
            </div>
            
            <div style="display:flex; justify-content:flex-end; gap:1rem;">
                <button type="button" onclick="document.getElementById('editModal').classList.remove('active')" style="background:none; border:1px solid var(--border); padding:0.75rem 1.5rem; border-radius:6px; cursor:pointer;">Cancel</button>
                <button type="submit" style="background:var(--primary); color:white; border:none; padding:0.75rem 1.5rem; border-radius:6px; cursor:pointer;">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEditModal(user) {
        document.getElementById('editUserID').value = user.userID;
        document.getElementById('editRole').value = user.role;
        document.getElementById('editModal').classList.add('active');
    }
</script>

<?php
$content = ob_get_clean();
include '../layout.php';
?>