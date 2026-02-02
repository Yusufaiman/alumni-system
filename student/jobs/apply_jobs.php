<?php
/**
 * STUDENT JOB OPPORTUNITIES
 * Browse and apply for jobs posted by alumni.
 */
session_start();
require_once "../../config/db.php";
require_once "../../config/functions.php";

// Access Control: Student Only
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'STUDENT') {
    header("Location: ../../auth/login.php");
    exit();
}

$userID = $_SESSION['userID'];

// Fetch Active Jobs and check if student already applied
$sql = "
    SELECT 
        j.*, 
        ja.status as applicationStatus,
        u.name as posterName
    FROM jobposting j
    LEFT JOIN job_application ja ON j.jobID = ja.jobID AND ja.applicantID = ? AND ja.applicantRole = 'STUDENT'
    JOIN user u ON j.postedByID = u.userID
    WHERE j.status = 'ACTIVE'
    ORDER BY j.datePosted DESC
";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("SQL Prepare Failed: " . $conn->error);
}

$stmt->bind_param("i", $userID);
$stmt->execute();
$jobsList = $stmt->get_result();

$isDashboard = false;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Opportunities | Alumni Connect</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- CSS -->
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <link rel="stylesheet" href="../../assets/css/jobs.css">
</head>

<body>

    <!-- Shared Topbar -->
    <?php include_once __DIR__ . '/../../components/topbar.php'; ?>

    <div class="jobs-container">

        <header class="jobs-header">
            <h1>Job Opportunities</h1>
            <p>Browse and apply for career openings shared by our successful alumni network.</p>
        </header>

        <!-- Feedback for Application -->
        <?php if (isset($_GET['success'])): ?>
            <div
                style="background: #F0FDF4; border: 1px solid #DCFCE7; color: #166534; padding: 1rem; border-radius: 0.75rem; margin-bottom: 2rem; font-weight: 600;">
                ✅ Application submitted! The alumni poster has been notified.
            </div>
        <?php elseif (isset($_GET['error'])): ?>
            <div
                style="background: #FEF2F2; border: 1px solid #FEE2E2; color: #991B1B; padding: 1rem; border-radius: 0.75rem; margin-bottom: 2rem; font-weight: 600;">
                ❌
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Job Grid Component -->
        <?php include('../../components/job/JobGridUI.php'); ?>
    </div>
    </div>

    <!-- Dropdown Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const profileBtn = document.getElementById('profileBtn');
            const profileDropdown = document.getElementById('profileDropdown');

            if (profileBtn && profileDropdown) {
                profileBtn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    profileDropdown.classList.toggle('show');
                });

                document.addEventListener('click', function (e) {
                    if (!profileDropdown.contains(e.target) && !profileBtn.contains(e.target)) {
                        profileDropdown.classList.remove('show');
                    }
                });
            }
        });
    </script>

</body>

</html>