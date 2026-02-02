<?php
/**
 * UPDATE JOB APPLICATION STATUS HANDLER
 * Allows alumni to accept/reject student job applications and notifies the student.
 */
session_start();
require_once "../config/db.php";
require_once "../config/functions.php";

// 1. Access Control
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'ALUMNI') {
    header("Location: ../auth/login.php");
    exit();
}

$userID = $_SESSION['userID'];

// 2. Process POST Data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['applicationID'], $_POST['status'])) {
    $applicationID = intval($_POST['applicationID']);
    $status = $_POST['status']; // 'APPROVED' or 'REJECTED'

    if (!in_array($status, ['APPROVED', 'REJECTED'])) {
        header("Location: job_applicants.php?error=Invalid status.");
        exit();
    }

    // 3. Verify Ownership (Alumni must own the job posting associated with this application)
    $verifyStmt = $conn->prepare("
        SELECT ja.applicantID, ja.jobID, ja.applicantRole, j.title, j.company
        FROM job_application ja
        JOIN jobposting j ON ja.jobID = j.jobID
        WHERE ja.applicationID = ? AND j.postedByID = ? AND j.postedByRole = 'ALUMNI' LIMIT 1
    ");
    $verifyStmt->bind_param("ii", $applicationID, $userID);
    $verifyStmt->execute();
    $appData = $verifyStmt->get_result()->fetch_assoc();

    if (!$appData) {
        header("Location: job_applicants.php?error=Unauthorized access.");
        exit();
    }

    // 4. Update Status
    $updateStmt = $conn->prepare("UPDATE job_application SET status = ? WHERE applicationID = ?");
    $updateStmt->bind_param("si", $status, $applicationID);

    if ($updateStmt->execute()) {
        // 5. Notify Applicant
        $targetUserID = $appData['applicantID'];
        $jobTitle = $appData['title'];
        $alumniName = $_SESSION['name'];
        $statusLabel = ($status === 'APPROVED') ? "Approved" : "Rejected";

        createNotification(
            $conn,
            "Job Application $statusLabel",
            "$alumniName has $statusLabel your application for the '$jobTitle' position at {$appData['company']}.",
            ($appData['applicantRole'] === 'STUDENT' ? "student/" : "alumni/") . "my_applications.php",
            $targetUserID,
            "job",
            "JOB",
            $appData['jobID'],
            $appData['applicantRole']
        );

        header("Location: job_applicants.php?success=1");
    } else {
        header("Location: job_applicants.php?error=Update failed.");
    }
    exit();
} else {
    header("Location: job_applicants.php");
    exit();
}
?>