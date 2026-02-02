<?php
/**
 * LOGIN PAGE
 * Redesigned to follow the Minimal White Theme.
 */
session_start();
require_once "../config/db.php";
require_once "../config/functions.php";

$error = "";

// 7. ENABLE ERROR VISIBILITY (DEBUG MODE)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $sql = "SELECT * FROM user WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['userID'] = $user['userID'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];

            // 4. CONFIRM SESSION VARIABLES SET BEFORE REDIRECT
            if ($user['role'] === 'CAREER_SERVICE_OFFICER') {
                $csoStmt = $conn->prepare("SELECT csoID FROM career_service_officer WHERE userID = ?");
                $csoStmt->bind_param("i", $user['userID']);
                $csoStmt->execute();
                $csoRes = $csoStmt->get_result()->fetch_assoc();
                if ($csoRes) {
                    $_SESSION['csoID'] = $csoRes['csoID'];
                }
            }

            // 3. FIX LOGIN REDIRECT LOGIC (MANDATORY)
            if ($user['role'] === 'STUDENT') {
                header("Location: /alumni-system/student/dashboard.php");
                exit;
            } elseif ($user['role'] === 'ALUMNI') {
                header("Location: /alumni-system/alumni/dashboard.php");
                exit;
            } elseif ($user['role'] === 'CAREER_SERVICE_OFFICER') {
                header("Location: /alumni-system/career_officer/dashboard.php");
                exit;
            } elseif ($user['role'] === 'ADMIN') {
                logActivity($conn, $user['userID'], 'ADMIN', 'Login', 'Sessions', 'Administrator logged in successfully');
                header("Location: /alumni-system/admin/dashboard.php");
                exit;
            } else {
                die("Invalid user role.");
            }
        } else {
            $error = "Invalid email or password.";
        }
    } else {
        $error = "Invalid email or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | Alumni Connect</title>
    <link rel="stylesheet" href="../assets/css/auth.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
</head>

<body>

    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>Welcome Back</h1>
                <p>Please enter your details to sign in</p>
            </div>

            <?php if ($error != ""): ?>
                <div class="error-msg"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if (isset($_GET['registered']) && $_GET['registered'] == 'success'): ?>
                <div class="success-msg">
                    Registration successful! Please login.
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" name="email" id="email" class="form-control" placeholder="name@university.edu"
                        required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" class="form-control" placeholder="••••••••"
                        required>
                </div>

                <button type="submit" class="btn-submit">Sign In</button>
            </form>

            <div class="auth-footer">
                <p>Don't have an account? <a href="register.php">Create Account</a></p>
            </div>
        </div>
    </div>

</body>

</html>