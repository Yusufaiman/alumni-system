<?php
require_once "config/db.php";
$res = $conn->query("DESCRIBE user");
while ($row = $res->fetch_assoc()) {
    if ($row['Field'] == 'role') {
        echo $row['Type'];
    }
}
