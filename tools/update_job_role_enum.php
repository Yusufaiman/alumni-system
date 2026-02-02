<?php
require_once "config/db.php";
$sql = "ALTER TABLE jobposting MODIFY COLUMN postedByRole ENUM('CAREER_SERVICE_OFFICER', 'ALUMNI') NOT NULL";
if ($conn->query($sql)) {
    echo "Jobposting table role enum updated successfully.\n";
} else {
    echo "Error updating jobposting table: " . $conn->error . "\n";
}
