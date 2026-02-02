<?php
require_once "config/db.php";
$res = $conn->query("DESCRIBE admin_notification");
while ($row = $res->fetch_assoc()) {
    if ($row['Field'] == 'targetRole' || $row['Field'] == 'status') {
        echo $row['Field'] . ": " . $row['Type'] . "\n";
    }
}
?>