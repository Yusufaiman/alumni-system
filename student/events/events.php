<?php
/**
 * STUDENT - BROWSE EVENTS
 */
session_start();
require_once "../../config/db.php";

// Access Control
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'STUDENT') {
    header("Location: ../../auth/login.php");
    exit();
}

// Handle Actions
require_once "../../components/event/EventAction.php";

$userID = $_SESSION['userID'];

// Fetch Events
$sql = "
    SELECT 
        e.*,
        (SELECT COUNT(*) FROM eventregistration WHERE eventID = e.eventID AND status = 'CONFIRMED') as confirmedCount,
        er.status as userRegistrationStatus
    FROM event e
    LEFT JOIN eventregistration er ON e.eventID = er.eventID AND er.userID = ?
    WHERE e.status != 'CANCELLED' AND e.date >= CURRENT_DATE
    ORDER BY e.date ASC
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
    <title>Upcoming Events | Student Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <link rel="stylesheet" href="../../assets/css/jobs.css">
</head>

<body>
    <?php include_once __DIR__ . '/../../components/topbar.php'; ?>

    <div class="jobs-container" style="max-width: 1200px; margin: 0 auto; padding: 2rem;">
        <header class="jobs-header" style="text-align: center; margin-bottom: 3rem;">
            <h1>Upcoming Events</h1>
            <p>Connect, learn, and grow through our university and alumni events.</p>
        </header>

        <?php if (isset($_GET['success'])): ?>
            <div
                style="background: #DCFCE7; color: #166534; padding: 1rem; border-radius: 0.5rem; margin-bottom: 2rem; text-align: center; font-weight: 600; border: 1px solid #BBF7D0;">
                <?php
                if ($_GET['success'] === 'registered')
                    echo "Successfully registered for the event!";
                if ($_GET['success'] === 'cancelled')
                    echo "Registration cancelled successfully.";
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div
                style="background: #FEE2E2; color: #991B1B; padding: 1rem; border-radius: 0.5rem; margin-bottom: 2rem; text-align: center; font-weight: 600; border: 1px solid #FECACA;">
                <?php
                if ($_GET['error'] === 'event_full')
                    echo "Sorry, this event is already full.";
                if ($_GET['error'] === 'already_registered')
                    echo "You are already registered for this event.";
                if ($_GET['error'] === 'event_not_open')
                    echo "This event is no longer open for registration.";
                if ($_GET['error'] === 'failed')
                    echo "An error occurred. Please try again.";
                ?>
            </div>
        <?php endif; ?>

        <div class="events-grid"
            style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 2rem;">
            <?php if ($events->num_rows > 0): ?>
                <?php while ($event = $events->fetch_assoc()): ?>
                    <?php include "../../components/event/EventCardUI.php"; ?>
                <?php endwhile; ?>
            <?php else: ?>
                <div
                    style="grid-column: 1 / -1; text-align: center; padding: 5rem; background: white; border: 1px dashed var(--border-light); border-radius: 1rem;">
                    <p style="color: var(--text-muted); font-size: 1.125rem;">No upcoming events at the moment. Check back
                        soon!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Profile dropdown logic
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