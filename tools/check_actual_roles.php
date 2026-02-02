<?php
require_once "config/db.php";
$res = $conn->query("SELECT DISTINCT role FROM user");
$roles = [];
while ($row = $res->fetch_assoc()) {
    $roles[] = $row['role'];
}
file_put_contents("actual_roles.txt", implode(", ", $roles));
