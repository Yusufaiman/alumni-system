<?php
/**
 * CAREER SERVICE OFFICER - POST NEW JOB
 * Reuses the shared Job Post component.
 */
session_start();
require_once "../../config/db.php";
require_once "../../config/functions.php";

// Access Control: CSO Only
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'CAREER_SERVICE_OFFICER') {
    header("Location: ../../auth/login.php");
    exit();
}

// 1. Process Logic BEFORE any HTML output
require_once "../../components/job/PostJobAction.php";

$isDashboard = false;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post New Job | CSO Dashboard</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- CSS - Reusing exact same styles -->
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <link rel="stylesheet" href="../../assets/css/jobs.css">
    <style>
        .form-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 2.5rem;
            border-radius: 1rem;
            border: 1px solid var(--border-light);
            box-shadow: var(--card-shadow);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            font-size: 0.875rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border-light);
            border-radius: 0.5rem;
            font-family: inherit;
            font-size: 1rem;
            transition: border-color 0.2s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-blue);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 120px;
        }

        .btn-submit-job {
            background: var(--primary-blue);
            color: white;
            border: none;
            padding: 0.875rem 2rem;
            border-radius: 0.5rem;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            width: 100%;
            transition: background 0.2s;
        }

        .btn-submit-job:hover {
            background: #1d4ed8;
        }

        .error-banner {
            background: #FEF2F2;
            border: 1px solid #FEE2E2;
            color: #991B1B;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            font-weight: 600;
        }
    </style>
</head>

<body>

    <!-- Shared Topbar -->
    <?php include_once __DIR__ . '/../../components/topbar.php'; ?>

    <div class="jobs-container">
        <header class="jobs-header" style="text-align: center; margin-bottom: 3rem;">
            <h1>Post New Job</h1>
            <p>Share a new career opportunity with students and alumni.</p>
        </header>

        <!-- Use the Shared Component -->
        <?php include('../../components/job/PostJobForm.php'); ?>
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