-- Add missing statuses for proper workflow
INSERT INTO LetterStatus (status_name) VALUES
    ('Under Review'),
    ('Approved'),
    ('Rejected')
ON DUPLICATE KEY UPDATE status_name = status_name;

-- Display all statuses
SELECT * FROM LetterStatus ORDER BY id;
