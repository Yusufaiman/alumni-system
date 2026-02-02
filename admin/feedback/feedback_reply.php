<?php
/**
 * ADMIN - FEEDBACK REPLY HANDLER
 * Process admin response to user feedback
 */
session_start();
require_once "../config/db.php";

// Access Control
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'ADMIN') {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $feedbackID = intval($_POST['feedbackID']);
    $userID = intval($_POST['userID']);
    $adminResponse = trim($_POST['adminResponse']);
    $status = $_POST['status'];

    // Get adminID from session
    $stmtAdmin = $conn->prepare("SELECT adminID FROM admin WHERE userID = ?");
    $stmtAdmin->bind_param("i", $_SESSION['userID']);
    $stmtAdmin->execute();
    $resAdmin = $stmtAdmin->get_result();

    if ($resAdmin->num_rows === 0) {
        header("Location: manage_feedback.php?error=admin_not_found");
        exit();
    }

    $adminRow = $resAdmin->fetch_assoc();
    $adminID = $adminRow['adminID'];
    $stmtAdmin->close();

    // Validation
    if (empty($adminResponse)) {
        header("Location: manage_feedback.php?error=empty_response");
        exit();
    }

    // Update feedback
    $stmt = $conn->prepare("UPDATE system_feedback SET adminResponse = ?, status = ?, handledBy = ?, handledDate = NOW() WHERE feedbackID = ?");

    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        header("Location: manage_feedback.php?error=prepare_failed");
        exit();
    }

    $stmt->bind_param("ssii", $adminResponse, $status, $adminID, $feedbackID);

    if ($stmt->execute()) {
        // Get feedback subject for notification
        $stmtFeedback = $conn->prepare("SELECT subject FROM system_feedback WHERE feedbackID = ?");
        $stmtFeedback->bind_param("i", $feedbackID);
        $stmtFeedback->execute();
        $resFeedback = $stmtFeedback->get_result();
        $feedbackData = $resFeedback->fetch_assoc();
        $subject = $feedbackData['subject'];
        $stmtFeedback->close();

        // Create notification for user
        $notifTitle = "Feedback Response Received";
        $notifMessage = "Your feedback \"" . $subject . "\" has been reviewed by the admin team. Please check the response.";
        $notifLink = "/feedback/view.php?id=" . $feedbackID;

        $stmtNotif = $conn->prepare("INSERT INTO notification (title, message, link, targetUserID, referenceType, referenceID, status, created_at) VALUES (?, ?, ?, ?, 'SYSTEM', ?, 'UNREAD', NOW())");
        $stmtNotif->bind_param("sssii", $notifTitle, $notifMessage, $notifLink, $userID, $feedbackID);
        $stmtNotif->execute();
        $stmtNotif->close();

        $stmt->close();
        header("Location: manage_feedback.php?success=1");
        exit();
    } else {
        header("Location: manage_feedback.php?error=update_failed");
        exit();
    }
} else {
    header("Location: manage_feedback.php");
    exit();
}
?>