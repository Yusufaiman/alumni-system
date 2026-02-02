<?php
require_once "config/db.php";
$res = $conn->query("SHOW TABLES");
while ($row = $res->fetch_array()) {
    $table = $row[0];
    echo "Table: $table\n";
    $desc = $conn->query("DESCRIBE $table");
    while ($d = $desc->fetch_assoc()) {
        echo "  - {$d['Field']} ({$d['Type']})\n";
    }
    echo "\n";
}
