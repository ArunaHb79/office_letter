-- Add "Filed - Information Only" status for letters received for information purposes
-- Date: 2025-12-19
-- Purpose: Allow subject officers to file letters that require no action

-- First, extend the status_name column to accommodate longer names
ALTER TABLE LetterStatus 
MODIFY COLUMN status_name VARCHAR(50);

-- Add the new status
INSERT INTO LetterStatus (status_name) 
VALUES ('Filed - Information Only')
ON DUPLICATE KEY UPDATE status_name = 'Filed - Information Only';

-- Verify the new status was added
SELECT * FROM LetterStatus WHERE status_name LIKE '%Filed%';
