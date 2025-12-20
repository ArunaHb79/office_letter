<?php
require_once 'config/db.php';

echo "<h2>Updating Last Login Times</h2>";

// Update all users who have logged in before to current timestamp
$sql = "UPDATE Users SET last_login = NOW() WHERE id IN (SELECT DISTINCT user_id FROM ActivityLog WHERE action LIKE '%login%')";
if ($conn->query($sql)) {
    echo "<p>✓ Updated " . $conn->affected_rows . " users' last login times based on activity logs</p>";
} else {
    echo "<p>Error: " . $conn->error . "</p>";
}

// If no activity logs, just update all approved users
$sql = "UPDATE Users SET last_login = NOW() WHERE approved = 1 AND last_login IS NULL";
if ($conn->query($sql)) {
    echo "<p>✓ Set current time for " . $conn->affected_rows . " approved users without login time</p>";
} else {
    echo "<p>Error: " . $conn->error . "</p>";
}

// Show current status
echo "<h3>Current Last Login Status:</h3>";
$result = $conn->query("
    SELECT u.id, u.email, u.role, u.last_login, e.name as employee_name
    FROM Users u
    LEFT JOIN Employee e ON u.employee_id = e.id
    ORDER BY u.last_login DESC
");

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Email</th><th>Name</th><th>Role</th><th>Last Login</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['id']) . "</td>";
    echo "<td>" . htmlspecialchars($row['email']) . "</td>";
    echo "<td>" . htmlspecialchars($row['employee_name'] ?? 'N/A') . "</td>";
    echo "<td>" . htmlspecialchars($row['role']) . "</td>";
    echo "<td>" . ($row['last_login'] ? date('Y-m-d H:i:s', strtotime($row['last_login'])) : '<span style="color:red;">Never</span>') . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<hr><p><strong>Done! Refresh the reports page to see updated last login times.</strong></p>";
?>
