-- ============================================================================
-- Migration: Make department_id nullable in Employee table
-- Purpose: Allow certain roles (Institution Head, Chief Management Assistant, 
--          Postal Subject Officer) to exist without a specific department
-- Date: 2025-12-19
-- ============================================================================

USE office_letter;

-- Drop the existing foreign key constraint
ALTER TABLE Employee 
DROP FOREIGN KEY employee_ibfk_1;

-- Modify the department_id column to allow NULL
ALTER TABLE Employee 
MODIFY COLUMN department_id INT NULL;

-- Re-add the foreign key constraint with NULL support
ALTER TABLE Employee 
ADD CONSTRAINT employee_ibfk_1 
FOREIGN KEY (department_id) REFERENCES Department(id) 
ON DELETE RESTRICT 
ON UPDATE CASCADE;

-- Verify the change
DESCRIBE Employee;

-- Success message
SELECT 'Migration completed: department_id is now nullable in Employee table' AS Status;
