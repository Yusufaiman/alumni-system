<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <i data-lucide="layout-grid" style="color: white;"></i>
        <span class="sidebar-brand">AlumniAdmin</span>
    </div>

    <nav class="nav-menu">
        <a href="/alumni-system/admin/dashboard.php"
            class="nav-item <?php echo $currentPage == 'dashboard.php' ? 'active' : ''; ?>">
            <i data-lucide="bar-chart-2" size="18"></i> Dashboard
        </a>
        <a href="/alumni-system/admin/users/manage_users.php"
            class="nav-item <?php echo $currentPage == 'manage_users.php' ? 'active' : ''; ?>">
            <i data-lucide="users" size="18"></i> Users
        </a>
        <a href="/alumni-system/admin/events/manage_events.php"
            class="nav-item <?php echo $currentPage == 'manage_events.php' ? 'active' : ''; ?>">
            <i data-lucide="calendar" size="18"></i> Events
        </a>
        <a href="/alumni-system/admin/notifications/manage_notifications.php"
            class="nav-item <?php echo $currentPage == 'manage_notifications.php' ? 'active' : ''; ?>">
            <i data-lucide="bell" size="18"></i> Notifications
        </a>
        <a href="/alumni-system/admin/feedback/manage_feedback.php"
            class="nav-item <?php echo $currentPage == 'manage_feedback.php' ? 'active' : ''; ?>">
            <i data-lucide="message-square" size="18"></i> Feedback
        </a>
        <a href="/alumni-system/admin/settings.php"
            class="nav-item <?php echo $currentPage == 'settings.php' ? 'active' : ''; ?>">
            <i data-lucide="settings" size="18"></i> Settings
        </a>
        <div style="height: 1px; background: rgba(255,255,255,0.1); margin: 0.5rem 0;"></div>
        <a href="/alumni-system/admin/reports.php"
            class="nav-item <?php echo $currentPage == 'reports.php' ? 'active' : ''; ?>">
            <i data-lucide="file-text" size="18"></i> Reports
        </a>
        <a href="/alumni-system/admin/activity_logs.php"
            class="nav-item <?php echo $currentPage == 'activity_logs.php' ? 'active' : ''; ?>">
            <i data-lucide="activity" size="18"></i> Activity Logs
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="admin-profile">
            <div class="admin-avatar">A</div>
            <div style="display: flex; flex-direction: column;">
                <span style="font-size: 0.875rem; font-weight: 600; color: white;">Super Admin</span>
                <span style="font-size: 0.75rem; color: var(--text-medium);">admin@system.com</span>
            </div>
        </div>
    </div>
</aside>