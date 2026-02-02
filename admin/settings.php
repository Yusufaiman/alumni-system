<?php
/**
 * ADMIN - SYSTEM SETTINGS
 * Manage platform configuration.
 */
session_start();
require_once "../config/db.php";
require_once "../config/functions.php";

// Access Control
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'ADMIN') {
    header("Location: ../auth/login.php");
    exit();
}

$successMsg = "";
$errorMsg = "";

// Handle Settings Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_settings') {
    $updatedBy = $_SESSION['userID'];
    unset($_POST['action']); // Remove action from post data

    // In a real system, we might want to handle checkboxes/toggles specifically
    // as they don't send anything if unchecked.
    $toggles = [
        'allow_student_registration',
        'allow_alumni_registration',
        'require_admin_approval_alumni',
        'enable_in_app_notifications',
        'enable_system_announcements',
        'allow_event_registration_after_full',
        'allow_alumni_create_events',
        'force_logout_inactivity'
    ];

    $conn->begin_transaction();
    try {
        // Handle normal inputs
        foreach ($_POST as $key => $value) {
            $stmt = $conn->prepare("UPDATE system_settings SET settingValue = ?, updatedBy = ?, updatedDate = NOW() WHERE settingKey = ?");
            $stmt->bind_param("sis", $value, $updatedBy, $key);
            $stmt->execute();
        }

        // Handle toggles (checkboxes) - if not set in POST, it means it's off (0)
        foreach ($toggles as $toggle) {
            $val = isset($_POST[$toggle]) ? '1' : '0';
            $stmt = $conn->prepare("UPDATE system_settings SET settingValue = ?, updatedBy = ?, updatedDate = NOW() WHERE settingKey = ?");
            $stmt->bind_param("sis", $val, $updatedBy, $toggle);
            $stmt->execute();
        }

        $conn->commit();
        logActivity($conn, $updatedBy, 'ADMIN', 'Update Settings', 'Settings', 'Updated platform configuration settings');
        $successMsg = "System settings updated successfully.";
    } catch (Exception $e) {
        $conn->rollback();
        $errorMsg = "Failed to update settings: " . $e->getMessage();
    }
}

// Fetch all settings
$res = $conn->query("SELECT * FROM system_settings");
$currentSettings = [];
while ($row = $res->fetch_assoc()) {
    $currentSettings[$row['settingKey']] = $row['settingValue'];
}

$pageTitle = "Settings";
ob_start();
?>

<style>
    .settings-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1.5rem;
    }

    .settings-section-title {
        font-size: 1.125rem;
        font-weight: 700;
        color: var(--text-dark);
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .form-group {
        margin-bottom: 1.25rem;
    }

    .form-group label {
        display: block;
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--text-dark);
        margin-bottom: 0.5rem;
    }

    .form-control {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        font-family: inherit;
        font-size: 0.875rem;
        background: #F8FAFC;
        transition: border-color 0.2s;
    }

    .form-control:focus {
        border-color: var(--primary);
        background: #fff;
    }

    /* Switch Component */
    .switch-group {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.75rem 0;
        border-bottom: 1px solid var(--border);
    }

    .switch-group:last-child {
        border-bottom: none;
    }

    .switch-info {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    .switch-label {
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--text-dark);
    }

    .switch-desc {
        font-size: 0.75rem;
        color: var(--text-medium);
    }

    .switch {
        position: relative;
        display: inline-block;
        width: 44px;
        height: 24px;
        flex-shrink: 0;
    }

    .switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #CBD5E1;
        transition: .4s;
        border-radius: 24px;
    }

    .slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: .4s;
        border-radius: 50%;
    }

    input:checked+.slider {
        background-color: var(--primary);
    }

    input:checked+.slider:before {
        transform: translateX(20px);
    }

    .btn-save-global {
        background: var(--primary);
        color: white;
        border: none;
        padding: 0.75rem 2rem;
        border-radius: var(--radius-md);
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: background 0.2s;
        margin-top: 1rem;
    }

    .btn-save-global:hover {
        background: var(--primary-hover);
    }

    .alert {
        padding: 1rem;
        border-radius: var(--radius-md);
        margin-bottom: 1.5rem;
        font-size: 0.875rem;
        font-weight: 500;
    }

    .alert-success {
        background: var(--success-bg);
        color: var(--success-text);
        border: 1px solid #BBF7D0;
    }

    .alert-error {
        background: var(--danger-bg);
        color: var(--danger-text);
        border: 1px solid #FECACA;
    }
</style>

<div class="page-header">
    <h1 class="page-title">Settings</h1>
    <p class="page-desc">Manage system configuration and administrative preferences</p>
</div>

<?php if ($successMsg): ?>
    <div class="alert alert-success">
        <?php echo $successMsg; ?>
    </div>
<?php endif; ?>
<?php if ($errorMsg): ?>
    <div class="alert alert-error">
        <?php echo $errorMsg; ?>
    </div>
<?php endif; ?>

<form method="POST">
    <input type="hidden" name="action" value="update_settings">

    <div class="settings-grid">
        <!-- 4.1 General System Settings -->
        <div class="card">
            <div class="card-header">
                <div class="settings-section-title">
                    <i data-lucide="monitor" size="18"></i> General System Settings
                </div>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label>System Name</label>
                    <input type="text" name="system_name" class="form-control"
                        value="<?php echo htmlspecialchars($currentSettings['system_name'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label>System Description</label>
                    <textarea name="system_description" class="form-control"
                        rows="3"><?php echo htmlspecialchars($currentSettings['system_description'] ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                    <label>Default Language</label>
                    <select name="default_language" class="form-control">
                        <option value="English" <?php echo ($currentSettings['default_language'] ?? '') === 'English' ? 'selected' : ''; ?>>English</option>
                        <option value="Malay" <?php echo ($currentSettings['default_language'] ?? '') === 'Malay' ? 'selected' : ''; ?>>Malay (Bahasa Melayu)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Default Time Zone</label>
                    <select name="default_timezone" class="form-control">
                        <option value="UTC+8 (Kuala Lumpur)" <?php echo ($currentSettings['default_timezone'] ?? '') === 'UTC+8 (Kuala Lumpur)' ? 'selected' : ''; ?>>UTC+8 (Kuala Lumpur)</option>
                        <option value="UTC+0 (Greenwich)" <?php echo ($currentSettings['default_timezone'] ?? '') === 'UTC+0 (Greenwich)' ? 'selected' : ''; ?>>UTC+0 (Greenwich)</option>
                        <option value="UTC-5 (New York)" <?php echo ($currentSettings['default_timezone'] ?? '') === 'UTC-5 (New York)' ? 'selected' : ''; ?>>UTC-5 (New York)</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- 4.2 User & Access Settings -->
        <div class="card">
            <div class="card-header">
                <div class="settings-section-title">
                    <i data-lucide="shield-check" size="18"></i> User & Access Settings
                </div>
            </div>
            <div class="card-body">
                <div class="switch-group">
                    <div class="switch-info">
                        <span class="switch-label">Allow Student Registration</span>
                        <span class="switch-desc">Permit new students to create accounts</span>
                    </div>
                    <label class="switch">
                        <input type="checkbox" name="allow_student_registration" <?php echo ($currentSettings['allow_student_registration'] ?? '0') === '1' ? 'checked' : ''; ?>>
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="switch-group">
                    <div class="switch-info">
                        <span class="switch-label">Allow Alumni Registration</span>
                        <span class="switch-desc">Permit new alumni to create accounts</span>
                    </div>
                    <label class="switch">
                        <input type="checkbox" name="allow_alumni_registration" <?php echo ($currentSettings['allow_alumni_registration'] ?? '0') === '1' ? 'checked' : ''; ?>>
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="switch-group">
                    <div class="switch-info">
                        <span class="switch-label">Require Admin Approval for Alumni Accounts</span>
                        <span class="switch-desc">All new alumni accounts must be manually approved</span>
                    </div>
                    <label class="switch">
                        <input type="checkbox" name="require_admin_approval_alumni" <?php echo ($currentSettings['require_admin_approval_alumni'] ?? '0') === '1' ? 'checked' : ''; ?>>
                        <span class="slider"></span>
                    </label>
                </div>
            </div>
        </div>

        <!-- 4.3 Notification Settings -->
        <div class="card">
            <div class="card-header">
                <div class="settings-section-title">
                    <i data-lucide="bell-ring" size="18"></i> Notification Settings
                </div>
            </div>
            <div class="card-body">
                <div class="switch-group">
                    <div class="switch-info">
                        <span class="switch-label">Enable In-App Notifications</span>
                        <span class="switch-desc">Master switch for real-time app notifications</span>
                    </div>
                    <label class="switch">
                        <input type="checkbox" name="enable_in_app_notifications" <?php echo ($currentSettings['enable_in_app_notifications'] ?? '0') === '1' ? 'checked' : ''; ?>>
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="switch-group">
                    <div class="switch-info">
                        <span class="switch-label">Enable System Announcements</span>
                        <span class="switch-desc">Allow broadcasting to the topbar/news feed</span>
                    </div>
                    <label class="switch">
                        <input type="checkbox" name="enable_system_announcements" <?php echo ($currentSettings['enable_system_announcements'] ?? '0') === '1' ? 'checked' : ''; ?>>
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="form-group" style="padding-top: 1rem;">
                    <label>Default Notification Audience</label>
                    <select name="default_notification_audience" class="form-control">
                        <option value="ALL" <?php echo ($currentSettings['default_notification_audience'] ?? '') === 'ALL' ? 'selected' : ''; ?>>All Users</option>
                        <option value="STUDENT" <?php echo ($currentSettings['default_notification_audience'] ?? '') === 'STUDENT' ? 'selected' : ''; ?>>Students Only</option>
                        <option value="ALUMNI" <?php echo ($currentSettings['default_notification_audience'] ?? '') === 'ALUMNI' ? 'selected' : ''; ?>>Alumni Only</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- 4.4 Event & Content Settings -->
        <div class="card">
            <div class="card-header">
                <div class="settings-section-title">
                    <i data-lucide="layers" size="18"></i> Event & Content Settings
                </div>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label>Default Event Capacity</label>
                    <input type="number" name="default_event_capacity" class="form-control"
                        value="<?php echo htmlspecialchars($currentSettings['default_event_capacity'] ?? '50'); ?>"
                        min="1">
                </div>
                <div class="switch-group">
                    <div class="switch-info">
                        <span class="switch-label">Allow Event Registration After Capacity</span>
                        <span class="switch-desc">Permit users to join a waitlist or register if full</span>
                    </div>
                    <label class="switch">
                        <input type="checkbox" name="allow_event_registration_after_full" <?php echo ($currentSettings['allow_event_registration_after_full'] ?? '0') === '1' ? 'checked' : ''; ?>>
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="switch-group">
                    <div class="switch-info">
                        <span class="switch-label">Allow Alumni to Create Events</span>
                        <span class="switch-desc">Grant event publishing permissions to trusted alumni</span>
                    </div>
                    <label class="switch">
                        <input type="checkbox" name="allow_alumni_create_events" <?php echo ($currentSettings['allow_alumni_create_events'] ?? '0') === '1' ? 'checked' : ''; ?>>
                        <span class="slider"></span>
                    </label>
                </div>
            </div>
        </div>

        <!-- 4.5 Security Settings -->
        <div class="card">
            <div class="card-header">
                <div class="settings-section-title">
                    <i data-lucide="shield-alert" size="18"></i> Security Settings
                </div>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label>Session Timeout Duration (Minutes)</label>
                    <input type="number" name="session_timeout_duration" class="form-control"
                        value="<?php echo htmlspecialchars($currentSettings['session_timeout_duration'] ?? '30'); ?>"
                        min="5">
                </div>
                <div class="switch-group">
                    <div class="switch-info">
                        <span class="switch-label">Force Logout After Inactivity</span>
                        <span class="switch-desc">Immediately terminate session on idle timeout</span>
                    </div>
                    <label class="switch">
                        <input type="checkbox" name="force_logout_inactivity" <?php echo ($currentSettings['force_logout_inactivity'] ?? '0') === '1' ? 'checked' : ''; ?>>
                        <span class="slider"></span>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <div style="display: flex; justify-content: flex-end; margin-bottom: 3rem;">
        <button type="submit" class="btn-save-global">
            <i data-lucide="save" size="18"></i> Save All Settings
        </button>
    </div>
</form>

<?php
$content = ob_get_clean();
include 'layout.php';
?>