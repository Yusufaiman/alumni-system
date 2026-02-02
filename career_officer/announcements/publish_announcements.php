<?php
/**
 * CAREER SERVICE OFFICER - PUBLISH ANNOUNCEMENTS
 * Create and manage announcements for students and alumni.
 */
session_start();
require_once "../../config/db.php";

// Access Control: CSO Only
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'CAREER_SERVICE_OFFICER') {
    header("Location: ../../auth/login.php");
    exit();
}

$userID = $_SESSION['userID'];
$successMsg = "";
$errorMsg = "";

// 1. Handle Create Announcement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['publish_announcement'])) {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $status = $_POST['status'];

    if (empty($title) || empty($content)) {
        $errorMsg = "Title and Content are required.";
    } else {
        $sql = "INSERT INTO announcement (title, content, createdBy, createdRole, createdDate, status) 
                VALUES (?, ?, ?, 'CAREER_OFFICER', CURDATE(), ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssis", $title, $content, $userID, $status);

        if ($stmt->execute()) {
            header("Location: publish_announcements.php?success=published");
            exit();
        } else {
            $errorMsg = "Error: " . $conn->error;
        }
    }
}

// 2. Handle Status Toggle
if (isset($_GET['toggle_status']) && isset($_GET['id'])) {
    $announcementID = intval($_GET['id']);
    $newStatus = $_GET['toggle_status'];

    if (in_array($newStatus, ['ACTIVE', 'ARCHIVED'])) {
        $sql = "UPDATE announcement SET status = ? WHERE announcementID = ? AND createdBy = ? AND createdRole = 'CAREER_OFFICER'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sii", $newStatus, $announcementID, $userID);

        if ($stmt->execute()) {
            header("Location: publish_announcements.php?status_updated=1");
            exit();
        }
    }
}

// 3. Fetch Announcements created by this CSO
$sql = "SELECT announcementID, title, createdDate, status 
        FROM announcement 
        WHERE createdBy = ? AND createdRole = 'CAREER_OFFICER' 
        ORDER BY createdDate DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userID);
$stmt->execute();
$announcements = $stmt->get_result();

$isDashboard = false;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publish Announcements | CSO Dashboard</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- CSS -->
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <style>
        .publish-container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }

        .section-card {
            background: white;
            border-radius: 1rem;
            border: 1px solid var(--border-light);
            padding: 2.5rem;
            box-shadow: var(--card-shadow);
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            border: 1px solid var(--border-light);
            font-size: 1rem;
            font-family: inherit;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-blue);
        }

        .btn-submit {
            background: var(--primary-blue);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 0.5rem;
            font-weight: 700;
            width: 100%;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn-submit:hover {
            background: #2563EB;
            transform: translateY(-1px);
        }

        .manage-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        .manage-table th {
            background: #F9FAFB;
            padding: 1rem 1.5rem;
            font-weight: 700;
            color: var(--text-dark);
            border-bottom: 1px solid var(--border-light);
        }

        .manage-table td {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border-light);
        }

        .badge {
            font-size: 0.7rem;
            font-weight: 700;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            text-transform: uppercase;
        }

        .badge-active {
            background: #DCFCE7;
            color: #166534;
        }

        .badge-archived {
            background: #F3F4F6;
            color: #4B5563;
        }

        .alert {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 2rem;
            font-weight: 600;
            text-align: center;
        }

        .alert-success {
            background: #DCFCE7;
            color: #166534;
            border: 1px solid #BBF7D0;
        }

        .alert-error {
            background: #FEE2E2;
            color: #991B1B;
            border: 1px solid #FECACA;
        }

        .action-link {
            color: var(--primary-blue);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .action-link:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <?php include_once __DIR__ . '/../../components/topbar.php'; ?>

    <div class="publish-container">
        <div style="margin-bottom: 1rem;">
            <a href="../dashboard.php"
                style="color: var(--primary-blue); text-decoration: none; font-size: 0.875rem; font-weight: 600;">&larr;
                Back to Dashboard</a>
        </div>

        <header style="margin-bottom: 3rem;">
            <h1 style="font-size: 2.25rem; color: var(--text-dark); margin-bottom: 0.5rem;">Publish Announcements</h1>
            <p style="color: var(--text-muted); font-size: 1.125rem;">Create and manage career-related announcements for
                students and alumni.</p>
        </header>

        <?php if ($successMsg || isset($_GET['status_updated']) || (isset($_GET['success']) && $_GET['success'] === 'published')): ?>
            <div class="alert alert-success">
                <?php
                if (isset($_GET['success']) && $_GET['success'] === 'published')
                    echo "Announcement published successfully.";
                elseif ($successMsg)
                    echo $successMsg;
                else
                    echo "Status updated successfully.";
                ?>
            </div>
        <?php endif; ?>

        <?php if ($errorMsg): ?>
            <div class="alert alert-error">
                <?php echo $errorMsg; ?>
            </div>
        <?php endif; ?>

        <!-- Section 1: Create Announcement -->
        <div class="section-card">
            <h2 style="font-size: 1.5rem; color: var(--text-dark); margin-bottom: 1.5rem;">New Announcement</h2>
            <form action="" method="POST">
                <div class="form-group">
                    <label for="title">Title *</label>
                    <input type="text" name="title" id="title" class="form-control" maxlength="150"
                        placeholder="Enter announcement title..." required>
                </div>
                <div class="form-group">
                    <label for="status">Initial Status</label>
                    <select name="status" id="status" class="form-control">
                        <option value="ACTIVE">ACTIVE</option>
                        <option value="ARCHIVED">ARCHIVED</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="content">Content *</label>
                    <textarea name="content" id="content" class="form-control" rows="6"
                        placeholder="Describe the announcement details..." required></textarea>
                </div>
                <button type="submit" name="publish_announcement" class="btn-submit">Publish Announcement</button>
            </form>
        </div>

        <!-- Section 2: Manage Announcements -->
        <div class="section-card" style="padding: 0; overflow: hidden;">
            <div style="padding: 1.5rem 2.5rem; border-bottom: 1px solid var(--border-light);">
                <h2 style="font-size: 1.5rem; color: var(--text-dark);">Manage Existing Announcements</h2>
            </div>
            <table class="manage-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Created Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($announcements->num_rows > 0): ?>
                        <?php while ($row = $announcements->fetch_assoc()): ?>
                            <tr>
                                <td style="font-weight: 600; color: var(--text-dark);">
                                    <?php echo htmlspecialchars($row['title']); ?>
                                </td>
                                <td style="color: var(--text-muted);">
                                    <?php echo date('d M Y', strtotime($row['createdDate'])); ?>
                                </td>
                                <td>
                                    <span
                                        class="badge <?php echo $row['status'] === 'ACTIVE' ? 'badge-active' : 'badge-archived'; ?>">
                                        <?php echo $row['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($row['status'] === 'ACTIVE'): ?>
                                        <a href="?toggle_status=ARCHIVED&id=<?php echo $row['announcementID']; ?>"
                                            class="action-link" style="color: #6B7280;"
                                            onclick="return confirm('Archive this announcement?')">Archive</a>
                                    <?php else: ?>
                                        <a href="?toggle_status=ACTIVE&id=<?php echo $row['announcementID']; ?>" class="action-link"
                                            onclick="return confirm('Reactivate this announcement?')">Activate</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 3rem; color: var(--text-muted);">
                                No announcements published yet.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Dropdown Interaction Logic -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const profileBtn = document.getElementById('profileBtn');
            const profileDropdown = document.getElementById('profileDropdown');
            if (profileBtn && profileDropdown) {
                profileBtn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    profileDropdown.classList.toggle('show');
                });
                document.addEventListener('click', function (e) {
                    if (!profileDropdown.contains(e.target) && !profileBtn.contains(e.target)) {
                        profileDropdown.classList.remove('show');
                    }
                });
            }
        });
    </script>
</body>

</html>