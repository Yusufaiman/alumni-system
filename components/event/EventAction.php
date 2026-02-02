<?php
/**
 * SHARED LOGIC: EventAction
 * Handles registration and cancellation for events.
 */

require_once __DIR__ . "/../../config/functions.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $userID = $_SESSION['userID'];
    $role = $_SESSION['role'];
    $eventID = intval($_POST['eventID']);
    $action = $_POST['action'];

    if ($action === 'register') {
        // 1. Check if record exists
        $checkStmt = $conn->prepare("SELECT registrationID, status FROM eventregistration WHERE eventID = ? AND userID = ?");
        $checkStmt->bind_param("ii", $eventID, $userID);
        $checkStmt->execute();
        $existing = $checkStmt->get_result()->fetch_assoc();

        if ($existing) {
            if ($existing['status'] === 'CONFIRMED') {
                header("Location: " . $_SERVER['PHP_SELF'] . "?error=already_registered");
                exit();
            }
            // If status is CANCELLED, we will proceed to update it later
        }

        // 2. Check capacity
        $eventStmt = $conn->prepare("SELECT title, capacity, status FROM event WHERE eventID = ?");
        $eventStmt->bind_param("i", $eventID);
        $eventStmt->execute();
        $event = $eventStmt->get_result()->fetch_assoc();

        if (!$event || $event['status'] !== 'OPEN') {
            header("Location: " . $_SERVER['PHP_SELF'] . "?error=event_not_open");
            exit();
        }

        $regCountStmt = $conn->prepare("SELECT COUNT(*) as count FROM eventregistration WHERE eventID = ? AND status = 'CONFIRMED'");
        $regCountStmt->bind_param("i", $eventID);
        $regCountStmt->execute();
        $confirmedCount = $regCountStmt->get_result()->fetch_assoc()['count'];

        if ($confirmedCount >= $event['capacity']) {
            header("Location: " . $_SERVER['PHP_SELF'] . "?error=event_full");
            exit();
        }

        // 3. Register or Re-activate
        $success = false;
        if ($existing) {
            // Re-activate previously cancelled registration
            $updateRegStmt = $conn->prepare("UPDATE eventregistration SET status = 'CONFIRMED', registrationDate = CURRENT_DATE WHERE registrationID = ?");
            $updateRegStmt->bind_param("i", $existing['registrationID']);
            $success = $updateRegStmt->execute();
        } else {
            // New registration
            $insertStmt = $conn->prepare("INSERT INTO eventregistration (eventID, userID, role, registrationDate, status) VALUES (?, ?, ?, CURRENT_DATE, 'CONFIRMED')");
            $insertStmt->bind_param("iis", $eventID, $userID, $role);
            $success = $insertStmt->execute();
        }

        if ($success) {
            // Update Event Status to CLOSED if now full
            if ($confirmedCount + 1 >= $event['capacity']) {
                $updateEventStmt = $conn->prepare("UPDATE event SET status = 'CLOSED' WHERE eventID = ?");
                $updateEventStmt->bind_param("i", $eventID);
                $updateEventStmt->execute();
            }

            // Send Notification
            createNotification(
                $conn,
                "Event Registration Successful",
                "You have successfully registered for \"" . $event['title'] . "\".",
                ($_SESSION['role'] === 'STUDENT' ? "student/events/my_events.php" : "alumni/events/my_events.php"),
                $userID,
                "event",
                "EVENT",
                $eventID,
                $role
            );

            header("Location: " . $_SERVER['PHP_SELF'] . "?success=registered");
            exit();
        } else {
            error_log("Event Registration Failed: " . $conn->error);
            header("Location: " . $_SERVER['PHP_SELF'] . "?error=failed");
            exit();
        }

    } elseif ($action === 'cancel') {
        // 1. Fetch info
        $infoStmt = $conn->prepare("
            SELECT e.title, e.capacity, er.registrationID 
            FROM eventregistration er
            JOIN event e ON er.eventID = e.eventID
            WHERE er.eventID = ? AND er.userID = ? AND er.status = 'CONFIRMED'
        ");
        $infoStmt->bind_param("ii", $eventID, $userID);
        $infoStmt->execute();
        $info = $infoStmt->get_result()->fetch_assoc();

        if (!$info) {
            header("Location: " . $_SERVER['PHP_SELF'] . "?error=not_registered");
            exit();
        }

        // 2. Cancel
        $cancelStmt = $conn->prepare("UPDATE eventregistration SET status = 'CANCELLED' WHERE registrationID = ?");
        $cancelStmt->bind_param("i", $info['registrationID']);

        if ($cancelStmt->execute()) {
            // Recalculate confirmed count
            $regCountStmt = $conn->prepare("SELECT COUNT(*) as count FROM eventregistration WHERE eventID = ? AND status = 'CONFIRMED'");
            $regCountStmt->bind_param("i", $eventID);
            $regCountStmt->execute();
            $newCount = $regCountStmt->get_result()->fetch_assoc()['count'];

            if ($newCount < $info['capacity']) {
                $updateEventStmt = $conn->prepare("UPDATE event SET status = 'OPEN' WHERE eventID = ?");
                $updateEventStmt->bind_param("i", $eventID);
                $updateEventStmt->execute();
            }

            // Send Notification
            createNotification(
                $conn,
                "Event Registration Cancelled",
                "You have cancelled your registration for \"" . $info['title'] . "\".",
                ($_SESSION['role'] === 'STUDENT' ? "student/" : "alumni/") . "events.php",
                $userID,
                "event",
                "EVENT",
                $eventID,
                $role
            );

            header("Location: " . $_SERVER['PHP_SELF'] . "?success=cancelled");
            exit();
        }
    }
}
?>