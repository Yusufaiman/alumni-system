<?php
/**
 * EVENT STATUS HANDLER
 * Processes Close, Reopen, and Cancel actions from Manage Events.
 */
session_start();
require_once "../config/db.php";
require_once "../config/functions.php";

// Access Control
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'CAREER_SERVICE_OFFICER') {
    header("Location: /alumni-system/auth/login.php");
    exit();
}

if (!isset($_GET['id']) || !isset($_GET['action'])) {
    header("Location: manage_events.php");
    exit();
}

$eventID = intval($_GET['id']);
$action = $_GET['action'];
$userID = $_SESSION['userID'];

// Ownership Check
$stmt = $conn->prepare("SELECT title, capacity, status FROM event WHERE eventID = ? AND createdBy = ?");
$stmt->bind_param("ii", $eventID, $userID);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();

if (!$event) {
    die("Event not found or unauthorized.");
}

$title = $event['title'];
$capacity = $event['capacity'];

// Fetch registered users for notification
$regUsersStmt = $conn->prepare("SELECT userID, role FROM eventregistration WHERE eventID = ? AND status = 'CONFIRMED'");
$regUsersStmt->bind_param("i", $eventID);
$regUsersStmt->execute();
$regUsersResult = $regUsersStmt->get_result();
$regUsers = [];
while ($u = $regUsersResult->fetch_assoc())
    $regUsers[] = $u;

if ($action === 'close') {
    $updateStmt = $conn->prepare("UPDATE event SET status = 'CLOSED' WHERE eventID = ?");
    $updateStmt->bind_param("i", $eventID);
    if ($updateStmt->execute()) {
        foreach ($regUsers as $u) {
            createNotification(
                $conn,
                "Event Closed",
                "The event \"$title\" is no longer accepting registrations.",
                ($u['role'] === 'STUDENT' ? "student/" : "alumni/") . "my_events.php",
                $u['userID'],
                "event",
                "EVENT",
                $eventID,
                $u['role']
            );
        }
        header("Location: manage_events.php?success=status_changed");
        exit();
    }
} elseif ($action === 'reopen') {
    // Check confirmed count vs capacity
    $countRes = $conn->query("SELECT COUNT(*) as count FROM eventregistration WHERE eventID = $eventID AND status = 'CONFIRMED'");
    $confirmedCount = $countRes->fetch_assoc()['count'];

    if ($confirmedCount < $capacity) {
        $updateStmt = $conn->prepare("UPDATE event SET status = 'OPEN' WHERE eventID = ?");
        $updateStmt->bind_param("i", $eventID);
        if ($updateStmt->execute()) {
            foreach ($regUsers as $u) {
                createNotification(
                    $conn,
                    "Event Reopened",
                    "The event \"$title\" is now open for registration again.",
                    ($u['role'] === 'STUDENT' ? "student/" : "alumni/") . "events.php",
                    $u['userID'],
                    "event",
                    "EVENT",
                    $eventID,
                    $u['role']
                );
            }
            header("Location: manage_events.php?success=status_changed");
            exit();
        }
    } else {
        header("Location: manage_events.php?error=reopen_failed_capacity");
        exit();
    }
} elseif ($action === 'cancel') {
    $conn->begin_transaction();
    try {
        // 1. Cancel Event
        $updateEvent = $conn->prepare("UPDATE event SET status = 'CANCELLED' WHERE eventID = ?");
        $updateEvent->bind_param("i", $eventID);
        $updateEvent->execute();

        // 2. Cancel Registrations
        $updateRegs = $conn->prepare("UPDATE eventregistration SET status = 'CANCELLED' WHERE eventID = ?");
        $updateRegs->bind_param("i", $eventID);
        $updateRegs->execute();

        // 3. Notify Users
        foreach ($regUsers as $u) {
            createNotification(
                $conn,
                "Event Cancelled",
                "The event \"$title\" has been cancelled by the organizer.",
                ($u['role'] === 'STUDENT' ? "student/" : "alumni/") . "events.php",
                $u['userID'],
                "event",
                "EVENT",
                $eventID,
                $u['role']
            );
        }

        $conn->commit();
        header("Location: manage_events.php?success=cancelled");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        die("Error cancelling event: " . $e->getMessage());
    }
}
?>