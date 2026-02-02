<?php
/**
 * ALUMNI - NOTIFICATIONS
 * View system and personal notifications.
 */
session_start();
require_once "../config/db.php";

// Access Control
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'ALUMNI') {
    header("Location: ../auth/login.php");
    exit();
}

$userID = $_SESSION['userID'];

// Handle AJAX actions
if (isset($_POST['action'])) {
    if ($_POST['action'] === 'read' && isset($_POST['id'])) {
        $notifID = intval($_POST['id']);
        $stmt = mysqli_prepare($conn, "UPDATE notification SET status = 'READ' WHERE notificationID = ? AND targetUserID = ?");
        mysqli_stmt_bind_param($stmt, "ii", $notifID, $userID);
        mysqli_stmt_execute($stmt);
        echo json_encode(['success' => true]);
        exit();
    }
    if ($_POST['action'] === 'read_all') {
        $stmt = mysqli_prepare($conn, "UPDATE notification SET status = 'READ' WHERE targetUserID = ? AND status = 'SENT'");
        mysqli_stmt_bind_param($stmt, "i", $userID);
        mysqli_stmt_execute($stmt);
        header("Location: notifications.php?success=all_read");
        exit();
    }
}

// Fetch Notifications for this user
$sql = "SELECT * FROM notification WHERE targetUserID = ? ORDER BY (status = 'SENT') DESC, sentDate DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $userID);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$notifications = [];
while ($row = mysqli_fetch_assoc($result)) {
    $notifications[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications | Alumni Connect</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .notif-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }

        .notif-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .notif-item {
            background: white;
            padding: 1.5rem;
            border-radius: 1rem;
            border: 1px solid var(--border-light);
            margin-bottom: 1rem;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
        }

        .notif-item:hover {
            transform: translateY(-2px);
            box-shadow: var(--card-shadow);
        }

        .notif-item.unread {
            border-left: 4px solid var(--primary-blue);
        }

        .notif-item.unread .notif-title {
            font-weight: 700;
        }

        .notif-title {
            font-size: 1rem;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .notif-badge {
            width: 8px;
            height: 8px;
            background: var(--primary-blue);
            border-radius: 50%;
        }

        .notif-message {
            color: var(--text-muted);
            font-size: 0.875rem;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .notif-item.expanded .notif-message {
            display: block;
            -webkit-line-clamp: unset;
        }

        .notif-meta {
            margin-top: 1rem;
            font-size: 0.75rem;
            color: var(--text-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .btn-mark-all {
            background: #F1F5F9;
            color: var(--text-dark);
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-mark-all:hover {
            background: #E2E8F0;
        }

        .empty-state {
            text-align: center;
            padding: 5rem 2rem;
            background: white;
            border-radius: 1rem;
            border: 1px dashed var(--border-light);
        }
    </style>
</head>

<body>
    <?php include_once __DIR__ . '/../components/topbar.php'; ?>

    <div class="notif-container">
        <div class="notif-header">
            <div>
                <h1 style="font-size: 1.75rem; color: var(--text-dark);">Notifications</h1>
                <p style="color: var(--text-muted); font-size: 0.875rem;">Stay updated with latest activities and news.
                </p>
            </div>
            <?php if (count($notifications) > 0): ?>
                <form method="POST">
                    <input type="hidden" name="action" value="read_all">
                    <button type="submit" class="btn-mark-all">Mark all as read</button>
                </form>
            <?php endif; ?>
        </div>

        <?php if (count($notifications) > 0): ?>
            <div class="notif-list">
                <?php foreach ($notifications as $n): ?>
                    <div class="notif-item <?php echo $n['status'] === 'SENT' ? 'unread' : ''; ?>"
                        onclick="toggleNotif(this, <?php echo $n['notificationID']; ?>, '<?php echo $n['status']; ?>')">
                        <div class="notif-title">
                            <?php if ($n['status'] === 'SENT'): ?><span class="notif-badge"></span>
                            <?php endif; ?>
                            <?php echo htmlspecialchars($n['title']); ?>
                        </div>
                        <div class="notif-message">
                            <?php echo htmlspecialchars($n['message']); ?>
                        </div>
                        <div class="notif-meta">
                            <span>
                                <?php echo date('M d, Y • h:i A', strtotime($n['sentDate'])); ?>
                            </span>
                            <?php if ($n['link']): ?>
                                <a href="<?php echo htmlspecialchars($n['link']); ?>"
                                    style="color: var(--primary-blue); font-weight: 600; text-decoration: none;">View Detail
                                    &rarr;</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div style="font-size: 3rem; margin-bottom: 1rem;">🔔</div>
                <h3 style="color: var(--text-dark); margin-bottom: 0.5rem;">No new notifications</h3>
                <p style="color: var(--text-muted);">You're all caught up! When you get a new alert, it will appear here.
                </p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function toggleNotif(element, id, currentStatus) {
            // Expand/Collapse message
            element.classList.toggle('expanded');

            // If unread, mark as read via AJAX
            if (element.classList.contains('unread')) {
                const formData = new FormData();
                formData.append('action', 'read');
                formData.append('id', id);

                fetch('notifications.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            element.classList.remove('unread');
                            const badge = element.querySelector('.notif-badge');
                            if (badge) badge.remove();

                            // Update the topbar badge count
                            if (typeof updateNotificationBadge === 'function') {
                                updateNotificationBadge();
                            }
                        }
                    })
                    .catch(err => console.error('Error marking as read:', err));
            }
        }
    </script>
</body>

</html>