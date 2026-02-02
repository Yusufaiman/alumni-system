<?php
/**
 * ADMIN - ACTIVITY LOGS
 * Track all system and user activities.
 */
session_start();
require_once "../config/db.php";
require_once "../config/functions.php";

// Access Control
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'ADMIN') {
    header("Location: ../auth/login.php");
    exit();
}

// ---------------------------------------------------------
// 1. FILTER LOGIC
// ---------------------------------------------------------
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$roleFilter = isset($_GET['role']) ? $_GET['role'] : '';
$moduleFilter = isset($_GET['module']) ? $_GET['module'] : '';
$actionFilter = isset($_GET['action_type']) ? $_GET['action_type'] : '';

$filterSql = " WHERE created_at BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59'";

if ($roleFilter) {
    $roleEscaped = mysqli_real_escape_string($conn, $roleFilter);
    $filterSql .= " AND user_role = '$roleEscaped'";
}
if ($moduleFilter) {
    $moduleEscaped = mysqli_real_escape_string($conn, $moduleFilter);
    $filterSql .= " AND module = '$moduleEscaped'";
}
if ($actionFilter) {
    $actionEscaped = mysqli_real_escape_string($conn, $actionFilter);
    $filterSql .= " AND action LIKE '%$actionEscaped%'";
}

// ---------------------------------------------------------
// 2. SUMMARY DATA
// ---------------------------------------------------------
$totalLogsRes = $conn->query("SELECT COUNT(*) as total FROM activity_logs $filterSql");
$totalLogs = ($totalLogsRes && $totalLogsRes->num_rows > 0) ? $totalLogsRes->fetch_assoc()['total'] : 0;

$today = date('Y-m-d');
$todayLogsRes = $conn->query("SELECT COUNT(*) as total FROM activity_logs WHERE DATE(created_at) = '$today'");
$todayLogs = ($todayLogsRes && $todayLogsRes->num_rows > 0) ? $todayLogsRes->fetch_assoc()['total'] : 0;

$adminActionsRes = $conn->query("SELECT COUNT(*) as total FROM activity_logs WHERE user_role = 'ADMIN' AND created_at BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59'");
$adminActions = ($adminActionsRes && $adminActionsRes->num_rows > 0) ? $adminActionsRes->fetch_assoc()['total'] : 0;

$userActionsRes = $conn->query("SELECT COUNT(*) as total FROM activity_logs WHERE user_role != 'ADMIN' AND created_at BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59'");
$userActions = ($userActionsRes && $userActionsRes->num_rows > 0) ? $userActionsRes->fetch_assoc()['total'] : 0;

// ---------------------------------------------------------
// 3. PAGINATION & DATA FETCH
// ---------------------------------------------------------
$limit = 15;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

$sql = "
    SELECT al.*, u.name as user_name 
    FROM activity_logs al 
    LEFT JOIN user u ON al.user_id = u.userID 
    $filterSql 
    ORDER BY al.created_at DESC 
    LIMIT $limit OFFSET $offset
";

$logsRes = $conn->query($sql);
$logs = [];
if ($logsRes && $logsRes->num_rows > 0) {
    while ($row = $logsRes->fetch_assoc()) {
        $logs[] = $row;
    }
}

// Log view action as requested
logActivity($conn, $_SESSION['userID'], $_SESSION['role'], 'Viewed Activity Logs', 'Reports', 'Accessed the activity logs page');

$pageTitle = "Activity Logs";
ob_start();
?>

<style>
    .logs-filter-bar {
        background: #fff;
        padding: 1.25rem 1.5rem;
        border-radius: var(--radius-lg);
        border: 1px solid var(--border);
        display: flex;
        flex-wrap: wrap;
        gap: 1.5rem;
        align-items: flex-end;
        margin-bottom: 2rem;
        box-shadow: var(--shadow-sm);
    }

    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .filter-group label {
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        color: var(--text-medium);
        letter-spacing: 0.05em;
    }

    .filter-control {
        padding: 0.625rem 0.875rem;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        font-size: 0.875rem;
        color: var(--text-dark);
        background: #F8FAFC;
    }

    .btn-apply {
        background: var(--primary);
        color: white;
        border: none;
        padding: 0.625rem 1.5rem;
        border-radius: var(--radius-md);
        font-weight: 600;
        cursor: pointer;
        transition: background 0.2s;
        height: 38px;
    }

    .btn-apply:hover {
        background: var(--primary-hover);
    }

    .pagination {
        display: flex;
        justify-content: center;
        gap: 0.5rem;
        margin-top: 2rem;
        margin-bottom: 3rem;
    }

    .page-link {
        padding: 0.5rem 1rem;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        text-decoration: none;
        color: var(--text-dark);
        background: #fff;
        font-weight: 500;
        transition: all 0.2s;
    }

    .page-link.active {
        background: var(--primary);
        color: #fff;
        border-color: var(--primary);
    }

    .page-link:hover:not(.active) {
        background: #F1F5F9;
    }

    .role-badge {
        padding: 0.25rem 0.625rem;
        border-radius: 9999px;
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
    }

    .role-admin { background: #DBEAFE; color: #1E40AF; }
    .role-student { background: #DCFCE7; color: #166534; }
    .role-alumni { background: #FFEDD5; color: #9A3412; }
    .role-career_service_officer { background: #F3E8FF; color: #6B21A8; }
</style>

<div class="page-header">
    <h1 class="page-title">Activity Logs</h1>
    <p class="page-desc">Track all system and user activities</p>
</div>

<!-- Logs Filters -->
<form method="GET" class="logs-filter-bar">
    <div class="filter-group">
        <label>Start Date</label>
        <input type="date" name="start_date" class="filter-control" value="<?php echo $startDate; ?>">
    </div>
    <div class="filter-group">
        <label>End Date</label>
        <input type="date" name="end_date" class="filter-control" value="<?php echo $endDate; ?>">
    </div>
    <div class="filter-group">
        <label>Role</label>
        <select name="role" class="filter-control">
            <option value="">All Roles</option>
            <option value="ADMIN" <?php echo $roleFilter === 'ADMIN' ? 'selected' : ''; ?>>Admin</option>
            <option value="STUDENT" <?php echo $roleFilter === 'STUDENT' ? 'selected' : ''; ?>>Student</option>
            <option value="ALUMNI" <?php echo $roleFilter === 'ALUMNI' ? 'selected' : ''; ?>>Alumni</option>
            <option value="CAREER_SERVICE_OFFICER" <?php echo $roleFilter === 'CAREER_SERVICE_OFFICER' ? 'selected' : ''; ?>>CSO</option>
        </select>
    </div>
    <div class="filter-group">
        <label>Module</label>
        <select name="module" class="filter-control">
            <option value="">All Modules</option>
            <option value="Users" <?php echo $moduleFilter === 'Users' ? 'selected' : ''; ?>>Users</option>
            <option value="Events" <?php echo $moduleFilter === 'Events' ? 'selected' : ''; ?>>Events</option>
            <option value="Notifications" <?php echo $moduleFilter === 'Notifications' ? 'selected' : ''; ?>>Notifications</option>
            <option value="Reports" <?php echo $moduleFilter === 'Reports' ? 'selected' : ''; ?>>Reports</option>
            <option value="Settings" <?php echo $moduleFilter === 'Settings' ? 'selected' : ''; ?>>Settings</option>
            <option value="Feedback" <?php echo $moduleFilter === 'Feedback' ? 'selected' : ''; ?>>Feedback</option>
        </select>
    </div>
    <div class="filter-group">
        <label>Action Type</label>
        <input type="text" name="action_type" class="filter-control" placeholder="e.g. Created" value="<?php echo htmlspecialchars($actionFilter); ?>">
    </div>
    <button type="submit" class="btn-apply">Apply Filter</button>
</form>

<!-- Summary Cards -->
<div class="kpi-grid">
    <div class="kpi-card">
        <span class="page-desc">Total Activities</span>
        <h2 style="font-size: 1.75rem; font-weight: 700; color: var(--primary);"><?php echo number_format($totalLogs); ?></h2>
        <span style="font-size: 0.75rem; color: var(--text-medium);">In selected range</span>
    </div>
    <div class="kpi-card">
        <span class="page-desc">Activities Today</span>
        <h2 style="font-size: 1.75rem; font-weight: 700; color: #10B981;"><?php echo number_format($todayLogs); ?></h2>
        <span style="font-size: 0.75rem; color: var(--success-text);">Live tracking</span>
    </div>
    <div class="kpi-card">
        <span class="page-desc">Admin Actions</span>
        <h2 style="font-size: 1.75rem; font-weight: 700; color: #6366F1;"><?php echo number_format($adminActions); ?></h2>
        <span style="font-size: 0.75rem; color: var(--text-medium);">Manager updates</span>
    </div>
    <div class="kpi-card">
        <span class="page-desc">User Actions</span>
        <h2 style="font-size: 1.75rem; font-weight: 700; color: #F59E0B;"><?php echo number_format($userActions); ?></h2>
        <span style="font-size: 0.75rem; color: var(--text-medium);">General engagement</span>
    </div>
</div>

<!-- Logs Table -->
<div class="card">
    <div class="table-wrapper">
        <table class="clean-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Module</th>
                    <th>Description</th>
                    <th>IP Address</th>
                    <th>Date & Time</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="7" style="text-align: center; padding: 4rem;">
                        <i data-lucide="history" size="48" style="color: var(--text-light); margin-bottom: 1rem;"></i>
                        <p style="color: var(--text-medium); font-weight: 500;">No activity recorded yet.</p>
                    </td></tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><span style="font-family: monospace; color: var(--text-medium);">#<?php echo $log['id']; ?></span></td>
                            <td>
                                <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                                    <span style="font-weight: 600;"><?php echo htmlspecialchars($log['user_name'] ?? 'System / Anonymous'); ?></span>
                                    <div>
                                        <span class="role-badge role-<?php echo strtolower($log['user_role']); ?>">
                                            <?php echo str_replace('_', ' ', $log['user_role']); ?>
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td><span style="font-weight: 500;"><?php echo htmlspecialchars($log['action']); ?></span></td>
                            <td>
                                <span style="background: #F1F5F9; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 600;">
                                    <?php echo htmlspecialchars($log['module']); ?>
                                </span>
                            </td>
                            <td style="max-width: 300px; font-size: 0.8rem; color: var(--text-medium);">
                                <?php echo htmlspecialchars($log['description']); ?>
                            </td>
                            <td style="font-family: monospace; font-size: 0.75rem;"><?php echo $log['ip_address']; ?></td>
                            <td style="white-space: nowrap;">
                                <div style="font-weight: 500;"><?php echo date('d M Y', strtotime($log['created_at'])); ?></div>
                                <div style="font-size: 0.75rem; color: var(--text-light);"><?php echo date('h:i A', strtotime($log['created_at'])); ?></div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
<?php if ($totalLogs > $limit): ?>
    <?php $totalPages = ceil($totalLogs / $limit); ?>
    <div class="pagination">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?page=<?php echo $i; ?>&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>&role=<?php echo $roleFilter; ?>&module=<?php echo $moduleFilter; ?>&action_type=<?php echo $actionFilter; ?>" 
               class="page-link <?php echo $page == $i ? 'active' : ''; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
include 'layout.php';
?>
