<?php
require_once "config/db.php";
$result = $conn->query("DESCRIBE event");
echo "<table border='1'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    foreach ($row as $val)
        echo "<td>$val</td>";
    echo "</tr>";
}
echo "</table>";

$result = $conn->query("SELECT * FROM event");
echo "<h3>Data</h3><table border='1'>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    foreach ($row as $val)
        echo "<td>$val</td>";
    echo "</tr>";
}
echo "</table>";
?>