<?php
// User Information Helper Functions

/**
 * Get complete user information including name, role, and department
 */
function get_user_info($user_id, $conn) {
    $userStmt = $conn->prepare("
        SELECT 
            u.role,
            e.name as employee_name,
            d.name as department_name,
            d.abbreviation as department_abbr,
            e.email as employee_email,
            e.id as employee_id
        FROM Users u
        LEFT JOIN Employee e ON u.employee_id = e.id
        LEFT JOIN Department d ON e.department_id = d.id
        WHERE u.id = ? 
        LIMIT 1
    ");
    $userStmt->bind_param('i', $user_id);
    $userStmt->execute();
    $userRes = $userStmt->get_result();
    
    if ($userRow = $userRes->fetch_assoc()) {
        return [
            'role' => strtolower(str_replace(' ', '_', $userRow['role'])),
            'role_display' => ucwords(str_replace('_', ' ', $userRow['role'])),
            'employee_name' => $userRow['employee_name'] ?? 'Unknown User',
            'department_name' => $userRow['department_name'] ?? 'No Department',
            'department_abbr' => $userRow['department_abbr'] ?? '',
            'employee_email' => $userRow['employee_email'] ?? '',
            'employee_id' => $userRow['employee_id'] ?? 0
        ];
    }
    
    return null;
}

/**
 * Display user information card (can be used on any page)
 */
function display_user_info_card($user_info, $show_date = true) {
    if (!$user_info) return '';
    
    $html = '
    <div class="user-info-card mb-3">
        <div class="row align-items-center">
            <div class="col-md-8">
                <div class="user-name">
                    <i class="bi bi-person-circle"></i> ' . htmlspecialchars($user_info['employee_name']) . '
                </div>
                <div class="user-role">
                    <i class="bi bi-briefcase"></i> ' . htmlspecialchars($user_info['role_display']) . '
                </div>
                <div class="user-department">
                    <i class="bi bi-building"></i> ' . htmlspecialchars($user_info['department_name']);
    
    if ($user_info['department_abbr']) {
        $html .= ' <span class="badge ms-2" style="background-color: #e9ecef; color: #1a1a1a; font-weight: 700; border: 2px solid #d0d0d0;">' . htmlspecialchars($user_info['department_abbr']) . '</span>';
    }
    
    $html .= '
                </div>
            </div>';
    
    if ($show_date) {
        $html .= '
            <div class="col-md-4 text-end">
                <div class="text-white">
                    <small>Welcome back!</small><br>
                    <small>' . date('F d, Y') . '</small>
                </div>
            </div>';
    }
    
    $html .= '
        </div>
    </div>';
    
    return $html;
}

/**
 * Get user info CSS styles (include this in pages that use user info display)
 */
function get_user_info_styles() {
    return '
    <style>
        .user-info-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .user-name {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .user-role {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 3px;
        }
        .user-department {
            font-size: 1rem;
            opacity: 0.8;
        }
        .user-info-compact {
            background: rgba(102, 126, 234, 0.1);
            border-left: 4px solid #667eea;
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .user-info-compact .user-name {
            font-size: 1.1rem;
            color: #667eea;
            font-weight: 600;
            margin-bottom: 2px;
        }
        .user-info-compact .user-details {
            font-size: 0.9rem;
            color: #6c757d;
        }
    </style>';
}

/**
 * Display compact user info (for smaller spaces)
 */
function display_user_info_compact($user_info) {
    if (!$user_info) return '';
    
    return '
    <div class="user-info-compact">
        <div class="user-name">' . htmlspecialchars($user_info['employee_name']) . '</div>
        <div class="user-details">
            ' . htmlspecialchars($user_info['role_display']) . ' • ' . htmlspecialchars($user_info['department_name']) . '
        </div>
    </div>';
}
?>