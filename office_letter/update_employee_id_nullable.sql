-- ============================================================================
-- Make employee_id nullable in Letter table
-- ============================================================================
-- This allows postal subject officers to create letters without assigning
-- employees. Institution Head will assign them later.
-- ============================================================================

USE office_letter;

-- Make employee_id column nullable
ALTER TABLE Letter 
MODIFY COLUMN employee_id INT NULL;

-- Verification
DESCRIBE Letter;

-- ============================================================================
-- Note: Letters created by postal subject officer will have NULL employee_id
-- Institution Head can later update these letters to assign employees
-- ============================================================================
