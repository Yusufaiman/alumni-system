<?php
/**
 * ADMIN - REPORTS & ANALYTICS
 * View system analytics and performance summaries.
 */
session_start();
require_once "../config/db.php";
require_once "../config/functions.php";

// Access Control
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'ADMIN') {
    header("Location: ../auth/login.php");
    exit();
}

// Log activity
logActivity($conn, $_SESSION['userID'], $_SESSION['role'], 'Viewed Reports', 'Reports', 'Accessed the system reports and analytics page');

// ---------------------------------------------------------
// 1. FILTER LOGIC
// ---------------------------------------------------------
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$reportType = isset($_GET['report_type']) ? $_GET['report_type'] : 'ALL';

// Date range for queries (MySQL handles date/datetime comparison with string date ranges)
$filterSqlUser = " AND createdDate BETWEEN '$startDate' AND '$endDate'";
$filterSqlSent = " AND sentDate BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59'";
$filterSqlApp = " AND appliedDate BETWEEN '$startDate' AND '$endDate'";

// ---------------------------------------------------------
// 2. SUMMARY DATA
// ---------------------------------------------------------

// Total Users
$userCountRes = $conn->query("SELECT COUNT(*) as total FROM user WHERE 1=1 $filterSqlUser");
$totalUsers = ($userCountRes && $userCountRes->num_rows > 0) ? $userCountRes->fetch_assoc()['total'] : 0;

// Active Users (proxy: registered in timeframe)
$activeUserRes = $conn->query("SELECT COUNT(*) as total FROM user WHERE status = 'ACTIVE' $filterSqlUser");
$activeUsers = ($activeUserRes && $activeUserRes->num_rows > 0) ? $activeUserRes->fetch_assoc()['total'] : 0;

// Total Events (Event table has no createdDate, we use all or filter by event date?)
// Let's use all events for now or filter by 'date' column which is the event date
$eventCountRes = $conn->query("SELECT COUNT(*) as total FROM event WHERE date BETWEEN '$startDate' AND '$endDate'");
$totalEvents = ($eventCountRes && $eventCountRes->num_rows > 0) ? $eventCountRes->fetch_assoc()['total'] : 0;

// Total Job Applications
$jobAppRes = $conn->query("SELECT COUNT(*) as total FROM job_application WHERE 1=1 $filterSqlApp");
$totalJobApps = ($jobAppRes && $jobAppRes->num_rows > 0) ? $jobAppRes->fetch_assoc()['total'] : 0;

// Total Notifications Sent
$notifRes = $conn->query("SELECT COUNT(*) as total FROM notification WHERE 1=1 $filterSqlSent");
$totalNotifs = ($notifRes && $notifRes->num_rows > 0) ? $notifRes->fetch_assoc()['total'] : 0;

// ---------------------------------------------------------
// 3. DETAILED DATA
// ---------------------------------------------------------

// User Roles Breakdown
$rolesRes = $conn->query("SELECT role, COUNT(*) as count FROM user WHERE 1=1 $filterSqlUser GROUP BY role");
$rolesData = [];
if ($rolesRes && $rolesRes->num_rows > 0) {
    while ($row = $rolesRes->fetch_assoc())
        $rolesData[] = $row;
}

// Job Application Status
$jobStatusRes = $conn->query("SELECT status, COUNT(*) as count FROM job_application WHERE 1=1 $filterSqlApp GROUP BY status");
$jobStatusData = [];
if ($jobStatusRes && $jobStatusRes->num_rows > 0) {
    while ($row = $jobStatusRes->fetch_assoc())
        $jobStatusData[] = $row;
}

// Event Participation List
$eventParticipationRes = $conn->query("
    SELECT e.title, COUNT(er.registrationID) as participants 
    FROM event e 
    LEFT JOIN eventregistration er ON e.eventID = er.eventID 
    WHERE e.date BETWEEN '$startDate' AND '$endDate'
    GROUP BY e.eventID 
    ORDER BY participants DESC 
    LIMIT 10
");
$eventPartData = [];
if ($eventParticipationRes && $eventParticipationRes->num_rows > 0) {
    while ($row = $eventParticipationRes->fetch_assoc())
        $eventPartData[] = $row;
}

// Notification Audience Breakdown
$notifAudRes = $conn->query("SELECT targetGroup, COUNT(*) as count FROM notification WHERE 1=1 $filterSqlSent GROUP BY targetGroup");
$notifAudData = [];
if ($notifAudRes && $notifAudRes->num_rows > 0) {
    while ($row = $notifAudRes->fetch_assoc())
        $notifAudData[] = $row;
}

$pageTitle = "Reports";
ob_start();
?>

<style>
    .reports-filter-bar {
        background: #fff;
        padding: 1.25rem 1.5rem;
        border-radius: var(--radius-lg);
        border: 1px solid var(--border);
        display: flex;
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

    .reports-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1.5rem;
    }

    .chart-container {
        height: 300px;
        position: relative;
    }

    @media (max-width: 1024px) {
        .reports-grid {
            grid-template-columns: 1fr;
        }

        .reports-filter-bar {
            flex-direction: column;
            align-items: stretch;
        }
    }
</style>

<div class="page-header">
    <h1 class="page-title">Reports</h1>
    <p class="page-desc">View system analytics and performance summaries</p>
</div>

<!-- Report Filters -->
<form method="GET" class="reports-filter-bar">
    <div class="filter-group">
        <label>Start Date</label>
        <input type="date" name="start_date" class="filter-control" value="<?php echo $startDate; ?>">
    </div>
    <div class="filter-group">
        <label>End Date</label>
        <input type="date" name="end_date" class="filter-control" value="<?php echo $endDate; ?>">
    </div>
    <div class="filter-group">
        <label>Report Type</label>
        <select name="report_type" class="filter-control">
            <option value="ALL" <?php echo $reportType === 'ALL' ? 'selected' : ''; ?>>All Reports</option>
            <option value="USERS" <?php echo $reportType === 'USERS' ? 'selected' : ''; ?>>User Activity</option>
            <option value="EVENTS" <?php echo $reportType === 'EVENTS' ? 'selected' : ''; ?>>Event Participation</option>
            <option value="JOBS" <?php echo $reportType === 'JOBS' ? 'selected' : ''; ?>>Job Applications</option>
        </select>
    </div>
    <button type="submit" class="btn-apply">Apply Filter</button>
</form>

<!-- Summary Stats -->
<div class="kpi-grid">
    <div class="kpi-card">
        <span class="page-desc">Total Users</span>
        <h2 style="font-size: 1.75rem; font-weight: 700; color: var(--primary);">
            <?php echo number_format($totalUsers); ?>
        </h2>
        <span style="font-size: 0.75rem; color: var(--success-text);">Registered in range</span>
    </div>
    <div class="kpi-card">
        <span class="page-desc">Active (Status: Active)</span>
        <h2 style="font-size: 1.75rem; font-weight: 700; color: #10B981;"><?php echo number_format($activeUsers); ?>
        </h2>
        <span style="font-size: 0.75rem; color: var(--text-medium);">Currently enabled accounts</span>
    </div>
    <div class="kpi-card">
        <span class="page-desc">Events Created</span>
        <h2 style="font-size: 1.75rem; font-weight: 700; color: #F59E0B;"><?php echo number_format($totalEvents); ?>
        </h2>
        <span style="font-size: 0.75rem; color: var(--text-medium);">Networking & sessions</span>
    </div>
    <div class="kpi-card">
        <span class="page-desc">Job Applications</span>
        <h2 style="font-size: 1.75rem; font-weight: 700; color: #8B5CF6;"><?php echo number_format($totalJobApps); ?>
        </h2>
        <span style="font-size: 0.75rem; color: var(--text-medium);">Across all postings</span>
    </div>
    <div class="kpi-card">
        <span class="page-desc">Notifications Sent</span>
        <h2 style="font-size: 1.75rem; font-weight: 700; color: #EC4899;"><?php echo number_format($totalNotifs); ?>
        </h2>
        <span style="font-size: 0.75rem; color: var(--text-medium);">System & target alerts</span>
    </div>
</div>

<!-- Detailed Reports Grid -->
<div class="reports-grid">

    <!-- User Roles Breakdown -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">User Distribution by Role</h3>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <canvas id="rolesChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Job Application Status -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Job Application Success Rate</h3>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <canvas id="jobStatusChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Event Participation -->
    <div class="card" style="grid-column: span 2;">
        <div class="card-header">
            <h3 class="card-title">Top Events by Participation</h3>
        </div>
        <div class="card-body">
            <div class="table-wrapper">
                <table class="clean-table">
                    <thead>
                        <tr>
                            <th>Event Title</th>
                            <th>Total Participants</th>
                            <th>Popularity</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($eventPartData)): ?>
                            <tr>
                                <td colspan="3" style="text-align: center; padding: 2rem;">No event data found for this
                                    period</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($eventPartData as $event): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($event['title']); ?></strong></td>
                                    <td><?php echo $event['participants']; ?> users</td>
                                    <td>
                                        <div
                                            style="width: 100%; background: #F1F5F9; height: 8px; border-radius: 4px; overflow: hidden;">
                                            <div
                                                style="width: <?php echo min(100, $event['participants'] * 2); ?>%; background: var(--primary); height: 100%;">
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Notification Audience -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Notification Audience Reach</h3>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <canvas id="notifAudChart"></canvas>
            </div>
        </div>
    </div>

    <!-- System Summary -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Quick System Insights</h3>
        </div>
        <div class="card-body">
            <div class="health-list">
                <div class="health-item">
                    <span class="health-text">Avg. Apps per User</span>
                    <span
                        class="health-pill health-ok"><?php echo $totalUsers > 0 ? round($totalJobApps / $totalUsers, 1) : 0; ?></span>
                </div>
                <div class="health-item">
                    <span class="health-text">Events per 100 Users</span>
                    <span
                        class="health-pill health-warning"><?php echo $totalUsers > 0 ? round(($totalEvents / $totalUsers) * 100, 1) : 0; ?></span>
                </div>
                <div class="health-item">
                    <span class="health-text">Notification Intensity</span>
                    <span
                        class="health-pill health-ok"><?php echo $totalUsers > 0 ? round($totalNotifs / $totalUsers, 1) : 0; ?></span>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // 1. Roles Chart
        new Chart(document.getElementById('rolesChart'), {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($rolesData, 'role')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($rolesData, 'count')); ?>,
                    backgroundColor: ['#2563EB', '#F59E0B', '#10B981', '#6366F1']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });

        // 2. Job Status Chart
        new Chart(document.getElementById('jobStatusChart'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($jobStatusData, 'status')); ?>,
                datasets: [{
                    label: 'Applications',
                    data: <?php echo json_encode(array_column($jobStatusData, 'count')); ?>,
                    backgroundColor: '#8B5CF6'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        });

        // 3. Notification Audience Chart
        new Chart(document.getElementById('notifAudChart'), {
            type: 'pie',
            data: {
                labels: <?php echo json_encode(array_column($notifAudData, 'targetGroup')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($notifAudData, 'count')); ?>,
                    backgroundColor: ['#EC4899', '#3B82F6', '#8B5CF6', '#10B981']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    });
</script>

<?php
$content = ob_get_clean();
include 'layout.php';
?>