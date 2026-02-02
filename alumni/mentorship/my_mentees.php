<?php
/**
 * MY MENTEES (ALUMNI)
 * Display students assigned for mentorship after CSO approval.
 */
session_start();
require_once "../../config/db.php";
require_once "../../config/functions.php";

// Check if user is logged in and is an ALUMNI
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'ALUMNI') {
    header("Location: ../auth/login.php");
    exit();
}

$userID = $_SESSION['userID'];

// Get alumniID safely
$alumniStmt = $conn->prepare("SELECT alumniID FROM alumni WHERE userID = ? LIMIT 1");
$alumniStmt->bind_param("i", $userID);
$alumniStmt->execute();
$alumniResult = $alumniStmt->get_result();

if ($alumniResult->num_rows === 0) {
    die("❌ Alumni profile not found. Please contact admin.");
}

$alumni = $alumniResult->fetch_assoc();
$alumniID = $alumni['alumniID'];

// Fetch approved mentees
$sql = "
    SELECT 
        mr.id AS requestID,
        mr.message AS reason,
        mr.reviewed_at AS approvedDate,
        u.name AS studentName,
        s.studentEmail,
        s.programme,
        s.yearOfStudy,
        s.skills
    FROM mentorship_requests mr
    JOIN student s ON mr.student_id = s.studentID
    JOIN user u ON s.userID = u.userID
    WHERE mr.alumni_id = ? AND mr.status = 'APPROVED'
    ORDER BY mr.reviewed_at DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $alumniID);
$stmt->execute();
$approvedMentees = $stmt->get_result();

$isDashboard = false; // For Topbar back button
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Mentees | Alumni Connect</title>
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

        .mentee-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
        }

        .mentee-card {
            background: var(--background-white);
            border: 1px solid var(--border-light);
            border-radius: 1.25rem;
            padding: 2rem;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .mentee-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 20px -8px rgba(0, 0, 0, 0.1);
        }

        .mentee-header {
            display: flex;
            align-items: center;
            gap: 1.25rem;
            margin-bottom: 1.5rem;
        }

        .avatar-lg {
            width: 64px;
            height: 64px;
            background: #F1F5F9;
            color: var(--primary-blue);
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 700;
            border: 2px solid #E2E8F0;
        }

        .mentee-name-meta h3 {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.25rem;
        }

        .mentee-name-meta p {
            font-size: 0.875rem;
            color: var(--text-muted);
        }

        .mentee-details {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            margin-bottom: 1.5rem;
            flex-grow: 1;
        }

        .detail-item {
            display: flex;
            gap: 0.875rem;
            align-items: flex-start;
        }

        .detail-icon {
            color: var(--primary-blue);
            background: rgba(37, 99, 235, 0.08);
            padding: 0.5rem;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
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
            line-height: 1.4;
        }

        .skills-container {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .skill-badge {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--primary-blue);
            background: rgba(37, 99, 235, 0.05);
            padding: 0.25rem 0.625rem;
            border-radius: 99px;
            border: 1px solid rgba(37, 99, 235, 0.1);
        }

        .card-footer {
            margin-top: auto;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .approval-info {
            font-size: 0.75rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        .empty-state {
            text-align: center;
            padding: 5rem 2rem;
            background: var(--background-white);
            border: 2px dashed var(--border-light);
            border-radius: 2rem;
            grid-column: 1 / -1;
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

        .message-preview {
            font-size: 0.875rem;
            color: #4B5563;
            background: #F9FAFB;
            padding: 1rem;
            border-radius: 0.75rem;
            margin-top: 1rem;
            border-left: 3px solid var(--primary-blue);
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
            <h1>My Mentees</h1>
            <p>Students currently assigned to you for mentorship after career officer approval.</p>
        </header>

        <div class="mentee-grid">
            <?php if ($approvedMentees && $approvedMentees->num_rows > 0): ?>
                <?php while ($row = $approvedMentees->fetch_assoc()): ?>
                    <div class="mentee-card">
                        <div class="mentee-header">
                            <div class="avatar-lg">
                                <?php echo strtoupper(substr($row['studentName'], 0, 1)); ?>
                            </div>
                            <div class="mentee-name-meta">
                                <h3>
                                    <?php echo htmlspecialchars($row['studentName']); ?>
                                </h3>
                                <p>
                                    <?php echo htmlspecialchars($row['programme']); ?>
                                </p>
                            </div>
                        </div>

                        <div class="mentee-details">
                            <div class="detail-item">
                                <div class="detail-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <span class="detail-label">Year of Study</span>
                                    <span class="detail-value">Year
                                        <?php echo htmlspecialchars($row['yearOfStudy']); ?>
                                    </span>
                                </div>
                            </div>

                            <div class="detail-item">
                                <div class="detail-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z">
                                        </path>
                                        <polyline points="22,6 12,13 2,6"></polyline>
                                    </svg>
                                </div>
                                <div>
                                    <span class="detail-label">Email Address</span>
                                    <span class="detail-value">
                                        <?php echo htmlspecialchars($row['studentEmail']); ?>
                                    </span>
                                </div>
                            </div>

                            <?php if ($row['skills']): ?>
                                <div class="detail-item">
                                    <div class="detail-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                            fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                                            stroke-linejoin="round">
                                            <polyline points="16 18 22 12 16 6"></polyline>
                                            <polyline points="8 6 2 12 8 18"></polyline>
                                        </svg>
                                    </div>
                                    <div>
                                        <span class="detail-label">Skills & Expertise</span>
                                        <div class="skills-container">
                                            <?php
                                            $skills = explode(',', $row['skills']);
                                            foreach ($skills as $skill):
                                                if (trim($skill)): ?>
                                                    <span class="skill-badge">
                                                        <?php echo htmlspecialchars(trim($skill)); ?>
                                                    </span>
                                                <?php endif; endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="message-preview">
                                <span class="detail-label"
                                    style="margin-bottom: 0.5rem; display: block; color: var(--primary-blue);">Request
                                    Message</span>
                                "
                                <?php echo htmlspecialchars($row['reason']); ?>"
                            </div>
                        </div>

                        <div class="card-footer">
                            <span class="approval-info">
                                Assigned on
                                <?php echo date('M d, Y', strtotime($row['approvedDate'])); ?>
                            </span>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M9 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </div>
                    <h3 style="font-size: 1.5rem; font-weight: 700; color: var(--text-dark); margin-bottom: 0.5rem;">No
                        assigned mentees yet</h3>
                    <p style="color: var(--text-muted); max-width: 450px; margin: 0 auto;">
                        Once the Career Service Officer approves a student's mentorship request for you, their details and
                        academic profile will appear here.
                    </p>
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