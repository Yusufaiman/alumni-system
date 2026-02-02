<?php
/**
 * ADMIN - MANAGE FEEDBACK
 * View and respond to feedback from students and alumni
 */
session_start();
require_once "../../config/db.php";
require_once "../../config/functions.php";

// Access Control
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'ADMIN') {
    header("Location: ../../auth/login.php");
    exit();
}

// Log activity
logActivity($conn, $_SESSION['userID'], $_SESSION['role'], 'Viewed Feedback', 'Feedback', 'Accessed the feedback management page');

$successMsg = "";
$errorMsg = "";

// Check for success message
if (isset($_GET['success'])) {
    $successMsg = "Response sent successfully. User has been notified.";
}

// Fetch Filters
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$roleFilter = isset($_GET['role']) ? $_GET['role'] : '';

// Fetch Feedback with Filters
try {
    $sql = "
        SELECT 
            f.feedbackID,
            f.userID,
            f.userRole,
            f.subject,
            f.message,
            f.status,
            f.adminResponse,
            f.createdDate,
            f.handledDate,
            u.name as userName,
            u.email as userEmail
        FROM system_feedback f
        LEFT JOIN user u ON f.userID = u.userID
        WHERE 1=1
    ";

    $params = [];
    $types = "";

    if ($statusFilter) {
        $sql .= " AND f.status = ?";
        $params[] = $statusFilter;
        $types .= "s";
    }

    if ($roleFilter) {
        $sql .= " AND f.userRole = ?";
        $params[] = $roleFilter;
        $types .= "s";
    }

    $sql .= " ORDER BY f.createdDate DESC";

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

} catch (Exception $e) {
    die("Database Error: " . $e->getMessage());
}

$pageTitle = "Feedback Management";
ob_start();
?>

<style>
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

    .badge-pill {
        display: inline-flex;
        align-items: center;
        padding: 0.25rem 0.625rem;
        border-radius: 99px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
    }

    .status-new {
        background: #DBEAFE;
        color: #1E40AF;
    }

    .status-in_progress {
        background: #FEF3C7;
        color: #B45309;
    }

    .status-resolved {
        background: #DCFCE7;
        color: #166534;
    }

    .role-student {
        background: #E0E7FF;
        color: #3730A3;
    }

    .role-alumni {
        background: #D1FAE5;
        color: #065F46;
    }

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
        overflow-y: auto;
        padding: 2rem;
    }

    .modal.active {
        display: flex;
    }

    .modal-content {
        background: white;
        border-radius: 12px;
        width: 100%;
        max-width: 700px;
        box-shadow: var(--shadow-md);
        max-height: 90vh;
        overflow-y: auto;
    }

    .modal-header {
        padding: 1.5rem;
        border-bottom: 1px solid var(--border);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-body {
        padding: 1.5rem;
    }

    .modal-footer {
        padding: 1.5rem;
        border-top: 1px solid var(--border);
        display: flex;
        justify-content: flex-end;
        gap: 0.75rem;
    }

    .detail-group {
        margin-bottom: 1.5rem;
    }

    .detail-label {
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        color: var(--text-medium);
        margin-bottom: 0.5rem;
    }

    .detail-value {
        font-size: 0.95rem;
        color: var(--text-dark);
    }

    .detail-message {
        background: #F8FAFC;
        padding: 1rem;
        border-radius: 8px;
        border-left: 3px solid var(--primary);
        white-space: pre-wrap;
        line-height: 1.6;
    }

    .form-group {
        margin-bottom: 1.25rem;
    }

    .form-label {
        display: block;
        font-weight: 600;
        font-size: 0.875rem;
        margin-bottom: 0.5rem;
        color: var(--text-dark);
    }

    .form-textarea {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid var(--border);
        border-radius: 8px;
        font-family: inherit;
        resize: vertical;
        min-height: 120px;
    }

    .form-select {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid var(--border);
        border-radius: 8px;
        background: white;
    }

    .btn-close {
        background: none;
        border: none;
        font-size: 1.5rem;
        color: var(--text-light);
        cursor: pointer;
        padding: 0;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 4px;
    }

    .btn-close:hover {
        background: #F1F5F9;
        color: var(--text-dark);
    }
</style>

<div class="page-header" style="display:flex; justify-content:space-between; align-items:center;">
    <div>
        <h1 class="page-title">Feedback Management</h1>
        <p class="page-desc">Review and respond to feedback submitted by students and alumni</p>
    </div>
</div>

<!-- Filters -->
<form class="filters" method="GET">
    <select name="status" class="filter-select">
        <option value="">All Status</option>
        <option value="NEW" <?php if ($statusFilter == 'NEW')
            echo 'selected'; ?>>New</option>
        <option value="IN_PROGRESS" <?php if ($statusFilter == 'IN_PROGRESS')
            echo 'selected'; ?>>In Progress</option>
        <option value="RESOLVED" <?php if ($statusFilter == 'RESOLVED')
            echo 'selected'; ?>>Resolved</option>
    </select>

    <select name="role" class="filter-select">
        <option value="">All Roles</option>
        <option value="STUDENT" <?php if ($roleFilter == 'STUDENT')
            echo 'selected'; ?>>Student</option>
        <option value="ALUMNI" <?php if ($roleFilter == 'ALUMNI')
            echo 'selected'; ?>>Alumni</option>
    </select>

    <button type="submit"
        style="background:#F1F5F9; border:1px solid var(--border); padding:0 1rem; border-radius:6px; cursor:pointer;">Filter</button>
</form>

<?php if ($successMsg): ?>
    <div
        style="background:#DCFCE7; color:#166534; padding:1rem; border-radius:6px; margin-bottom:1.5rem; display:flex; align-items:center; gap:0.75rem;">
        <i data-lucide="check-circle" size="20"></i>
        <span>
            <?php echo $successMsg; ?>
        </span>
    </div>
<?php endif; ?>

<div class="card">
    <div class="table-wrapper">
        <table class="clean-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Role</th>
                    <th>Subject</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td>#
                                <?php echo $row['feedbackID']; ?>
                            </td>
                            <td>
                                <div style="font-weight:500;">
                                    <?php echo htmlspecialchars($row['userName'] ?? 'Unknown'); ?>
                                </div>
                                <div style="font-size:0.75rem; color:var(--text-light);">
                                    <?php echo htmlspecialchars($row['userEmail'] ?? ''); ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge-pill role-<?php echo strtolower($row['userRole']); ?>">
                                    <?php echo $row['userRole']; ?>
                                </span>
                            </td>
                            <td>
                                <div style="max-width:300px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"
                                    title="<?php echo htmlspecialchars($row['subject']); ?>">
                                    <?php echo htmlspecialchars($row['subject']); ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge-pill status-<?php echo strtolower($row['status']); ?>">
                                    <?php echo str_replace('_', ' ', $row['status']); ?>
                                </span>
                            </td>
                            <td style="font-size:0.875rem; color:var(--text-light);">
                                <?php echo date('d M Y, h:i A', strtotime($row['createdDate'])); ?>
                            </td>
                            <td>
                                <button onclick='openFeedbackModal(<?php echo json_encode($row); ?>)' class="btn-icon"
                                    title="View & Respond">
                                    <i data-lucide="message-square" size="18"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align:center; padding:2rem; color:var(--text-light);">No feedback
                            submissions yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Feedback Detail & Response Modal -->
<div id="feedbackModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 style="margin:0; font-size:1.25rem; font-weight:700;">Feedback Details</h2>
            <button class="btn-close" onclick="closeFeedbackModal()">×</button>
        </div>

        <div class="modal-body">
            <div class="detail-group">
                <div class="detail-label">Feedback ID</div>
                <div class="detail-value" id="modalFeedbackID"></div>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem; margin-bottom:1.5rem;">
                <div class="detail-group" style="margin:0;">
                    <div class="detail-label">User</div>
                    <div class="detail-value" id="modalUserName"></div>
                </div>
                <div class="detail-group" style="margin:0;">
                    <div class="detail-label">Role</div>
                    <div class="detail-value" id="modalUserRole"></div>
                </div>
            </div>

            <div class="detail-group">
                <div class="detail-label">Subject</div>
                <div class="detail-value" id="modalSubject"></div>
            </div>

            <div class="detail-group">
                <div class="detail-label">Message</div>
                <div class="detail-message" id="modalMessage"></div>
            </div>

            <div class="detail-group">
                <div class="detail-label">Submitted Date</div>
                <div class="detail-value" id="modalDate"></div>
            </div>

            <div id="existingResponseSection"
                style="display:none; margin-bottom:1.5rem; padding:1rem; background:#F8FAFC; border-radius:8px; border-left:3px solid #22c55e;">
                <div class="detail-label" style="color:#166534;">Previous Admin Response</div>
                <div id="existingResponse" style="color:#1e293b; margin-top:0.5rem;"></div>
            </div>

            <form method="POST" action="feedback_reply.php">
                <input type="hidden" name="feedbackID" id="formFeedbackID">
                <input type="hidden" name="userID" id="formUserID">

                <div class="form-group">
                    <label class="form-label">Admin Response</label>
                    <textarea name="adminResponse" class="form-textarea"
                        placeholder="Write your response to the user..." required></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Update Status</label>
                    <select name="status" class="form-select" required>
                        <option value="IN_PROGRESS">In Progress</option>
                        <option value="RESOLVED">Resolved</option>
                    </select>
                </div>

                <div class="modal-footer" style="padding:0; border:none; margin-top:1.5rem;">
                    <button type="button" onclick="closeFeedbackModal()"
                        style="background:white; border:1px solid var(--border); padding:0.75rem 1.5rem; border-radius:8px; cursor:pointer; font-weight:500;">Cancel</button>
                    <button type="submit"
                        style="background:var(--primary); color:white; border:none; padding:0.75rem 1.5rem; border-radius:8px; cursor:pointer; font-weight:600; display:flex; align-items:center; gap:0.5rem;">
                        <i data-lucide="send" size="16"></i>
                        Send Response
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openFeedbackModal(feedback) {
        document.getElementById('modalFeedbackID').textContent = '#' + feedback.feedbackID;
        document.getElementById('modalUserName').textContent = feedback.userName || 'Unknown User';
        document.getElementById('modalUserRole').innerHTML = '<span class="badge-pill role-' + feedback.userRole.toLowerCase() + '">' + feedback.userRole + '</span>';
        document.getElementById('modalSubject').textContent = feedback.subject;
        document.getElementById('modalMessage').textContent = feedback.message;
        document.getElementById('modalDate').textContent = new Date(feedback.createdDate).toLocaleString('en-MY', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });

        document.getElementById('formFeedbackID').value = feedback.feedbackID;
        document.getElementById('formUserID').value = feedback.userID;

        // Show existing response if available
        if (feedback.adminResponse) {
            document.getElementById('existingResponse').textContent = feedback.adminResponse;
            document.getElementById('existingResponseSection').style.display = 'block';
        } else {
            document.getElementById('existingResponseSection').style.display = 'none';
        }

        document.getElementById('feedbackModal').classList.add('active');
        lucide.createIcons();
    }

    function closeFeedbackModal() {
        document.getElementById('feedbackModal').classList.remove('active');
    }

    // Close modal on outside click
    document.getElementById('feedbackModal').addEventListener('click', function (e) {
        if (e.target === this) {
            closeFeedbackModal();
        }
    });
</script>

<?php
$content = ob_get_clean();
include '../layout.php';
?>