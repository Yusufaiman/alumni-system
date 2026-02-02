<?php
/**
 * FEEDBACK SUBMISSION PROCESSOR
 * Handles feedback submission from both students and alumni
 * NO UI - Processor only
 */
session_start();
require_once "../config/db.php";

// Access Control
if (!isset($_SESSION['userID']) || !in_array($_SESSION['role'], ['STUDENT', 'ALUMNI'])) {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userID = $_SESSION['userID'];
    $userRole = $_SESSION['role'];
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);

    // Validation
    if (empty($subject) || empty($message)) {
        $redirectPage = $userRole === 'ALUMNI' ? '../alumni/feedback.php' : '../student/feedback.php';
        header("Location: $redirectPage?error=empty_fields");
        exit();
    }

    if (strlen($subject) > 150) {
        $redirectPage = $userRole === 'ALUMNI' ? '../alumni/feedback.php' : '../student/feedback.php';
        header("Location: $redirectPage?error=subject_too_long");
        exit();
    }

    // Insert feedback
    $stmt = $conn->prepare("INSERT INTO system_feedback (userID, userRole, subject, message, status, createdDate) VALUES (?, ?, ?, ?, 'NEW', NOW())");
    $stmt->bind_param("isss", $userID, $userRole, $subject, $message);

    if ($stmt->execute()) {
        $stmt->close();
        // Redirect back to feedback page with success
        $redirectPage = $userRole === 'ALUMNI' ? '../alumni/feedback.php' : '../student/feedback.php';
        header("Location: $redirectPage?status=success");
        exit();
    } else {
        $stmt->close();
        $redirectPage = $userRole === 'ALUMNI' ? '../alumni/feedback.php' : '../student/feedback.php';
        header("Location: $redirectPage?error=db_error");
        exit();
    }
} else {
    // If not POST, redirect to appropriate dashboard
    $redirectPage = $_SESSION['role'] === 'ALUMNI' ? '../alumni/dashboard.php' : '../student/dashboard.php';
    header("Location: $redirectPage");
    exit();
}
?>