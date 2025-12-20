-- ============================================================================
-- User Approval System Implementation
-- Purpose: Add approval workflow for new user accounts
-- Date: 2025-12-19
-- ============================================================================

USE office_letter;

-- Add approval fields to Users table
ALTER TABLE Users 
ADD COLUMN IF NOT EXISTS approved TINYINT(1) DEFAULT 0 COMMENT 'Account approval status: 0=pending, 1=approved',
ADD COLUMN IF NOT EXISTS approved_by INT NULL COMMENT 'User ID who approved this account',
ADD COLUMN IF NOT EXISTS approved_at TIMESTAMP NULL COMMENT 'When account was approved',
ADD COLUMN IF NOT EXISTS created_by INT NULL COMMENT 'User ID who created this account';

-- Add foreign key for approved_by
ALTER TABLE Users 
ADD CONSTRAINT fk_users_approved_by 
FOREIGN KEY (approved_by) REFERENCES Users(id) 
ON DELETE SET NULL ON UPDATE CASCADE;

-- Add foreign key for created_by
ALTER TABLE Users 
ADD CONSTRAINT fk_users_created_by 
FOREIGN KEY (created_by) REFERENCES Users(id) 
ON DELETE SET NULL ON UPDATE CASCADE;

-- Add index for quick lookup of pending users
CREATE INDEX idx_users_approved ON Users(approved);

-- Success message
SELECT 'User approval system implemented successfully!' AS Status;
SELECT 'New features:' AS Info,
       '1. approved field - tracks if account is approved' AS Feature1,
       '2. approved_by - tracks who approved the account' AS Feature2,
       '3. created_by - tracks who created the account' AS Feature3;
