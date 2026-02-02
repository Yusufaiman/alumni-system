<?php
/**
 * APPLY JOB PROCESSING SCRIPT (ALUMNI)
 * Handles the POST request from JobGridUI.
 */
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['userID']) || !isset($_POST['jobID'])) {
    header("Location: ../auth/login.php");
    exit();
}

$jobID = intval($_POST['jobID']);
$applicantID = $_SESSION['userID'];
$role = $_SESSION['role'];

// 🔐 ROLE GUARDS: Only Students and Alumni can apply
if ($role !== 'STUDENT' && $role !== 'ALUMNI') {
    header("Location: dashboard.php?error=unauthorized");
    exit();
}

// 🧪 DUPLICATE CHECK (MANDATORY)
$checkStmt = $conn->prepare("SELECT applicationID FROM job_application WHERE jobID = ? AND applicantID = ? AND applicantRole = ?");
$checkStmt->bind_param("iis", $jobID, $applicantID, $role);
$checkStmt->execute();
if ($checkStmt->get_result()->num_rows > 0) {
    header("Location: apply_jobs.php?error=already_applied");
    exit();
}

// 🧠 BACKEND FLOW: INSERT QUERY
$appliedDate = date('Y-m-d');
$insertStmt = $conn->prepare("INSERT INTO job_application (jobID, applicantID, applicantRole, status, appliedDate) VALUES (?, ?, ?, 'PENDING', ?)");
$insertStmt->bind_param("iiss", $jobID, $applicantID, $role, $appliedDate);

if ($insertStmt->execute()) {
    header("Location: apply_jobs.php?success=applied");
    exit();
} else {
    header("Location: apply_jobs.php?error=db_error");
    exit();
}
