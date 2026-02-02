<?php
/**
 * REUSABLE TOPBAR COMPONENT
 * Included on all student pages.
 * Use $isDashboard = true; before including this to disable the back button.
 */
$isDashboard = isset($isDashboard) ? $isDashboard : false;
$userName = $_SESSION['name'] ?? 'User';
$avatarInitial = strtoupper(substr($userName, 0, 1));

// Robust dashboard link logic for both subdirectories and root access
$isInsideSubdir = (strpos($_SERVER['PHP_SELF'], '/student/') !== false ||
    strpos($_SERVER['PHP_SELF'], '/alumni/') !== false ||
    strpos($_SERVER['PHP_SELF'], '/career_officer/') !== false);

if (!$isInsideSubdir) {
    // We are in the root (e.g., notifications.php)
    $role = $_SESSION['role'] ?? 'STUDENT';
    $folder = 'student/';
    if ($role === 'ALUMNI')
        $folder = 'alumni/';
    if ($role === 'CAREER_SERVICE_OFFICER')
        $folder = 'career_officer/';

    $dashboardLink = $folder . "dashboard.php";
    $searchAction = "student/alumni_directory.php";
    $logoutAction = "auth/logout.php";
} else {
    // We are inside a subdirectory
    $dashboardLink = "dashboard.php";
    $isCareerOfficer = (strpos($_SERVER['PHP_SELF'], '/career_officer/') !== false);
    $isAlumni = (strpos($_SERVER['PHP_SELF'], '/alumni/') !== false);

    $searchAction = ($isAlumni || $isCareerOfficer ? '../student/' : '') . "alumni_directory.php";
    $logoutAction = "../auth/logout.php";
}
?>

<header class="top-bar">
    <div style="display: flex; align-items: center; gap: 0.75rem;">
        <?php if ($isDashboard): ?>
            <!-- Disabled Back Button on Dashboard -->
            <button class="nav-action-btn back-btn disabled" title="Already on Dashboard" disabled>
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
            </button>
        <?php else: ?>
            <!-- Active Back Button for other pages -->
            <a href="<?php echo $dashboardLink; ?>" class="nav-action-btn back-btn" title="Back to Dashboard"
                style="text-decoration: none;">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
            </a>
        <?php endif; ?>

        <a href="<?php echo $dashboardLink; ?>" class="logo-text">Alumni Connect</a>
    </div>

    <div class="search-container">
        <span class="search-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
        </span>
        <form action="<?php echo $searchAction; ?>" method="GET" style="width: 100%;">
            <input type="text" name="search" class="search-input" placeholder="Search alumni, jobs, events..."
                value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
        </form>
    </div>

    <nav class="user-nav">
        <!-- Notification Button -->
        <a href="<?php echo $isInsideSubdir ? '../' : ''; ?>notifications.php" class="nav-action-btn"
            title="Notifications">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
            </svg>
            <span class="notification-badge" id="navNotificationBadge" style="display: none;">0</span>
        </a>

        <!-- Profile Dropdown -->
        <div class="profile-dropdown-container">
            <button class="user-avatar-btn" id="profileBtn">
                <div class="avatar-circle">
                    <?php echo $avatarInitial; ?>
                </div>
                <svg class="dropdown-arrow" xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </button>

            <div class="dropdown-menu" id="profileDropdown">
                <a href="profile.php" class="dropdown-item">My Profile</a>
                <a href="settings.php" class="dropdown-item">Settings</a>
                <div class="dropdown-divider"></div>
                <form action="<?php echo $logoutAction; ?>" method="POST" class="logout-btn-form">
                    <button type="submit" class="dropdown-item" style="color: #DC2626;">Logout</button>
                </form>
            </div>
        </div>
    </nav>
</header>

<!-- Notification Polling Script -->
<script>
    function updateNotificationBadge() {
        const badge = document.getElementById('navNotificationBadge');
        if (!badge) return;

        fetch('<?php echo $isInsideSubdir ? '../' : ''; ?>api/get_unread_notifications.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.count > 0) {
                        badge.textContent = data.count > 99 ? '99+' : data.count;
                        badge.style.display = 'flex';
                    } else {
                        badge.style.display = 'none';
                    }
                }
            })
            .catch(err => console.error('Notification Error:', err));
    }

    // Initial check and set interval for polling every 10 seconds
    updateNotificationBadge();
    setInterval(updateNotificationBadge, 10000);
</script>