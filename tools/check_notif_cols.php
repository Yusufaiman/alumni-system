<?php
require_once "config/db.php";
$res = $conn->query("SHOW COLUMNS FROM admin_notification");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        echo $row['Field'] . " ";
    }
}
?>