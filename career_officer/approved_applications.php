<?php
/**
 * CAREER SERVICE OFFICER - APPROVED APPLICATIONS
 * View student and alumni job applications that have been approved.
 */
session_start();
require_once "../config/db.php";

// 1. Access Control: CSO Only
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'CAREER_SERVICE_OFFICER') {
    header("Location: ../auth/login.php");
    exit;
}

$userID = $_SESSION['userID'];

// 2. Fetch Approved Applications
// Joining career_service_officer to ensure we only get jobs belonging to this CSO profile
$sql = "
    SELECT 
        ja.applicationID,
        ja.status,
        ja.appliedDate,
        ja.applicantRole,
        jp.title AS jobTitle,
        jp.company,
        u.name AS applicantName,
        u.email AS applicantEmail
    FROM job_application ja
    JOIN jobposting jp ON ja.jobID = jp.jobID
    JOIN career_service_officer cso ON jp.careerOfficerID = cso.csoID
    JOIN user u ON ja.applicantID = u.userID
    WHERE 
        cso.userID = ? 
        AND ja.status = 'APPROVED'
    ORDER BY ja.appliedDate DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userID);
$stmt->execute();
$approvedList = $stmt->get_result();

$isDashboard = false;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approved Applications | CSO Dashboard</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- CSS -->
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/jobs.css">
    <style>
        .approved-card {
            background: white;
            border: 1px solid var(--border-light);
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--card-shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .applicant-info h3 {
            font-size: 1.125rem;
            color: var(--text-dark);
            margin-bottom: 0.25rem;
        }

        .job-ref {
            font-size: 0.875rem;
            color: var(--primary-blue);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .applicant-details {
            font-size: 0.875rem;
            color: var(--text-muted);
            display: flex;
            gap: 1.5rem;
            align-items: center;
        }

        .role-badge {
            font-size: 0.7rem;
            padding: 0.25rem 0.625rem;
            border-radius: 4px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .badge-student {
            background: #E0F2FE;
            color: #0369A1;
        }

        .badge-alumni {
            background: #F0FDF4;
            color: #166534;
        }

        .status-section {
            text-align: right;
        }

        .approved-date {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-top: 0.5rem;
        }

        .status-pill {
            background: #DCFCE7;
            color: #166534;
            padding: 0.375rem 1rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 700;
            border: 1px solid #BBF7D0;
        }
    </style>
</head>

<body>

    <!-- Shared Topbar -->
    <?php include_once __DIR__ . '/../components/topbar.php'; ?>

    <div class="jobs-container">
        <header class="jobs-header">
            <div style="margin-bottom: 1rem;">
                <a href="dashboard.php"
                    style="color: var(--primary-blue); text-decoration: none; font-size: 0.875rem; font-weight: 600;">&larr;
                    Back to Dashboard</a>
            </div>
            <h1>Approved Applications</h1>
            <p>View and manage job applications for your postings that have been successfully approved.</p>
        </header>

        <div class="approved-grid">
            <?php if ($approvedList->num_rows > 0): ?>
                <?php while ($row = $approvedList->fetch_assoc()): ?>
                    <div class="approved-card">
                        <div class="applicant-info">
                            <div class="job-ref">
                                <?php echo htmlspecialchars($row['jobTitle']); ?> @
                                <?php echo htmlspecialchars($row['company']); ?>
                            </div>
                            <h3>
                                <?php echo htmlspecialchars($row['applicantName']); ?>
                            </h3>
                            <div class="applicant-details">
                                <span>
                                    <?php echo htmlspecialchars($row['applicantEmail']); ?>
                                </span>
                                <span
                                    class="role-badge <?php echo $row['applicantRole'] === 'STUDENT' ? 'badge-student' : 'badge-alumni'; ?>">
                                    <?php echo $row['applicantRole']; ?>
                                </span>
                            </div>
                        </div>
                        <div class="status-section">
                            <span class="status-pill">✓ APPROVED</span>
                            <div class="approved-date">Applied on
                                <?php echo date('M d, Y', strtotime($row['appliedDate'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div
                    style="text-align: center; padding: 5rem 2rem; background: white; border: 1px dashed var(--border-light); border-radius: 1rem;">
                    <p style="color: var(--text-muted); font-size: 1.125rem;">No approved job applications yet.</p>
                    <a href="job_applications.php"
                        style="display:inline-block; margin-top: 1rem; color: var(--primary-blue); font-weight: 600;">Review
                        Pending Applications</a>
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