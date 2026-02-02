<?php
/**
 * ADMIN - MANAGE EVENTS
 * View, search, filter and manage all system events.
 */
session_start();
require_once "../../config/db.php";
require_once "../../config/functions.php";

// Access Control
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'ADMIN') {
    header("Location: ../../auth/login.php");
    exit();
}

$successMsg = "";
$errorMsg = "";

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $targetEventID = intval($_POST['event_id']);
    $action = $_POST['action'];

    if ($action === 'toggle_status') {
        $currentStatus = $_POST['current_status'];
        // Simple toggle for manual action. 
        // Note: Logic for 'FULL' is usually automatic, but admin can force CLOSE/OPEN.
        $newStatus = ($currentStatus === 'OPEN') ? 'CLOSED' : 'OPEN';

        $stmt = $conn->prepare("UPDATE event SET status = ? WHERE eventID = ?");
        $stmt->bind_param("si", $newStatus, $targetEventID);
        if ($stmt->execute()) {
            logActivity($conn, $_SESSION['userID'], 'ADMIN', 'Toggle Event Status', 'Events', "Changed status of event #$targetEventID to $newStatus");
            $successMsg = "Event status updated to $newStatus.";
        } else {
            $errorMsg = "Failed to update status.";
        }
        $stmt->close();
    } elseif ($action === 'delete') {
        // First delete registrations? Or rely on CASCADE? 
        // Safest to rely on schema, but let's assume we might need to be careful.
        // For now, simple delete. table `event`
        $stmt = $conn->prepare("DELETE FROM event WHERE eventID = ?");
        $stmt->bind_param("i", $targetEventID);
        if ($stmt->execute()) {
            logActivity($conn, $_SESSION['userID'], 'ADMIN', 'Delete Event', 'Events', "Permanently deleted event #$targetEventID");
            $successMsg = "Event deleted successfully.";
        } else {
            $errorMsg = "Failed to delete event.";
        }
        $stmt->close();
    }
}

// Fetch Events with Filter & Search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';

// Query to fetch events + registration count
try {
    $sql = "
        SELECT 
            e.eventID,
            e.title,
            e.date,
            e.location,
            e.capacity,
            e.status,
            (SELECT COUNT(*) FROM eventregistration WHERE eventID = e.eventID) as registered_count
        FROM event e
        WHERE 1=1
    ";

    $params = [];
    $types = "";

    if ($search) {
        $sql .= " AND (e.title LIKE ? OR e.location LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= "ss";
    }

    if ($statusFilter) {
        // If viewing 'FULL', we might need complex logic, but usually filter is just db status
        // DB status is likely OPEN/CLOSED/CANCELLED
        // If filter is 'FULL', we handle differently?
        // Let's assume standard status filter for now.
        $sql .= " AND e.status = ?";
        $params[] = $statusFilter;
        $types .= "s";
    }

    $sql .= " ORDER BY e.date DESC LIMIT 50";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Failed to prepare SQL: " . $conn->error);
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

} catch (Exception $e) {
    die("Database Error: " . $e->getMessage());
}

$pageTitle = "Manage Events";
ob_start();
?>

<style>
    /* Local styles matching Manage Users */
    .filters {
        display: flex;
        gap: 1rem;
        margin-bottom: 1.5rem;
        background: var(--bg-card);
        padding: 1rem;
        border-radius: 8px;
        border: 1px solid var(--border);
    }

    .filter-select {
        padding: 0.5rem;
        border-radius: 6px;
        border: 1px solid var(--border);
        background: #fff;
        min-width: 150px;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.25rem 0.625rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .status-open {
        background: #DCFCE7;
        color: #166534;
    }

    .status-closed {
        background: #F1F5F9;
        color: #64748B;
    }

    .status-full {
        background: #FEF3C7;
        color: #B45309;
    }

    .status-cancelled {
        background: #FEE2E2;
        color: #991B1B;
    }
</style>

<div class="page-header" style="display:flex; justify-content:space-between; align-items:center;">
    <div>
        <h1 class="page-title">Manage Events</h1>
        <p class="page-desc">View, create, update, and control all platform events</p>
    </div>
    <a href="create_event.php" class="btn-primary"
        style="background:var(--primary); color:white; border:none; padding:0.75rem 1.5rem; border-radius:6px; font-weight:600; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; gap:0.5rem;">
        <i data-lucide="plus" size="16"></i> Create Event
    </a>
</div>

<!-- Filters -->
<form class="filters">
    <input type="text" name="search" class="search-input" placeholder="Search by event title..."
        value="<?php echo htmlspecialchars($search); ?>" style="flex:1;">

    <select name="status" class="filter-select">
        <option value="">All Status</option>
        <option value="OPEN" <?php if ($statusFilter == 'OPEN')
            echo 'selected'; ?>>Open</option>
        <option value="CLOSED" <?php if ($statusFilter == 'CLOSED')
            echo 'selected'; ?>>Closed</option>
        <option value="CANCELLED" <?php if ($statusFilter == 'CANCELLED')
            echo 'selected'; ?>>Cancelled</option>
    </select>

    <button type="submit"
        style="background:#F1F5F9; border:1px solid var(--border); padding:0 1rem; border-radius:6px; cursor:pointer;">Filter</button>
</form>

<?php if ($successMsg): ?>
    <div style="background:#DCFCE7; color:#166534; padding:1rem; border-radius:6px; margin-bottom:1.5rem;">
        <?php echo $successMsg; ?>
    </div>
<?php endif; ?>

<?php if ($errorMsg): ?>
    <div style="background:#FEE2E2; color:#991B1B; padding:1rem; border-radius:6px; margin-bottom:1.5rem;">
        <?php echo $errorMsg; ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="table-wrapper">
        <table class="clean-table">
            <thead>
                <tr>
                    <th>Event ID</th>
                    <th>Event Title</th>
                    <th>Date</th>
                    <th>Location</th>
                    <th>Capacity</th>
                    <th>Registered</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <?php
                        // Determine Display Status (Logic as per requirements)
                        // If registered >= capacity -> FULL (Amber)
                        // If DB status is OPEN but full -> FULL
                        // Else DB status
                        $displayStatus = $row['status'];
                        $badgeClass = 'status-closed';

                        if ($row['status'] === 'OPEN') {
                            if ($row['registered_count'] >= $row['capacity']) {
                                $displayStatus = 'FULL';
                                $badgeClass = 'status-full';
                            } else {
                                $badgeClass = 'status-open';
                            }
                        } elseif ($row['status'] === 'CANCELLED') {
                            $badgeClass = 'status-cancelled';
                        }
                        ?>
                        <tr>
                            <td>#<?php echo $row['eventID']; ?></td>
                            <td>
                                <div style="font-weight:500; color:var(--text-dark);">
                                    <?php echo htmlspecialchars($row['title']); ?>
                                </div>
                            </td>
                            <td><?php echo date('d M Y', strtotime($row['date'])); ?></td>
                            <td><?php echo htmlspecialchars($row['location']); ?></td>
                            <td><?php echo number_format($row['capacity']); ?></td>
                            <td style="font-weight:500;">
                                <?php echo number_format($row['registered_count']); ?>
                            </td>
                            <td>
                                <span class="status-badge <?php echo $badgeClass; ?>">
                                    <?php echo $displayStatus; ?>
                                </span>
                            </td>
                            <td style="display:flex; gap:0.5rem;">
                                <!-- Edit (Placeholder link) -->
                                <a href="edit_event.php?id=<?php echo $row['eventID']; ?>" class="btn-icon" title="Edit Event"
                                    style="display:inline-flex; align-items:center; justify-content:center;">
                                    <i data-lucide="edit-2" size="18"></i>
                                </a>

                                <!-- Toggle Status -->
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Change event status?');">
                                    <input type="hidden" name="event_id" value="<?php echo $row['eventID']; ?>">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="current_status" value="<?php echo $row['status']; ?>">
                                    <button type="submit" class="btn-icon"
                                        title="<?php echo $row['status'] == 'OPEN' ? 'Close Event' : 'Reopen Event'; ?>">
                                        <i data-lucide="<?php echo $row['status'] == 'OPEN' ? 'lock' : 'unlock'; ?>"
                                            size="18"></i>
                                    </button>
                                </form>

                                <!-- Delete -->
                                <form method="POST" style="display:inline;"
                                    onsubmit="return confirm('Are you sure you want to delete this event? This action cannot be undone.');">
                                    <input type="hidden" name="event_id" value="<?php echo $row['eventID']; ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button type="submit" class="btn-icon" title="Delete Event" style="color:#EF4444;">
                                        <i data-lucide="trash-2" size="18"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="text-align:center; padding:2rem; color:var(--text-light);">No events found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../layout.php';
?>