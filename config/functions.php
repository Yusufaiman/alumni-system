<?php
/**
 * NOTIFICATION HELPERS & LOGIC
 * Includes the core createNotification function and database schema management.
 */
require_once __DIR__ . "/db.php";

/**
 * Creates a notification for a specific user.
 */
function createNotification($conn, $title, $message, $link, $targetUserID, $category = 'system', $refType = null, $refID = null, $targetGroup = null)
{
    $sql = "INSERT INTO notification (title, message, link, targetUserID, category, referenceType, referenceID, targetGroup, sentDate, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'SENT')";

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        error_log("Notification Prepare Failed: " . mysqli_error($conn));
        return false;
    }

    // Exactly 8 placeholders = 8 type markers = 8 variables
    // Types: s(title), s(message), s(link), i(targetUserID), s(category), s(refType), i(refID), s(targetGroup)
    mysqli_stmt_bind_param($stmt, "sssisisi", $title, $message, $link, $targetUserID, $category, $refType, $refID, $targetGroup);

    $result = mysqli_stmt_execute($stmt);

    if (!$result) {
        error_log("Notification Execute Failed: " . mysqli_stmt_error($stmt));
    }

    mysqli_stmt_close($stmt);
    return $result;
}

// Initialization Logic: Ensure the table structure is correct
$tableCheck = "CREATE TABLE IF NOT EXISTS notification (
    notificationID INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(255),
    targetUserID INT NOT NULL,
    category VARCHAR(50) DEFAULT 'system',
    referenceType ENUM('JOB', 'MENTORSHIP', 'EVENT', 'SYSTEM') DEFAULT 'SYSTEM',
    referenceID INT,
    targetGroup ENUM('STUDENT', 'ALUMNI', 'CAREER_SERVICE_OFFICER', 'ADMIN') DEFAULT 'STUDENT',
    sentDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('SENT', 'READ', 'ARCHIVED') DEFAULT 'SENT',
    FOREIGN KEY (targetUserID) REFERENCES user(userID) ON DELETE CASCADE
)";
mysqli_query($conn, $tableCheck);

// Migrations: Check and Add missing columns
$columns = [
    'category' => "category VARCHAR(50) DEFAULT 'system' AFTER targetUserID",
    'link' => "link VARCHAR(255) AFTER message",
    'referenceType' => "referenceType ENUM('JOB', 'MENTORSHIP', 'EVENT', 'SYSTEM') DEFAULT 'SYSTEM' AFTER category",
    'referenceID' => "referenceID INT AFTER referenceType",
    'targetGroup' => "targetGroup ENUM('STUDENT', 'ALUMNI', 'CAREER_SERVICE_OFFICER', 'ADMIN') DEFAULT 'STUDENT' AFTER referenceID"
];

foreach ($columns as $col => $definition) {
    $check = mysqli_query($conn, "SHOW COLUMNS FROM notification LIKE '$col'");
    if (mysqli_num_rows($check) == 0) {
        mysqli_query($conn, "ALTER TABLE notification ADD COLUMN $definition");
    }
}

// Ensure ARCHIVED status is supported
mysqli_query($conn, "ALTER TABLE notification MODIFY COLUMN status ENUM('SENT', 'READ', 'ARCHIVED') DEFAULT 'SENT'");

/**
 * Records an activity log in the database.
 */
function logActivity($conn, $userId, $role, $action, $module, $description)
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    $sql = "INSERT INTO activity_logs (user_id, user_role, action, module, description, ip_address) 
            VALUES (?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "isssss", $userId, $role, $action, $module, $description, $ip);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}
?>