<?php
require_once "config/db.php";

// 1. Check if eventID 0 exists and update it to a proper number if it does
$check = $conn->query("SELECT eventID FROM event WHERE eventID = 0");
if ($check && $check->num_rows > 0) {
    echo "Fixing existing record with ID 0...<br>";
    $maxRes = $conn->query("SELECT MAX(eventID) as max_id FROM event");
    $maxRow = $maxRes->fetch_assoc();
    $nextId = ($maxRow['max_id'] ?? 0) + 1;
    $conn->query("UPDATE event SET eventID = $nextId WHERE eventID = 0");
}

// 2. Alter table to add AUTO_INCREMENT
echo "Adding AUTO_INCREMENT to event table...<br>";
$query = "ALTER TABLE event MODIFY eventID INT AUTO_INCREMENT PRIMARY KEY";
// Note: It's already PRI, so just MODIFY is enough usually, but some DBs prefer full spec.
// Let's try simpler MODIFY first.
$query = "ALTER TABLE event MODIFY eventID INT NOT NULL AUTO_INCREMENT";

if ($conn->query($query)) {
    echo "✅ Successfully added AUTO_INCREMENT to eventID.<br>";
} else {
    echo "❌ Error adding AUTO_INCREMENT: " . $conn->error . "<br>";
}
?>