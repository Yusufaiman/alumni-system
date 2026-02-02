<?php
/**
 * MARK NOTIFICATION STATUS API
 * Handles Read, Archive, and Clear All actions.
 */
session_start();
require_once "../config/db.php";

header('Content-Type: application/json');

if (!isset($_SESSION['userID'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$userID = $_SESSION['userID'];
$action = $_POST['action'] ?? '';
$notificationID = isset($_POST['id']) ? intval($_POST['id']) : 0;

if ($action === 'READ' && $notificationID > 0) {
    $sql = "UPDATE notification SET status = 'READ' WHERE notificationID = ? AND targetUserID = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $notificationID, $userID);
} elseif ($action === 'ARCHIVE' && $notificationID > 0) {
    $sql = "UPDATE notification SET status = 'ARCHIVED' WHERE notificationID = ? AND targetUserID = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $notificationID, $userID);
} elseif ($action === 'MARK_ALL_READ') {
    $sql = "UPDATE notification SET status = 'READ' WHERE targetUserID = ? AND status = 'SENT'";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $userID);
} elseif ($action === 'CLEAR_ALL') {
    $sql = "UPDATE notification SET status = 'ARCHIVED' WHERE targetUserID = ? AND status != 'ARCHIVED'";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $userID);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit();
}

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>