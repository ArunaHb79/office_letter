-- ============================================================================
-- QUICK INSTALLATION GUIDE FOR NOTES FIELD
-- ============================================================================

-- Step 1: Run this SQL to add the notes field to existing database
-- Execute in MySQL/phpMyAdmin or via command line

USE office_letter;

ALTER TABLE Letter 
ADD COLUMN notes TEXT NULL COMMENT 'Instructions/notes for assignment or sending' 
AFTER status_id;

-- Step 2: Verify the column was added
SHOW COLUMNS FROM Letter LIKE 'notes';

-- Step 3: Test the feature
-- Go to: http://localhost/office_letter/src/letter_form.php
-- You should see the "Notes / Instructions" field at the bottom of the form

-- That's it! The notes field is now ready to use.

-- ============================================================================
-- ROLLBACK (if needed)
-- ============================================================================
-- If you need to remove the notes field, run:
-- ALTER TABLE Letter DROP COLUMN notes;
