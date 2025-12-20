-- ============================================================================
-- Office Letter Management System - Complete Database Schema
-- ============================================================================
-- Database: office_letter
-- DBMS: MySQL/MariaDB
-- Character Set: UTF-8 (utf8mb4)
-- Collation: utf8mb4_unicode_ci
-- Engine: InnoDB (for foreign key support)
-- Created: 2025-11-18
-- Created by: Group No-05
-- ============================================================================

-- Drop database if exists (USE WITH CAUTION!)
-- DROP DATABASE IF EXISTS office_letter;

-- Create database
CREATE DATABASE IF NOT EXISTS office_letter
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE office_letter;

-- ============================================================================
-- TABLE: Department
-- Purpose: Stores organizational departments
-- ============================================================================
CREATE TABLE IF NOT EXISTS Department (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    abbreviation VARCHAR(10) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_dept_name (name),
    INDEX idx_dept_abbr (abbreviation)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Organizational departments';

-- ============================================================================
-- TABLE: Employee
-- Purpose: Stores employee information
-- ============================================================================
CREATE TABLE IF NOT EXISTS Employee (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    department_id INT NOT NULL,
    role VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES Department(id) 
        ON DELETE RESTRICT 
        ON UPDATE CASCADE,
    INDEX idx_employee_dept (department_id),
    INDEX idx_employee_email (email),
    INDEX idx_employee_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Employee master data';

-- ============================================================================
-- TABLE: Users
-- Purpose: User authentication and authorization
-- ============================================================================
CREATE TABLE IF NOT EXISTS Users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    employee_id INT NOT NULL UNIQUE,
    role VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    FOREIGN KEY (employee_id) REFERENCES Employee(id) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE,
    INDEX idx_users_employee (employee_id),
    INDEX idx_users_email (email),
    INDEX idx_users_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='User authentication accounts';

-- ============================================================================
-- TABLE: LetterMethod
-- Purpose: Lookup table for letter delivery methods
-- ============================================================================
CREATE TABLE IF NOT EXISTS LetterMethod (
    id INT AUTO_INCREMENT PRIMARY KEY,
    method_name VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_method_name (method_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Letter delivery methods lookup';

-- ============================================================================
-- TABLE: LetterStatus
-- Purpose: Lookup table for letter processing status
-- ============================================================================
CREATE TABLE IF NOT EXISTS LetterStatus (
    id INT AUTO_INCREMENT PRIMARY KEY,
    status_name VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255),
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status_name (status_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Letter status lookup';

-- ============================================================================
-- TABLE: Letter
-- Purpose: Core entity storing letter/document information
-- ============================================================================
CREATE TABLE IF NOT EXISTS Letter (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject VARCHAR(255) NOT NULL,
    content TEXT NULL,
    attachment_filename VARCHAR(255) NULL,
    attachment_path VARCHAR(500) NULL,
    attachment_uploaded_at TIMESTAMP NULL,
    sender VARCHAR(255) NOT NULL,
    receiver VARCHAR(255) NOT NULL,
    method_id INT NOT NULL,
    date_received DATE NOT NULL,
    date_sent DATE,
    department_id INT NOT NULL,
    employee_id INT NOT NULL,
    letter_number VARCHAR(50) NOT NULL UNIQUE,
    status_id INT NOT NULL,
    notes TEXT NULL COMMENT 'Instructions/notes for assignment or sending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (method_id) REFERENCES LetterMethod(id) 
        ON DELETE RESTRICT 
        ON UPDATE CASCADE,
    FOREIGN KEY (department_id) REFERENCES Department(id) 
        ON DELETE RESTRICT 
        ON UPDATE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES Employee(id) 
        ON DELETE RESTRICT 
        ON UPDATE CASCADE,
    FOREIGN KEY (status_id) REFERENCES LetterStatus(id) 
        ON DELETE RESTRICT 
        ON UPDATE CASCADE,
    INDEX idx_letter_dept (department_id),
    INDEX idx_letter_employee (employee_id),
    INDEX idx_letter_status (status_id),
    INDEX idx_letter_method (method_id),
    INDEX idx_letter_number (letter_number),
    INDEX idx_letter_date_received (date_received),
    INDEX idx_letter_date_sent (date_sent),
    INDEX idx_letter_attachment (attachment_filename),
    FULLTEXT INDEX idx_letter_search (subject, sender, receiver)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Letter/Document master data';

-- ============================================================================
-- TABLE: ActivityLog
-- Purpose: Audit trail for all system activities
-- ============================================================================
CREATE TABLE IF NOT EXISTS ActivityLog (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    letter_id INT NULL,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Users(id) 
        ON DELETE SET NULL 
        ON UPDATE CASCADE,
    FOREIGN KEY (letter_id) REFERENCES Letter(id) 
        ON DELETE SET NULL 
        ON UPDATE CASCADE,
    INDEX idx_activitylog_user (user_id),
    INDEX idx_activitylog_letter (letter_id),
    INDEX idx_activitylog_action (action),
    INDEX idx_activitylog_timestamp (timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='System activity audit trail';

-- ============================================================================
-- TABLE: password_resets
-- Purpose: Manages password reset tokens
-- ============================================================================
CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    used_at TIMESTAMP NULL,
    FOREIGN KEY (employee_id) REFERENCES Employee(id) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE,
    INDEX idx_reset_employee (employee_id),
    INDEX idx_reset_token (token),
    INDEX idx_reset_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Password reset tokens';

-- ============================================================================
-- INSERT DEFAULT/SEED DATA
-- ============================================================================

-- Insert Default Departments
INSERT INTO Department (name, abbreviation) VALUES
    ('Administration', 'ADMIN'),
    ('Finance', 'FIN'),
    ('Human Resources', 'HR'),
    ('Information Technology', 'IT'),
    ('Operations', 'OPS'),
    ('Legal', 'LEGAL'),
    ('Marketing', 'MKT'),
    ('Customer Service', 'CS')
ON DUPLICATE KEY UPDATE name=name;

-- Insert Default Letter Methods
INSERT INTO LetterMethod (method_name, description) VALUES
    ('Email', 'Electronic mail delivery'),
    ('Post', 'Traditional postal service'),
    ('Hand Delivery', 'Hand-delivered document'),
    ('Courier', 'Courier service delivery'),
    ('Fax', 'Facsimile transmission'),
    ('Internal Mail', 'Internal office mail system')
ON DUPLICATE KEY UPDATE method_name=method_name;

-- Insert Default Letter Statuses
INSERT INTO LetterStatus (status_name, description, display_order) VALUES
    ('Pending', 'Awaiting initial review', 1),
    ('In Progress', 'Currently being processed', 2),
    ('Under Review', 'Under review by department head', 3),
    ('Approved', 'Approved for action', 4),
    ('Rejected', 'Rejected or declined', 5),
    ('On Hold', 'Temporarily on hold', 6),
    ('Completed', 'Processing completed', 7),
    ('Archived', 'Archived for record keeping', 8),
    ('Cancelled', 'Cancelled or withdrawn', 9)
ON DUPLICATE KEY UPDATE status_name=status_name;

-- ============================================================================
-- TRIGGERS FOR ACTIVITY LOGGING
-- ============================================================================

-- Trigger: Log letter creation
DELIMITER $$
CREATE TRIGGER IF NOT EXISTS trg_letter_after_insert
AFTER INSERT ON Letter
FOR EACH ROW
BEGIN
    DECLARE v_user_id INT;
    DECLARE v_ip VARCHAR(45);
    DECLARE v_agent TEXT;
    
    -- Get session variables (set by PHP application)
    SET v_user_id = @current_user_id;
    SET v_ip = @current_ip_address;
    SET v_agent = @current_user_agent;
    
    -- Only log if user_id is set
    IF v_user_id IS NOT NULL THEN
        INSERT INTO ActivityLog (user_id, action, letter_id, new_values, ip_address, user_agent)
        VALUES (
            v_user_id,
            'letter_created',
            NEW.id,
            JSON_OBJECT(
                'subject', NEW.subject,
                'sender', NEW.sender,
                'receiver', NEW.receiver,
                'letter_number', NEW.letter_number,
                'department_id', NEW.department_id,
                'employee_id', NEW.employee_id,
                'status_id', NEW.status_id
            ),
            v_ip,
            v_agent
        );
    END IF;
END$$
DELIMITER ;

-- Trigger: Log letter updates
DELIMITER $$
CREATE TRIGGER IF NOT EXISTS trg_letter_after_update
AFTER UPDATE ON Letter
FOR EACH ROW
BEGIN
    DECLARE v_user_id INT;
    DECLARE v_ip VARCHAR(45);
    DECLARE v_agent TEXT;
    
    SET v_user_id = @current_user_id;
    SET v_ip = @current_ip_address;
    SET v_agent = @current_user_agent;
    
    IF v_user_id IS NOT NULL THEN
        INSERT INTO ActivityLog (user_id, action, letter_id, old_values, new_values, ip_address, user_agent)
        VALUES (
            v_user_id,
            'letter_updated',
            NEW.id,
            JSON_OBJECT(
                'subject', OLD.subject,
                'status_id', OLD.status_id,
                'employee_id', OLD.employee_id
            ),
            JSON_OBJECT(
                'subject', NEW.subject,
                'status_id', NEW.status_id,
                'employee_id', NEW.employee_id
            ),
            v_ip,
            v_agent
        );
    END IF;
END$$
DELIMITER ;

-- Trigger: Log letter deletion
DELIMITER $$
CREATE TRIGGER IF NOT EXISTS trg_letter_before_delete
BEFORE DELETE ON Letter
FOR EACH ROW
BEGIN
    DECLARE v_user_id INT;
    DECLARE v_ip VARCHAR(45);
    DECLARE v_agent TEXT;
    
    SET v_user_id = @current_user_id;
    SET v_ip = @current_ip_address;
    SET v_agent = @current_user_agent;
    
    IF v_user_id IS NOT NULL THEN
        INSERT INTO ActivityLog (user_id, action, letter_id, old_values, ip_address, user_agent)
        VALUES (
            v_user_id,
            'letter_deleted',
            OLD.id,
            JSON_OBJECT(
                'subject', OLD.subject,
                'letter_number', OLD.letter_number,
                'sender', OLD.sender,
                'receiver', OLD.receiver
            ),
            v_ip,
            v_agent
        );
    END IF;
END$$
DELIMITER ;

-- ============================================================================
-- VIEWS FOR COMMON QUERIES
-- ============================================================================

-- View: Complete letter information with related data
CREATE OR REPLACE VIEW vw_letter_details AS
SELECT 
    l.id,
    l.letter_number,
    l.subject,
    l.sender,
    l.receiver,
    l.date_received,
    l.date_sent,
    d.name AS department_name,
    d.abbreviation AS department_abbr,
    e.name AS employee_name,
    e.email AS employee_email,
    lm.method_name,
    ls.status_name,
    l.created_at,
    l.updated_at
FROM Letter l
JOIN Department d ON l.department_id = d.id
JOIN Employee e ON l.employee_id = e.id
JOIN LetterMethod lm ON l.method_id = lm.id
JOIN LetterStatus ls ON l.status_id = ls.id;

-- View: User activity summary
CREATE OR REPLACE VIEW vw_user_activity_summary AS
SELECT 
    u.id AS user_id,
    u.username,
    e.name AS employee_name,
    d.name AS department_name,
    COUNT(al.id) AS total_activities,
    MAX(al.timestamp) AS last_activity,
    MIN(al.timestamp) AS first_activity
FROM Users u
JOIN Employee e ON u.employee_id = e.id
JOIN Department d ON e.department_id = d.id
LEFT JOIN ActivityLog al ON u.id = al.user_id
GROUP BY u.id, u.username, e.name, d.name;

-- View: Department letter statistics
CREATE OR REPLACE VIEW vw_department_letter_stats AS
SELECT 
    d.id AS department_id,
    d.name AS department_name,
    d.abbreviation,
    COUNT(l.id) AS total_letters,
    COUNT(CASE WHEN ls.status_name = 'Pending' THEN 1 END) AS pending_count,
    COUNT(CASE WHEN ls.status_name = 'In Progress' THEN 1 END) AS in_progress_count,
    COUNT(CASE WHEN ls.status_name = 'Completed' THEN 1 END) AS completed_count,
    COUNT(CASE WHEN ls.status_name = 'Archived' THEN 1 END) AS archived_count
FROM Department d
LEFT JOIN Letter l ON d.id = l.department_id
LEFT JOIN LetterStatus ls ON l.status_id = ls.id
GROUP BY d.id, d.name, d.abbreviation;

-- ============================================================================
-- STORED PROCEDURES
-- ============================================================================

-- Procedure: Get letter statistics by date range
DELIMITER $$
CREATE PROCEDURE IF NOT EXISTS sp_get_letter_stats(
    IN p_start_date DATE,
    IN p_end_date DATE
)
BEGIN
    SELECT 
        DATE(date_received) AS received_date,
        COUNT(*) AS letter_count,
        COUNT(DISTINCT department_id) AS departments_involved,
        COUNT(DISTINCT employee_id) AS employees_involved
    FROM Letter
    WHERE date_received BETWEEN p_start_date AND p_end_date
    GROUP BY DATE(date_received)
    ORDER BY received_date DESC;
END$$
DELIMITER ;

-- Procedure: Clean expired password reset tokens
DELIMITER $$
CREATE PROCEDURE IF NOT EXISTS sp_cleanup_expired_tokens()
BEGIN
    DELETE FROM password_resets
    WHERE expires_at < NOW()
    AND used_at IS NULL;
    
    SELECT ROW_COUNT() AS deleted_tokens;
END$$
DELIMITER ;

-- ============================================================================
-- PERMISSIONS & SECURITY (Optional - adjust usernames as needed)
-- ============================================================================

-- Create application user (uncomment and modify as needed)
-- CREATE USER IF NOT EXISTS 'office_letter_app'@'localhost' IDENTIFIED BY 'your_secure_password_here';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON office_letter.* TO 'office_letter_app'@'localhost';
-- FLUSH PRIVILEGES;

-- ============================================================================
-- VERIFICATION QUERIES
-- ============================================================================

-- Show all tables
SELECT 
    TABLE_NAME,
    TABLE_ROWS,
    AUTO_INCREMENT,
    CREATE_TIME,
    TABLE_COMMENT
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'office_letter'
ORDER BY TABLE_NAME;

-- Show all foreign keys
SELECT 
    CONSTRAINT_NAME,
    TABLE_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'office_letter'
AND REFERENCED_TABLE_NAME IS NOT NULL
ORDER BY TABLE_NAME, CONSTRAINT_NAME;

-- ============================================================================
-- END OF SCHEMA
-- ============================================================================
-- Notes:
-- 1. Run this script on a fresh MySQL/MariaDB instance
-- 2. Adjust passwords and permissions as needed
-- 3. Backup existing data before running on production
-- 4. Test in development environment first
-- 5. Timezone is set in PHP (Asia/Colombo)
-- ============================================================================
