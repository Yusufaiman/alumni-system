<?php
/**
 * CAREER SERVICE OFFICER - UPDATE APPLICATION STATUS
 * Handles the approval or rejection of job applications.
 */
session_start();
require_once "../../config/db.php";

if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'CAREER_SERVICE_OFFICER') {
    header("Location: ../../auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['applicationID']) && isset($_POST['status'])) {
    $applicationID = intval($_POST['applicationID']);
    $status = $_POST['status'];
    // Verify Ownership (CSO must own the job posting)
    $verifyStmt = $conn->prepare("
        SELECT ja.applicantID, ja.jobID, ja.applicantRole, j.title, j.company
        FROM job_application ja
        JOIN jobposting j ON ja.jobID = j.jobID
        WHERE ja.applicationID = ? AND j.postedByID = ? AND j.postedByRole = 'CAREER_SERVICE_OFFICER' LIMIT 1
    ");
    $verifyStmt->bind_param("ii", $applicationID, $_SESSION['userID']);
    $verifyStmt->execute();
    $appData = $verifyStmt->get_result()->fetch_assoc();

    if (!$appData) {
        header("Location: job_applications.php?error=unauthorized");
        exit();
    }

    $sql = "UPDATE job_application SET status = ? WHERE applicationID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $status, $applicationID);

    if ($stmt->execute()) {
        require_once "../../config/functions.php";
        // Notify Applicant
        $targetUserID = $appData['applicantID'];
        $jobTitle = $appData['title'];
        $csoName = $_SESSION['name'];
        $statusLabel = ($status === 'APPROVED') ? "Approved" : "Rejected";

        createNotification(
            $conn,
            "Job Application $statusLabel",
            "$csoName has $statusLabel your application for the '$jobTitle' position at {$appData['company']}.",
            ($appData['applicantRole'] === 'STUDENT' ? "student/" : "alumni/") . "my_applications.php",
            $targetUserID,
            "job",
            "JOB",
            $appData['jobID'],
            $appData['applicantRole']
        );

        header("Location: job_applications.php?success=updated");
        exit();
    } else {
        header("Location: job_applications.php?error=db_error");
        exit();
    }
} else {
    header("Location: job_applications.php");
    exit();
}
