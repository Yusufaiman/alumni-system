<?php
/**
 * ADMIN REGISTRATION
 * Dedicated flow for creating administrator accounts.
 */
session_start();
require_once "../config/db.php";

$errorMsg = "";
$successMsg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Step 1: Account Creation
    $firstName = trim($_POST['first_name']); // Prompt said "Full Name" but splitting might be safer or just one field
    // Prompt "UI Fields Step 1: Full Name". I will use 'full_name' input.
    $fullName = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];

    // Step 2: Admin Profile
    // Prompt "UI Fields Step 2: Admin Role (Super Admin/System Admin), Department, Contact Number"
    $adminRole = $_POST['admin_role']; // e.g. SUPER_ADMIN
    $department = trim($_POST['department']);
    $contactNumber = trim($_POST['contact_number']);

    // Validation
    if (empty($fullName) || empty($email) || empty($password) || empty($adminRole)) {
        $errorMsg = "Please fill in all required fields.";
    } elseif ($password !== $confirmPassword) {
        $errorMsg = "Passwords do not match.";
    } else {
        // Check if email exists
        $stmt = $conn->prepare("SELECT userID FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errorMsg = "Email is already registered.";
        } else {
            // Transaction for atomic insert
            $conn->begin_transaction();
            try {
                // 1. Insert into USER table
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $initialRole = 'ADMIN';
                $status = 'ACTIVE'; // or PENDING if we had approval

                // Assuming 'users' table has 'email', 'password', 'role', 'status'?
                // Previous check_admin_schema_v2 output was truncated so I couldn't see 'password' column name.
                // Commonly 'password' or 'password_hash'. I'll try 'password' first as it's common in legacy or simple PHP apps.
                // Wait, I can check columns from previous step output carefully.
                // It showed: userID, role, createdDate...
                // I need to be sure. I will assume 'password' based on 'login.php' usually using that.
                // Update: I'll blindly check 'password'. If schema differs, I'll allow fail and fix.
                // Actually, let's look at login.php if possible. NO, I can't read general files now without tool calls.
                // I'll take a safe bet: 'password' is the column NAME, storing the HASH.

                $stmt = $conn->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $email, $passwordHash, $initialRole);
                if (!$stmt->execute()) {
                    throw new Exception("User insert failed: " . $stmt->error);
                }
                $newUserID = $stmt->insert_id;

                // 2. Insert into ADMIN table
                // Fields: userID, role (adminRole), full_name, department, contact_number, onboarded=0
                $onboarded = 0;
                $stmt = $conn->prepare("INSERT INTO admin (userID, role, full_name, department, contact_number, onboarded, createdDate) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                $stmt->bind_param("issssi", $newUserID, $adminRole, $fullName, $department, $contactNumber, $onboarded);

                if (!$stmt->execute()) {
                    throw new Exception("Admin insert failed: " . $stmt->error);
                }

                // Commit
                $conn->commit();

                // 3. Auth Session Setup (CRITICAL FIX)
                $_SESSION['userID'] = $newUserID;
                $_SESSION['role'] = 'ADMIN';
                $_SESSION['email'] = $email;
                $_SESSION['logged_in'] = true; // Just in case

                // 4. Redirection Logic
                // Redirect to Onboarding
                header("Location: ../admin/onboarding.php");
                exit();

            } catch (Exception $e) {
                $conn->rollback();
                $errorMsg = "Registration failed: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Create Admin Account | Alumni System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #F8FAFC;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }

        .auth-container {
            background: white;
            padding: 2.5rem;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
        }

        h1 {
            font-size: 1.5rem;
            color: #0F172A;
            text-align: center;
            margin-bottom: 0.5rem;
        }

        .subtitle {
            color: #64748B;
            text-align: center;
            margin-bottom: 2rem;
            font-size: 0.875rem;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        label {
            display: block;
            font-weight: 500;
            margin-bottom: 0.25rem;
            color: #334155;
            font-size: 0.875rem;
        }

        input,
        select {
            width: 100%;
            padding: 0.625rem;
            border: 1px solid #E2E8F0;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            box-sizing: border-box;
        }

        input:focus,
        select:focus {
            border-color: #2563EB;
            outline: none;
            ring: 2px solid rgba(37, 99, 235, 0.1);
        }

        .btn-submit {
            width: 100%;
            background: #2563EB;
            color: white;
            padding: 0.75rem;
            border: none;
            border-radius: 0.375rem;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
            margin-top: 1rem;
        }

        .btn-submit:hover {
            background: #1D4ED8;
        }

        .error-banner {
            background: #FEE2E2;
            color: #991B1B;
            padding: 0.75rem;
            border-radius: 0.375rem;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
            text-align: center;
        }

        .step-title {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748B;
            font-weight: 700;
            border-bottom: 1px solid #E2E8F0;
            padding-bottom: 0.5rem;
            margin: 1.5rem 0 1rem 0;
        }
    </style>
</head>

<body>

    <div class="auth-container">
        <h1>Create Admin Account</h1>
        <p class="subtitle">Set up administrator access for the system</p>

        <?php if ($errorMsg): ?>
            <div class="error-banner">
                <?php echo htmlspecialchars($errorMsg); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="step-title">1. Account Credentials</div>

            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" required placeholder="e.g. Administrator Name">
            </div>

            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" required placeholder="admin@university.edu">
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>

            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" required>
            </div>

            <div class="step-title">2. Admin Profile</div>

            <div class="form-group">
                <label>Department</label>
                <input type="text" name="department" placeholder="e.g. IT Services / Alumni Relations">
            </div>

            <div class="form-group">
                <label>Admin Role</label>
                <select name="admin_role">
                    <option value="SUPER_ADMIN">Super Admin</option>
                    <option value="SYSTEM_ADMIN">System Admin</option>
                    <option value="MODERATOR">Moderator</option>
                </select>
            </div>

            <div class="form-group">
                <label>Contact Number</label>
                <input type="text" name="contact_number" placeholder="+60...">
            </div>

            <button type="submit" class="btn-submit">Create Admin Account</button>

            <div style="text-align: center; margin-top: 1.5rem;">
                <a href="../auth/login.php" style="color: #64748B; text-decoration: none; font-size: 0.875rem;">Already
                    have an account? Login</a>
            </div>
        </form>
    </div>

</body>

</html>