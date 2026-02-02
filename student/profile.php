<?php
/**
 * STUDENT UPDATE PROFILE PAGE
 * Allows students to view and edit their profile details.
 */
session_start();
require_once "../config/db.php";

// Access Control: Student Only
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'STUDENT') {
    header("Location: ../auth/login.php");
    exit();
}

$userID = $_SESSION['userID'];
$successMsg = "";
$errorMsg = "";

// 1. Fetch Current Data
$sql = "SELECT u.name, u.email, s.* 
        FROM user u 
        JOIN student s ON u.userID = s.userID 
        WHERE u.userID = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userID);
$stmt->execute();
$userData = $stmt->get_result()->fetch_assoc();

if (!$userData) {
    die("Profile not found.");
}

// 2. Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $programme = trim($_POST['programme']);
    $faculty = trim($_POST['faculty']);
    $yearOfStudy = $_POST['yearOfStudy'];
    $enrollmentStatus = $_POST['enrollmentStatus'];
    $studentEmail = trim($_POST['studentEmail']);
    $phone = trim($_POST['phone']);
    $linkedinURL = trim($_POST['linkedinURL']);
    $careerInterest = trim($_POST['careerInterest']);
    $skills = trim($_POST['skills']);
    $internshipAvailability = $_POST['internshipAvailability'];

    // File Upload Logic
    $profilePhoto = $userData['profilePhoto']; // Default to existing
    if (isset($_FILES['profilePhoto']) && $_FILES['profilePhoto']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['profilePhoto']['tmp_name'];
        $fileName = $_FILES['profilePhoto']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $newFileName = $userID . "_" . time() . "." . $fileExtension;
        $uploadDir = "../uploads/profile_photos/";

        if (!is_dir($uploadDir))
            mkdir($uploadDir, 0777, true);

        if (move_uploaded_file($fileTmpPath, $uploadDir . $newFileName)) {
            $profilePhoto = "uploads/profile_photos/" . $newFileName;
        }
    }

    // Begin Transaction
    mysqli_begin_transaction($conn);
    try {
        // Update User Table
        $stmtUser = $conn->prepare("UPDATE user SET name = ? WHERE userID = ?");
        $stmtUser->bind_param("si", $name, $userID);
        $stmtUser->execute();

        // Update Student Table
        $stmtStudent = $conn->prepare("UPDATE student SET 
            programme = ?, 
            faculty = ?, 
            yearOfStudy = ?, 
            enrollmentStatus = ?, 
            studentEmail = ?, 
            phone = ?, 
            linkedinURL = ?, 
            careerInterest = ?, 
            skills = ?, 
            internshipAvailability = ?, 
            profilePhoto = ? 
            WHERE userID = ?");
        $stmtStudent->bind_param(
            "ssissssssssi",
            $programme,
            $faculty,
            $yearOfStudy,
            $enrollmentStatus,
            $studentEmail,
            $phone,
            $linkedinURL,
            $careerInterest,
            $skills,
            $internshipAvailability,
            $profilePhoto,
            $userID
        );
        $stmtStudent->execute();

        mysqli_commit($conn);
        $successMsg = "Profile updated successfully!";

        // Refresh local data
        $userData['name'] = $name;
        $userData['programme'] = $programme;
        $userData['faculty'] = $faculty;
        $userData['yearOfStudy'] = $yearOfStudy;
        $userData['enrollmentStatus'] = $enrollmentStatus;
        $userData['studentEmail'] = $studentEmail;
        $userData['phone'] = $phone;
        $userData['linkedinURL'] = $linkedinURL;
        $userData['careerInterest'] = $careerInterest;
        $userData['skills'] = $skills;
        $userData['internshipAvailability'] = $internshipAvailability;
        $userData['profilePhoto'] = $profilePhoto;
        $_SESSION['name'] = $name; // Update session name

    } catch (Exception $e) {
        mysqli_rollback($conn);
        $errorMsg = "Error updating profile: " . $e->getMessage();
    }
}

$isDashboard = false;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Profile | Alumni Connect</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- CSS -->
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .profile-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 2rem;
        }

        .profile-card {
            background: white;
            border-radius: 1rem;
            border: 1px solid var(--border-light);
            box-shadow: var(--card-shadow);
            overflow: hidden;
        }

        .profile-header {
            padding: 2rem;
            background: var(--background-light);
            border-bottom: 1px solid var(--border-light);
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .profile-avatar-large {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: var(--primary-blue);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: 700;
            object-fit: cover;
            border: 3px solid white;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .profile-title h1 {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 0.25rem;
        }

        .profile-title p {
            color: var(--text-muted);
            font-size: 0.875rem;
        }

        .profile-form {
            padding: 2rem;
        }

        .form-section {
            margin-bottom: 2.5rem;
        }

        .form-section-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--primary-blue);
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border-bottom: 1px solid var(--border-light);
            padding-bottom: 0.5rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-group.full-width {
            grid-column: span 2;
        }

        .form-group label {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-dark);
        }

        .form-control {
            padding: 0.75rem 1rem;
            border: 1px solid var(--border-light);
            border-radius: 0.5rem;
            font-family: inherit;
            font-size: 0.875rem;
            transition: all 0.2s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .form-control:disabled {
            background: var(--background-light);
            cursor: not-allowed;
        }

        .btn-save {
            background: var(--primary-blue);
            color: white;
            border: none;
            padding: 1rem 2.5rem;
            border-radius: 0.5rem;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            width: fit-content;
            margin-top: 1rem;
        }

        .btn-save:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }

        .alert {
            padding: 1rem;
            border-radius: 0.75rem;
            margin-bottom: 2rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-success {
            background: #F0FDF4;
            border: 1px solid #DCFCE7;
            color: #166534;
        }

        .alert-error {
            background: #FEF2F2;
            border: 1px solid #FEE2E2;
            color: #991B1B;
        }

        @media (max-width: 640px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-group.full-width {
                grid-column: span 1;
            }
        }
    </style>
</head>

<body>

    <!-- Shared Topbar -->
    <?php include_once __DIR__ . '/../components/topbar.php'; ?>

    <div class="profile-container">

        <!-- Alerts -->
        <?php if ($successMsg): ?>
            <div class="alert alert-success">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                <?php echo $successMsg; ?>
            </div>
        <?php endif; ?>

        <?php if ($errorMsg): ?>
            <div class="alert alert-error">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <?php echo $errorMsg; ?>
            </div>
        <?php endif; ?>

        <div class="profile-card">
            <div class="profile-header">
                <?php if ($userData['profilePhoto']): ?>
                    <img src="../<?php echo htmlspecialchars($userData['profilePhoto']); ?>" class="profile-avatar-large"
                        alt="Avatar">
                <?php else: ?>
                    <div class="profile-avatar-large">
                        <?php echo strtoupper(substr($userData['name'], 0, 1)); ?>
                    </div>
                <?php endif; ?>
                <div class="profile-title">
                    <h1>Update Profile</h1>
                    <p>Keep your information up to date to get the best out of the platform.</p>
                </div>
            </div>

            <form action="" method="POST" enctype="multipart/form-data" class="profile-form">

                <!-- 1. Basic Information -->
                <div class="form-section">
                    <h2 class="form-section-title">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        Basic Information
                    </h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" name="name" id="name" class="form-control"
                                value="<?php echo htmlspecialchars($userData['name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>University Email (Read-only)</label>
                            <input type="email" class="form-control"
                                value="<?php echo htmlspecialchars($userData['email']); ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label for="profilePhoto">Profile Photo</label>
                            <input type="file" name="profilePhoto" id="profilePhoto" class="form-control"
                                accept="image/*">
                        </div>
                        <div class="form-group">
                            <label>Student ID (Link to User)</label>
                            <input type="text" class="form-control"
                                value="STUDENT_<?php echo $userData['studentID']; ?>" disabled>
                        </div>
                    </div>
                </div>

                <!-- 2. Academic Information -->
                <div class="form-section">
                    <h2 class="form-section-title">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 10v6M2 10l10-5 10 5-10 5z"></path>
                            <path d="M6 12v5c3 3 9 3 12 0v-5"></path>
                        </svg>
                        Academic Information
                    </h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="faculty">Faculty</label>
                            <input type="text" name="faculty" id="faculty" class="form-control"
                                placeholder="e.g. Faculty of Computing"
                                value="<?php echo htmlspecialchars($userData['faculty']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="programme">Programme</label>
                            <input type="text" name="programme" id="programme" class="form-control"
                                placeholder="e.g. Software Engineering"
                                value="<?php echo htmlspecialchars($userData['programme']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="yearOfStudy">Year of Study</label>
                            <select name="yearOfStudy" id="yearOfStudy" class="form-control">
                                <option value="1" <?php if ($userData['yearOfStudy'] == 1)
                                    echo 'selected'; ?>>Year 1
                                </option>
                                <option value="2" <?php if ($userData['yearOfStudy'] == 2)
                                    echo 'selected'; ?>>Year 2
                                </option>
                                <option value="3" <?php if ($userData['yearOfStudy'] == 3)
                                    echo 'selected'; ?>>Year 3
                                </option>
                                <option value="4" <?php if ($userData['yearOfStudy'] == 4)
                                    echo 'selected'; ?>>Year 4+
                                </option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="enrollmentStatus">Enrollment Status</label>
                            <select name="enrollmentStatus" id="enrollmentStatus" class="form-control">
                                <option value="ACTIVE" <?php if ($userData['enrollmentStatus'] == 'ACTIVE')
                                    echo 'selected'; ?>>Active</option>
                                <option value="GRADUATED" <?php if ($userData['enrollmentStatus'] == 'GRADUATED')
                                    echo 'selected'; ?>>Graduated</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- 3. Contact Information -->
                <div class="form-section">
                    <h2 class="form-section-title">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path
                                d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l2.27-2.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z">
                            </path>
                        </svg>
                        Contact & Social
                    </h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="text" name="phone" id="phone" class="form-control" placeholder="+60 123 456789"
                                value="<?php echo htmlspecialchars($userData['phone']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="studentEmail">Personal Email (Optional)</label>
                            <input type="email" name="studentEmail" id="studentEmail" class="form-control"
                                placeholder="personal@email.com"
                                value="<?php echo htmlspecialchars($userData['studentEmail']); ?>">
                        </div>
                        <div class="form-group full-width">
                            <label for="linkedinURL">LinkedIn URL</label>
                            <input type="url" name="linkedinURL" id="linkedinURL" class="form-control"
                                placeholder="https://linkedin.com/in/username"
                                value="<?php echo htmlspecialchars($userData['linkedinURL']); ?>">
                        </div>
                    </div>
                </div>

                <!-- 4. Career Interests -->
                <div class="form-section">
                    <h2 class="form-section-title">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                            <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                        </svg>
                        Career Interests & Skills
                    </h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="careerInterest">Primary Career Interest</label>
                            <input type="text" name="careerInterest" id="careerInterest" class="form-control"
                                placeholder="e.g. Full Stack Development"
                                value="<?php echo htmlspecialchars($userData['careerInterest']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="internshipAvailability">Internship Availability</label>
                            <select name="internshipAvailability" id="internshipAvailability" class="form-control">
                                <option value="YES" <?php if ($userData['internshipAvailability'] == 'YES')
                                    echo 'selected'; ?>>Available (Yes)</option>
                                <option value="NO" <?php if ($userData['internshipAvailability'] == 'NO')
                                    echo 'selected'; ?>>Not Available (No)</option>
                            </select>
                        </div>
                        <div class="form-group full-width">
                            <label for="skills">Skills (Comma-separated)</label>
                            <textarea name="skills" id="skills" class="form-control" rows="3"
                                placeholder="PHP, Javascript, MySQL, UI/UX Design..."><?php echo htmlspecialchars($userData['skills']); ?></textarea>
                        </div>
                    </div>
                </div>

                <button type="submit" name="update_profile" class="btn-save">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17 21 17 13 7 13 7 21"></polyline>
                        <polyline points="7 3 7 8 15 8"></polyline>
                    </svg>
                    Save Changes
                </button>

            </form>
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