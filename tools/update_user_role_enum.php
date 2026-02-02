<?php
require_once "config/db.php";
$sql = "ALTER TABLE user MODIFY COLUMN role ENUM('STUDENT', 'ALUMNI', 'CAREER_SERVICE_OFFICER', 'ADMIN') NOT NULL";
if ($conn->query($sql)) {
    echo "User table role enum updated successfully.\n";
} else {
    echo "Error updating user table: " . $conn->error . "\n";
}
