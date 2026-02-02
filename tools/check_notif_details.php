<?php
require_once "config/db.php";

echo "--- ADMIN NOTIFICATION ---\n";
$res = $conn->query("DESCRIBE admin_notification");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
} else {
    echo "admin_notification not found.\n";
}

echo "\n--- NOTIFICATION ---\n";
$res = $conn->query("DESCRIBE notification");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
} else {
    echo "notification not found.\n";
}
?>