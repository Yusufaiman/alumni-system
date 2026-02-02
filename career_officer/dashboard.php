<?php
/**
 * CAREER SERVICE OFFICER (CSO) DASHBOARD
 * Role-specific dashboard focusing on job management, mentorship oversight, and events.
 * UI is consistent with Student and Alumni dashboards.
 */
session_start();

// 6. ADD AUTH GUARD IN DASHBOARD
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'CAREER_SERVICE_OFFICER') {
    header("Location: ../auth/login.php");
    exit;
}

$csoName = $_SESSION['name'] ?? 'Career Service Officer';
$isDashboard = true; // Disables the back button in topbar
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSO Dashboard | Alumni Connect</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- CSS - Reusing the same dashboard styles -->
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>

<body>

    <!-- Shared Top Navigation Bar -->
    <?php include_once __DIR__ . '/../components/topbar.php'; ?>

    <div class="dashboard-container">

        <!-- Welcome Section -->
        <section class="welcome-section">
            <h1>Welcome back,
                <?php echo htmlspecialchars($csoName); ?>
            </h1>
            <p>What would you like to manage today?</p>
        </section>

        <!-- Actions Grid (2 x 3) -->
        <div class="actions-grid">

            <!-- 1. Post Job Opportunities -->
            <a href="jobs/post_job.php" class="action-card">
                <div class="card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                        <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                    </svg>
                </div>
                <h3>Post Job Opportunities</h3>
                <p>Post new job and internship opportunities for students.</p>
            </a>

            <!-- 2. View Job Applications -->
            <a href="jobs/job_applications.php" class="action-card">
                <div class="card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
                        <rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect>
                        <line x1="12" y1="11" x2="12" y2="11"></line>
                        <line x1="12" y1="16" x2="12" y2="16"></line>
                        <line x1="8" y1="11" x2="8" y2="11"></line>
                        <line x1="8" y1="16" x2="8" y2="16"></line>
                    </svg>
                </div>
                <h3>View Job Applications</h3>
                <p>Review student applications submitted for job postings.</p>
            </a>

            <!-- 3. Approve Job Applications -->
            <a href="approved_applications.php" class="action-card">
                <div class="card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                </div>
                <h3>Approve Job Applications</h3>
                <p>View all approved student and alumni job applications.</p>
            </a>

            <!-- 4. Approve Mentorship Requests -->
            <a href="approve_mentorship_requests.php" class="action-card">
                <div class="card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="8.5" cy="7" r="4"></circle>
                        <polyline points="17 11 19 13 23 9"></polyline>
                    </svg>
                </div>
                <h3>Approve Mentorship Requests</h3>
                <p>Review and approve mentorship requests between students and alumni.</p>
            </a>

            <!-- 5. Manage Events -->
            <a href="events/manage_events.php" class="action-card">
                <div class="card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                </div>
                <h3>Manage Events</h3>
                <p>Create, update, and manage alumni and university events.</p>
            </a>



            <!-- 7. Publish Announcements -->
            <a href="announcements/publish_announcements.php" class="action-card">
                <div class="card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M11 5L6 9H2v6h4l5 4V5z"></path>
                        <path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"></path>
                    </svg>
                </div>
                <h3>Publish Announcements</h3>
                <p>Publish career-related announcements and updates for students and alumni.</p>
            </a>

        </div>
    </div>

    <!-- Dropdown Interaction Logic -->
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