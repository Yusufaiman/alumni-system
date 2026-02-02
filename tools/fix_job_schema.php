<?php
require_once "config/db.php";
$stmt = $conn->prepare("ALTER TABLE jobposting MODIFY COLUMN careerOfficerID INT NULL");
if ($stmt->execute()) {
    echo "careerOfficerID made nullable.\n";
} else {
    echo "Error: " . $conn->error . "\n";
}
