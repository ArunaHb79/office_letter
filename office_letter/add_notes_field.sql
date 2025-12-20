-- ============================================================================
-- Migration: Add Notes Field to Letter Table
-- Purpose: Add notes/instructions field for letter assignments and sending
-- Date: 2025-12-18
-- ============================================================================

USE office_letter;

-- Add notes column to Letter table
ALTER TABLE Letter 
ADD COLUMN notes TEXT NULL COMMENT 'Instructions/notes for assignment or sending' 
AFTER status_id;

-- Verify the column was added
DESCRIBE Letter;

-- Success message
SELECT 'Notes field successfully added to Letter table!' AS Status;
