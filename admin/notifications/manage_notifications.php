<?php
/**
 * ADMIN - MANAGE NOTIFICATIONS
 * Create, send, and manage system notifications.
 */
session_start();
require_once "../../config/db.php";
require_once "../../config/functions.php";
require_once "../distribute_helper.php";

// Access Control
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'ADMIN') {
    header("Location: ../../auth/login.php");
    exit();
}

$successMsg = "";
$errorMsg = "";

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];

    if ($action === 'create_notification') {
        $title = trim($_POST['title']);
        $message = trim($_POST['message']);
        $targetRole = $_POST['target_role'];
        $deliveryType = $_POST['delivery_type'];
        $status = $_POST['submit_action'] === 'send' ? 'SENT' : 'DRAFT';
        // Check if user is actually an admin and get their adminID
        // The table admin_notification.createdBy references admin.adminID, not user.userID
        $stmtAdmin = $conn->prepare("SELECT adminID FROM admin WHERE userID = ?");
        $stmtAdmin->bind_param("i", $_SESSION['userID']);
        $stmtAdmin->execute();
        $resAdmin = $stmtAdmin->get_result();

        if ($resAdmin->num_rows === 0) {
            $errorMsg = "Error: Current user is not found in the admin table.";
        } else {
            $adminRow = $resAdmin->fetch_assoc();
            $realAdminID = $adminRow['adminID']; // This allows FK to work

            // Validation
            if (empty($title) || empty($message)) {
                $errorMsg = "Title and message are required.";
            } else {
                $stmt = $conn->prepare("INSERT INTO admin_notification (title, message, targetRole, delivery_type, status, createdBy, createdDate) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                $stmt->bind_param("sssssi", $title, $message, $targetRole, $deliveryType, $status, $realAdminID);

                if ($stmt->execute()) {
                    $successMsg = "Notification " . ($status === 'SENT' ? "sent" : "saved as draft") . " successfully.";

                    // If SENT, we would trigger logic here to populate `notification` table or send emails
                    // For this implementation, we assume the DB trigger or separate process handles it, 
                    // or just log it as sent for now.
                    if ($status === 'SENT') {
                        // Logic to distribute content to 'notification' table would go here
                        distribute_notifications($conn, $stmt->insert_id, $targetRole, $title, $message);
                        logActivity($conn, $_SESSION['userID'], 'ADMIN', 'Sent Notification', 'Notifications', "Sent notification to $targetRole: $title");
                    } else {
                        logActivity($conn, $_SESSION['userID'], 'ADMIN', 'Created Draft', 'Notifications', "Created draft notification: $title");
                    }
                } else {
                    $errorMsg = "Failed to create notification: " . $stmt->error;
                }
                $stmt->close();
            }
        }

    } elseif ($action === 'delete') {
        $notifID = intval($_POST['notification_id']);
        $stmt = $conn->prepare("DELETE FROM admin_notification WHERE notificationID = ?");
        $stmt->bind_param("i", $notifID);
        if ($stmt->execute()) {
            $successMsg = "Notification deleted.";
        } else {
            $errorMsg = "Error deleting notification.";
        }
        $stmt->close();

    } elseif ($action === 'send_draft') {
        $notifID = intval($_POST['notification_id']);
        // Update status to SENT
        $stmt = $conn->prepare("UPDATE admin_notification SET status = 'SENT', createdDate = NOW() WHERE notificationID = ? AND status = 'DRAFT'");
        $stmt->bind_param("i", $notifID);
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $successMsg = "Draft notification has been sent.";

                // Fetch info to distribute
                $q = $conn->prepare("SELECT title, message, targetRole FROM admin_notification WHERE notificationID = ?");
                $q->bind_param("i", $notifID);
                $q->execute();
                $res = $q->get_result();
                if ($r = $res->fetch_assoc()) {
                    distribute_notifications($conn, $notifID, $r['targetRole'], $r['title'], $r['message']);
                }
                $q->close();
            } else {
                $errorMsg = "Could not send notification (maybe already sent).";
            }
        } else {
            $errorMsg = "Database error.";
        }
        $stmt->close();
    }
}

// Fetch Notifications with Filter & Search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$targetFilter = isset($_GET['target']) ? $_GET['target'] : '';

try {
    $sql = "
        SELECT 
            notificationID,
            title,
            message,
            targetRole,
            delivery_type,
            status,
            createdDate
        FROM admin_notification
        WHERE 1=1
    ";

    $params = [];
    $types = "";

    if ($search) {
        $sql .= " AND (title LIKE ? OR message LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= "ss";
    }

    if ($statusFilter) {
        $sql .= " AND status = ?";
        $params[] = $statusFilter;
        $types .= "s";
    }

    if ($targetFilter) {
        $sql .= " AND targetRole = ?";
        $params[] = $targetFilter;
        $types .= "s";
    }

    $sql .= " ORDER BY createdDate DESC LIMIT 50";

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

} catch (Exception $e) {
    die("Database Error: " . $e->getMessage());
}

$pageTitle = "Manage Notifications";
ob_start();
?>

<style>
    /* Local Styles (Consistent with local Manage Users/Events styles) */
    .filters {
        display: flex;
        gap: 1rem;
        margin-bottom: 1.5rem;
        background: var(--bg-card);
        padding: 1rem;
        border-radius: 8px;
        border: 1px solid var(--border);
    }

    .filter-select {
        padding: 0.5rem;
        border-radius: 6px;
        border: 1px solid var(--border);
        background: #fff;
        min-width: 150px;
    }

    /* Badges */
    .badge-pill {
        display: inline-flex;
        align-items: center;
        padding: 0.25rem 0.625rem;
        border-radius: 99px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
    }

    .status-draft {
        background: #F1F5F9;
        color: #64748B;
    }

    .status-sent {
        background: #DCFCE7;
        color: #166534;
    }

    .type-in-app {
        background: #DBEAFE;
        color: #1E40AF;
    }

    .type-email {
        background: #F3E8FF;
        color: #6B21A8;
    }

    .type-both {
        background: linear-gradient(90deg, #DBEAFE 50%, #F3E8FF 50%);
        color: #1E293B;
        border: 1px solid #E2E8F0;
    }

    /* .type-both gradient is a bit hacky for text, let's use a solid specific color or gradient background with dark text */
    .type-both-pill {
        background: #E0F2FE;
        color: #0C4A6E;
        border-left: 4px solid #7C3AED;
    }

    /* Custom style for 'BOTH' */

    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        justify-content: center;
        align-items: center;
        z-index: 1000;
    }

    .modal.active {
        display: flex;
    }

    .modal-content {
        background: white;
        padding: 2rem;
        border-radius: 12px;
        width: 500px;
        box-shadow: var(--shadow-md);
        max-height: 90vh;
        overflow-y: auto;
    }
</style>

<div class="page-header" style="display:flex; justify-content:space-between; align-items:center;">
    <div>
        <h1 class="page-title">Manage Notifications</h1>
        <p class="page-desc">Create, send, and manage system notifications</p>
    </div>
    <button onclick="openCreateModal()" class="btn-primary"
        style="background:var(--primary); color:white; border:none; padding:0.75rem 1.5rem; border-radius:6px; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:0.5rem;">
        <i data-lucide="plus" size="16"></i> Create Notification
    </button>
</div>

<!-- Filters -->
<form class="filters">
    <input type="text" name="search" class="search-input" placeholder="Search by title or message..."
        value="<?php echo htmlspecialchars($search); ?>" style="flex:1;">

    <select name="target" class="filter-select">
        <option value="">All Targets</option>
        <option value="ALL" <?php if ($targetFilter == 'ALL')
            echo 'selected'; ?>>All Users</option>
        <option value="STUDENT" <?php if ($targetFilter == 'STUDENT')
            echo 'selected'; ?>>Students</option>
        <option value="ALUMNI" <?php if ($targetFilter == 'ALUMNI')
            echo 'selected'; ?>>Alumni</option>
        <option value="CAREER_OFFICER" <?php if ($targetFilter == 'CAREER_OFFICER')
            echo 'selected'; ?>>Career Service
            Officers</option>
    </select>

    <select name="status" class="filter-select">
        <option value="">All Status</option>
        <option value="DRAFT" <?php if ($statusFilter == 'DRAFT')
            echo 'selected'; ?>>Draft</option>
        <option value="SENT" <?php if ($statusFilter == 'SENT')
            echo 'selected'; ?>>Sent</option>
    </select>

    <button type="submit"
        style="background:#F1F5F9; border:1px solid var(--border); padding:0 1rem; border-radius:6px; cursor:pointer;">Filter</button>
</form>

<?php if ($successMsg): ?>
    <div style="background:#DCFCE7; color:#166534; padding:1rem; border-radius:6px; margin-bottom:1.5rem;">
        <?php echo $successMsg; ?>
    </div>
<?php endif; ?>
<?php if ($errorMsg): ?>
    <div style="background:#FEE2E2; color:#991B1B; padding:1rem; border-radius:6px; margin-bottom:1.5rem;">
        <?php echo $errorMsg; ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="table-wrapper">
        <table class="clean-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Target Audience</th>
                    <th>Delivery Type</th>
                    <th>Status</th>
                    <th>Sent/Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $row['notificationID']; ?></td>
                            <td>
                                <div style="font-weight:600; color:var(--text-dark); max-width:250px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"
                                    title="<?php echo htmlspecialchars($row['title']); ?>">
                                    <?php echo htmlspecialchars($row['title']); ?>
                                </div>
                                <div
                                    style="font-size:0.75rem; color:var(--text-light); max-width:250px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                    <?php echo htmlspecialchars($row['message']); ?>
                                </div>
                            </td>
                            <td>
                                <span
                                    style="font-weight:500; font-size:0.875rem;"><?php echo $row['targetRole'] == 'ALL' ? 'All Users' : ucfirst(strtolower($row['targetRole'])); ?></span>
                            </td>
                            <td>
                                <?php
                                $dtype = $row['delivery_type'];
                                $dClass = 'type-in-app';
                                if ($dtype === 'EMAIL')
                                    $dClass = 'type-email';
                                if ($dtype === 'BOTH')
                                    $dClass = 'type-both-pill'; // Fallback
                                ?>
                                <span class="badge-pill <?php echo $dClass; ?>"><?php echo $dtype; ?></span>
                            </td>
                            <td>
                                <span
                                    class="badge-pill status-<?php echo strtolower($row['status']); ?>"><?php echo $row['status']; ?></span>
                            </td>
                            <td style="font-size:0.875rem; color:var(--text-light);">
                                <?php echo date('d M Y, h:i A', strtotime($row['createdDate'])); ?>
                            </td>
                            <td style="display:flex; gap:0.5rem;">
                                <?php if ($row['status'] === 'DRAFT'): ?>
                                    <!-- Edit (For now just disabled visually for consistency or minimal functionality) -->
                                    <button class="btn-icon" title="Edit"
                                        onclick="alert('Edit feature coming soon. Please create new for now.')">
                                        <i data-lucide="edit-2" size="18"></i>
                                    </button>

                                    <!-- Send Draft -->
                                    <form method="POST" style="display:inline;"
                                        onsubmit="return confirm('Send this notification now?');">
                                        <input type="hidden" name="action" value="send_draft">
                                        <input type="hidden" name="notification_id" value="<?php echo $row['notificationID']; ?>">
                                        <button type="submit" class="btn-icon" title="Send Now" style="color:var(--primary);">
                                            <i data-lucide="send" size="18"></i>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button class="btn-icon" title="Already Sent" disabled style="opacity:0.5; cursor:not-allowed;">
                                        <i data-lucide="check-circle" size="18"></i>
                                    </button>
                                <?php endif; ?>

                                <!-- Delete -->
                                <form method="POST" style="display:inline;"
                                    onsubmit="return confirm('Delete this notification?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="notification_id" value="<?php echo $row['notificationID']; ?>">
                                    <button type="submit" class="btn-icon" title="Delete" style="color:#EF4444;">
                                        <i data-lucide="trash-2" size="18"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align:center; padding:2rem; color:var(--text-light);">No notifications
                            created yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Create Modal -->
<div id="createModal" class="modal">
    <div class="modal-content">
        <h2 style="margin-top:0; border-bottom:1px solid var(--border); padding-bottom:1rem; margin-bottom:1.5rem;">
            Create Notification</h2>

        <form method="POST">
            <input type="hidden" name="action" value="create_notification">

            <div style="margin-bottom:1.25rem;">
                <label
                    style="display:block; font-weight:600; font-size:0.875rem; margin-bottom:0.5rem; color:var(--text-dark);">Title</label>
                <input type="text" name="title" required placeholder="Notification Subject"
                    style="width:100%; padding:0.75rem; border:1px solid var(--border); border-radius:8px; font-family:inherit;">
            </div>

            <div style="margin-bottom:1.25rem;">
                <label
                    style="display:block; font-weight:600; font-size:0.875rem; margin-bottom:0.5rem; color:var(--text-dark);">Message</label>
                <textarea name="message" required rows="4" placeholder="Write your message here..."
                    style="width:100%; padding:0.75rem; border:1px solid var(--border); border-radius:8px; font-family:inherit; resize:vertical;"></textarea>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem; margin-bottom:1.5rem;">
                <div>
                    <label
                        style="display:block; font-weight:600; font-size:0.875rem; margin-bottom:0.5rem; color:var(--text-dark);">Target
                        Audience</label>
                    <select name="target_role"
                        style="width:100%; padding:0.75rem; border:1px solid var(--border); border-radius:8px; background:white;">
                        <option value="ALL">All Users</option>
                        <option value="STUDENT">Students</option>
                        <option value="ALUMNI">Alumni</option>
                        <option value="CAREER_OFFICER">Career Officers</option>
                    </select>
                </div>
                <div>
                    <label
                        style="display:block; font-weight:600; font-size:0.875rem; margin-bottom:0.5rem; color:var(--text-dark);">Delivery
                        Type</label>
                    <select name="delivery_type"
                        style="width:100%; padding:0.75rem; border:1px solid var(--border); border-radius:8px; background:white;">
                        <option value="IN-APP">In-App Only</option>
                        <option value="EMAIL">Email Only</option>
                        <option value="BOTH">Both (App + Email)</option>
                    </select>
                </div>
            </div>

            <div
                style="display:flex; justify-content:flex-end; gap:0.75rem; border-top:1px solid var(--border); padding-top:1.5rem;">
                <button type="button" onclick="document.getElementById('createModal').classList.remove('active')"
                    style="background:white; border:1px solid var(--border); padding:0.75rem 1.5rem; border-radius:8px; cursor:pointer; font-weight:500;">Cancel</button>

                <button type="submit" name="submit_action" value="draft"
                    style="background:#F1F5F9; color:var(--text-dark); border:1px solid var(--border); padding:0.75rem 1.5rem; border-radius:8px; cursor:pointer; font-weight:600;">Save
                    Draft</button>

                <button type="submit" name="submit_action" value="send"
                    style="background:var(--primary); color:white; border:none; padding:0.75rem 1.5rem; border-radius:8px; cursor:pointer; font-weight:600;">Send
                    Notification</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openCreateModal() {
        document.getElementById('createModal').classList.add('active');
    }
</script>

<?php
$content = ob_get_clean();
include '../layout.php';
?>