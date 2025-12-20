-- ============================================================================
-- Fix Letter Numbers
-- Purpose: Update letters with missing or invalid letter numbers
-- Date: 2025-12-20
-- ============================================================================

USE office_letter;

-- Fix letters with NULL, empty, or '0' letter numbers
UPDATE Letter l
JOIN Department d ON l.department_id = d.id
SET l.letter_number = CONCAT(
    d.abbreviation, 
    '-', 
    DATE_FORMAT(l.date_received, '%y%m'), 
    '-', 
    l.id
)
WHERE l.letter_number IS NULL 
   OR l.letter_number = '' 
   OR l.letter_number = '0'
   OR CAST(l.letter_number AS UNSIGNED) = 0;

-- Show updated letters
SELECT 
    id, 
    letter_number, 
    subject, 
    date_received 
FROM Letter 
WHERE letter_number IS NOT NULL 
ORDER BY id DESC 
LIMIT 10;

SELECT 'Letter numbers fixed successfully!' AS Status;
