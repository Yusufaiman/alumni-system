<?php
require_once "config/db.php";
$res = $conn->query("DESCRIBE announcement");
$output = "";
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $output .= $row['Field'] . " (" . $row['Type'] . ") Null:" . $row['Null'] . "\n";
    }
} else {
    $output = "Table announcement does not exist.";
}
file_put_contents("announcement_schema.txt", $output);
?>