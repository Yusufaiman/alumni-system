<?php
// Function to distribute notifications to individual users
function distribute_notifications($conn, $adminNotificationID, $targetRole, $title, $message)
{
    // Basic Query: Insert into notification table from user table
    // Columns: title, message, link, targetUserID, referenceType, referenceID, status
    // We assume 'link' can be empty for system messages
    // We map adminNotificationID to referenceID for traceability

    $sql = "INSERT INTO notification (title, message, link, targetUserID, referenceType, referenceID, status, targetGroup, sentDate) 
            SELECT ?, ?, '', userID, 'SYSTEM', ?, 'SENT', ?, NOW() 
            FROM user";

    // Map role strings to database Enum values for targetGroup
    $group = 'STUDENT';
    if ($targetRole === 'ALUMNI')
        $group = 'ALUMNI';
    if ($targetRole === 'CAREER_OFFICER')
        $group = 'CAREER_SERVICE_OFFICER';
    if ($targetRole === 'ALL')
        $group = 'STUDENT'; // Fallback / Simplified

    // Add filtering based on targetRole
    if ($targetRole !== 'ALL') {
        // Correct the role string to match user table if necessary
        $dbRole = $targetRole;
        if ($targetRole === 'CAREER_OFFICER')
            $dbRole = 'CAREER_SERVICE_OFFICER';

        $sql .= " WHERE role = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt)
            return false;
        $stmt->bind_param("ssiss", $title, $message, $adminNotificationID, $group, $dbRole);
    } else {
        $stmt = $conn->prepare($sql);
        if (!$stmt)
            return false;
        $stmt->bind_param("ssis", $title, $message, $adminNotificationID, $group);
    }

    $res = $stmt->execute();
    $stmt->close();
    return $res;
}
?>