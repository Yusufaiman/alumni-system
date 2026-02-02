<?php
/**
 * ADMIN DASHBOARD
 */
session_start();
require_once "../config/db.php";

// Access Control
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'ADMIN') {
    header("Location: ../auth/login.php");
    exit();
}

// Fetch KPI Data
$kpiSql = "SELECT * FROM admin_engagement_dashboard";
$kpiRes = $conn->query($kpiSql);
$kpi = $kpiRes->fetch_assoc();

// Fetch Recent Events
$recentEventsSql = "SELECT title, date, status FROM event ORDER BY date DESC LIMIT 5";
$recentEventsRes = $conn->query($recentEventsSql);

// Fetch System Health
$healthSql = "SELECT * FROM admin_system_health";
$healthRes = $conn->query($healthSql);

$pageTitle = "Dashboard Overview";

// Start Content Buffering
ob_start();
?>
<div class="section-header">
    <h1 class="page-title">Dashboard Overview</h1>
    <p class="page-desc">System performance metrics and quick controls.</p>
</div>

<!-- KPI SUMMARY -->
<div class="kpi-grid">
    <!-- Students -->
    <div class="kpi-card">
        <div style="display:flex; justify-content:space-between; margin-bottom:0.5rem;">
            <div style="padding:0.625rem; background:#EFF6FF; color:#2563EB; border-radius:8px;">
                <i data-lucide="graduation-cap" size="24"></i>
            </div>
        </div>
        <div>
            <div style="font-size:1.875rem; font-weight:700; color:#1E293B;">
                <?php echo number_format($kpi['total_students']); ?>
            </div>
            <div style="font-size:0.875rem; font-weight:500; color:#64748B;">Total Students</div>
        </div>
    </div>

    <!-- Alumni -->
    <div class="kpi-card">
        <div style="display:flex; justify-content:space-between; margin-bottom:0.5rem;">
            <div style="padding:0.625rem; background:#EFF6FF; color:#2563EB; border-radius:8px;">
                <i data-lucide="briefcase" size="24"></i>
            </div>
        </div>
        <div>
            <div style="font-size:1.875rem; font-weight:700; color:#1E293B;">
                <?php echo number_format($kpi['total_alumni']); ?>
            </div>
            <div style="font-size:0.875rem; font-weight:500; color:#64748B;">Total Alumni</div>
        </div>
    </div>

    <!-- Active Jobs -->
    <div class="kpi-card">
        <div style="display:flex; justify-content:space-between; margin-bottom:0.5rem;">
            <div style="padding:0.625rem; background:#EFF6FF; color:#2563EB; border-radius:8px;">
                <i data-lucide="target" size="24"></i>
            </div>
        </div>
        <div>
            <div style="font-size:1.875rem; font-weight:700; color:#1E293B;">
                <?php echo number_format($kpi['total_jobs']); ?>
            </div>
            <div style="font-size:0.875rem; font-weight:500; color:#64748B;">Active Job Posts</div>
        </div>
    </div>

    <!-- Applications -->
    <div class="kpi-card">
        <div style="display:flex; justify-content:space-between; margin-bottom:0.5rem;">
            <div style="padding:0.625rem; background:#EFF6FF; color:#2563EB; border-radius:8px;">
                <i data-lucide="file-check" size="24"></i>
            </div>
        </div>
        <div>
            <div style="font-size:1.875rem; font-weight:700; color:#1E293B;">
                <?php echo number_format($kpi['total_applications']); ?>
            </div>
            <div style="font-size:0.875rem; font-weight:500; color:#64748B;">Applications</div>
        </div>
    </div>

    <!-- Events -->
    <div class="kpi-card">
        <div style="display:flex; justify-content:space-between; margin-bottom:0.5rem;">
            <div style="padding:0.625rem; background:#EFF6FF; color:#2563EB; border-radius:8px;">
                <i data-lucide="calendar-days" size="24"></i>
            </div>
        </div>
        <div>
            <div style="font-size:1.875rem; font-weight:700; color:#1E293B;">
                <?php echo number_format($kpi['total_events']); ?>
            </div>
            <div style="font-size:0.875rem; font-weight:500; color:#64748B;">Total Events</div>
        </div>
    </div>

    <!-- Registrations -->
    <div class="kpi-card">
        <div style="display:flex; justify-content:space-between; margin-bottom:0.5rem;">
            <div style="padding:0.625rem; background:#EFF6FF; color:#2563EB; border-radius:8px;">
                <i data-lucide="users" size="24"></i>
            </div>
        </div>
        <div>
            <div style="font-size:1.875rem; font-weight:700; color:#1E293B;">
                <?php echo number_format($kpi['total_registrations']); ?>
            </div>
            <div style="font-size:0.875rem; font-weight:500; color:#64748B;">Event Registrations</div>
        </div>
    </div>
</div>

<div class="grid-split" style="display:grid; grid-template-columns: 2fr 1fr; gap:1.5rem;">

    <!-- LEFT COLUMN -->
    <div style="display: flex; flex-direction: column; gap: 2rem;">

        <!-- Analytics Chart -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">User Roles Distribution</span>
            </div>
            <div class="card-body">
                <div style="position:relative; height:250px; width:100%;">
                    <canvas id="roleChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Recent Events Table -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">Recent Events Created</span>
                <a href="/alumni-system/admin/events/manage_events.php" class="card-action-btn">
                    View All <i data-lucide="arrow-right" size="16"></i>
                </a>
            </div>
            <div class="table-wrapper">
                <table class="clean-table">
                    <thead>
                        <tr>
                            <th>Event Title</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($recentEventsRes && $recentEventsRes->num_rows > 0): ?>
                            <?php while ($row = $recentEventsRes->fetch_assoc()): ?>
                                <tr>
                                    <td style="font-weight: 500; color: #2563EB;">
                                        <?php echo htmlspecialchars($row['title']); ?>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($row['date'])); ?></td>
                                    <td>
                                        <?php
                                        $status = $row['status'];
                                        $cls = 'status-closed';
                                        if ($status === 'OPEN')
                                            $cls = 'status-open';
                                        if ($status === 'CANCELLED')
                                            $cls = 'status-cancelled';
                                        ?>
                                        <span class="status-badge <?php echo $cls; ?>">
                                            <?php echo $status; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" style="text-align:center; padding: 2rem; color: #64748B;">No recent events
                                    found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- RIGHT COLUMN -->
    <div style="display: flex; flex-direction: column; gap: 2rem;">

        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">Quick Actions</span>
            </div>
            <div class="card-body">
                <div class="quick-actions-grid">
                    <a href="/alumni-system/admin/users/manage_users.php" class="action-card">
                        <div class="action-card-icon"><i data-lucide="user-plus" size="24"></i></div>
                        <span class="action-label">Manage Users</span>
                    </a>
                    <a href="/alumni-system/admin/notifications/manage_notifications.php" class="action-card">
                        <div class="action-card-icon"><i data-lucide="bell" size="24"></i></div>
                        <span class="action-label">Send Notif</span>
                    </a>
                    <a href="/alumni-system/admin/feedback/manage_feedback.php" class="action-card">
                        <div class="action-card-icon"><i data-lucide="message-square" size="24"></i></div>
                        <span class="action-label">Review Feeds</span>
                    </a>
                    <a href="/alumni-system/admin/settings.php" class="action-card">
                        <div class="action-card-icon"><i data-lucide="settings" size="24"></i></div>
                        <span class="action-label">Settings</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- System Health -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">System Health</span>
            </div>
            <div class="card-body">
                <div class="health-list">
                    <?php if ($healthRes && $healthRes->num_rows > 0): ?>
                        <?php while ($row = $healthRes->fetch_assoc()): ?>
                            <div class="health-item">
                                <div class="health-info">
                                    <i data-lucide="activity" class="health-icon" size="18"></i>
                                    <span class="health-text"><?php echo htmlspecialchars($row['module']); ?></span>
                                </div>
                                <?php
                                $status = $row['status'];
                                $badgeClass = 'health-ok';
                                if ($status === 'Warning')
                                    $badgeClass = 'health-warning';
                                if ($status === 'Critical')
                                    $badgeClass = 'health-critical';
                                ?>
                                <span class="health-pill <?php echo $badgeClass; ?>">
                                    <?php echo $status; ?>
                                </span>
                            </div>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    // Chart.js - User Roles Distribution
    const ctx = document.getElementById('roleChart').getContext('2d');
    const roleChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Students', 'Alumni'],
            datasets: [{
                data: [<?php echo $kpi['total_students']; ?>, <?php echo $kpi['total_alumni']; ?>],
                backgroundColor: ['#2563EB', '#10B981'],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'right', labels: { usePointStyle: true, font: { family: 'Inter', size: 12 } } }
            },
            cutout: '75%'
        }
    });
</script>

<?php
$content = ob_get_clean();
include 'layout.php';
?>