-- ============================================================================
-- Add Last Login Column to Users Table
-- Date: 2025-12-20
-- ============================================================================

USE office_letter;

-- Add last_login column to Users table
ALTER TABLE Users 
ADD COLUMN IF NOT EXISTS last_login DATETIME NULL 
COMMENT 'Last login timestamp'
AFTER password;

-- Create index for better performance
CREATE INDEX IF NOT EXISTS idx_last_login ON Users(last_login);

SELECT 'Last login column added successfully!' AS Status;

-- Show Users table structure
DESCRIBE Users;
