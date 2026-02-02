<?php
/**
 * STUDENT - MY REGISTERED EVENTS
 */
session_start();
require_once "../../config/db.php";

// Access Control
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'STUDENT') {
    header("Location: ../../auth/login.php");
    exit();
}

// Handle Actions (Cancellation)
require_once "../../components/event/EventAction.php";

$userID = $_SESSION['userID'];

// Fetch Registered Events
$sql = "
    SELECT 
        e.eventID, e.title, e.description, e.date, e.location, e.status as eventStatus,
        er.registrationDate, er.status as regStatus
    FROM eventregistration er
    JOIN event e ON er.eventID = e.eventID
    WHERE er.userID = ? AND er.status = 'CONFIRMED'
    ORDER BY e.date ASC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userID);
$stmt->execute();
$registrations = $stmt->get_result();

$isDashboard = false;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Registered Events | Student Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <link rel="stylesheet" href="../../assets/css/jobs.css">
</head>

<body>
    <?php include_once __DIR__ . '/../../components/topbar.php'; ?>

    <div class="jobs-container" style="max-width: 1200px; margin: 0 auto; padding: 2rem;">
        <header class="jobs-header" style="text-align: left; margin-bottom: 2rem;">
            <div style="margin-bottom: 0.5rem;">
                <a href="events.php"
                    style="color: var(--primary-blue); text-decoration: none; font-size: 0.875rem; font-weight: 600;">&larr;
                    Browse All Events</a>
            </div>
            <h1>My Registered Events</h1>
            <p>Review and manage the events you've signed up for.</p>
        </header>

        <?php if (isset($_GET['success'])): ?>
            <div
                style="background: #DCFCE7; color: #166534; padding: 1rem; border-radius: 0.5rem; margin-bottom: 2rem; text-align: center; font-weight: 600; border: 1px solid #BBF7D0;">
                <?php if ($_GET['success'] === 'cancelled')
                    echo "Event registration cancelled successfully."; ?>
            </div>
        <?php endif; ?>

        <?php include "../../components/event/MyEventTableUI.php"; ?>
    </div>

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