<?php
/**
 * MY MENTOR
 * Display approved alumni mentors for the student.
 */
session_start();
require_once "../../config/db.php";
require_once "../../config/functions.php";

// Check if user is logged in and is a STUDENT
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'STUDENT') {
    header("Location: ../auth/login.php");
    exit();
}

$userID = $_SESSION['userID'];

// Get studentID
$studentStmt = $conn->prepare("SELECT studentID FROM student WHERE userID = ? LIMIT 1");
$studentStmt->bind_param("i", $userID);
$studentStmt->execute();
$studentResult = $studentStmt->get_result();

if ($studentResult->num_rows === 0) {
    die("❌ Student profile not found.");
}

$student = $studentResult->fetch_assoc();
$studentID = $student['studentID'];

// Fetch approved mentors
$sql = "
    SELECT 
        mr.id AS requestID,
        mr.message,
        mr.reviewed_at AS approvedDate,
        u.name,
        a.graduationYear,
        a.currentPosition,
        a.currentCompany,
        a.industry
    FROM mentorship_requests mr
    JOIN alumni a ON mr.alumni_id = a.alumniID
    JOIN user u ON a.userID = u.userID
    WHERE mr.student_id = ? AND mr.status = 'APPROVED'
    ORDER BY mr.reviewed_at DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $studentID);
$stmt->execute();
$approvedMentors = $stmt->get_result();

$isDashboard = false; // For Topbar back button
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Mentor | Alumni Connect</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- CSS -->
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <link rel="stylesheet" href="../../assets/css/directory.css">
    <style>
        .mentorship-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }

        .page-header {
            margin-bottom: 2.5rem;
        }

        .page-header h1 {
            font-size: 2.25rem;
            font-weight: 800;
            color: var(--text-dark);
            letter-spacing: -0.025em;
            margin-bottom: 0.5rem;
        }

        .page-header p {
            color: var(--text-muted);
            font-size: 1.1rem;
        }

        .mentor-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
        }

        .mentor-card {
            background: var(--background-white);
            border: 1px solid var(--border-light);
            border-radius: 1.25rem;
            padding: 2rem;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
        }

        .mentor-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 20px -8px rgba(0, 0, 0, 0.1);
        }

        .mentor-header {
            display: flex;
            align-items: center;
            gap: 1.25rem;
            margin-bottom: 1.5rem;
        }

        .avatar-lg {
            width: 64px;
            height: 64px;
            background: var(--primary-blue);
            color: white;
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .mentor-name-meta h3 {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.25rem;
        }

        .mentor-name-meta p {
            font-size: 0.875rem;
            color: var(--text-muted);
        }

        .mentor-details {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--border-light);
            margin-bottom: 1.5rem;
        }

        .detail-item {
            display: flex;
            gap: 1rem;
            align-items: flex-start;
        }

        .detail-icon {
            color: var(--primary-blue);
            margin-top: 2px;
        }

        .detail-label {
            display: block;
            font-size: 0.75rem;
            text-transform: uppercase;
            font-weight: 700;
            color: var(--text-muted);
            letter-spacing: 0.05em;
            margin-bottom: 0.125rem;
        }

        .detail-value {
            font-size: 0.9375rem;
            font-weight: 600;
            color: var(--text-dark);
        }

        .approval-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: #F0FDF4;
            color: #166534;
            border-radius: 99px;
            font-size: 0.8125rem;
            font-weight: 700;
        }

        .empty-state {
            text-align: center;
            padding: 5rem 2rem;
            background: var(--background-white);
            border: 2px dashed var(--border-light);
            border-radius: 2rem;
        }

        .empty-icon {
            width: 80px;
            height: 80px;
            background: #F8FAFC;
            color: #94A3B8;
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
        }
    </style>
</head>

<body>

    <!-- Shared Topbar -->
    <?php include_once __DIR__ . '/../../components/topbar.php'; ?>

    <div class="mentorship-container">

        <!-- Back Link -->
        <a href="../dashboard.php"
            style="display: inline-flex; align-items: center; gap: 0.5rem; color: var(--primary-blue); font-weight: 600; text-decoration: none; margin-bottom: 2rem; font-size: 0.95rem;">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M19 12H5" />
                <path d="M12 19l-7-7 7-7" />
            </svg>
            Back to Dashboard
        </a>

        <!-- Page Header -->
        <header class="page-header">
            <h1>My Mentor</h1>
            <p>Your approved alumni mentor(s) providing guidance and industry insights.</p>
        </header>

        <?php if ($approvedMentors->num_rows > 0): ?>
            <div class="mentor-grid">
                <?php while ($row = $approvedMentors->fetch_assoc()): ?>
                    <div class="mentor-card">
                        <div class="mentor-header">
                            <div class="avatar-lg">
                                <?php echo strtoupper(substr($row['name'], 0, 1)); ?>
                            </div>
                            <div class="mentor-name-meta">
                                <h3>
                                    <?php echo htmlspecialchars($row['name']); ?>
                                </h3>
                                <p>Class of
                                    <?php echo htmlspecialchars($row['graduationYear']); ?>
                                </p>
                            </div>
                        </div>

                        <div class="mentor-details">
                            <div class="detail-item">
                                <div class="detail-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                                        <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                                    </svg>
                                </div>
                                <div>
                                    <span class="detail-label">Position & Company</span>
                                    <span class="detail-value">
                                        <?php echo htmlspecialchars($row['currentPosition'] . ' at ' . $row['currentCompany']); ?>
                                    </span>
                                </div>
                            </div>

                            <div class="detail-item">
                                <div class="detail-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <path
                                            d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z">
                                        </path>
                                        <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                                        <line x1="12" y1="22.08" x2="12" y2="12"></line>
                                    </svg>
                                </div>
                                <div>
                                    <span class="detail-label">Industry</span>
                                    <span class="detail-value">
                                        <?php echo htmlspecialchars($row['industry'] ?: 'General'); ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="mentor-footer">
                            <div class="approval-badge">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                </svg>
                                Approved on
                                <?php echo date('M d, Y', strtotime($row['approvedDate'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                </div>
                <h3 style="font-size: 1.5rem; font-weight: 700; color: var(--text-dark); margin-bottom: 0.5rem;">No approved
                    mentors yet</h3>
                <p style="color: var(--text-muted); max-width: 400px; margin: 0 auto;">
                    Once your mentorship request is approved by the Career Service Officer, your mentor's details will
                    appear here.
                </p>
                <div style="margin-top: 2rem;">
                    <a href="request_mentorship.php" class="btn-primary"
                        style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.875rem 1.75rem; background: var(--primary-blue); color: white; border-radius: 0.75rem; text-decoration: none; font-weight: 700;">
                        Submit Request
                    </a>
                </div>
            </div>
        <?php endif; ?>

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