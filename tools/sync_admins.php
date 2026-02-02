<?php
require_once "config/db.php";

echo "Syncing Admins...\n";

// 1. Fetch all users with role 'ADMIN'
$sql = "SELECT userID, name, email FROM user WHERE role = 'ADMIN'";
$res = $conn->query($sql);

if ($res->num_rows > 0) {
    while ($user = $res->fetch_assoc()) {
        $userID = $user['userID'];
        $name = $user['name']; // Assuming name exists
        // Check if exists in admin
        $check = $conn->query("SELECT adminID FROM admin WHERE userID = $userID");
        if ($check->num_rows == 0) {
            echo "Inserting Admin for UserID: $userID ($name)...\n";
            // Insert
            $stmt = $conn->prepare("INSERT INTO admin (userID, role, full_name, createdDate) VALUES (?, 'SYSTEM_ADMIN', ?, NOW())");
            // Defaulting to SYSTEM_ADMIN, assuming enum has it
            $stmt->bind_param("is", $userID, $name);
            if ($stmt->execute()) {
                echo "Success.\n";
            } else {
                echo "Failed: " . $stmt->error . "\n";
            }
        } else {
            echo "Admin already exists for UserID: $userID\n";
        }
    }
} else {
    echo "No ADMIN users found in user table.\n";
}
echo "Done.\n";
?>