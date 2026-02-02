<?php
require_once "config/db.php";

echo "TABLES LIKE NOTIF: ";
$res = $conn->query("SHOW TABLES LIKE '%notification%'");
while ($row = $res->fetch_row()) {
    echo $row[0] . ", ";
}
echo "\n";

// specific check for admin notifications if they are separate
echo "TABLES LIKE MSG: ";
$res = $conn->query("SHOW TABLES LIKE '%messag%'");
while ($row = $res->fetch_row()) {
    echo $row[0] . ", ";
}
echo "\n";
?>