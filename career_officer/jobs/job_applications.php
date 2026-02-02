<?php
/**
 * CAREER SERVICE OFFICER - VIEW JOB APPLICATIONS
 * Reuses the shared Job Applications Table component.
 */
session_start();
require_once "../../config/db.php";
require_once "../../config/functions.php";

// Access Control: CSO Only
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'CAREER_SERVICE_OFFICER') {
    header("Location: ../../auth/login.php");
    exit();
}

$isDashboard = false;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Applications | CSO Dashboard</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- CSS - Reusing exact same styles -->
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <link rel="stylesheet" href="../../assets/css/jobs.css">
    <style>
        .job-section {
            background: var(--background-white);
            border: 1px solid var(--border-light);
            border-radius: 1rem;
            margin-bottom: 2rem;
            overflow: hidden;
            box-shadow: var(--card-shadow);
        }

        .job-section-header {
            padding: 1.5rem;
            background: var(--background-light);
            border-bottom: 1px solid var(--border-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .job-section-header h2 {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--text-dark);
        }

        .applicants-list {
            padding: 1.5rem;
        }

        .applicant-row {
            display: grid;
            grid-template-columns: 2fr 2fr 1fr 1.5fr;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            border-bottom: 1px solid var(--border-light);
        }

        .applicant-row:last-child {
            border-bottom: none;
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .status-PENDING {
            background-color: #FEF3C7;
            color: #92400E;
        }

        .status-APPROVED {
            background-color: #DCFCE7;
            color: #166534;
        }

        .status-REJECTED {
            background-color: #FECACA;
            color: #991B1B;
        }

        .action-btns {
            display: flex;
            gap: 0.5rem;
        }

        .btn-sm {
            padding: 0.4rem 0.8rem;
            font-size: 0.75rem;
            border-radius: 0.375rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }

        .btn-accept {
            background: var(--primary-blue);
            color: white;
        }

        .btn-reject {
            background: #F3F4F6;
            color: #4B5563;
        }

        .btn-accept:hover {
            background: #1d4ed8;
        }

        .btn-reject:hover {
            background: #E5E7EB;
            color: #111827;
        }
    </style>
</head>

<body>

    <!-- Shared Topbar -->
    <?php include_once __DIR__ . '/../../components/topbar.php'; ?>

    <div class="jobs-container">
        <header class="jobs-header">
            <?php if (isset($_GET['jobID'])): ?>
                <div style="margin-bottom: 1rem;">
                    <a href="job_applications.php"
                        style="color: var(--primary-blue); text-decoration: none; font-size: 0.875rem; font-weight: 600;">&larr;
                        Back to All Applications</a>
                </div>
            <?php endif; ?>
            <h1>Job Applications</h1>
            <p>Review and manage student applications for jobs posted by you.</p>
        </header>

        <!-- Use the Shared Component -->
        <?php include('../../components/job/JobApplicationsTable.php'); ?>
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