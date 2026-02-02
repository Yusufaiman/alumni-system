<?php
require_once "config/db.php";
$res = $conn->query("DESCRIBE jobposting");
while ($row = $res->fetch_assoc()) {
    if ($row['Field'] == 'postedByRole') {
        echo $row['Type'];
    }
}
