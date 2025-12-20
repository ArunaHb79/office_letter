<?php
require_once 'config/db.php';

echo "<h2>Adding Last Login Column</h2>";

// Add last_login column
$sql = "ALTER TABLE Users ADD COLUMN IF NOT EXISTS last_login DATETIME NULL COMMENT 'Last login timestamp' AFTER password";
if ($conn->query($sql)) {
    echo "<p>✓ Last login column added successfully!</p>";
} else {
    echo "<p>Error: " . $conn->error . "</p>";
}

// Create index
$sql = "CREATE INDEX IF NOT EXISTS idx_last_login ON Users(last_login)";
if ($conn->query($sql)) {
    echo "<p>✓ Index created successfully!</p>";
} else {
    echo "<p>Note: " . $conn->error . "</p>";
}

// Show table structure
echo "<h3>Users Table Structure:</h3>";
$result = $conn->query("DESCRIBE Users");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
    echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
    echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
    echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
    echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<hr><p><strong>Done! Now the last_login column is available.</strong></p>";
?>
