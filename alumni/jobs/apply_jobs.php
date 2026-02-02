<?php
/**
 * ALUMNI BROWSE JOBS
 * Alumni can browse all active job opportunities.
 * Reuses the same UI as Student Job Opportunities.
 */
session_start();
require_once "../../config/db.php";
require_once "../../config/functions.php";

// Access Control: Alumni Only
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'ALUMNI') {
    header("Location: ../../auth/login.php");
    exit();
}

$userID = $_SESSION['userID'];

// Fetch Active Jobs and check if alumni already applied
$sql = "
    SELECT 
        j.*, 
        ja.status as applicationStatus,
        u.name as posterName
    FROM jobposting j
    LEFT JOIN job_application ja ON j.jobID = ja.jobID AND ja.applicantID = ? AND ja.applicantRole = 'ALUMNI'
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
    <title>Browse Jobs | Alumni Connect</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- CSS - Reusing exact same styles -->
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <link rel="stylesheet" href="../../assets/css/jobs.css">
</head>

<body>

    <!-- Shared Topbar (Relative path adjusted for alumni folder) -->
    <?php include_once __DIR__ . '/../../components/topbar.php'; ?>

    <div class="jobs-container">

        <header class="jobs-header">
            <h1>Browse Job Opportunities</h1>
            <p>Explore career openings and internship opportunities across the network.</p>
        </header>

        <!-- Use the Shared Component -->
        <?php include('../../components/job/JobGridUI.php'); ?>

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