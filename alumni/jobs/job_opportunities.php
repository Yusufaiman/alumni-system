<?php
/**
 * ALUMNI - JOB OPPORTUNITIES DASHBOARD
 * List and manage job postings created by the alumni.
 */
session_start();
require_once "../config/db.php";
require_once "../config/functions.php";

// Access Control: Alumni Only
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'ALUMNI') {
    header("Location: ../auth/login.php");
    exit();
}

$userID = $_SESSION['userID'];

// Fetch Jobs with Applicant Count
$sql = "
    SELECT 
        j.*, 
        (SELECT COUNT(*) FROM job_application ja WHERE ja.jobID = j.jobID) as applicantCount
    FROM jobposting j
    WHERE j.postedByID = ? AND j.postedByRole = 'ALUMNI'
    ORDER BY j.datePosted DESC
";
$stmt = $conn->prepare($sql);
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
    <title>Manage Jobs | Alumni Connect</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- CSS -->
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/jobs.css">
    <style>
        .page-actions {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 2rem;
        }

        .btn-post {
            background-color: var(--primary-blue);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 700;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s;
        }

        .btn-post:hover {
            background-color: #1d4ed8;
            transform: translateY(-1px);
        }

        .job-table-card {
            background: white;
            border: 1px solid var(--border-light);
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: var(--card-shadow);
        }

        .job-table {
            width: 100%;
            border-collapse: collapse;
        }

        .job-table th {
            text-align: left;
            padding: 1rem 1.5rem;
            background: var(--background-light);
            color: var(--text-muted);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 700;
        }

        .job-table td {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border-light);
            vertical-align: middle;
        }

        .job-table tr:last-child td {
            border-bottom: none;
        }

        .job-title-cell {
            font-weight: 700;
            color: var(--text-dark);
        }

        .job-company-cell {
            color: var(--text-muted);
            font-size: 0.875rem;
        }

        .applicant-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #EFF6FF;
            color: var(--primary-blue);
            width: 28px;
            height: 28px;
            border-radius: 50%;
            font-weight: 700;
            font-size: 0.75rem;
        }

        .action-links {
            display: flex;
            gap: 1rem;
        }

        .action-link {
            font-size: 0.8125rem;
            font-weight: 600;
            text-decoration: none;
            color: var(--primary-blue);
        }

        .action-link:hover {
            text-decoration: underline;
        }

        .action-link.danger {
            color: #DC2626;
        }
    </style>
</head>

<body>

    <!-- Shared Topbar -->
    <?php include_once __DIR__ . '/../components/topbar.php'; ?>

    <div class="jobs-container">

        <header class="jobs-header" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1>Manage Job Opportunities</h1>
                <p>Post and manage career openings for the student community.</p>
            </div>
            <a href="post_job.php" class="btn-post">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Post New Job
            </a>
        </header>

        <?php if (isset($_GET['success'])): ?>
            <div
                style="background: #F0FDF4; border: 1px solid #DCFCE7; color: #166534; padding: 1rem; border-radius: 0.75rem; margin-bottom: 2rem; font-weight: 600;">
                ✅
                <?php echo htmlspecialchars($_GET['success'] == 'posted' ? 'Job posted successfully!' : 'Job updated successfully!'); ?>
            </div>
        <?php endif; ?>

        <div class="job-table-card">
            <?php if ($jobsList->num_rows > 0): ?>
                <table class="job-table">
                    <thead>
                        <tr>
                            <th>Job Information</th>
                            <th>Date Posted</th>
                            <th>Status</th>
                            <th>Applicants</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $jobsList->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div class="job-title-cell">
                                        <?php echo htmlspecialchars($row['title']); ?>
                                    </div>
                                    <div class="job-company-cell">
                                        <?php echo htmlspecialchars($row['company']); ?>
                                    </div>
                                </td>
                                <td>
                                    <span style="font-size: 0.875rem; color: var(--text-muted);">
                                        <?php echo date('M d, Y', strtotime($row['datePosted'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <span
                                        class="job-badge <?php echo $row['status'] == 'ACTIVE' ? 'badge-active' : 'badge-closed'; ?>">
                                        <?php echo $row['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                        <span class="applicant-count">
                                            <?php echo $row['applicantCount']; ?>
                                        </span>
                                        <a href="job_applicants.php?jobID=<?php echo $row['jobID']; ?>"
                                            class="action-link">View</a>
                                    </div>
                                </td>
                                <td>
                                    <div class="action-links">
                                        <?php if ($row['status'] == 'ACTIVE'): ?>
                                            <a href="process_job_action.php?action=close&id=<?php echo $row['jobID']; ?>"
                                                class="action-link" onclick="return confirm('Close this job posting?')">Close</a>
                                        <?php endif; ?>
                                        <a href="process_job_action.php?action=delete&id=<?php echo $row['jobID']; ?>"
                                            class="action-link danger"
                                            onclick="return confirm('Are you sure you want to delete this job posting?')">Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="text-align: center; padding: 4rem; color: var(--text-muted);">
                    <p>You haven't posted any jobs yet. Start by clicking the "Post New Job" button.</p>
                </div>
            <?php endif; ?>
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