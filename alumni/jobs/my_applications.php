<?php
/**
 * ALUMNI MY APPLICATIONS
 * View and track jobs the alumnus has applied for.
 */
session_start();
require_once "../../config/db.php";

// Access Control: Alumni Only
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'ALUMNI') {
    header("Location: ../../auth/login.php");
    exit();
}

$userID = $_SESSION['userID'];

// 📋 MY APPLICATIONS QUERY
$sql = "
    SELECT 
        ja.*, 
        jp.title, 
        jp.company, 
        jp.location 
    FROM job_application ja
    JOIN jobposting jp ON ja.jobID = jp.jobID
    WHERE ja.applicantID = ? AND ja.applicantRole = 'ALUMNI'
    ORDER BY ja.appliedDate DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userID);
$stmt->execute();
$applications = $stmt->get_result();

$isDashboard = false;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Applications | Alumni Connect</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- CSS -->
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
</head>

<body>

    <!-- Shared Topbar (Relative path adjusted) -->
    <?php include_once __DIR__ . '/../../components/topbar.php'; ?>

    <div class="dashboard-container" style="max-width: 1100px; margin: 0 auto; padding: 2rem;">

        <header style="margin-bottom: 2rem;">
            <h1 style="font-size: 1.875rem; font-weight: 800; color: var(--text-dark); margin-bottom: 0.5rem;">My
                Applications</h1>
            <p style="color: var(--text-muted);">Track the status of your submitted job and internship applications.</p>
        </header>

        <!-- Use Shared Table Component -->
        <?php include('../../components/job/MyApplicationsTableUI.php'); ?>

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