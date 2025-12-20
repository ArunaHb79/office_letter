<?php
require_once '../config/db.php';

echo "<h3>All Action Values in Database:</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Action</th><th>Count</th></tr>";

$result = $conn->query("SELECT action, COUNT(*) as count FROM activitylog GROUP BY action ORDER BY action");
while ($row = $result->fetch_assoc()) {
    $action_display = $row['action'] === '' ? '[EMPTY]' : ($row['action'] === null ? '[NULL]' : htmlspecialchars($row['action']));
    echo "<tr><td><strong>$action_display</strong></td><td>{$row['count']}</td></tr>";
}
echo "</table>";

echo "<hr>";
echo "<h3>Records with Action = '0':</h3>";
$result2 = $conn->query("SELECT * FROM activitylog WHERE action = '0' LIMIT 10");
if ($result2->num_rows > 0) {
    echo "<p style='color:#b30000; font-weight:700;'>Found {$result2->num_rows} records with action='0'</p>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>User ID</th><th>Action</th><th>Letter ID</th><th>Timestamp</th></tr>";
    while ($row = $result2->fetch_assoc()) {
        echo "<tr><td>{$row['id']}</td><td>{$row['user_id']}</td><td><strong style='color:#b30000; font-weight:700;'>{$row['action']}</strong></td><td>{$row['letter_id']}</td><td>{$row['timestamp']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:#0a6000; font-weight:700;'>No records found with action='0'</p>";
}
?>
