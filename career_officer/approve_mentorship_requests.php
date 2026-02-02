<?php
/**
 * CAREER OFFICER - APPROVE MENTORSHIP REQUESTS
 * Review and approve mentorship requests submitted by students.
 */
session_start();
require_once "../config/db.php";
require_once "../config/functions.php";

// Access Control: Career Service Officer Only
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'CAREER_SERVICE_OFFICER') {
    header("Location: ../auth/login.php");
    exit();
}

$isDashboard = false;
$successMsg = "";
$errorMsg = "";

// Handle Actions (Approve/Reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['requestID'], $_POST['action'])) {
    $requestID = intval($_POST['requestID']);
    $action = $_POST['action']; // 'APPROVED' or 'REJECTED'
    $reviewerID = $_SESSION['userID'];

    if (in_array($action, ['APPROVED', 'REJECTED'])) {
        // Verify it's pending
        $checkStmt = $conn->prepare("SELECT status, student_id, alumni_id FROM mentorship_requests WHERE id = ? LIMIT 1");
        $checkStmt->bind_param("i", $requestID);
        $checkStmt->execute();
        $checkRes = $checkStmt->get_result();

        if ($checkRes->num_rows === 1) {
            $req = $checkRes->fetch_assoc();
            if ($req['status'] === 'PENDING') {
                $updateStmt = $conn->prepare("UPDATE mentorship_requests SET status = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?");
                $updateStmt->bind_param("sii", $action, $reviewerID, $requestID);

                if ($updateStmt->execute()) {
                    $successMsg = "Mentorship request " . strtolower($action) . " successfully.";

                    // Track activity
                    logActivity($conn, $reviewerID, 'CAREER_SERVICE_OFFICER', 'Processed Mentorship Request', 'Mentorship', "Set status of request #$requestID to $action");

                    // 1. Notify Student
                    $studentUserStmt = $conn->prepare("SELECT userID FROM student WHERE studentID = ?");
                    $studentUserStmt->bind_param("i", $req['student_id']);
                    $studentUserStmt->execute();
                    $sRes = $studentUserStmt->get_result();
                    if ($sRow = $sRes->fetch_assoc()) {
                        createNotification(
                            $conn,
                            "Mentorship Update",
                            "Your mentorship request has been " . strtolower($action) . ".",
                            "student/dashboard.php",
                            $sRow['userID'],
                            "mentorship"
                        );
                    }

                    // 2. Notify Alumni (Only if Approved)
                    if ($action === 'APPROVED') {
                        $alumniUserStmt = $conn->prepare("SELECT userID FROM alumni WHERE alumniID = ?");
                        $alumniUserStmt->bind_param("i", $req['alumni_id']);
                        $alumniUserStmt->execute();
                        $aRes = $alumniUserStmt->get_result();
                        if ($aRow = $aRes->fetch_assoc()) {
                            createNotification(
                                $conn,
                                "New Mentee Assigned",
                                "A new mentorship request has been approved and assigned to you.",
                                "alumni/mentorship/mentorship_requests.php",
                                $aRow['userID'],
                                "mentorship"
                            );
                        }
                    }

                } else {
                    $errorMsg = "Failed to update status.";
                }
            } else {
                $errorMsg = "Request is already processed.";
            }
        } else {
            $errorMsg = "Request not found.";
        }
    }
}

// Fetch Pending Requests
$sql = "
    SELECT 
        mr.id AS requestID, 
        mr.message AS reason, 
        mr.created_at AS requestDate,
        s_user.name AS studentName,
        s_user.email AS studentEmail,
        a_user.name AS alumniName,
        a.currentPosition,
        a.currentCompany,
        a.industry
    FROM mentorship_requests mr
    JOIN student s ON mr.student_id = s.studentID
    JOIN user s_user ON s.userID = s_user.userID
    JOIN alumni a ON mr.alumni_id = a.alumniID
    JOIN user a_user ON a.userID = a_user.userID
    WHERE mr.status = 'PENDING'
    ORDER BY mr.created_at ASC
";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approve Mentorship | Career Officer</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- CSS -->
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        /* Reusing styles from job_applications.php pattern */
        .page-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .header-section {
            margin-bottom: 2rem;
        }

        .header-section h1 {
            font-size: 1.875rem;
            font-weight: 800;
            color: var(--text-dark);
            letter-spacing: -0.025em;
            margin-bottom: 0.5rem;
        }

        .header-section p {
            color: var(--text-muted);
        }

        .requests-card {
            background: var(--background-white);
            border: 1px solid var(--border-light);
            border-radius: 1rem;
            box-shadow: var(--card-shadow);
            overflow: hidden;
        }

        .request-item {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-light);
            display: grid;
            grid-template-columns: 1.5fr 1.5fr 2fr 1fr 120px;
            gap: 1.5rem;
            align-items: center;
        }

        .request-item:last-child {
            border-bottom: none;
        }

        .info-group h4 {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.25rem;
        }

        .info-group p {
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .message-box {
            background: #F9FAFB;
            padding: 1rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            color: var(--text-dark);
            border: 1px solid var(--border-light);
            max-height: 100px;
            overflow-y: auto;
        }

        .date-badge {
            display: inline-block;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-muted);
            background: #F3F4F6;
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
        }

        .action-btns {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            width: 100%;
        }

        .btn-approve {
            background-color: #DCFCE7;
            color: #166534;
        }

        .btn-approve:hover {
            background-color: #bbf7d0;
        }

        .btn-reject {
            background-color: #FEE2E2;
            color: #991B1B;
        }

        .btn-reject:hover {
            background-color: #fecaca;
        }

        .empty-state {
            padding: 4rem 2rem;
            text-align: center;
            color: var(--text-muted);
        }

        .alert-success {
            background-color: #F0FDF4;
            color: #166534;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid #DCFCE7;
        }

        .alert-error {
            background-color: #FEF2F2;
            color: #991B1B;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid #FEE2E2;
        }

        @media (max-width: 1024px) {
            .request-item {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .action-btns {
                flex-direction: row;
            }
        }
    </style>
</head>

<body>

    <!-- Shared Topbar -->
    <?php include_once __DIR__ . '/../components/topbar.php'; ?>

    <div class="page-container">

        <div class="header-section">
            <a href="dashboard.php"
                style="display: inline-flex; align-items: center; gap: 0.5rem; color: var(--primary-blue); font-weight: 600; font-size: 0.9rem; margin-bottom: 1rem; text-decoration: none;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 12H5" />
                    <path d="M12 19l-7-7 7-7" />
                </svg>
                Back to Dashboard
            </a>
            <h1>Approve Mentorship Requests</h1>
            <p>Review and approve mentorship requests submitted by students.</p>
        </div>

        <?php if ($successMsg): ?>
            <div class="alert-success">
                <?php echo htmlspecialchars($successMsg); ?>
            </div>
        <?php endif; ?>

        <?php if ($errorMsg): ?>
            <div class="alert-error">
                <?php echo htmlspecialchars($errorMsg); ?>
            </div>
        <?php endif; ?>

        <div class="requests-card">
            <?php if ($result && $result->num_rows > 0): ?>

                <!-- Table Header for Desktop -->
                <div
                    style="padding: 1rem 1.5rem; border-bottom: 1px solid var(--border-light); background: #f8fafc; display: grid; grid-template-columns: 1.5fr 1.5fr 2fr 1fr 120px; gap: 1.5rem; font-weight: 600; font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase;">
                    <div>Student</div>
                    <div>Requested Mentor</div>
                    <div>Reason</div>
                    <div>Date</div>
                    <div>Actions</div>
                </div>

                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="request-item">

                        <!-- Student Info -->
                        <div class="info-group">
                            <h4>
                                <?php echo htmlspecialchars($row['studentName']); ?>
                            </h4>
                            <p>
                                <?php echo htmlspecialchars($row['studentEmail']); ?>
                            </p>
                        </div>

                        <!-- Mentor Info -->
                        <div class="info-group">
                            <h4>
                                <?php echo htmlspecialchars($row['alumniName']); ?>
                            </h4>
                            <p>
                                <?php echo htmlspecialchars($row['currentPosition']); ?> at
                                <?php echo htmlspecialchars($row['currentCompany']); ?>
                            </p>
                            <p class="date-badge" style="margin-top: 0.25rem;">
                                <?php echo htmlspecialchars($row['industry']); ?>
                            </p>
                        </div>

                        <!-- Reason -->
                        <div class="message-box">
                            <?php echo nl2br(htmlspecialchars($row['reason'])); ?>
                        </div>

                        <!-- Date -->
                        <div style="font-size: 0.9rem; color: var(--text-dark);">
                            <?php echo date('M d, Y', strtotime($row['requestDate'])); ?>
                            <div style="margin-top: 0.25rem;">
                                <span
                                    style="background: #FEF3C7; color: #92400E; padding: 0.15rem 0.5rem; border-radius: 99px; font-size: 0.75rem; font-weight: 700;">PENDING</span>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="action-btns">
                            <form method="POST" onsubmit="return confirm('Are you sure you want to approve this request?');">
                                <input type="hidden" name="requestID" value="<?php echo $row['requestID']; ?>">
                                <input type="hidden" name="action" value="APPROVED">
                                <button type="submit" class="btn-action btn-approve">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" style="margin-right: 4px;">
                                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                    </svg>
                                    Approve
                                </button>
                            </form>

                            <form method="POST" onsubmit="return confirm('Reject this request?');">
                                <input type="hidden" name="requestID" value="<?php echo $row['requestID']; ?>">
                                <input type="hidden" name="action" value="REJECTED">
                                <button type="submit" class="btn-action btn-reject">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" style="margin-right: 4px;">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <line x1="15" y1="9" x2="9" y2="15"></line>
                                        <line x1="9" y1="9" x2="15" y2="15"></line>
                                    </svg>
                                    Reject
                                </button>
                            </form>
                        </div>

                    </div>
                <?php endwhile; ?>

            <?php else: ?>
                <div class="empty-state">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"
                        style="color: var(--border); margin-bottom: 1rem;">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    <h3>No Pending Requests</h3>
                    <p>There are no mentorship requests pending approval at this time.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Use existing dropdown logic from dashboard if needed
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