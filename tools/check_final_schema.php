<?php
require_once "config/db.php";
echo "NOTIFICATION COLS: ";
$res = $conn->query("SHOW COLUMNS FROM notification");
while ($row = $res->fetch_assoc()) {
    echo $row['Field'] . " ";
}
echo "\n";
echo "USER COLS: ";
$res = $conn->query("SHOW COLUMNS FROM user");
while ($row = $res->fetch_assoc()) {
    echo $row['Field'] . " ";
}
?>