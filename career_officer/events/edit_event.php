<?php
/**
 * CAREER SERVICE OFFICER - EDIT EVENT
 */
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/functions.php';

// Access Control
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'CAREER_SERVICE_OFFICER') {
    header("Location: /alumni-system/auth/login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: manage_events.php");
    exit();
}

$eventID = intval($_GET['id']);
$userID = $_SESSION['userID'];

// Fetch event details and ensure ownership
$stmt = $conn->prepare("SELECT * FROM event WHERE eventID = ? AND createdBy = ?");
$stmt->bind_param("ii", $eventID, $userID);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();

if (!$event) {
    die("Event not found or unauthorized.");
}

if ($event['status'] === 'CANCELLED') {
    die("Cannot edit a cancelled event.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_event'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $date = $_POST['date'];
    $location = trim($_POST['location']);
    $capacity = intval($_POST['capacity']);

    if (empty($title) || empty($description) || empty($date) || empty($location) || $capacity <= 0) {
        $errorMsg = "Please fill in all fields correctly.";
    } else {
        // Update Logic
        $updateSql = "UPDATE event SET title = ?, description = ?, date = ?, location = ?, capacity = ? WHERE eventID = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("ssssii", $title, $description, $date, $location, $capacity, $eventID);

        if ($updateStmt->execute()) {
            // Capacity Re-check Logic
            $regCountStmt = $conn->prepare("SELECT COUNT(*) as count FROM eventregistration WHERE eventID = ? AND status = 'CONFIRMED'");
            $regCountStmt->bind_param("i", $eventID);
            $regCountStmt->execute();
            $confirmedCount = $regCountStmt->get_result()->fetch_assoc()['count'];

            if ($confirmedCount >= $capacity) {
                $conn->query("UPDATE event SET status = 'CLOSED' WHERE eventID = $eventID");
            } else if ($event['status'] === 'CLOSED' && $confirmedCount < $capacity) {
                $conn->query("UPDATE event SET status = 'OPEN' WHERE eventID = $eventID");
            }

            // Notification: Notify all CONFIRMED users
            $notifUsersStmt = $conn->prepare("SELECT userID, role FROM eventregistration WHERE eventID = ? AND status = 'CONFIRMED'");
            $notifUsersStmt->bind_param("i", $eventID);
            $notifUsersStmt->execute();
            $regUsers = $notifUsersStmt->get_result();

            while ($u = $regUsers->fetch_assoc()) {
                createNotification(
                    $conn,
                    "Event Updated",
                    "Details of \"" . $title . "\" have been updated by the organizer.",
                    ($u['role'] === 'STUDENT' ? "student/" : "alumni/") . "my_events.php",
                    $u['userID'],
                    "event",
                    "EVENT",
                    $eventID,
                    $u['role']
                );
            }

            header("Location: manage_events.php?success=updated");
            exit();
        } else {
            $errorMsg = "Error updating event: " . $conn->error;
        }
    }
}

$isDashboard = false;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Event | CSO Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .form-container {
            max-width: 600px;
            margin: 3rem auto;
            background: white;
            padding: 2.5rem;
            border-radius: 1rem;
            border: 1px solid var(--border-light);
            box-shadow: var(--card-shadow);
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
            margin-top: 1rem;
        }

        .btn-submit:hover {
            background: #2563EB;
            transform: translateY(-1px);
        }
    </style>
</head>

<body>
    <?php include_once __DIR__ . '/../../components/topbar.php'; ?>

    <div class="manage-events-container" style="max-width: 800px; margin: 2rem auto; padding: 0 2rem;">
        <div style="margin-bottom: 1rem;">
            <a href="manage_events.php"
                style="color: var(--primary-blue); text-decoration: none; font-size: 0.875rem; font-weight: 600;">&larr;
                Back to Manage Events</a>
        </div>

        <div class="form-container">
            <h1 style="font-size: 1.75rem; color: var(--text-dark); margin-bottom: 0.5rem;">Edit Event</h1>
            <p style="color: var(--text-muted); margin-bottom: 2.5rem;">Update the event details below. Registered users
                will be notified of changes.</p>

            <?php if (isset($errorMsg)): ?>
                <div
                    style="background: #FEE2E2; color: #991B1B; padding: 1rem; border-radius: 0.5rem; margin-bottom: 2rem; font-weight: 600; border: 1px solid #FECACA; text-align: center;">
                    <?php echo $errorMsg; ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="form-group">
                    <label for="title">Event Title *</label>
                    <input type="text" name="title" id="title" class="form-control"
                        value="<?php echo htmlspecialchars($event['title']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="date">Event Date *</label>
                    <input type="date" name="date" id="date" class="form-control" value="<?php echo $event['date']; ?>"
                        required min="<?php echo date('Y-m-d'); ?>">
                </div>

                <div class="form-group">
                    <label for="location">Location *</label>
                    <input type="text" name="location" id="location" class="form-control"
                        value="<?php echo htmlspecialchars($event['location']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="capacity">Maximum Capacity *</label>
                    <input type="number" name="capacity" id="capacity" class="form-control"
                        value="<?php echo $event['capacity']; ?>" required min="1">
                </div>

                <div class="form-group">
                    <label for="description">Event Description *</label>
                    <textarea name="description" id="description" class="form-control" rows="5"
                        required><?php echo htmlspecialchars($event['description']); ?></textarea>
                </div>

                <button type="submit" name="update_event" class="btn-submit">Save Changes</button>
            </form>
        </div>
    </div>
</body>

</html>