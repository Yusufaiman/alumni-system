<?php
require_once "config/db.php";
$res = $conn->query("SELECT COUNT(*) as c FROM announcement");
if ($res && $res->fetch_assoc()['c'] == 0) {
    $conn->query("INSERT INTO announcement (title, content, createdBy, createdRole, createdDate, status) 
                  VALUES ('Welcome to the Alumni Portal', 'We are excited to launch the new Alumni Engagement System. Explore job opportunities, register for events, and connect with mentors.', 1, 'ADMIN', CURRENT_DATE, 'ACTIVE')");
    echo "Mock announcement created.";
} else {
    echo "Announcements already exist.";
}
?>