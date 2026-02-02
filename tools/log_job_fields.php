<?php
require_once "config/db.php";
$table = 'jobposting';
$desc = $conn->query("DESCRIBE $table");
while ($d = $desc->fetch_assoc()) {
    echo "Field: " . $d['Field'] . " | Type: " . $d['Type'] . "\n";
}
