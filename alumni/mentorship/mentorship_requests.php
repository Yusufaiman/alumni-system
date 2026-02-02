<?php
/**
 * VIEW MENTORSHIP REQUESTS (ALUMNI)
 * Features: Clean University Theme, Shared Topbar, and Request Management UI.
 */
session_start();
require_once "../config/db.php";

// 1. Access Control
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'ALUMNI') {
    header("Location: ../auth/login.php");
    exit();
}

$userID = $_SESSION['userID'];

// 2. Get Alumni ID
$stmt = $conn->prepare("SELECT alumniID FROM alumni WHERE userID = ? LIMIT 1");
$stmt->bind_param("i", $userID);
$stmt->execute();
$alumniID = $stmt->get_result()->fetch_assoc()['alumniID'];

// 3. Fetch Requests (Prepared Statement)
$sql = "
    SELECT 
        mr.id AS requestID,
        mr.message AS reason,
        mr.status,
        mr.created_at AS requestDate,
        mr.reviewed_at AS decisionDate,
        u.name AS studentName,
        u.email AS studentEmail,
        s.programme,
        s.yearOfStudy
    FROM mentorship_requests mr
    JOIN student s ON mr.student_id = s.studentID
    JOIN user u ON s.userID = u.userID
    WHERE mr.alumni_id = ?
    ORDER BY mr.created_at DESC
";
$fetchStmt = $conn->prepare($sql);
$fetchStmt->bind_param("i", $alumniID);
$fetchStmt->execute();
$requests = $fetchStmt->get_result();

$isDashboard = false; // Active back button in topbar
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mentorship Requests | Alumni Connect</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- CSS -->
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .mentorship-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .status-pending {
            background-color: #FEF3C7;
            color: #92400E;
        }

        .status-approved {
            background-color: #DCFCE7;
            color: #166534;
        }

        .status-rejected {
            background-color: #FECACA;
            color: #991B1B;
        }

        .request-card {
            background: var(--background-white);
            border: 1px solid var(--border-light);
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            display: flex;
            flex-direction: column;
            gap: 1rem;
            transition: all 0.2s;
        }

        .request-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .student-info h3 {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.25rem;
        }

        .student-info p {
            font-size: 0.875rem;
            color: var(--text-muted);
        }

        .reason-box {
            background-color: var(--background-light);
            padding: 1rem;
            border-radius: 0.5rem;
            font-size: 0.9375rem;
            line-height: 1.6;
            color: #374151;
        }

        .request-footer {
            margin-top: auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1rem;
            border-top: 1px solid var(--border-light);
        }

        .action-btns {
            display: flex;
            gap: 0.75rem;
        }

        .btn-action {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.8125rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
        }

        .btn-approve {
            background-color: var(--primary-blue);
            color: white;
        }

        .btn-approve:hover {
            background-color: #1d4ed8;
        }

        .btn-reject {
            background-color: #F3F4F6;
            color: #4B5563;
        }

        .btn-reject:hover {
            background-color: #E5E7EB;
            color: #111827;
        }

        .success-banner {
            background-color: #F0FDF4;
            color: #166534;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 2rem;
            border: 1px solid #DCFCE7;
            text-align: center;
            font-weight: 600;
        }

        @media (max-width: 640px) {
            .mentorship-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

    <!-- Shared Topbar -->
    <?php include_once __DIR__ . '/../components/topbar.php'; ?>

    <div class="dashboard-container">

        <header class="welcome-section">
            <h1>Mentorship Requests</h1>
            <p>Review and respond to mentorship requests from students seeking your guidance.</p>
        </header>

        <!-- Feedback Messages -->
        <?php if (isset($_GET['success'])): ?>
            <div class="success-banner">
                Request successfully
                <?php echo $_GET['success'] == 'approved' ? 'approved' : 'rejected'; ?>!
            </div>
        <?php endif; ?>

        <div class="mentorship-grid">
            <?php if ($requests->num_rows > 0): ?>
                <?php while ($row = $requests->fetch_assoc()): ?>
                    <div class="request-card">
                        <div class="request-header">
                            <div class="student-info">
                                <h3>
                                    <?php echo htmlspecialchars($row['studentName']); ?>
                                </h3>
                                <p>
                                    <?php echo htmlspecialchars($row['programme']); ?> • Year
                                    <?php echo $row['yearOfStudy']; ?>
                                </p>
                                <p style="font-size: 0.75rem; margin-top: 0.25rem;">
                                    <?php echo htmlspecialchars($row['studentEmail']); ?>
                                </p>
                            </div>
                            <span class="status-badge status-<?php echo strtolower($row['status']); ?>">
                                <?php echo $row['status']; ?>
                            </span>
                        </div>

                        <div class="reason-box">
                            <strong>Reason for request:</strong><br>
                            <?php echo nl2br(htmlspecialchars($row['reason'])); ?>
                        </div>

                        <div class="request-footer">
                            <span style="font-size: 0.75rem; color: var(--text-muted);">
                                Requested on:
                                <?php echo date('M d, Y', strtotime($row['requestDate'])); ?>
                                <?php if ($row['status'] !== 'PENDING'): ?>
                                    <br>Decided on:
                                    <?php echo date('M d, Y', strtotime($row['decisionDate'])); ?>
                                <?php endif; ?>
                            </span>

                            <?php if ($row['status'] === 'PENDING'): ?>
                                <span style="font-size: 0.8125rem; font-weight: 600; color: #92400E; background: #FEF3C7; padding: 0.4rem 0.8rem; border-radius: 0.5rem;">
                                    Awaiting CSO Approval
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div
                    style="grid-column: 1/-1; text-align: center; padding: 4rem; background: var(--background-white); border: 1px dashed var(--border-light); border-radius: 1rem;">
                    <p style="color: var(--text-muted);">You don't have any mentorship requests at the moment.</p>
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