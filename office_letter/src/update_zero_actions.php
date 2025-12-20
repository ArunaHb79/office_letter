<?php
/**
 * Update Invalid Actions to Meaningful Names
 * This script will intelligently update all records with action='0' or 'unknown_action' 
 * to meaningful action names based on the context (old_values, new_values, letter_id presence)
 */

require_once '../config/db.php';

$cleanup_message = '';
$cleanup_status = '';

// Handle update execution BEFORE displaying the page
if (isset($_POST['execute_update'])) {
    $update_method = $_POST['update_method'];
    
    if ($update_method === 'auto') {
        // Automatic intelligent update using CASE statement with deep analysis
        $update_sql = "UPDATE activitylog 
                       SET action = CASE 
                           -- Letter-related actions
                           WHEN old_values IS NOT NULL AND new_values IS NOT NULL AND letter_id IS NOT NULL THEN 'letter_updated'
                           WHEN old_values IS NULL AND new_values IS NOT NULL AND letter_id IS NOT NULL THEN 'letter_created'
                           WHEN old_values IS NOT NULL AND new_values IS NULL AND letter_id IS NOT NULL THEN 'letter_deleted'
                           WHEN letter_id IS NOT NULL THEN 'letter_viewed'
                           
                           -- System actions - analyze JSON content (check all possibilities)
                           WHEN letter_id IS NULL AND (new_values LIKE '%login%' OR new_values LIKE '%Login%' OR new_values LIKE '%logged in%') THEN 'user_login'
                           WHEN letter_id IS NULL AND (new_values LIKE '%logout%' OR new_values LIKE '%Logout%' OR new_values LIKE '%logged out%') THEN 'user_logout'
                           WHEN letter_id IS NULL AND (new_values LIKE '%password%' OR new_values LIKE '%Password%') AND new_values NOT LIKE '%reset%' THEN 'password_changed'
                           WHEN letter_id IS NULL AND (new_values LIKE '%reset%' OR new_values LIKE '%Reset%') AND (new_values LIKE '%password%' OR new_values LIKE '%email%') THEN 'password_reset_requested'
                           WHEN letter_id IS NULL AND (new_values LIKE '%role%' OR new_values LIKE '%Role%' OR new_values LIKE '%permission%') THEN 'role_changed'
                           WHEN letter_id IS NULL AND (new_values LIKE '%department%' OR new_values LIKE '%Department%') THEN 'department_changed'
                           WHEN letter_id IS NULL AND (new_values LIKE '%employee%' OR new_values LIKE '%Employee%') THEN 'employee_updated'
                           WHEN letter_id IS NULL AND (new_values LIKE '%user%' OR new_values LIKE '%User%') AND old_values IS NOT NULL THEN 'user_updated'
                           WHEN letter_id IS NULL AND old_values IS NOT NULL AND new_values IS NOT NULL THEN 'profile_updated'
                           
                           -- Keep as generic system action if none match
                           ELSE 'system_operation'
                       END
                       WHERE action = '0' OR action = 'unknown_action' OR action = 'system_action'";
        
        if ($conn->query($update_sql)) {
            $total_affected = $conn->affected_rows;
            $cleanup_message = "Automatically updated {$total_affected} records from generic actions to specific action names based on intelligent context analysis.";
            $cleanup_status = 'success';
        } else {
            $cleanup_message = "Error: " . $conn->error;
            $cleanup_status = 'danger';
        }
        
    } elseif ($update_method === 'manual') {
        $new_action = $_POST['new_action'];
        
        if ($new_action === 'custom' && !empty($_POST['custom_action'])) {
            $new_action = trim($_POST['custom_action']);
            $new_action = preg_replace('/[^a-z0-9_]/i', '_', $new_action);
            $new_action = strtolower($new_action);
        }
        
        if (!empty($new_action) && $new_action !== 'custom') {
            $update_sql = "UPDATE activitylog SET action = ? WHERE action = '0' OR action = 'unknown_action' OR action = 'system_action'";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param('s', $new_action);
            
            if ($stmt->execute()) {
                $cleanup_message = "Updated {$stmt->affected_rows} records to '{$new_action}'.";
                $cleanup_status = 'success';
            } else {
                $cleanup_message = "Error: " . $conn->error;
                $cleanup_status = 'danger';
            }
        }
    }
}

// Check what records have action='0' or 'unknown_action' and try to determine context
$check_sql = "SELECT al.*, l.letter_number, u.username,
              CASE 
                -- Letter-related actions
                WHEN al.old_values IS NOT NULL AND al.new_values IS NOT NULL AND al.letter_id IS NOT NULL THEN 'letter_updated'
                WHEN al.old_values IS NULL AND al.new_values IS NOT NULL AND al.letter_id IS NOT NULL THEN 'letter_created'
                WHEN al.old_values IS NOT NULL AND al.new_values IS NULL AND al.letter_id IS NOT NULL THEN 'letter_deleted'
                WHEN al.old_values IS NULL AND al.new_values IS NULL AND al.letter_id IS NOT NULL THEN 'letter_viewed'
                
                -- System actions - analyze content (comprehensive)
                WHEN al.letter_id IS NULL AND (al.new_values LIKE '%login%' OR al.new_values LIKE '%Login%' OR al.new_values LIKE '%logged in%') THEN 'user_login'
                WHEN al.letter_id IS NULL AND (al.new_values LIKE '%logout%' OR al.new_values LIKE '%Logout%' OR al.new_values LIKE '%logged out%') THEN 'user_logout'
                WHEN al.letter_id IS NULL AND (al.new_values LIKE '%password%' OR al.new_values LIKE '%Password%') AND al.new_values NOT LIKE '%reset%' THEN 'password_changed'
                WHEN al.letter_id IS NULL AND (al.new_values LIKE '%reset%' OR al.new_values LIKE '%Reset%') AND (al.new_values LIKE '%password%' OR al.new_values LIKE '%email%') THEN 'password_reset_requested'
                WHEN al.letter_id IS NULL AND (al.new_values LIKE '%role%' OR al.new_values LIKE '%Role%' OR al.new_values LIKE '%permission%') THEN 'role_changed'
                WHEN al.letter_id IS NULL AND (al.new_values LIKE '%department%' OR al.new_values LIKE '%Department%') THEN 'department_changed'
                WHEN al.letter_id IS NULL AND (al.new_values LIKE '%employee%' OR al.new_values LIKE '%Employee%') THEN 'employee_updated'
                WHEN al.letter_id IS NULL AND (al.new_values LIKE '%user%' OR al.new_values LIKE '%User%') AND al.old_values IS NOT NULL THEN 'user_updated'
                WHEN al.letter_id IS NULL AND al.old_values IS NOT NULL AND al.new_values IS NOT NULL THEN 'profile_updated'
                WHEN al.letter_id IS NULL THEN 'system_operation'
                
                ELSE 'unknown_action'
              END as suggested_action
              FROM activitylog al 
              LEFT JOIN letter l ON al.letter_id = l.id
              LEFT JOIN users u ON al.user_id = u.id
              WHERE al.action = '0' OR al.action = 'unknown_action' OR al.action = 'system_action'
              ORDER BY al.timestamp DESC
              LIMIT 30";

$result = $conn->query($check_sql);

echo "<!DOCTYPE html>
<html>
<head>
    <title>Update Action '0' - Database Maintenance</title>
    <link rel=\"stylesheet\" href=\"https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css\">
</head>
<body>
<div class=\"container mt-5\">
    <h2>Update Invalid Actions to Meaningful Names</h2>
    <div class=\"alert alert-info\">
        <strong>Purpose:</strong> This tool analyzes context and intelligently updates invalid action records ('0', 'unknown_action', 'system_action') to meaningful, specific action names.
    </div>";

// Show cleanup result message if exists
if (!empty($cleanup_message)) {
    echo "<div class=\"alert alert-{$cleanup_status}\">
            <strong>" . ($cleanup_status === 'success' ? 'Success!' : 'Error!') . "</strong> {$cleanup_message}
          </div>";
}

echo "";

if ($result && $result->num_rows > 0) {
    $total_count_result = $conn->query("SELECT COUNT(*) as total FROM activitylog WHERE action = '0' OR action = 'unknown_action' OR action = 'system_action'");
    $total_count = $total_count_result->fetch_assoc()['total'];
    
    echo "<div class=\"alert alert-warning\">
            <strong>Found {$total_count} records with invalid/generic actions ('0', 'unknown_action', 'system_action')</strong>
          </div>";
    
    echo "<h5>Sample Records with Suggested Actions (showing up to 30):</h5>";
    echo "<table class=\"table table-bordered table-striped table-sm\">
            <thead class=\"table-dark\">
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Current</th>
                    <th>Suggested Action</th>
                    <th>Letter</th>
                    <th>Has Old Values</th>
                    <th>Has New Values</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>";
    
    $suggestions_count = [];
    while ($row = $result->fetch_assoc()) {
        $has_old = $row['old_values'] ? '✓' : '-';
        $has_new = $row['new_values'] ? '✓' : '-';
        $suggested = $row['suggested_action'];
        
        // Count suggestions
        if (!isset($suggestions_count[$suggested])) {
            $suggestions_count[$suggested] = 0;
        }
        $suggestions_count[$suggested]++;
        
        // Determine badge color
        $badge_colors = [
            'letter_updated' => 'primary',
            'letter_created' => 'success',
            'letter_deleted' => 'danger',
            'letter_viewed' => 'info',
            'password_changed' => 'warning',
            'user_login' => 'success',
            'user_logout' => 'secondary',
            'role_changed' => 'primary',
            'department_changed' => 'primary',
            'employee_updated' => 'info',
            'user_updated' => 'info',
            'system_operation' => 'secondary',
            'password_reset_requested' => 'warning',
            'profile_updated' => 'info',
            'system_action' => 'secondary',
            'unknown_action' => 'dark'
        ];
        $badge_color = $badge_colors[$suggested] ?? 'warning';
        
        echo "<tr>
                <td>{$row['id']}</td>
                <td><small>" . htmlspecialchars($row['username']) . "</small></td>
                <td><span class=\"badge bg-danger\">" . htmlspecialchars($row['action']) . "</span></td>
                <td><span class=\"badge bg-{$badge_color}\">{$suggested}</span></td>
                <td><small>" . htmlspecialchars($row['letter_number'] ?: 'N/A') . "</small></td>
                <td class=\"text-center\">{$has_old}</td>
                <td class=\"text-center\">{$has_new}</td>
                <td><small>{$row['timestamp']}</small></td>
              </tr>";
    }
    
    echo "</tbody></table>";
    
    // Show suggestion summary
    if (!empty($suggestions_count)) {
        echo "<div class=\"alert alert-light\">
                <strong>AI Suggestion Summary:</strong><br>";
        foreach ($suggestions_count as $action => $count) {
            echo "<span class=\"badge bg-secondary me-2\">{$action}: {$count} records</span>";
        }
        echo "</div>";
    }
    
    // Update form with two methods
    echo "<div class=\"card mt-4\">
            <div class=\"card-header bg-success text-white\">
                <h5>✨ Recommended: Automatic Intelligent Update</h5>
            </div>
            <div class=\"card-body\">
                <form method=\"post\" action=\"\" onsubmit=\"return confirm('This will automatically analyze and update {$total_count} records based on their context. Continue?');\">
                    <input type=\"hidden\" name=\"update_method\" value=\"auto\">
                    <p class=\"mb-3\">
                        <strong>How it works:</strong> The system analyzes each record's context (letter_id, old_values, new_values content) and assigns the most appropriate action:
                    </p>
                    <div class=\"row\">
                        <div class=\"col-md-6\">
                            <strong>Letter Actions:</strong>
                            <ul class=\"small mb-3\">
                                <li>Old + New values + Letter → <span class=\"badge bg-primary\">letter_updated</span></li>
                                <li>New values only + Letter → <span class=\"badge bg-success\">letter_created</span></li>
                                <li>Old values only + Letter → <span class=\"badge bg-danger\">letter_deleted</span></li>
                                <li>Letter ID but no values → <span class=\"badge bg-info\">letter_viewed</span></li>
                            </ul>
                        </div>
                        <div class=\"col-md-6\">
                            <strong>System Actions (analyzed by content):</strong>
                            <ul class=\"small mb-3\">
                                <li>Contains \"password\" → <span class=\"badge bg-warning\">password_changed</span></li>
                                <li>Contains \"login\" → <span class=\"badge bg-success\">user_login</span></li>
                                <li>Contains \"logout\" → <span class=\"badge bg-secondary\">user_logout</span></li>
                                <li>Contains \"role\" → <span class=\"badge bg-primary\">role_changed</span></li>
                                <li>Contains \"department\" → <span class=\"badge bg-primary\">department_changed</span></li>
                                <li>Contains \"email\" + \"reset\" → <span class=\"badge bg-warning\">password_reset_requested</span></li>
                                <li>Has old + new (no letter) → <span class=\"badge bg-info\">profile_updated</span></li>
                                <li>Generic system activity → <span class=\"badge bg-secondary\">system_action</span></li>
                            </ul>
                        </div>
                    </div>
                    <button type=\"submit\" name=\"execute_update\" class=\"btn btn-success btn-lg\">
                        <i class=\"fas fa-magic\"></i> Auto-Update All ({$total_count} records)
                    </button>
                </form>
            </div>
        </div>
        
        <div class=\"card mt-3\">
            <div class=\"card-header bg-primary text-white\">
                <h5>Manual Update (All to Same Action)</h5>
            </div>
            <div class=\"card-body\">
                <form method=\"post\" action=\"\" onsubmit=\"return confirm('Update all {$total_count} records to the same action?');\">
                    <input type=\"hidden\" name=\"update_method\" value=\"manual\">
                    <div class=\"mb-3\">
                        <label class=\"form-label\"><strong>Choose one action for all records:</strong></label>
                        
                        <div class=\"form-check\">
                            <input class=\"form-check-input\" type=\"radio\" name=\"new_action\" id=\"updated\" value=\"letter_updated\" checked>
                            <label class=\"form-check-label\" for=\"updated\">
                                <strong>letter_updated</strong> - Letter update records
                            </label>
                        </div>
                        
                        <div class=\"form-check\">
                            <input class=\"form-check-input\" type=\"radio\" name=\"new_action\" id=\"viewed\" value=\"letter_viewed\">
                            <label class=\"form-check-label\" for=\"viewed\">
                                <strong>letter_viewed</strong> - Letter view records
                            </label>
                        </div>
                        
                        <div class=\"form-check\">
                            <input class=\"form-check-input\" type=\"radio\" name=\"new_action\" id=\"created\" value=\"letter_created\">
                            <label class=\"form-check-label\" for=\"created\">
                                <strong>letter_created</strong> - Letter creation records
                            </label>
                        </div>
                        
                        <div class=\"form-check\">
                            <input class=\"form-check-input\" type=\"radio\" name=\"new_action\" id=\"system\" value=\"system_action\">
                            <label class=\"form-check-label\" for=\"system\">
                                <strong>system_action</strong> - System-generated activity
                            </label>
                        </div>
                        
                        <div class=\"form-check\">
                            <input class=\"form-check-input\" type=\"radio\" name=\"new_action\" id=\"unknown\" value=\"unknown_action\">
                            <label class=\"form-check-label\" for=\"unknown\">
                                <strong>unknown_action</strong> - Generic unknown activity
                            </label>
                        </div>
                        
                        <div class=\"form-check\">
                            <input class=\"form-check-input\" type=\"radio\" name=\"new_action\" id=\"custom\" value=\"custom\">
                            <label class=\"form-check-label\" for=\"custom\">
                                <strong>Custom:</strong> 
                                <input type=\"text\" name=\"custom_action\" class=\"form-control form-control-sm d-inline-block\" style=\"width: 250px;\" placeholder=\"Enter custom action name\">
                            </label>
                        </div>
                    </div>
                    
                    <button type=\"submit\" name=\"execute_update\" class=\"btn btn-primary\">
                        <i class=\"fas fa-sync\"></i> Manual Update All ({$total_count})
                    </button>
                    <a href=\"user_activity_summary.php\" class=\"btn btn-secondary\">Cancel</a>
                </form>
            </div>
        </div>";
    
} else {
    echo "<div class=\"alert alert-success\">
            <strong>Great!</strong> No records found with invalid actions ('0', 'unknown_action', 'system_action') in the database.
          </div>
          <a href=\"user_activity_summary.php\" class=\"btn btn-primary\">Back to Activity Summary</a>";
}

echo "</div>
<script src=\"https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js\"></script>
<link rel=\"stylesheet\" href=\"https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css\">
</body>
</html>";

$conn->close();
?>
