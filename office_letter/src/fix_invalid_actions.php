<?php
/**
 * Database Maintenance Script
 * Fixes invalid action values in activitylog table
 * 
 * This script will:
 * 1. Show all records with invalid actions ('0', empty, or NULL)
 * 2. Optionally delete or update these records
 */

require_once '../config/db.php';

$cleanup_message = '';
$cleanup_status = '';

// Handle cleanup execution BEFORE displaying the page
if (isset($_POST['execute_cleanup'])) {
    $cleanup_action = $_POST['cleanup_action'];
    
    if ($cleanup_action === 'delete') {
        $cleanup_sql = "DELETE FROM activitylog WHERE action IS NULL OR action = '' OR action = '0'";
        if ($conn->query($cleanup_sql)) {
            $cleanup_message = "Deleted {$conn->affected_rows} invalid records.";
            $cleanup_status = 'success';
        } else {
            $cleanup_message = "Error: " . $conn->error;
            $cleanup_status = 'danger';
        }
    } elseif ($cleanup_action === 'update') {
        $cleanup_sql = "UPDATE activitylog SET action = 'unknown_action' WHERE action IS NULL OR action = '' OR action = '0'";
        if ($conn->query($cleanup_sql)) {
            $cleanup_message = "Updated {$conn->affected_rows} records to 'unknown_action'.";
            $cleanup_status = 'success';
        } else {
            $cleanup_message = "Error: " . $conn->error;
            $cleanup_status = 'danger';
        }
    }
}

// Check for invalid actions
$check_sql = "SELECT id, user_id, action, letter_id, timestamp 
              FROM activitylog 
              WHERE action IS NULL OR action = '' OR action = '0'
              ORDER BY timestamp DESC";

$result = $conn->query($check_sql);

echo "<!DOCTYPE html>
<html>
<head>
    <title>Fix Invalid Actions - Database Maintenance</title>
    <link rel=\"stylesheet\" href=\"https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css\">
</head>
<body>
<div class=\"container mt-5\">
    <h2>Database Maintenance - Invalid Action Records</h2>
    <div class=\"alert alert-warning\">
        <strong>Warning:</strong> This tool helps identify and fix invalid action values in the activitylog table.
    </div>";

// Show cleanup result message if exists
if (!empty($cleanup_message)) {
    echo "<div class=\"alert alert-{$cleanup_status}\">
            <strong>" . ($cleanup_status === 'success' ? 'Success!' : 'Error!') . "</strong> {$cleanup_message}
          </div>";
}

echo "";

if ($result && $result->num_rows > 0) {
    echo "<div class=\"alert alert-danger\">
            <strong>Found {$result->num_rows} records with invalid actions!</strong>
          </div>";
    
    echo "<table class=\"table table-bordered table-striped\">
            <thead class=\"table-dark\">
                <tr>
                    <th>ID</th>
                    <th>User ID</th>
                    <th>Action (Current)</th>
                    <th>Letter ID</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>{$row['id']}</td>
                <td>{$row['user_id']}</td>
                <td><span class=\"badge bg-danger\">" . htmlspecialchars($row['action'] ?: 'NULL/Empty') . "</span></td>
                <td>" . ($row['letter_id'] ?: 'N/A') . "</td>
                <td>{$row['timestamp']}</td>
              </tr>";
    }
    
    echo "</tbody></table>";
    
    // Action form
    echo "<div class=\"card mt-4\">
            <div class=\"card-header bg-primary text-white\">
                <h5>Cleanup Options</h5>
            </div>
            <div class=\"card-body\">
                <form method=\"post\" action=\"\" onsubmit=\"return confirm('Are you sure you want to perform this action?');\">
                    <div class=\"mb-3\">
                        <label class=\"form-label\"><strong>Choose an action:</strong></label>
                        <div class=\"form-check\">
                            <input class=\"form-check-input\" type=\"radio\" name=\"cleanup_action\" id=\"delete\" value=\"delete\" checked>
                            <label class=\"form-check-label\" for=\"delete\">
                                <strong>Delete</strong> all records with invalid actions (Recommended)
                            </label>
                        </div>
                        <div class=\"form-check\">
                            <input class=\"form-check-input\" type=\"radio\" name=\"cleanup_action\" id=\"update\" value=\"update\">
                            <label class=\"form-check-label\" for=\"update\">
                                <strong>Update</strong> to 'unknown_action' (Keep for reference)
                            </label>
                        </div>
                    </div>
                    <button type=\"submit\" name=\"execute_cleanup\" class=\"btn btn-warning\">
                        Execute Cleanup
                    </button>
                    <a href=\"dashboard.php\" class=\"btn btn-secondary\">Cancel</a>
                </form>
            </div>
        </div>";
    
} else {
    echo "<div class=\"alert alert-success\">
            <strong>Great!</strong> No invalid action records found in the database.
          </div>
          <a href=\"dashboard.php\" class=\"btn btn-primary\">Back to Dashboard</a>";
}

echo "</div>
<script src=\"https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js\"></script>
</body>
</html>";

$conn->close();
?>
