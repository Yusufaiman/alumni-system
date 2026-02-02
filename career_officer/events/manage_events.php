<?php
/**
 * CAREER SERVICE OFFICER - MANAGE EVENTS
 * List, status management, and dashboard for events.
 */
session_start();
require_once "../../config/db.php";

// Access Control: CSO Only
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'CAREER_SERVICE_OFFICER') {
    header("Location: ../../auth/login.php");
    exit();
}

$userID = $_SESSION['userID'];

// Fetch Events created by this CSO
// Also join to get registration counts
$sql = "
    SELECT 
        e.*,
        (SELECT COUNT(*) FROM eventregistration WHERE eventID = e.eventID AND status = 'CONFIRMED') as confirmedCount
    FROM event e
    WHERE e.createdBy = ?
    ORDER BY e.date DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userID);
$stmt->execute();
$events = $stmt->get_result();

$isDashboard = false;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Events | CSO Dashboard</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- CSS -->
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <link rel="stylesheet" href="../../assets/css/jobs.css">
    <style>
        .manage-events-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .btn-create {
            background: var(--primary-blue);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            text-decoration: none;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: 0.2s;
        }

        .btn-create:hover {
            background: #2563EB;
            transform: translateY(-1px);
        }

        .events-table-wrapper {
            background: white;
            border-radius: 1rem;
            border: 1px solid var(--border-light);
            overflow: hidden;
            box-shadow: var(--card-shadow);
        }

        .events-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        .events-table th {
            background: #F9FAFB;
            padding: 1rem 1.5rem;
            font-weight: 700;
            color: var(--text-dark);
            font-size: 0.875rem;
            border-bottom: 1px solid var(--border-light);
        }

        .events-table td {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border-light);
            vertical-align: middle;
        }

        .status-badge {
            font-size: 0.7rem;
            font-weight: 700;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            text-transform: uppercase;
        }

        .status-open {
            background: #DCFCE7;
            color: #166534;
        }

        .status-closed {
            background: #FEE2E2;
            color: #991B1B;
        }

        .status-cancelled {
            background: #F3F4F6;
            color: #4B5563;
        }

        .action-btns {
            display: flex;
            gap: 1rem;
        }

        .btn-action {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--primary-blue);
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
            text-decoration: none;
        }

        .btn-action:hover {
            text-decoration: underline;
        }

        .btn-cancel {
            color: #EF4444;
        }

        .empty-state {
            text-align: center;
            padding: 5rem 2rem;
            color: var(--text-muted);
        }
    </style>
</head>

<body>

    <!-- Shared Topbar -->
    <?php include_once __DIR__ . '/../../components/topbar.php'; ?>

    <div class="manage-events-container">
        <div style="margin-bottom: 1rem;">
            <a href="../dashboard.php"
                style="color: var(--primary-blue); text-decoration: none; font-size: 0.875rem; font-weight: 600;">&larr;
                Back to Dashboard</a>
        </div>

        <div class="header-actions">
            <div>
                <h1 style="font-size: 2rem; color: var(--text-dark); margin-bottom: 0.5rem;">Manage Events</h1>
                <p style="color: var(--text-muted);">Create and moderate upcoming alumni and student events.</p>
            </div>
            <a href="create_event.php" class="btn-create">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Create New Event
            </a>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div
                style="background: #DCFCE7; color: #166534; padding: 1rem; border-radius: 0.5rem; margin-bottom: 2rem; font-weight: 600; border: 1px solid #BBF7D0;">
                <?php
                if ($_GET['success'] === 'created')
                    echo "Event created successfully!";
                if ($_GET['success'] === 'updated')
                    echo "Event details updated!";
                if ($_GET['success'] === 'status_changed')
                    echo "Event status updated successfully!";
                if ($_GET['success'] === 'cancelled')
                    echo "Event has been permanently cancelled.";
                ?>
            </div>
        <?php endif; ?>

        <div class="events-table-wrapper">
            <table class="events-table">
                <thead>
                    <tr>
                        <th>Event Name</th>
                        <th>Date & Location</th>
                        <th>Confirmed Reg.</th>
                        <th>Capacity</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($events->num_rows > 0): ?>
                        <?php while ($row = $events->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 700; color: var(--text-dark); mb-1;">
                                        <?php echo htmlspecialchars($row['title']); ?>
                                    </div>
                                    <div
                                        style="font-size: 0.75rem; color: var(--text-muted); line-height: 1.4; max-width: 250px;">
                                        <?php echo htmlspecialchars(substr($row['description'], 0, 80)) . '...'; ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-size: 0.875rem; color: var(--text-dark); margin-bottom: 0.25rem;">📅
                                        <?php echo date('M d, Y', strtotime($row['date'])); ?>
                                    </div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">📍
                                        <?php echo htmlspecialchars($row['location']); ?>
                                    </div>
                                </td>
                                <td style="text-align: center;">
                                    <span style="font-weight: 700; color: var(--text-dark);">
                                        <?php echo $row['confirmedCount']; ?>
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <span style="color: var(--text-muted);">
                                        <?php echo $row['capacity']; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($row['status']); ?>">
                                        <?php echo $row['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-btns">
                                        <?php if ($row['status'] !== 'CANCELLED'): ?>
                                            <a href="edit_event.php?id=<?php echo $row['eventID']; ?>" class="btn-action">Edit</a>

                                            <?php if ($row['status'] === 'OPEN'): ?>
                                                <a href="event_status_handler.php?id=<?php echo $row['eventID']; ?>&action=close"
                                                    class="btn-action" style="color: #F59E0B;">Close</a>
                                            <?php elseif ($row['status'] === 'CLOSED' && $row['confirmedCount'] < $row['capacity']): ?>
                                                <a href="event_status_handler.php?id=<?php echo $row['eventID']; ?>&action=reopen"
                                                    class="btn-action" style="color: #10B981;">Reopen</a>
                                            <?php endif; ?>

                                            <a href="event_status_handler.php?id=<?php echo $row['eventID']; ?>&action=cancel"
                                                class="btn-action btn-cancel"
                                                onclick="return confirm('Are you sure you want to CANCEL this event permanently? All registered users will be notified.');">Cancel</a>
                                        <?php else: ?>
                                            <span style="color: var(--text-muted); font-size: 0.75rem;">No actions</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="empty-state">
                                No events created yet. Click "Create New Event" to get started.
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