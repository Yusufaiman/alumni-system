<?php
require_once "config/db.php";

$tables = ["user", "student"];
foreach ($tables as $table) {
    echo "TABLE: $table\n";
    $result = $conn->query("SHOW COLUMNS FROM $table");
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
    echo "------------------\n";
}
