<?php
/**
 * ONBOARDING & REGISTRATION FLOW
 * Redesigned to follow the Minimal White Theme.
 */
session_start();
require_once "../config/db.php";

$error = "";
$current_step = isset($_SESSION['reg_step']) ? $_SESSION['reg_step'] : 1;

// Reset registration if requested
if (isset($_GET['reset'])) {
    unset($_SESSION['reg_data']);
    $_SESSION['reg_step'] = 1;
    header("Location: register.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // --- STEP 1: Account Info ---
    if (isset($_POST['go_to_step2'])) {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $role = $_POST['role'];

        if (empty($name) || empty($email) || empty($password) || empty($role)) {
            $error = "All fields are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format.";
        } else {
            // Check email
            $checkEmail = $conn->prepare("SELECT userID FROM user WHERE email = ?");
            $checkEmail->bind_param("s", $email);
            $checkEmail->execute();
            if ($checkEmail->get_result()->num_rows > 0) {
                $error = "Email already registered.";
            } else {
                $_SESSION['reg_data'] = [
                    'name' => $name,
                    'email' => $email,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'role' => $role
                ];

                if ($role === 'CAREER_SERVICE_OFFICER') {
                    header("Location: onboarding_cso.php");
                    exit();
                }

                $_SESSION['reg_step'] = 2;
                $current_step = 2;
            }
        }
    }

    // --- STEP 2: Profile Completion ---
    elseif (isset($_POST['complete_registration'])) {
        if (!isset($_SESSION['reg_data'])) {
            header("Location: register.php?reset=1");
            exit();
        }

        $role = $_SESSION['reg_data']['role'];
        $extra_data = [];

        if ($role === 'STUDENT') {
            $extra_data['programme'] = trim($_POST['programme']);
            $extra_data['yearOfStudy'] = trim($_POST['yearOfStudy']);
            $extra_data['studentEmail'] = trim($_POST['studentEmail']);
            if (empty($extra_data['programme']) || empty($extra_data['yearOfStudy'])) {
                $error = "Please fill in all details.";
            }
        } elseif ($role === 'ALUMNI') {
            $extra_data['graduationYear'] = trim($_POST['graduationYear']);
            $extra_data['currentPosition'] = trim($_POST['currentPosition']);
            $extra_data['currentCompany'] = trim($_POST['currentCompany']);
            $extra_data['industry'] = trim($_POST['industry']);
            if (empty($extra_data['graduationYear']) || empty($extra_data['currentPosition'])) {
                $error = "Please fill in all details.";
            }
        }

        if (empty($error)) {
            mysqli_begin_transaction($conn);
            try {
                $data = $_SESSION['reg_data'];
                $stmt = $conn->prepare("INSERT INTO user (name, email, password, role, status, createdDate) VALUES (?, ?, ?, ?, 'ACTIVE', NOW())");
                $stmt->bind_param("ssss", $data['name'], $data['email'], $data['password'], $data['role']);
                if (!$stmt->execute())
                    throw new Exception("Error creating account.");
                $userID = $stmt->insert_id;

                if ($role === 'STUDENT') {
                    $stmt2 = $conn->prepare("INSERT INTO student (userID, programme, yearOfStudy, studentEmail) VALUES (?, ?, ?, ?)");
                    $stmt2->bind_param("isss", $userID, $extra_data['programme'], $extra_data['yearOfStudy'], $extra_data['studentEmail']);
                    if (!$stmt2->execute())
                        throw new Exception("Error creating student profile.");
                } else {
                    $stmt3 = $conn->prepare("INSERT INTO alumni (userID, graduationYear, currentPosition, currentCompany, industry) VALUES (?, ?, ?, ?, ?)");
                    $stmt3->bind_param("issss", $userID, $extra_data['graduationYear'], $extra_data['currentPosition'], $extra_data['currentCompany'], $extra_data['industry']);
                    if (!$stmt3->execute())
                        throw new Exception("Error creating alumni profile.");
                }

                mysqli_commit($conn);
                unset($_SESSION['reg_data'], $_SESSION['reg_step']);
                header("Location: login.php?registered=success");
                exit();
            } catch (Exception $e) {
                mysqli_rollback($conn);
                $error = $e->getMessage();
            }
        }
    } elseif (isset($_POST['back_to_step1'])) {
        $_SESSION['reg_step'] = 1;
        $current_step = 1;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Alumni Connect</title>
    <link rel="stylesheet" href="../assets/css/auth.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
</head>

<body>

    <div class="auth-container">
        <div class="auth-card">

            <div class="step-indicator">
                <div class="step-item <?php echo $current_step >= 1 ? 'active' : ''; ?>">
                    <div class="step-number">1</div>
                    <span>Account</span>
                </div>
                <div class="step-divider"></div>
                <div class="step-item <?php echo $current_step >= 2 ? 'active' : ''; ?>">
                    <div class="step-number">2</div>
                    <span>Profile</span>
                </div>
            </div>

            <div class="auth-header">
                <h1><?php echo $current_step == 1 ? "Create Account" : "Complete Profile"; ?></h1>
                <p><?php echo $current_step == 1 ? "Start your journey today" : "Just a few more details"; ?></p>
            </div>

            <?php if ($error != ""): ?>
                <div class="error-msg"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="">

                <?php if ($current_step == 1): ?>
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="name" class="form-control" placeholder="John Doe"
                            value="<?php echo isset($_SESSION['reg_data']['name']) ? htmlspecialchars($_SESSION['reg_data']['name']) : ''; ?>"
                            required>
                    </div>

                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" class="form-control" placeholder="name@university.edu"
                            value="<?php echo isset($_SESSION['reg_data']['email']) ? htmlspecialchars($_SESSION['reg_data']['email']) : ''; ?>"
                            required>
                    </div>

                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                    </div>

                    <div class="form-group">
                        <label>Register As</label>
                        <select name="role" class="form-control" required>
                            <option value="" disabled selected>Select Role</option>
                            <option value="STUDENT" <?php echo (isset($_SESSION['reg_data']['role']) && $_SESSION['reg_data']['role'] == 'STUDENT') ? 'selected' : ''; ?>>Student</option>
                            <option value="ALUMNI" <?php echo (isset($_SESSION['reg_data']['role']) && $_SESSION['reg_data']['role'] == 'ALUMNI') ? 'selected' : ''; ?>>Alumni</option>
                            <option value="CAREER_SERVICE_OFFICER" <?php echo (isset($_SESSION['reg_data']['role']) && $_SESSION['reg_data']['role'] == 'CAREER_SERVICE_OFFICER') ? 'selected' : ''; ?>>Career
                                Service
                                Officer</option>
                            <option value="ADMIN" <?php echo (isset($_SESSION['reg_data']['role']) && $_SESSION['reg_data']['role'] == 'ADMIN') ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>

                    <button type="submit" name="go_to_step2" class="btn-submit">Continue</button>

                <?php else: ?>
                    <input type="hidden" name="role" value="<?php echo $_SESSION['reg_data']['role']; ?>">

                    <?php if ($_SESSION['reg_data']['role'] === 'STUDENT'): ?>
                        <div class="form-group">
                            <label>Programme</label>
                            <input type="text" name="programme" class="form-control" placeholder="e.g. Computer Science"
                                required>
                        </div>
                        <div class="form-group">
                            <label>Year of Study</label>
                            <select name="yearOfStudy" class="form-control" required>
                                <option value="1">Year 1</option>
                                <option value="2">Year 2</option>
                                <option value="3">Year 3</option>
                                <option value="4">Year 4+</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Student Email</label>
                            <input type="email" name="studentEmail" class="form-control" placeholder="student@university.edu">
                        </div>

                    <?php else: ?>
                        <div class="form-group">
                            <label>Graduation Year</label>
                            <input type="number" name="graduationYear" class="form-control" placeholder="2023" required>
                        </div>
                        <div class="form-group">
                            <label>Current Position</label>
                            <input type="text" name="currentPosition" class="form-control" placeholder="Software Engineer"
                                required>
                        </div>
                        <div class="form-group">
                            <label>Current Company</label>
                            <input type="text" name="currentCompany" class="form-control" placeholder="Company Name">
                        </div>
                        <div class="form-group">
                            <label>Industry</label>
                            <input type="text" name="industry" class="form-control" placeholder="Technology / Finance">
                        </div>
                    <?php endif; ?>

                    <div class="btn-group">
                        <button type="submit" name="back_to_step1" class="btn-secondary">Back</button>
                        <button type="submit" name="complete_registration" class="btn-submit">Create Account</button>
                    </div>

                <?php endif; ?>
            </form>

            <div class="auth-footer">
                <p>Already have an account? <a href="login.php">Sign In</a></p>
            </div>
        </div>
    </div>

</body>

</html>