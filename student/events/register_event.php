<?php
/**
 * EVENT REGISTRATION HANDLER
 * This script processes the registration request from the event list page.
 */

session_start();
require_once('../../config/db.php');

// 1. Check if user is logged in and is a STUDENT
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'STUDENT') {
    header("Location: ../../auth/login.php");
    exit();
}

// 2. Validate eventID from POST
if (!isset($_POST['eventID']) || empty($_POST['eventID'])) {
    die("Invalid request. Event ID is missing.");
}

$eventID = $_POST['eventID'];
$userID = $_SESSION['userID'];

// 3. Get studentID using session userID
$studentQuery = "SELECT studentID FROM student WHERE userID = ?";
$stmt = mysqli_prepare($conn, $studentQuery);
mysqli_stmt_bind_param($stmt, "i", $userID);
mysqli_stmt_execute($stmt);
$studentResult = mysqli_stmt_get_result($stmt);

if ($studentRow = mysqli_fetch_assoc($studentResult)) {
    $studentID = $studentRow['studentID'];
} else {
    die("Error: Student record not found for this user.");
}

// 4. Check if student already registered for that event
$checkQuery = "SELECT registrationID FROM eventregistration WHERE eventID = ? AND studentID = ?";
$stmt = mysqli_prepare($conn, $checkQuery);
mysqli_stmt_bind_param($stmt, "ii", $eventID, $studentID);
mysqli_stmt_execute($stmt);
$checkResult = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($checkResult) > 0) {
    // Already registered
    $message = "You have already registered for this event.";
    $type = "error";
} else {
    // 5. If not registered -> insert into eventregistration with CURDATE()
    $insertQuery = "INSERT INTO eventregistration (eventID, studentID, registrationDate) VALUES (?, ?, CURDATE())";
    $stmt = mysqli_prepare($conn, $insertQuery);
    mysqli_stmt_bind_param($stmt, "ii", $eventID, $studentID);

    if (mysqli_stmt_execute($stmt)) {
        $message = "Successfully registered for the event!";
        $type = "success";
    } else {
        $message = "Registration failed. Please try again later.";
        $type = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Registration Status - Alumni Engagement System</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 40px;
            text-align: center;
        }

        .message-box {
            border-radius: 8px;
            padding: 20px;
            display: inline-block;
            min-width: 300px;
        }

        .success {
            background-color: #dff0d8;
            color: #3c763d;
            border: 1px solid #d6e9c6;
        }

        .error {
            background-color: #f2dede;
            color: #a94442;
            border: 1px solid #ebccd1;
        }

        .back-link {
            margin-top: 20px;
            display: block;
            text-decoration: none;
            color: #337ab7;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>

    <div class="message-box <?php echo $type; ?>">
        <h2><?php echo ($type === 'success') ? "Success" : "Notice"; ?></h2>
        <p><?php echo htmlspecialchars($message); ?></p>
    </div>

    <br>
    <a href="events.php" class="back-link">Back to Events List</a>
    <a href="../dashboard.php" class="back-link">Back to Dashboard</a>

</body>

</html>