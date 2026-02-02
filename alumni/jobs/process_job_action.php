<?php
/**
 * PROCESS JOB ACTION
 * Handles closing and deleting (soft delete/status change) of job postings.
 */
session_start();
require_once "../config/db.php";

// Access Control: Alumni Only
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'ALUMNI') {
    header("Location: ../auth/login.php");
    exit();
}

$userID = $_SESSION['userID'];
$action = $_GET['action'] ?? '';
$jobID = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($jobID > 0) {
    // Verify Ownership
    $alumniStmt = $conn->prepare("SELECT alumniID FROM alumni WHERE userID = ? LIMIT 1");
    $alumniStmt->bind_param("i", $userID);
    $alumniStmt->execute();
    $alumniID = $alumniStmt->get_result()->fetch_assoc()['alumniID'];

    $checkStmt = $conn->prepare("SELECT jobID FROM jobposting WHERE jobID = ? AND alumniID = ? LIMIT 1");
    $checkStmt->bind_param("ii", $jobID, $alumniID);
    $checkStmt->execute();

    if ($checkStmt->get_result()->num_rows > 0) {
        if ($action === 'close') {
            $updateStmt = $conn->prepare("UPDATE jobposting SET status = 'CLOSED' WHERE jobID = ?");
            $updateStmt->bind_param("i", $jobID);
            $updateStmt->execute();
            header("Location: job_opportunities.php?success=updated");
        } elseif ($action === 'delete') {
            // Usually we'd do a status update for soft delete if requested, or permanent delete.
            // Prompt says "soft delete via status CLOSED", but we already have a "close" action.
            // I'll make delete archive it or just set to CLOSED if that's what's meant.
            // But let's follow the prompt: "Delete Job (soft delete via status CLOSED)"
            $updateStmt = $conn->prepare("UPDATE jobposting SET status = 'CLOSED' WHERE jobID = ?");
            $updateStmt->bind_param("i", $jobID);
            $updateStmt->execute();
            header("Location: job_opportunities.php?success=updated");
        } else {
            header("Location: job_opportunities.php");
        }
    } else {
        header("Location: job_opportunities.php?error=unauthorized");
    }
} else {
    header("Location: job_opportunities.php");
}
exit();
?>