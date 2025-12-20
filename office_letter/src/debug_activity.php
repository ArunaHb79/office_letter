<?php
require_once 'session_config.php';
session_start();
require_once '../config/db.php';

// Quick check for activity data
echo "<h3>Database Activity Check</h3>";

// Check if activitylog table exists and has data
$result = $conn->query("SELECT COUNT(*) as total FROM activitylog");
if ($result) {
    $row = $result->fetch_assoc();
    echo "<p><strong>Total activity logs:</strong> " . $row['total'] . "</p>";
} else {
    echo "<p><strong>Error:</strong> " . $conn->error . "</p>";
}

# Check recent activities
$result = $conn->query("DESCRIBE activitylog");
if ($result) {
    echo "<h4>ActivityLog Table Structure:</h4>";
    echo "<table border='1'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

$result = $conn->query("SELECT * FROM activitylog ORDER BY timestamp DESC LIMIT 5");
if ($result && $result->num_rows > 0) {
    echo "<h4>Recent Activities:</h4>";
    echo "<table border='1'>";
    echo "<tr><th>User ID</th><th>Action</th><th>Timestamp</th><th>IP Address</th><th>Changes</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['user_id'] . "</td>";
        echo "<td>" . $row['action'] . "</td>";
        echo "<td>" . $row['timestamp'] . "</td>";
        echo "<td>" . (isset($row['ip_address']) ? $row['ip_address'] : 'N/A') . "</td>";
        echo "<td>" . (isset($row['changes']) ? substr($row['changes'], 0, 50) . "..." : 'N/A') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p><strong>No recent activities found.</strong></p>";
}

// Check users
$result = $conn->query("
    SELECT u.id, u.username, e.name as employee_name, 
           COUNT(al.id) as activity_count
    FROM users u 
    JOIN employee e ON u.employee_id = e.id 
    LEFT JOIN activitylog al ON u.id = al.user_id 
    GROUP BY u.id, u.username, e.name
    ORDER BY activity_count DESC
");

if ($result && $result->num_rows > 0) {
    echo "<h4>User Activity Summary:</h4>";
    echo "<table border='1'>";
    echo "<tr><th>User ID</th><th>Username</th><th>Employee Name</th><th>Activity Count</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['username'] . "</td>";
        echo "<td>" . $row['employee_name'] . "</td>";
        echo "<td>" . $row['activity_count'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p><strong>No users found or error:</strong> " . $conn->error . "</p>";
}

echo "<p><a href='user_activity_summary.php'>Back to Activity Summary</a></p>";

echo "<hr>";
echo "<h3>Generate Test Data</h3>";
echo "<p>If you have no activity data, you can generate some test activities:</p>";
echo "<form method='post'>";
echo "<button type='submit' name='generate_test_data' value='1'>Generate Test Activities</button>";
echo "</form>";

if (isset($_POST['generate_test_data'])) {
    // Check if ActivityLogger exists
    if (file_exists('activity_logger.php')) {
        require_once 'activity_logger.php';
        
        // Get a user ID for testing
        $user_result = $conn->query("SELECT id FROM users LIMIT 1");
        if ($user_result && $user_result->num_rows > 0) {
            $user_row = $user_result->fetch_assoc();
            $user_id = $user_row['id'];
            
            // Generate some test activities
            $activities = [
                'login_success',
                'letter_created',
                'letter_updated', 
                'letter_viewed',
                'dashboard_accessed'
            ];
            
            for ($i = 0; $i < 10; $i++) {
                $action = $activities[array_rand($activities)];
                $changes = json_encode(['test' => 'data', 'iteration' => $i]);
                
                $stmt = $conn->prepare("INSERT INTO activitylog (user_id, action, ip_address, changes, timestamp) VALUES (?, ?, ?, ?, NOW())");
                $stmt->bind_param("isss", $user_id, $action, $_SERVER['REMOTE_ADDR'], $changes);
                $stmt->execute();
            }
            
            echo "<div style='color: #0a6000; font-weight:700;'><strong>Generated 10 test activities!</strong></div>";
            echo "<script>setTimeout(function(){ window.location.reload(); }, 2000);</script>";
        } else {
            echo "<div style='color: #b30000; font-weight:700;'><strong>No users found to generate test data.</strong></div>";
        }
    } else {
        echo "<div style='color: #b30000; font-weight:700;'><strong>ActivityLogger not found.</strong></div>";
    }
}
?>