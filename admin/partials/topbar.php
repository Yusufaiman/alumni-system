<header class="top-nav">
    <div class="search-container">
        <i data-lucide="search" class="search-icon"></i>
        <input type="text" class="search-input" placeholder="Search users, events, data...">
    </div>

    <div class="header-actions">
        <!-- Notification Link -->
        <a href="/alumni-system/admin/notifications/manage_notifications.php" class="btn-icon"
            style="display:inline-flex; align-items:center; justify-content:center; text-decoration:none;">
            <i data-lucide="bell" size="20"></i>
            <span class="badge"></span>
        </a>

        <!-- Profile Dropdown -->
        <div style="position: relative;">
            <div style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer;"
                onclick="document.getElementById('adminDropdown').classList.toggle('show')">
                <div class="admin-avatar"
                    style="background: var(--primary); width: 32px; height: 32px; font-size: 0.875rem;">A</div>
            </div>

            <!-- Dropdown Menu -->
            <div id="adminDropdown"
                style="display: none; position: absolute; right: 0; top: 120%; background: white; border: 1px solid var(--border); border-radius: 8px; box-shadow: var(--shadow-md); width: 150px; z-index: 100; overflow:hidden;">
                <a href="/alumni-system/admin/settings.php"
                    style="display: block; padding: 10px 15px; color: var(--text-dark); text-decoration: none; font-size: 0.875rem; border-bottom:1px solid var(--border);">Settings</a>
                <a href="/alumni-system/auth/logout.php"
                    style="display: block; padding: 10px 15px; color: var(--danger-text); text-decoration: none; font-size: 0.875rem; background: var(--danger-bg);">Logout</a>
            </div>
        </div>
    </div>
    <style>
        .show {
            display: block !important;
        }
    </style>
    <script>
        // Close dropdown when clicking outside
        document.addEventListener('click', function (e) {
            const dropdown = document.getElementById('adminDropdown');
            const avatar = document.querySelector('.admin-avatar');
            if (dropdown && !dropdown.contains(e.target) && !avatar.contains(e.target)) {
                dropdown.classList.remove('show');
            }
        });
    </script>
</header>