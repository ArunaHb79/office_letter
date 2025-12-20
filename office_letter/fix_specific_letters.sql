-- ============================================================================
-- Fix Specific Letter Numbers (INV-2512-20 and INV-2512-22)
-- Date: 2025-12-20
-- ============================================================================

USE office_letter;

-- Show current values
SELECT 'Before Update:' AS Status;
SELECT id, letter_number, subject, department_id FROM Letter WHERE id IN (20, 22);

-- Update letter 20
UPDATE Letter l
JOIN Department d ON l.department_id = d.id
SET l.letter_number = CONCAT(d.abbreviation, '-', DATE_FORMAT(l.date_received, '%y%m'), '-', l.id)
WHERE l.id = 20;

-- Update letter 22
UPDATE Letter l
JOIN Department d ON l.department_id = d.id
SET l.letter_number = CONCAT(d.abbreviation, '-', DATE_FORMAT(l.date_received, '%y%m'), '-', l.id)
WHERE l.id = 22;

-- Show updated values
SELECT 'After Update:' AS Status;
SELECT id, letter_number, subject, department_id FROM Letter WHERE id IN (20, 22);

-- Fix any other letters with NULL or empty letter_number
UPDATE Letter l
JOIN Department d ON l.department_id = d.id
SET l.letter_number = CONCAT(d.abbreviation, '-', DATE_FORMAT(l.date_received, '%y%m'), '-', l.id)
WHERE l.letter_number IS NULL 
   OR l.letter_number = '' 
   OR l.letter_number = '0';

SELECT 'All letter numbers fixed!' AS Status;
