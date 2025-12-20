<?php
/**
 * Activity Logger Class
 * Provides comprehensive audit trail functionality for the Office Letter Management System
 * 
 * Features:
 * - Automatic logging via database triggers
 * - Manual logging for custom actions
 * - JSON change tracking
 * - IP and user agent tracking
 * - Role-based activity filtering
 */

class ActivityLogger {
    private $conn;
    private $user_id;
    private $ip_address;
    private $user_agent;
    
    public function __construct($database_connection, $user_id) {
        $this->conn = $database_connection;
        $this->user_id = intval($user_id);
        $this->ip_address = $this->getClientIP();
        $this->user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        // Set session variables for database triggers
        $this->setTriggerVariables();
    }
    
    /**
     * Get real client IP address (handles proxies and load balancers)
     */
    private function getClientIP() {
        $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, 
                        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    /**
     * Set session variables for database triggers
     */
    private function setTriggerVariables() {
        try {
            $this->conn->query("SET @current_user_id = " . $this->user_id);
            $this->conn->query("SET @current_ip_address = '" . $this->conn->real_escape_string($this->ip_address) . "'");
            $this->conn->query("SET @current_user_agent = '" . $this->conn->real_escape_string($this->user_agent) . "'");
        } catch (Exception $e) {
            error_log("ActivityLogger: Failed to set trigger variables: " . $e->getMessage());
        }
    }
    
    /**
     * Log an activity manually
     * 
     * @param string $action The action performed
     * @param int|null $letter_id The letter ID (if applicable)
     * @param array|null $old_values Previous values for update operations
     * @param array|null $new_values New values for update operations
     * @param array $additional_data Extra data to log
     * @return bool Success status
     */
    public function log($action, $letter_id = null, $old_values = null, $new_values = null, $additional_data = []) {
        try {
            // Prepare additional data
            if (!empty($additional_data)) {
                if ($new_values) {
                    $new_values = array_merge($new_values, $additional_data);
                } else {
                    $new_values = $additional_data;
                }
            }
            
            $stmt = $this->conn->prepare("
                INSERT INTO activitylog (
                    user_id, action, letter_id, old_values, new_values, 
                    ip_address, user_agent, timestamp
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $old_json = $old_values ? json_encode($old_values, JSON_UNESCAPED_UNICODE) : null;
            $new_json = $new_values ? json_encode($new_values, JSON_UNESCAPED_UNICODE) : null;
            
            $stmt->bind_param(
                'isissss',
                $this->user_id,
                $action,
                $letter_id,
                $old_json,
                $new_json,
                $this->ip_address,
                $this->user_agent
            );
            
            $result = $stmt->execute();
            
            if (!$result) {
                error_log("ActivityLogger: Failed to log action '$action': " . $stmt->error);
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("ActivityLogger: Exception logging action '$action': " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log letter creation
     */
    public function logLetterCreated($letter_id, $letter_data) {
        return $this->log('letter_created', $letter_id, null, $letter_data);
    }
    
    /**
     * Log letter update (manual - supplement to automatic trigger)
     */
    public function logLetterUpdated($letter_id, $old_data, $new_data) {
        return $this->log('letter_updated_manual', $letter_id, $old_data, $new_data);
    }
    
    /**
     * Log letter deletion
     */
    public function logLetterDeleted($letter_id, $letter_data) {
        return $this->log('letter_deleted', $letter_id, $letter_data, null);
    }
    
    /**
     * Log letter viewed
     */
    public function logLetterViewed($letter_id, $view_context = 'list') {
        return $this->log('letter_viewed', $letter_id, null, ['context' => $view_context]);
    }
    
    /**
     * Log letter assignment change
     */
    public function logLetterAssigned($letter_id, $old_employee_id, $new_employee_id) {
        return $this->log('letter_assigned', $letter_id, 
            ['employee_id' => $old_employee_id], 
            ['employee_id' => $new_employee_id]
        );
    }
    
    /**
     * Log status change
     */
    public function logStatusChanged($letter_id, $old_status, $new_status) {
        return $this->log('status_changed', $letter_id, 
            ['status' => $old_status], 
            ['status' => $new_status]
        );
    }
    
    /**
     * Log user authentication events
     */
    public function logLogin($login_method = 'standard') {
        return $this->log('user_login', null, null, ['method' => $login_method]);
    }
    
    public function logLogout($logout_type = 'manual') {
        return $this->log('user_logout', null, null, ['type' => $logout_type]);
    }
    
    public function logLoginFailed($username, $reason = 'invalid_credentials') {
        return $this->log('login_failed', null, null, [
            'username' => $username,
            'reason' => $reason
        ]);
    }
    
    /**
     * Static method to log failed login attempts without requiring a user ID
     */
    public static function logFailedLoginAttempt($database_connection, $username, $reason = 'invalid_credentials') {
        try {
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            $timestamp = date('Y-m-d H:i:s');
            
            $stmt = $database_connection->prepare(
                "INSERT INTO activitylog (user_id, action, old_values, new_values, ip_address, user_agent, timestamp) 
                 VALUES (NULL, 'login_failed', NULL, ?, ?, ?, ?)"
            );
            
            $new_values = json_encode([
                'username' => $username,
                'reason' => $reason,
                'ip_address' => $ip_address
            ]);
            
            $stmt->bind_param('ssss', $new_values, $ip_address, $user_agent, $timestamp);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("ActivityLogger: Failed to log failed login attempt: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log security events
     */
    public function logUnauthorizedAccess($resource, $attempted_action = 'access') {
        return $this->log('unauthorized_access', null, null, [
            'resource' => $resource,
            'attempted_action' => $attempted_action
        ]);
    }
    
    public function logPasswordChange($change_type = 'user_initiated') {
        return $this->log('password_changed', null, null, ['type' => $change_type]);
    }
    
    public function logPasswordResetRequested($email) {
        return $this->log('password_reset_requested', null, null, ['email' => $email]);
    }
    
    public function logPasswordResetCompleted($reset_token_id) {
        return $this->log('password_reset_completed', null, null, ['token_id' => $reset_token_id]);
    }
    
    /**
     * Log administrative actions
     */
    public function logRoleChanged($target_user_id, $old_role, $new_role) {
        return $this->log('role_changed', null, 
            ['target_user_id' => $target_user_id, 'role' => $old_role], 
            ['target_user_id' => $target_user_id, 'role' => $new_role]
        );
    }
    
    public function logDepartmentChanged($employee_id, $old_dept_id, $new_dept_id) {
        return $this->log('department_changed', null,
            ['employee_id' => $employee_id, 'department_id' => $old_dept_id],
            ['employee_id' => $employee_id, 'department_id' => $new_dept_id]
        );
    }
    
    /**
     * Log system events
     */
    public function logSystemMaintenance($action, $details = []) {
        return $this->log('system_maintenance', null, null, [
            'action' => $action,
            'details' => $details
        ]);
    }
    
    public function logDataExport($export_type, $filters = []) {
        return $this->log('data_exported', null, null, [
            'export_type' => $export_type,
            'filters' => $filters
        ]);
    }
    
    public function logConfigurationChange($setting, $old_value, $new_value) {
        return $this->log('configuration_changed', null,
            ['setting' => $setting, 'value' => $old_value],
            ['setting' => $setting, 'value' => $new_value]
        );
    }
    
    /**
     * Bulk log multiple activities
     */
    public function logBulk($activities) {
        $success_count = 0;
        
        foreach ($activities as $activity) {
            $result = $this->log(
                $activity['action'],
                $activity['letter_id'] ?? null,
                $activity['old_values'] ?? null,
                $activity['new_values'] ?? null,
                $activity['additional_data'] ?? []
            );
            
            if ($result) {
                $success_count++;
            }
        }
        
        return $success_count;
    }
    
    /**
     * Get recent activities for current user
     */
    public function getRecentActivities($limit = 10) {
        try {
            $stmt = $this->conn->prepare("
                SELECT al.*, l.letter_number, l.subject as letter_subject
                FROM activitylog al
                LEFT JOIN letter l ON al.letter_id = l.id
                WHERE al.user_id = ?
                ORDER BY al.timestamp DESC
                LIMIT ?
            ");
            
            $stmt->bind_param('ii', $this->user_id, $limit);
            $stmt->execute();
            
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
        } catch (Exception $e) {
            error_log("ActivityLogger: Error fetching recent activities: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Check if user has permission to view activity logs
     */
    public static function canViewLogs($user_role) {
        return in_array($user_role, ['institution_head', 'department_head']);
    }
    
    /**
     * Get activity statistics
     */
    public function getActivityStats($date_from = null, $date_to = null) {
        try {
            $where_clause = "WHERE al.user_id = ?";
            $params = [$this->user_id];
            $types = 'i';
            
            if ($date_from && $date_to) {
                $where_clause .= " AND DATE(al.timestamp) BETWEEN ? AND ?";
                $params[] = $date_from;
                $params[] = $date_to;
                $types .= 'ss';
            }
            
            $stmt = $this->conn->prepare("
                SELECT 
                    al.action,
                    COUNT(*) as count,
                    MAX(al.timestamp) as last_occurrence
                FROM activitylog al
                $where_clause
                GROUP BY al.action
                ORDER BY count DESC
            ");
            
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
        } catch (Exception $e) {
            error_log("ActivityLogger: Error fetching activity stats: " . $e->getMessage());
            return [];
        }
    }
}
?>