<?php
/**
 * ALUMNI UPDATE PROFILE PAGE
 * Allows alumni to view and edit their professional and academic details.
 */
session_start();
require_once "../config/db.php";

// Access Control: Alumni Only
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'ALUMNI') {
    header("Location: ../auth/login.php");
    exit();
}

$userID = $_SESSION['userID'];
$successMsg = "";
$errorMsg = "";

// 1. Fetch Current Data
$sql = "SELECT u.name, u.email, a.* 
        FROM user u 
        JOIN alumni a ON u.userID = a.userID 
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
    $faculty = trim($_POST['faculty']);
    $programme = trim($_POST['programme']);
    $graduationYear = intval($_POST['graduationYear']);
    $currentPosition = trim($_POST['currentPosition']);
    $currentCompany = trim($_POST['currentCompany']);
    $industry = trim($_POST['industry']);
    $yearsExperience = intval($_POST['yearsExperience']);
    $phone = trim($_POST['phone']);
    $linkedinURL = trim($_POST['linkedinURL']);
    $website = trim($_POST['website']);
    $openMentorship = $_POST['openMentorship'];
    $openReferral = $_POST['openReferral'];
    $bio = trim($_POST['bio']);

    // File Upload Logic
    $profilePhoto = $userData['profilePhoto']; // Default to existing
    if (isset($_FILES['profilePhoto']) && $_FILES['profilePhoto']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['profilePhoto']['tmp_name'];
        $fileName = $_FILES['profilePhoto']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $newFileName = "alumni_" . $userID . "_" . time() . "." . $fileExtension;
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

        // Update Alumni Table
        $stmtAlumni = $conn->prepare("UPDATE alumni SET 
            faculty = ?, 
            programme = ?, 
            graduationYear = ?, 
            currentPosition = ?, 
            currentCompany = ?, 
            industry = ?, 
            yearsExperience = ?, 
            phone = ?, 
            linkedinURL = ?, 
            website = ?, 
            openMentorship = ?, 
            openReferral = ?, 
            bio = ?, 
            profilePhoto = ? 
            WHERE userID = ?");
        $stmtAlumni->bind_param(
            "ssisssisssssssi",
            $faculty,
            $programme,
            $graduationYear,
            $currentPosition,
            $currentCompany,
            $industry,
            $yearsExperience,
            $phone,
            $linkedinURL,
            $website,
            $openMentorship,
            $openReferral,
            $bio,
            $profilePhoto,
            $userID
        );
        $stmtAlumni->execute();

        mysqli_commit($conn);
        $successMsg = "Profile updated successfully!";

        // Refresh local data
        $userData['name'] = $name;
        $userData['faculty'] = $faculty;
        $userData['programme'] = $programme;
        $userData['graduationYear'] = $graduationYear;
        $userData['currentPosition'] = $currentPosition;
        $userData['currentCompany'] = $currentCompany;
        $userData['industry'] = $industry;
        $userData['yearsExperience'] = $yearsExperience;
        $userData['phone'] = $phone;
        $userData['linkedinURL'] = $linkedinURL;
        $userData['website'] = $website;
        $userData['openMentorship'] = $openMentorship;
        $userData['openReferral'] = $openReferral;
        $userData['bio'] = $bio;
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
            background: #10b981;
            /* Green for Alumni */
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
            color: #10b981;
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
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }

        .form-control:disabled {
            background: var(--background-light);
            cursor: not-allowed;
        }

        .btn-save {
            background: #10b981;
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
            background: #059669;
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
                    <p>Keep your alumni profile up to date to stay connected with the network.</p>
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
                            <label>Registered Email (Read-only)</label>
                            <input type="email" class="form-control"
                                value="<?php echo htmlspecialchars($userData['email']); ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label for="profilePhoto">Profile Photo</label>
                            <input type="file" name="profilePhoto" id="profilePhoto" class="form-control"
                                accept="image/*">
                        </div>
                        <div class="form-group">
                            <label>Alumni ID</label>
                            <input type="text" class="form-control" value="ALUMNI_<?php echo $userData['alumniID']; ?>"
                                disabled>
                        </div>
                    </div>
                </div>

                <!-- 2. Academic Background -->
                <div class="form-section">
                    <h2 class="form-section-title">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 10v6M2 10l10-5 10 5-10 5z"></path>
                            <path d="M6 12v5c3 3 9 3 12 0v-5"></path>
                        </svg>
                        Academic Background
                    </h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="faculty">Faculty</label>
                            <input type="text" name="faculty" id="faculty" class="form-control"
                                placeholder="e.g. Faculty of Engineering"
                                value="<?php echo htmlspecialchars($userData['faculty'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="programme">Programme</label>
                            <input type="text" name="programme" id="programme" class="form-control"
                                placeholder="e.g. Bachelor of Civil Engineering"
                                value="<?php echo htmlspecialchars($userData['programme'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="graduationYear">Graduation Year</label>
                            <input type="number" name="graduationYear" id="graduationYear" class="form-control"
                                placeholder="2020" value="<?php echo htmlspecialchars($userData['graduationYear']); ?>"
                                required>
                        </div>
                    </div>
                </div>

                <!-- 3. Professional Information -->
                <div class="form-section">
                    <h2 class="form-section-title">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                            <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                        </svg>
                        Professional Information
                    </h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="currentPosition">Current Job Title</label>
                            <input type="text" name="currentPosition" id="currentPosition" class="form-control"
                                placeholder="e.g. Senior Software Engineer"
                                value="<?php echo htmlspecialchars($userData['currentPosition']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="currentCompany">Company / Organization</label>
                            <input type="text" name="currentCompany" id="currentCompany" class="form-control"
                                placeholder="e.g. Google Malaysia"
                                value="<?php echo htmlspecialchars($userData['currentCompany']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="industry">Industry</label>
                            <input type="text" name="industry" id="industry" class="form-control"
                                placeholder="e.g. Technology"
                                value="<?php echo htmlspecialchars($userData['industry']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="yearsExperience">Years of Experience</label>
                            <input type="number" name="yearsExperience" id="yearsExperience" class="form-control"
                                placeholder="e.g. 5"
                                value="<?php echo htmlspecialchars($userData['yearsExperience'] ?? 0); ?>">
                        </div>
                    </div>
                </div>

                <!-- 4. Contact & Social -->
                <div class="form-section">
                    <h2 class="form-section-title">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path
                                d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l2.27-2.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z">
                            </path>
                        </svg>
                        Contact & Links
                    </h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="text" name="phone" id="phone" class="form-control"
                                placeholder="+60 12 345 6789"
                                value="<?php echo htmlspecialchars($userData['phone'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="linkedinURL">LinkedIn URL</label>
                            <input type="url" name="linkedinURL" id="linkedinURL" class="form-control"
                                placeholder="https://linkedin.com/in/username"
                                value="<?php echo htmlspecialchars($userData['linkedinURL'] ?? ''); ?>">
                        </div>
                        <div class="form-group full-width">
                            <label for="website">Personal Website / Portfolio (Optional)</label>
                            <input type="url" name="website" id="website" class="form-control"
                                placeholder="https://yourwebsite.com"
                                value="<?php echo htmlspecialchars($userData['website'] ?? ''); ?>">
                        </div>
                    </div>
                </div>

                <!-- 5. Career Visibility -->
                <div class="form-section">
                    <h2 class="form-section-title">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        Visibility & Bio
                    </h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="openMentorship">Open to Mentorship?</label>
                            <select name="openMentorship" id="openMentorship" class="form-control">
                                <option value="YES" <?php if (($userData['openMentorship'] ?? 'NO') == 'YES')
                                    echo 'selected'; ?>>Yes, I want to mentor students</option>
                                <option value="NO" <?php if (($userData['openMentorship'] ?? 'NO') == 'NO')
                                    echo 'selected'; ?>>Not at this moment</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="openReferral">Open to Job Referrals?</label>
                            <select name="openReferral" id="openReferral" class="form-control">
                                <option value="YES" <?php if (($userData['openReferral'] ?? 'NO') == 'YES')
                                    echo 'selected'; ?>>Yes, I can provide referrals</option>
                                <option value="NO" <?php if (($userData['openReferral'] ?? 'NO') == 'NO')
                                    echo 'selected'; ?>>No</option>
                            </select>
                        </div>
                        <div class="form-group full-width">
                            <label for="bio">Career Summary / Bio</label>
                            <textarea name="bio" id="bio" class="form-control" rows="4"
                                placeholder="Briefly describe your career journey and areas of expertise..."><?php echo htmlspecialchars($userData['bio'] ?? ''); ?></textarea>
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