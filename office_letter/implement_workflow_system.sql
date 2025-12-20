-- ============================================================================
-- Workflow System Implementation
-- Purpose: Implement complete letter workflow with instructions and tracking
-- Date: 2025-12-19
-- ============================================================================

USE office_letter;

-- ============================================================================
-- 1. Create Letter Instructions/Notes Table
-- ============================================================================
CREATE TABLE IF NOT EXISTS LetterInstructions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    letter_id INT NOT NULL,
    user_id INT NOT NULL,
    user_name VARCHAR(100) NOT NULL,
    user_role VARCHAR(50) NOT NULL,
    instruction_type ENUM('assignment', 'note', 'instruction', 'update') NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (letter_id) REFERENCES Letter(id) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE,
    FOREIGN KEY (user_id) REFERENCES Users(id) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE,
    INDEX idx_instructions_letter (letter_id),
    INDEX idx_instructions_user (user_id),
    INDEX idx_instructions_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Instructions and notes for letters with user tracking';

-- ============================================================================
-- 2. Update Letter Status Values for Workflow
-- ============================================================================
-- First, check existing statuses
SELECT * FROM LetterStatus;

-- Add new statuses if they don't exist
INSERT IGNORE INTO LetterStatus (status_name, description) VALUES
('Received', 'Letter received and entered into system'),
('Assigned', 'Letter assigned to subject officer'),
('In Progress', 'Subject officer working on the letter'),
('Sent to Department Head', 'Letter sent to department head for review'),
('Processed', 'Department head processed the letter'),
('Completed', 'Letter workflow completed by institution head'),
('Pending', 'Awaiting action'),
('On Hold', 'Temporarily paused');

-- ============================================================================
-- 3. Add Assignment Tracking Fields to Letter Table
-- ============================================================================
-- Add assigned_by field to track who assigned the letter
ALTER TABLE Letter 
ADD COLUMN IF NOT EXISTS assigned_by INT NULL COMMENT 'User who assigned this letter',
ADD COLUMN IF NOT EXISTS assigned_at TIMESTAMP NULL COMMENT 'When letter was assigned',
ADD COLUMN IF NOT EXISTS processed_by INT NULL COMMENT 'Department head who processed',
ADD COLUMN IF NOT EXISTS processed_at TIMESTAMP NULL COMMENT 'When processed by dept head',
ADD COLUMN IF NOT EXISTS completed_by INT NULL COMMENT 'Institution head who completed',
ADD COLUMN IF NOT EXISTS completed_at TIMESTAMP NULL COMMENT 'When marked as completed';

-- Add foreign keys for new fields
ALTER TABLE Letter 
ADD CONSTRAINT fk_letter_assigned_by 
FOREIGN KEY (assigned_by) REFERENCES Users(id) 
ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE Letter 
ADD CONSTRAINT fk_letter_processed_by 
FOREIGN KEY (processed_by) REFERENCES Users(id) 
ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE Letter 
ADD CONSTRAINT fk_letter_completed_by 
FOREIGN KEY (completed_by) REFERENCES Users(id) 
ON DELETE SET NULL ON UPDATE CASCADE;

-- ============================================================================
-- 4. Make employee_id nullable initially (for Chief Management Assistant entry)
-- ============================================================================
ALTER TABLE Letter 
MODIFY COLUMN employee_id INT NULL COMMENT 'Assigned subject officer';

-- ============================================================================
-- Success Message
-- ============================================================================
SELECT 'Workflow system tables and fields created successfully!' AS Status;
SELECT 'New features:' AS Info, 
       '1. LetterInstructions table for tracking all communications' AS Feature1,
       '2. Enhanced statuses: Received → Assigned → In Progress → Sent to Dept Head → Processed → Completed' AS Feature2,
       '3. Assignment tracking fields in Letter table' AS Feature3;
