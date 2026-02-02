<?php
require_once "config/db.php";
$desc = $conn->query("DESCRIBE jobposting");
while ($d = $desc->fetch_assoc()) {
    echo $d['Field'] . "\n";
}
