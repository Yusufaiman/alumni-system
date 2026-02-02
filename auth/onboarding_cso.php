<?php
/**
 * CAREER SERVICE OFFICER ONBOARDING
 * Final step for CSO registration.
 */
session_start();
require_once "../config/db.php";

// Check if basic registration data exists
if (!isset($_SESSION['reg_data']) || $_SESSION['reg_data']['role'] !== 'CAREER_SERVICE_OFFICER') {
    header("Location: register.php?reset=1");
    exit();
}

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $staffID = trim($_POST['staffID']);
    $department = trim($_POST['department']);
    $position = trim($_POST['position']);

    if (empty($staffID) || empty($department) || empty($position)) {
        $error = "All fields are required.";
    } else {
        // Validation: Unique staffID
        $checkStaffID = $conn->prepare("SELECT csoID FROM career_service_officer WHERE staffID = ?");
        $checkStaffID->bind_param("s", $staffID);
        $checkStaffID->execute();
        if ($checkStaffID->get_result()->num_rows > 0) {
            $error = "Staff ID already registered.";
        } else {
            // Start Transaction
            $conn->begin_transaction();
            try {
                $reg_data = $_SESSION['reg_data'];

                // STEP A: Insert into user table
                $stmtUser = $conn->prepare("INSERT INTO user (name, email, password, role, status, createdDate) VALUES (?, ?, ?, 'CAREER_SERVICE_OFFICER', 'ACTIVE', CURDATE())");
                $stmtUser->bind_param("sss", $reg_data['name'], $reg_data['email'], $reg_data['password']);
                if (!$stmtUser->execute()) {
                    throw new Exception("Error creating user account.");
                }

                $userID = $conn->insert_id;

                // STEP B: Insert into career_service_officer table
                $stmtCSO = $conn->prepare("INSERT INTO career_service_officer (userID, staffID, department, position, createdDate, status) VALUES (?, ?, ?, ?, CURDATE(), 'ACTIVE')");
                $stmtCSO->bind_param("isss", $userID, $staffID, $department, $position);
                if (!$stmtCSO->execute()) {
                    throw new Exception("Error creating CSO profile.");
                }

                $conn->commit();

                // Part 4: Auto-login & Session Handling
                unset($_SESSION['reg_data']); // Clear sensitive data
                $_SESSION['userID'] = $userID;
                $_SESSION['name'] = $reg_data['name'];
                $_SESSION['role'] = 'CAREER_SERVICE_OFFICER';

                header("Location: ../career_officer/dashboard.php");
                exit();

            } catch (Exception $e) {
                $conn->rollback();
                $error = $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSO Onboarding | Alumni Connect</title>
    <link rel="stylesheet" href="../assets/css/auth.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
</head>

<body>

    <div class="auth-container">
        <div class="auth-card">

            <div class="step-indicator">
                <div class="step-item active">
                    <div class="step-number">1</div>
                    <span>Account</span>
                </div>
                <div class="step-divider"></div>
                <div class="step-item active">
                    <div class="step-number">2</div>
                    <span>CSO Details</span>
                </div>
            </div>

            <div class="auth-header">
                <h1>Officer Details</h1>
                <p>Complete your professional profile to continue</p>
            </div>

            <?php if ($error != ""): ?>
                <div class="error-msg">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="staffID">Staff ID</label>
                    <input type="text" name="staffID" id="staffID" class="form-control" placeholder="E.g. STAFF123"
                        required>
                </div>

                <div class="form-group">
                    <label for="department">Department</label>
                    <input type="text" name="department" id="department" class="form-control"
                        placeholder="E.g. Career Services" required>
                </div>

                <div class="form-group">
                    <label for="position">Position</label>
                    <input type="text" name="position" id="position" class="form-control"
                        placeholder="E.g. Senior Career Officer" required>
                </div>

                <div class="btn-group">
                    <a href="register.php?reset=1" class="btn-secondary"
                        style="text-decoration: none; text-align: center; display: flex; align-items: center; justify-content: center;">Cancel</a>
                    <button type="submit" class="btn-submit">Complete Registration</button>
                </div>
            </form>

        </div>
    </div>

</body>

</html>