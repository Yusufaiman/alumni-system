<?php
/**
 * GET UNREAD NOTIFICATIONS COUNT API
 * Used for AJAX polling of the notification badge.
 */
session_start();
require_once "../config/db.php";

header('Content-Type: application/json');

if (!isset($_SESSION['userID'])) {
    echo json_encode(['success' => false, 'count' => 0]);
    exit();
}

$userID = $_SESSION['userID'];

$sql = "SELECT COUNT(*) as unread_count FROM notification WHERE targetUserID = ? AND status = 'SENT'";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $userID);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);

echo json_encode(['success' => true, 'count' => (int) $row['unread_count']]);
?>