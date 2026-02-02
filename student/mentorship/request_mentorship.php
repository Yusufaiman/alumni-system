<?php
/**
 * REQUEST MENTORSHIP
 * Redesigned with Selection-based Cards and Clean Theme.
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

// Get studentID safely
$studentStmt = $conn->prepare("SELECT studentID FROM student WHERE userID = ? LIMIT 1");
$studentStmt->bind_param("i", $userID);
$studentStmt->execute();
$studentResult = $studentStmt->get_result();

if ($studentResult->num_rows === 0) {
    die("❌ Student profile not found. Please contact admin.");
}

$student = $studentResult->fetch_assoc();
$studentID = $student['studentID'];

$successMsg = "";
$errorMsg = "";

// Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['submit_request'])) {
    $alumniID = intval($_POST['alumniID'] ?? 0);
    $message = trim($_POST['reason'] ?? "");

    if ($alumniID <= 0 || empty($message)) {
        $errorMsg = "Please select an alumni mentor and provide a reason.";
    } else {
        // 1. Check for duplicate pending requests
        $dupStmt = $conn->prepare("SELECT id FROM mentorship_requests WHERE student_id = ? AND alumni_id = ? AND status = 'PENDING'");
        $dupStmt->bind_param("ii", $studentID, $alumniID);
        $dupStmt->execute();
        $dupRes = $dupStmt->get_result();

        if ($dupRes->num_rows > 0) {
            $errorMsg = "You already have a pending request with this mentor.";
        } else {
            // 2. Insert into new table
            $insertStmt = $conn->prepare("
                INSERT INTO mentorship_requests (student_id, alumni_id, message, status, created_at)
                VALUES (?, ?, ?, 'PENDING', NOW())
            ");
            $insertStmt->bind_param("iis", $studentID, $alumniID, $message);

            if ($insertStmt->execute()) {
                $lastID = $insertStmt->insert_id;
                $successMsg = "Mentorship request submitted successfully and is pending approval.";

                // Track activity
                logActivity($conn, $userID, 'STUDENT', 'Requested Mentorship', 'Mentorship', "Requested mentorship from alumni ID #$alumniID");

                // 3. Notification Logic: Notify ALL Career Service Officers (CSO)
                $csoStmt = $conn->query("SELECT userID FROM user WHERE role = 'CAREER_SERVICE_OFFICER'");
                if ($csoStmt) {
                    $studentName = $_SESSION['name'];
                    while ($cso = $csoStmt->fetch_assoc()) {
                        createNotification(
                            $conn,
                            "New Mentorship Request",
                            "New mentorship request from $studentName awaiting approval.",
                            "career_officer/approve_mentorship_requests.php",
                            $cso['userID'],
                            "mentorship"
                        );
                    }
                }
            } else {
                $errorMsg = "Something went wrong: " . $conn->error;
            }
        }
    }
}

// Fetch Alumni List for the Selector (ONLY role = 'ALUMNI')
$alumni_sql = "
    SELECT 
        a.alumniID, 
        u.name, 
        a.graduationYear, 
        a.currentPosition, 
        a.currentCompany, 
        a.industry 
    FROM alumni a
    JOIN user u ON a.userID = u.userID
    WHERE u.role = 'ALUMNI' AND u.status = 'ACTIVE'
    ORDER BY u.name ASC
";
$alumniList = $conn->query($alumni_sql);

$isDashboard = false; // For Topbar back button
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Mentorship | Alumni Connect</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- CSS -->
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <link rel="stylesheet" href="../../assets/css/directory.css">
    <link rel="stylesheet" href="../../assets/css/mentorship.css">
</head>

<body>

    <!-- Shared Top Navigation Bar -->
    <?php include_once __DIR__ . '/../../components/topbar.php'; ?>

    <div class="mentorship-container">

        <!-- Page Header -->
        <header class="page-header">
            <h1>Request Mentorship</h1>
            <p>Connect with an alumni mentor for personalized career guidance and industry insights.</p>
        </header>

        <?php if ($successMsg): ?>
            <div class="mentorship-card"
                style="border-color: #DCFCE7; background-color: #F0FDF4; display: flex; align-items: center; gap: 1rem;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="#166534" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                <p style="color: #166534; font-weight: 600;"><?php echo $successMsg; ?></p>
            </div>
        <?php endif; ?>

        <?php if ($errorMsg): ?>
            <div class="mentorship-card"
                style="border-color: #FEE2E2; background-color: #FEF2F2; display: flex; align-items: center; gap: 1rem;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="#991B1B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <p style="color: #991B1B; font-weight: 600;"><?php echo $errorMsg; ?></p>
            </div>
        <?php endif; ?>

        <form id="mentorshipForm" method="POST" action="">
            <!-- Alumni Selection Card -->
            <section class="mentorship-card">
                <h3>1. Select an Alumni Mentor</h3>
                <div class="selection-grid">
                    <?php if ($alumniList->num_rows > 0): ?>
                        <?php while ($row = $alumniList->fetch_assoc()): ?>
                            <div class="alumni-card selectable" data-id="<?php echo $row['alumniID']; ?>"
                                onclick="selectAlumni(this)">
                                <div class="alumni-info-header">
                                    <div class="avatar-circle">
                                        <?php echo strtoupper(substr($row['name'], 0, 1)); ?>
                                    </div>
                                    <div class="alumni-meta-name">
                                        <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                                        <p>Class of <?php echo htmlspecialchars($row['graduationYear']); ?></p>
                                    </div>
                                </div>
                                <div class="alumni-details">
                                    <div class="detail-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round">
                                            <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                                            <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                                        </svg>
                                        <div>
                                            <span class="detail-label">Position & Company</span>
                                            <span
                                                class="detail-value"><?php echo htmlspecialchars($row['currentPosition'] . ' at ' . $row['currentCompany']); ?></span>
                                        </div>
                                    </div>
                                    <div class="detail-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round">
                                            <path
                                                d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z">
                                            </path>
                                            <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                                            <line x1="12" y1="22.08" x2="12" y2="12"></line>
                                        </svg>
                                        <div>
                                            <span class="detail-label">Industry</span>
                                            <span class="badge"
                                                style="background: rgba(37, 99, 235, 0.05); color: var(--primary-blue);"><?php echo htmlspecialchars($row['industry'] ?: 'General'); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <!-- Hidden Radio for Form Submit -->
                                <input type="radio" name="alumniID" value="<?php echo $row['alumniID']; ?>"
                                    style="display: none;">
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <p>No alumni mentors available at the moment.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Reason Section -->
            <section class="mentorship-card">
                <h3>2. Why do you want mentorship?</h3>
                <div class="form-group">
                    <p style="color: var(--text-muted); font-size: 0.875rem; margin-bottom: 1rem;">
                        Briefly explain your career goals and what you hope to learn from this mentor.
                    </p>
                    <textarea name="reason" id="reasonText" class="reason-textarea"
                        placeholder="I am interested in your career path as a Software Engineer and would love to learn more about..."
                        required></textarea>
                </div>
            </section>

            <!-- Submit Action -->
            <div class="action-bar">
                <button type="submit" name="submit_request" id="submitBtn" class="btn-submit" disabled>
                    Submit Mentorship Request
                </button>
            </div>
        </form>
    </div>

    <!-- Dropdown & Selection Logic -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Profile Dropdown
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

            // Form Validation
            const reasonText = document.getElementById('reasonText');
            const submitBtn = document.getElementById('submitBtn');
            const radios = document.getElementsByName('alumniID');

            function validateForm() {
                let anySelected = false;
                radios.forEach(radio => {
                    if (radio.checked) anySelected = true;
                });

                if (anySelected && reasonText.value.trim().length > 10) {
                    submitBtn.disabled = false;
                } else {
                    submitBtn.disabled = true;
                }
            }

            reasonText.addEventListener('input', validateForm);

            // Initial validation check
            validateForm();
        });

        function selectAlumni(card) {
            // Remove selection from others
            document.querySelectorAll('.alumni-card.selectable').forEach(c => {
                c.classList.remove('selected');
            });

            // Add selection to clicked
            card.classList.add('selected');

            // Check the hidden radio
            const radio = card.querySelector('input[type="radio"]');
            radio.checked = true;

            // Trigger validation
            const reasonText = document.getElementById('reasonText');
            const submitBtn = document.getElementById('submitBtn');
            if (reasonText.value.trim().length > 10) {
                submitBtn.disabled = false;
            }
        }
    </script>

</body>

</html>