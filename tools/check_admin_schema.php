<?php
require_once "config/db.php";

echo "Checking tables...\n";

// Check if admin table exists specifically, or if we use users
$res = $conn->query("SHOW TABLES LIKE 'admin'");
if ($res->num_rows > 0) {
    echo "Table 'admin' exists.\n";
    $conn->query("DESCRIBE admin");
} else {
    echo "Table 'admin' does NOT exist. Assuming 'users' table with role='ADMIN'.\n";
    $res = $conn->query("DESCRIBE users");
    while ($row = $res->fetch_assoc()) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
}

// Check if we need to add 'is_onboarded' to users
$res = $conn->query("SHOW COLUMNS FROM users LIKE 'is_onboarded'");
if ($res->num_rows == 0) {
    echo "Adding 'is_onboarded' column to users...\n";
    $conn->query("ALTER TABLE users ADD COLUMN is_onboarded BOOLEAN DEFAULT FALSE");
}

// Create admin_system_settings
$sql = "CREATE TABLE IF NOT EXISTS admin_system_settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
if ($conn->query($sql)) {
    echo "Table 'admin_system_settings' ready.\n";
}

?>