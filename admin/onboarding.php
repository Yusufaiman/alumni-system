<?php
/**
 * ADMIN ONBOARDING FLOW
 * Mandatory setup wizard for new admins.
 */
session_start();
require_once "../config/db.php";

// Access Control
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'ADMIN') {
    header("Location: ../auth/login.php");
    exit();
}

$userID = $_SESSION['userID'];

// Check if already onboarded
$stmt = $conn->prepare("SELECT onboarded, adminID FROM admin WHERE userID = ?");
$stmt->bind_param("i", $userID);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    // Admin profile doesn't exist? Create one default if user is ADMIN role.
    // This handles edge case where user has role but no profile row.
    $conn->query("INSERT INTO admin (userID, role, onboarded) VALUES ($userID, 'SUPER_ADMIN', 0)");
    $adminID = $conn->insert_id;
    $onboarded = 0;
} else {
    $row = $res->fetch_assoc();
    $onboarded = $row['onboarded'];
    $adminID = $row['adminID'];
}

if ($onboarded == 1 && !isset($_GET['reset'])) {
    header("Location: dashboard.php");
    exit();
}

// Handle Steps
$step = isset($_GET['step']) ? intval($_GET['step']) : 1;
$errorMsg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['step_1'])) {
        // Identity Setup
        $fullName = trim($_POST['fullName']);
        $contact = trim($_POST['contact']);
        $dept = trim($_POST['department']);

        if (empty($fullName)) {
            $errorMsg = "Full Name is required.";
        } else {
            $stmt = $conn->prepare("UPDATE admin SET full_name = ?, contact_number = ?, department = ? WHERE adminID = ?");
            $stmt->bind_param("sssi", $fullName, $contact, $dept, $adminID);
            $stmt->execute();
            header("Location: onboarding.php?step=2");
            exit();
        }
    } elseif (isset($_POST['step_2'])) {
        // System Config
        $settings = [
            'event_capacity' => $_POST['event_capacity'],
            'event_autoclose' => isset($_POST['event_autoclose']) ? '1' : '0',
            'alumni_updates' => isset($_POST['alumni_updates']) ? '1' : '0',
            'public_announcements' => isset($_POST['public_announcements']) ? '1' : '0',
            'feedback_module' => isset($_POST['feedback_module']) ? '1' : '0'
        ];

        foreach ($settings as $key => $val) {
            $stmt = $conn->prepare("INSERT INTO admin_system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->bind_param("sss", $key, $val, $val);
            $stmt->execute();
        }
        header("Location: onboarding.php?step=3");
        exit();

    } elseif (isset($_POST['step_3'])) {
        // Notification & Security
        $settings = [
            'notify_system' => isset($_POST['notify_system']) ? '1' : '0',
            'notify_event' => isset($_POST['notify_event']) ? '1' : '0',
            'notify_feedback' => isset($_POST['notify_feedback']) ? '1' : '0',
            'security_logging' => isset($_POST['security_logging']) ? '1' : '0',
            'session_timeout' => $_POST['session_timeout'],
            'multi_session' => isset($_POST['multi_session']) ? '1' : '0'
        ];

        foreach ($settings as $key => $val) {
            $stmt = $conn->prepare("INSERT INTO admin_system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->bind_param("sss", $key, $val, $val);
            $stmt->execute();
        }
        header("Location: onboarding.php?step=4");
        exit();

    } elseif (isset($_POST['complete_onboarding'])) {
        // Activate System
        $conn->query("UPDATE admin SET onboarded = 1 WHERE adminID = $adminID");

        // Log Activity
        $conn->query("INSERT INTO admin_activity_log (adminID, action, module, description) VALUES ($adminID, 'ONBOARDING', 'SYSTEM', 'Admin completed initial onboarding.')");

        header("Location: dashboard.php");
        exit();
    }
}

// Fetch Admin Data for Review (Step 4)
if ($step == 4) {
    $adminData = $conn->query("SELECT * FROM admin WHERE adminID = $adminID")->fetch_assoc();
    $settingsRes = $conn->query("SELECT * FROM admin_system_settings");
    $settings = [];
    while ($s = $settingsRes->fetch_assoc()) {
        $settings[$s['setting_key']] = $s['setting_value'];
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>System Setup | Alumni Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #F1F5F9;
            color: #1E293B;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }

        .wizard-container {
            background: white;
            padding: 2.5rem;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 600px;
        }

        .progress-bar {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 2rem;
        }

        .progress-step {
            flex: 1;
            height: 6px;
            background: #E2E8F0;
            border-radius: 3px;
            position: relative;
        }

        .progress-step.active {
            background: #2563EB;
        }

        .progress-step.completed {
            background: #10B981;
        }

        h1 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: #0F172A;
        }

        p {
            color: #64748B;
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #E2E8F0;
            border-radius: 0.5rem;
            font-family: inherit;
            font-size: 1rem;
        }

        .toggle-label {
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            padding: 0.75rem;
            border: 1px solid #E2E8F0;
            border-radius: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .toggle-label:hover {
            background: #F8FAFC;
        }

        .btn-primary {
            background: #2563EB;
            color: white;
            border: none;
            padding: 0.875rem 2rem;
            border-radius: 0.5rem;
            font-weight: 600;
            width: 100%;
            cursor: pointer;
            font-size: 1rem;
        }

        .btn-primary:hover {
            background: #1D4ED8;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 2rem;
            border: 1px solid #E2E8F0;
            border-radius: 0.5rem;
            padding: 1rem;
        }

        .summary-item {
            font-size: 0.875rem;
        }

        .summary-label {
            color: #64748B;
            display: block;
            margin-bottom: 0.25rem;
        }

        .summary-val {
            font-weight: 600;
        }
    </style>
</head>

<body>

    <div class="wizard-container">
        <div class="progress-bar">
            <div
                class="progress-step <?php echo $step >= 1 ? 'active' : ''; ?> <?php echo $step > 1 ? 'completed' : ''; ?>">
            </div>
            <div
                class="progress-step <?php echo $step >= 2 ? 'active' : ''; ?> <?php echo $step > 2 ? 'completed' : ''; ?>">
            </div>
            <div
                class="progress-step <?php echo $step >= 3 ? 'active' : ''; ?> <?php echo $step > 3 ? 'completed' : ''; ?>">
            </div>
            <div class="progress-step <?php echo $step >= 4 ? 'active' : ''; ?>"></div>
        </div>

        <!-- STEP 1: Admin Identity -->
        <?php if ($step == 1): ?>
            <form method="POST">
                <h1>Admin Identity Setup</h1>
                <p>Verify your identity and administrative details.</p>

                <?php if ($errorMsg): ?>
                    <div style="color:red;margin-bottom:1rem;">
                        <?php echo $errorMsg; ?>
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="fullName" class="form-control" placeholder="e.g. Ahmad Razak" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Role</label>
                    <input type="text" value="System Administrator" class="form-control" disabled
                        style="background:#F1F5F9;">
                </div>
                <div class="form-group">
                    <label class="form-label">Contact Number</label>
                    <input type="text" name="contact" class="form-control" placeholder="+60...">
                </div>
                <div class="form-group">
                    <label class="form-label">Department / Unit</label>
                    <input type="text" name="department" class="form-control" placeholder="e.g. IT Services">
                </div>

                <button type="submit" name="step_1" class="btn-primary">Save & Continue</button>
            </form>
        <?php endif; ?>

        <!-- STEP 2: System Config -->
        <?php if ($step == 2): ?>
            <form method="POST">
                <h1>System Configuration</h1>
                <p>Initialize core platform settings.</p>

                <div class="form-group">
                    <label class="form-label">Default Event Capacity</label>
                    <input type="number" name="event_capacity" class="form-control" value="100">
                </div>

                <label class="toggle-label">
                    <span>Auto-Close Events When Full</span>
                    <input type="checkbox" name="event_autoclose" checked>
                </label>

                <label class="toggle-label">
                    <span>Allow Alumni Career Updates</span>
                    <input type="checkbox" name="alumni_updates" checked>
                </label>

                <label class="toggle-label">
                    <span>Allow Public Announcements</span>
                    <input type="checkbox" name="public_announcements" checked>
                </label>

                <label class="toggle-label">
                    <span>Enable Feedback Module</span>
                    <input type="checkbox" name="feedback_module" checked>
                </label>

                <button type="submit" name="step_2" class="btn-primary" style="margin-top: 1rem;">Save & Continue</button>
            </form>
        <?php endif; ?>

        <!-- STEP 3: Notifications -->
        <?php if ($step == 3): ?>
            <form method="POST">
                <h1>Notification & Security</h1>
                <p>Configure alerts and security parameters.</p>

                <h3 style="font-size:1rem; margin-bottom:0.5rem;">Notifications</h3>
                <label class="toggle-label">
                    <span>Enable System Notifications</span>
                    <input type="checkbox" name="notify_system" checked>
                </label>
                <label class="toggle-label">
                    <span>Notify Admin on New Events</span>
                    <input type="checkbox" name="notify_event">
                </label>
                <label class="toggle-label">
                    <span>Notify Admin on Feedback</span>
                    <input type="checkbox" name="notify_feedback" checked>
                </label>

                <h3 style="font-size:1rem; margin:1.5rem 0 0.5rem 0;">Security</h3>
                <label class="toggle-label">
                    <span>Enable Activity Logging</span>
                    <input type="checkbox" name="security_logging" checked>
                </label>

                <div class="form-group" style="margin-top:1rem;">
                    <label class="form-label">Session Timeout (Minutes)</label>
                    <input type="number" name="session_timeout" class="form-control" value="30">
                </div>

                <button type="submit" name="step_3" class="btn-primary" style="margin-top: 1rem;">Review Setup</button>
            </form>
        <?php endif; ?>

        <!-- STEP 4: Review -->
        <?php if ($step == 4): ?>
            <form method="POST">
                <div style="text-align:center; margin-bottom:2rem;">
                    <div style="font-size:3rem;">✅</div>
                    <h1>Ready to Activate</h1>
                    <p>Review your settings before launching the dashboard.</p>
                </div>

                <div class="summary-grid">
                    <div>
                        <span class="summary-label">Admin Name</span>
                        <span class="summary-val">
                            <?php echo htmlspecialchars($adminData['full_name']); ?>
                        </span>
                    </div>
                    <div>
                        <span class="summary-label">Department</span>
                        <span class="summary-val">
                            <?php echo htmlspecialchars($adminData['department']); ?>
                        </span>
                    </div>
                    <div>
                        <span class="summary-label">Event Capacity</span>
                        <span class="summary-val">
                            <?php echo htmlspecialchars($settings['event_capacity'] ?? '100'); ?>
                        </span>
                    </div>
                    <div>
                        <span class="summary-label">Feedback Module</span>
                        <span class="summary-val">
                            <?php echo ($settings['feedback_module'] == '1') ? 'Enabled' : 'Disabled'; ?>
                        </span>
                    </div>
                    <div>
                        <span class="summary-label">Security Logging</span>
                        <span class="summary-val">
                            <?php echo ($settings['security_logging'] == '1') ? 'Active' : 'Off'; ?>
                        </span>
                    </div>
                    <div>
                        <span class="summary-label">Timeout</span>
                        <span class="summary-val">
                            <?php echo htmlspecialchars($settings['session_timeout'] ?? '30'); ?> mins
                        </span>
                    </div>
                </div>

                <button type="submit" name="complete_onboarding" class="btn-primary">Confirm & Activate System</button>
                <a href="onboarding.php?step=1"
                    style="display:block; text-align:center; margin-top:1rem; color:#64748B; text-decoration:none;">Make
                    Changes</a>
            </form>
        <?php endif; ?>

    </div>

</body>

</html>